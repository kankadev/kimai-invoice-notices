<?php
declare(strict_types=1);

// Native renderer regression checks; run only in an isolated fixture installation.
require '/opt/kimai/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv('/opt/kimai/.env');
if (!str_contains($_ENV['DATABASE_URL'] ?? '', '/kimai_stage?')) {
    throw new RuntimeException('The isolated kimai_stage fixture database is required.');
}
$kernel = new App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();
$reflection = new ReflectionClass($container);
require_once dirname($reflection->getFileName()).'/getTwigService.php';
$factory = $reflection->getNamespaceName().'\\getTwigService';
$twig = $factory::do($container);
$root = sys_get_temp_dir().'/notice-sandbox-'.bin2hex(random_bytes(8));
mkdir($root, 0700);
$twig->getLoader()->addPath($root, 'invoice');
$renderer = new class($twig) extends App\Invoice\Renderer\AbstractTwigRenderer {
    public function supports(App\Model\InvoiceDocument $document): bool { return true; }
    public function render(App\Model\InvoiceDocument $document, App\Invoice\InvoiceModel $model): Symfony\Component\HttpFoundation\Response {
        return new Symfony\Component\HttpFoundation\Response($this->renderTwigTemplate($document, $model));
    }
};
$em = $container->get('doctrine')->getManager();
$native = new ReflectionClass($container);
require_once dirname($native->getFileName()).'/getInvoiceServiceService.php';
$serviceFactory = $native->getNamespaceName().'\\getInvoiceServiceService';
$service = $serviceFactory::do($container);
$modelFactory = (new ReflectionProperty($service, 'invoiceModelFactory'))->getValue($service);
$locales = (new ReflectionProperty($service, 'formatter'))->getValue($service);
$customer = $em->getRepository(App\Entity\Customer::class)->findOneBy([]);
$template = clone $em->getRepository(App\Entity\InvoiceTemplate::class)->findOneBy([]);
$template->setLanguage('en');
$model = $modelFactory->createModel(new App\Invoice\DefaultInvoiceFormatter($locales, 'en'), $customer, $template, new App\Repository\Query\InvoiceQuery());
$model->setCalculator(new App\Invoice\Calculator\DefaultCalculator());
$model->setNumberGenerator(new class implements App\Invoice\NumberGeneratorInterface {
    public function setModel(App\Invoice\InvoiceModel $model): void {}
    public function getInvoiceNumber(): string { return 'TEST-NOTICE-SANDBOX'; }
    public function getId(): string { return 'synthetic'; }
});
$notice = (new KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeResolver())->resolve(null, 'en');
$model->setOption(KimaiPlugin\KankaInvoiceNoticesBundle\Service\SnapshotCodec::OPTION, $notice);
$checks = 0;
try {
    foreach ([
        'allowed' => ["{{ invoice_notices(invoice).status }}", null],
        'function' => ["{{ source('untrusted.txt') }}", Twig\Sandbox\SecurityNotAllowedFunctionError::class],
        'method' => ["{{ model.setOption('unexpected', 'mutation') }}", Twig\Sandbox\SecurityNotAllowedMethodError::class],
        'missing-data' => ["{{ invoice_notices({}) }}", InvalidArgumentException::class],
    ] as $name => [$source, $expected]) {
        $path = $root.'/'.$name.'.html.twig';
        file_put_contents($path, $source);
        try {
            $response = $renderer->render(new App\Model\InvoiceDocument(new SplFileInfo($path)), $model);
            if ($expected !== null || $response->getContent() !== 'unassigned') throw new RuntimeException('Unexpected renderer result: '.$name);
        } catch (Throwable $error) {
            $cause = $error;
            while ($cause->getPrevious() !== null) $cause = $cause->getPrevious();
            if ($expected === null || !$cause instanceof $expected) throw $error;
        } finally {
            // Kimai's native renderer does not disable its sandbox on an exception.
            $twig->getExtension(Twig\Extension\SandboxExtension::class)->disableSandbox();
        }
        ++$checks;
    }
    if (isset($model->getOptions()['unexpected'])) throw new RuntimeException('Forbidden method executed.');
    ++$checks;
    $withoutPlugin = new Twig\Environment(new Twig\Loader\ArrayLoader(['adapted' => '{{ invoice_notices(invoice) }}']));
    try { $withoutPlugin->render('adapted'); throw new RuntimeException('Adapted template silently accepted missing plugin.'); }
    catch (Twig\Error\SyntaxError) { ++$checks; }
} finally {
    foreach (glob($root.'/*') ?: [] as $path) unlink($path);
    rmdir($root);
}
echo $checks." native renderer sandbox checks passed; no database writes.\n";
