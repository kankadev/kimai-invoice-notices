<?php
declare(strict_types=1);

// Run only inside an isolated Kimai fixture installation, never against production.
require '/opt/kimai/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv('/opt/kimai/.env');
if (!str_contains($_ENV['DATABASE_URL'] ?? '', '/kimai_stage?')) {
    throw new RuntimeException('This check requires the isolated kimai_stage fixture database.');
}
$kernel = new App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$db = $em->getConnection();
$checks = 0;
function verify(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
}
function fingerprint($db): string
{
    $values = [];
    foreach (['kimai2_invoices', 'kimai2_timesheet', 'kimai2_customers'] as $table) {
        $values[$table] = $db->fetchAllAssociative('SELECT * FROM '.$table.' ORDER BY id');
    }
    return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
}
$before = fingerprint($db);
$config = new App\Configuration\ConfigurationService($em->getRepository(App\Entity\Configuration::class), new Symfony\Component\Cache\Adapter\ArrayAdapter());
$registry = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry($config);
$resolver = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeResolver();
$hydrator = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeHydrator($registry, $resolver);
$fixture = ['id' => 'synthetic-business', 'name' => 'Synthetic business notice', 'description' => 'Test only, no legal statement.', 'revision' => 1, 'active' => true, 'translations' => [
    'de' => ['short' => 'Prüfhinweis: 150 €', 'blocks' => [['title' => 'Hinweis', 'text' => '<script>alert(1)</script> {{ 7 * 7 }}']]],
    'en' => ['short' => 'Synthetic test notice', 'blocks' => []],
]];
$db->beginTransaction();
try {
    $setting = $config->getConfiguration($registry::KEY) ?? (new App\Entity\Configuration())->setName($registry::KEY);
    $setting->setValue(json_encode(['schema' => 1, 'profiles' => [$fixture]], JSON_THROW_ON_ERROR));
    $config->saveConfiguration($setting);
    verify($registry->get('synthetic-business')->revision === 1, 'Native configuration load');
    $auth = new class implements Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface {
        public function isGranted(mixed $attribute, mixed $subject = null): bool { return true; }
    };
    $subscriber = new KimaiPlugin\KankaInvoiceNoticesBundle\EventSubscriber\IntegrationSubscriber($registry, $auth, $container->get('translator'));
    $customer = new App\Entity\Customer('Synthetic test customer');
    $event = new App\Event\CustomerMetaDefinitionEvent($customer);
    $subscriber->fields($event);
    $field = $customer->getMetaField($registry::FIELD);
    verify($field !== null && in_array('synthetic-business', $field->getOptions()['choices'], true), 'Customer choice field registered');
    $field->setValue('synthetic-business');
    $subscriber->fields($event);
    verify($field->getValue() === 'synthetic-business', 'Re-registering preserves selected ID');
    $template = new App\Entity\InvoiceTemplate();
    $template->setLanguage(null);
    $customer->setLanguage('en');
    $formatter = new class implements App\Invoice\InvoiceFormatter {
        public function getLocale(): string { return 'de'; }
        public function setLocale(string $locale): void {}
        public function getFormattedDateTime(DateTimeInterface $date): string { return ''; }
        public function getFormattedTime(DateTimeInterface $date): string { return ''; }
        public function getFormattedAmount(float $amount): string { return ''; }
        public function getFormattedMoney(float $amount, ?string $currency, bool $withCurrency = true): string { return ''; }
        public function getFormattedMonthName(DateTimeInterface $date): string { return ''; }
        public function getFormattedDuration(int $seconds): string { return ''; }
        public function getFormattedDecimalDuration(int $seconds): string { return ''; }
        public function getCurrencySymbol(string $currency): string { return ''; }
    };
    $mode = new class implements App\Timesheet\RateCalculator\RateCalculatorMode {
        public function calculateRate(float $hourlyRate, int $seconds): float { return 0.0; }
        public function roundDuration(int $seconds): int { return $seconds; }
    };
    $model = new App\Invoice\InvoiceModel($formatter, $customer, $template, $mode);
    $resolved = $hydrator->hydrate($model)['kanka_notices'];
    verify($resolved['language'] === 'de' && $resolved['short'] === 'Prüfhinweis: 150 €', 'Effective invoice language overrides customer language and handles null template language');
    $reflection = new ReflectionClass($container);
    require_once dirname($reflection->getFileName()).'/getTwigService.php';
    $twigFactory = $reflection->getNamespaceName().'\\getTwigService';
    $twig = $twigFactory::do($container);
    $html = $twig->createTemplate('{{ notice|nl2br }}')->render(['notice' => $resolved['blocks'][0]['text']]);
    verify(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;') && str_contains($html, '{{ 7 * 7 }}'), 'Actual Twig escapes markup and does not evaluate notice text');
    $duplicate = ['schema' => 1, 'profiles' => [$fixture, $fixture]];
    $setting->setValue(json_encode($duplicate, JSON_THROW_ON_ERROR));
    $config->saveConfiguration($setting);
    try { $registry->all(); throw new RuntimeException('Duplicate ID accepted'); } catch (InvalidArgumentException) { verify(true, 'Duplicate identifiers rejected'); }
    $fixture['active'] = false;
    $setting->setValue(json_encode(['schema' => 1, 'profiles' => [$fixture]], JSON_THROW_ON_ERROR));
    $config->saveConfiguration($setting);
    $subscriber->fields($event);
    verify(in_array('synthetic-business', $field->getOptions()['choices'], true), 'Assigned inactive profile remains visible');
    try { $hydrator->hydrate($model); throw new RuntimeException('Inactive profile rendered'); } catch (InvalidArgumentException) { verify(true, 'Inactive profile does not silently render'); }
} finally {
    $db->rollBack();
    $em->clear();
    $config->clearCache();
}
verify(fingerprint($db) === $before, 'Customer, invoice and timesheet tables unchanged');
echo $checks." native integration checks passed; transaction rolled back.\n";
