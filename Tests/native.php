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
$db->setNestTransactionsWithSavepoints(true);
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
    foreach (['kimai2_invoices', 'kimai2_invoices_meta', 'kimai2_timesheet', 'kimai2_customers', 'kimai2_customers_meta'] as $table) {
        $values[$table] = $db->fetchAllAssociative('SELECT * FROM '.$table.' ORDER BY id');
    }
    return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
}
$before = fingerprint($db);
$config = new App\Configuration\ConfigurationService($em->getRepository(App\Entity\Configuration::class), new Symfony\Component\Cache\Adapter\ArrayAdapter());
$registry = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry($config, $em);
$resolver = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeResolver();
$hydrator = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeHydrator($registry, $resolver, $container->get('translator'));
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
    $writeData = $fixture;
    unset($writeData['id'], $writeData['revision']);
    $writeData['active'] = true;
    $saved = $registry->save(null, $writeData, $registry->token());
    verify($saved->revision === 1 && $registry->get($saved->id)->name === $fixture['name'], 'Native profile creation persisted');
    $staleToken = $registry->token();
    $writeData['name'] = 'Updated synthetic profile';
    $updated = $registry->save($saved->id, $writeData, $staleToken);
    verify($updated->id === $saved->id && $updated->revision === 2 && $registry->get($updated->id)->name === $writeData['name'], 'Update preserves identifier and increments revision');
    $localeService = new App\Configuration\LocaleService(['en' => App\Configuration\LocaleService::DEFAULT_SETTINGS, 'de' => App\Configuration\LocaleService::DEFAULT_SETTINGS]);
    $discovery = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\ContentLanguages($em, $registry, $localeService);
    $languages = $discovery->discover();
    verify(isset($languages['de'], $languages['en']) && $languages['de']['configured'] === 2, 'Native language discovery includes configured profile languages');
    try { $registry->save($updated->id, $writeData, $staleToken); throw new RuntimeException('Stale token accepted'); }
    catch (KimaiPlugin\KankaInvoiceNoticesBundle\Exception\ProfileConflict) { verify($registry->get($updated->id)->revision === 2, 'Stale edit rejected without overwriting saved revision'); }
    $oversized = $writeData;
    $oversized['translations']['en']['blocks'] = array_fill(0, 7, ['title' => 'Long test block', 'text' => str_repeat('x', 10000)]);
    try { $registry->save(null, $oversized, $registry->token()); throw new RuntimeException('Oversized configuration accepted'); }
    catch (InvalidArgumentException $error) { verify($error->getMessage() === 'kanka_notices.storage_limit', 'Storage limit enforced before database truncation'); }

    $nativeCustomer = $em->getRepository(App\Entity\Customer::class)->findOneBy([]);
    $nativeUser = $em->getRepository(App\Entity\User::class)->findOneBy([]);
    $nativeTemplate = $em->getRepository(App\Entity\InvoiceTemplate::class)->findOneBy([]);
    if ($nativeCustomer === null || $nativeUser === null || $nativeTemplate === null) throw new RuntimeException('Synthetic staging installation needs customer, user and invoice template fixtures.');
    $nativeCustomer->setMetaField((new App\Entity\CustomerMeta())->setName($registry::FIELD)->setValue($updated->id));
    $snapshotCodec = new KimaiPlugin\KankaInvoiceNoticesBundle\Service\SnapshotCodec();
    $requests = new Symfony\Component\HttpFoundation\RequestStack();
    $snapshots = new KimaiPlugin\KankaInvoiceNoticesBundle\EventSubscriber\SnapshotSubscriber($hydrator, $snapshotCodec, $requests, $em, $container->get('translator'));
    $events = new Symfony\Component\EventDispatcher\EventDispatcher();
    $events->addSubscriber($snapshots);
    $tempRoot = sys_get_temp_dir().'/notice-native-'.bin2hex(random_bytes(8));
    mkdir($tempRoot, 0700);
    $docPath = $tempRoot.'/'.$nativeTemplate->getRenderer().'.html.twig';
    file_put_contents($docPath, 'Synthetic renderer fixture');
    try {
        $service = new App\Invoice\InvoiceService(new App\Repository\InvoiceDocumentRepository([$tempRoot]), new App\Utils\FileHelper($tempRoot), $em->getRepository(App\Entity\Invoice::class), $localeService, new App\Invoice\InvoiceModelFactory($mode, [$hydrator], []), $events);
        $renderer = new class($hydrator) implements App\Invoice\RendererInterface {
            public function __construct(private $hydrator) {}
            public function supports(App\Model\InvoiceDocument $document): bool { return true; }
            public function render(App\Model\InvoiceDocument $document, App\Invoice\InvoiceModel $model): Symfony\Component\HttpFoundation\Response {
                return new Symfony\Component\HttpFoundation\Response(json_encode($this->hydrator->hydrate($model), JSON_THROW_ON_ERROR), 200, ['Content-Type' => 'text/plain']);
            }
        };
        $service->addRenderer($renderer);
        $snapshotModel = new App\Invoice\InvoiceModel($formatter, $nativeCustomer, $nativeTemplate, $mode);
        $snapshotModel->setUser($nativeUser);
        $snapshotModel->setCalculator(new App\Invoice\Calculator\DefaultCalculator());
        $snapshotModel->setNumberGenerator(new class implements App\Invoice\NumberGeneratorInterface {
            private string $number;
            public function __construct() { $this->number = 'TEST-NOTICES-'.bin2hex(random_bytes(8)); }
            public function setModel(App\Invoice\InvoiceModel $model): void {}
            public function getInvoiceNumber(): string { return $this->number; }
            public function getId(): string { return 'synthetic-only'; }
        });
        $invoice = $service->createInvoice($snapshotModel, $events);
        $meta = $invoice->getMetaField($snapshotCodec::FIELD);
        verify($invoice->getId() !== null && $meta !== null && !$meta->isVisible(), 'Native createInvoice flushes hidden snapshot metadata with the new invoice');
        $snapshotJson = $meta->getValue();
        $snapshot = $snapshotCodec->decode($snapshotJson);
        $invoicePath = $tempRoot.'/invoices/'.$invoice->getInvoiceFilename();
        verify($snapshot['document_sha256'] === hash_file('sha256', $invoicePath) && $snapshot['notices']['revision'] === 2, 'Snapshot refers to the exact generated document and profile revision');
        $writeData['translations']['de']['short'] = 'Changed after creation';
        $registry->save($updated->id, $writeData, $registry->token());
        verify($hydrator->hydrate($snapshotModel)['kanka_notices']['short'] === 'Prüfhinweis: 150 €', 'Rendered model is frozen across later profile changes');
        $invoice->setIsPending();
        $service->saveInvoice($invoice);
        verify($invoice->getMetaField($snapshotCodec::FIELD)->getValue() === $snapshotJson && $snapshot['notices']['short'] === 'Prüfhinweis: 150 €', 'Status changes and profile edits preserve the historical snapshot');
        $id = $invoice->getId();
        $em->clear();
        $reloaded = $em->find(App\Entity\Invoice::class, $id);
        verify($reloaded->getMetaField($snapshotCodec::FIELD)->getValue() === $snapshotJson, 'Snapshot survives native database reload');
        $reloaded->getMetaField($snapshotCodec::FIELD)->setValue('tampered');
        try { $service->saveInvoice($reloaded); throw new RuntimeException('Snapshot mutation accepted'); }
        catch (Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException) { verify(true, 'Native invoice save rejects snapshot mutation, including API custom-field updates'); }
        $reloaded->getMetaField($snapshotCodec::FIELD)->setValue($snapshotJson);
        verify(hash_file('sha256', $service->getInvoiceFile($reloaded)->getPathname()) === $snapshot['document_sha256'], 'Historical native file access preserves original bytes after profile/status changes');
        $historicalMeta = $reloaded->getMetaField($snapshotCodec::FIELD);
        $reloaded->getMetaFields()->removeElement($historicalMeta);
        try { $service->saveInvoice($reloaded); throw new RuntimeException('Snapshot removal accepted'); }
        catch (Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException) { verify(true, 'Native invoice save rejects snapshot removal'); }
        $reloaded->setMetaField($historicalMeta);
        $injected = new App\Entity\Invoice();
        $injected->setMetaField((new App\Entity\InvoiceMeta())->setName($snapshotCodec::FIELD)->setValue($snapshotJson));
        try { $snapshots->attach(new App\Event\InvoiceUpdatePreEvent($injected)); throw new RuntimeException('Injected new snapshot accepted'); }
        catch (Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException) { verify(true, 'Reserved snapshot cannot be supplied on a new invoice'); }
        try { $hydrator->resolve($snapshotModel, 'missing-profile'); throw new RuntimeException('Missing override profile accepted'); }
        catch (InvalidArgumentException) { verify(true, 'Missing per-export profile rejected'); }
        try { $hydrator->resolve($snapshotModel, 'synthetic-business'); throw new RuntimeException('Inactive override accepted'); }
        catch (InvalidArgumentException) { verify(true, 'Inactive per-export profile rejected'); }
        $englishOnly = $writeData;
        unset($englishOnly['translations']['de']);
        $noGerman = $registry->save(null, $englishOnly, $registry->token());
        try { $hydrator->resolve($snapshotModel, $noGerman->id); throw new RuntimeException('Missing locale accepted'); }
        catch (InvalidArgumentException) { verify(true, 'No fallback for missing invoice language'); }
        $request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', ['kankaNoticeProfile' => '__none__']);
        $request->attributes->set('_route', 'invoice_preview');
        $requests->push($request);
        $snapshotModel->setPreview(true);
        $response = $service->renderInvoice($snapshotModel, $events);
        verify(json_decode($response->getContent(), true)['kanka_notices']['status'] === 'unassigned', 'Native preview supports a per-export no-notice exception');
        verify($snapshotModel->getCustomer()->getMetaField($registry::FIELD)->getValue() === $updated->id, 'Per-export exception does not change the customer assignment');
        $requests->pop();
        $snapshots->reset();
    } finally {
        foreach (glob($tempRoot.'/invoices/*') ?: [] as $file) unlink($file);
        if (is_dir($tempRoot.'/invoices')) rmdir($tempRoot.'/invoices');
        unlink($docPath);
        rmdir($tempRoot);
    }
} finally {
    $db->rollBack();
    $em->clear();
    $config->clearCache();
}
verify(fingerprint($db) === $before, 'Customer, invoice and timesheet tables unchanged');
echo $checks." native integration checks passed; transaction rolled back.\n";
