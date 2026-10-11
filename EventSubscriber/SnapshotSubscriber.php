<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\EventSubscriber;

use App\Entity\InvoiceMeta;
use App\Event\InvoicePreRenderEvent;
use App\Event\InvoicePostRenderEvent;
use App\Event\InvoiceUpdatePreEvent;
use KimaiPlugin\KankaInvoiceNoticesBundle\Form\InvoiceNoticeExtension;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeHydrator;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\SnapshotCodec;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Service\ResetInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** Attaches the rendered text before the native invoice and metadata are flushed together. */
final class SnapshotSubscriber implements EventSubscriberInterface, ResetInterface
{
    private array $pending = [];

    public function __construct(private NoticeHydrator $notices, private SnapshotCodec $codec, private RequestStack $requests, private EntityManagerInterface $em, private TranslatorInterface $translator) {}

    public static function getSubscribedEvents(): array
    {
        return [InvoicePreRenderEvent::class => ['prepare', -100], InvoicePostRenderEvent::class => ['rendered', -100], InvoiceUpdatePreEvent::class => ['attach', -100]];
    }

    public function reset(): void { $this->pending = []; }

    public function prepare(InvoicePreRenderEvent $event): void
    {
        $model = $event->getModel();
        $override = null;
        $request = $this->requests->getCurrentRequest();
        if ($request !== null && in_array($request->attributes->get('_route'), ['invoice_preview', 'invoice_create'], true)) {
            $parameters = $request->isMethod('POST') ? $request->request : $request->query;
            $override = $parameters->getString(InvoiceNoticeExtension::FIELD, InvoiceNoticeExtension::CUSTOMER);
        }
        $resolved = $this->notices->resolve($model, $override);
        // Check metadata capacity before rendering or saving a document.
        $this->codec->encode($resolved, $event->getDocument()->getName(), str_repeat('0', 64));
        $model->setOption(SnapshotCodec::OPTION, $resolved);
    }

    public function rendered(InvoicePostRenderEvent $event): void
    {
        $model = $event->getModel();
        if ($model->isPreview()) return;
        $resolved = $model->getOptions()[SnapshotCodec::OPTION] ?? null;
        if (!is_array($resolved)) throw new \RuntimeException('Notice snapshot was not prepared.');
        $response = $event->getResponse();
        $hash = $response instanceof BinaryFileResponse ? hash_file('sha256', $response->getFile()->getPathname()) : hash('sha256', $response->getContent());
        if ($hash === false) throw new \RuntimeException('Cannot verify the rendered invoice document.');
        $key = $this->key($model->getCustomer()->getId(), $model->getUser()?->getId(), $model->getInvoiceNumber());
        $this->pending[$key] = $this->codec->encode($resolved, $event->getDocument()->getName(), $hash);
    }

    public function attach(InvoiceUpdatePreEvent $event): void
    {
        $invoice = $event->getInvoice();
        // Existing invoices, including their status edits, are never re-snapshotted.
        if ($invoice->getId() !== null) {
            $db = $this->em->getConnection();
            $table = $db->quoteIdentifier($this->em->getClassMetadata(InvoiceMeta::class)->getTableName());
            $stored = $db->fetchOne('SELECT '.$db->quoteIdentifier('value').' FROM '.$table.' WHERE '.$db->quoteIdentifier('invoice_id').' = ? AND '.$db->quoteIdentifier('name').' = ?', [$invoice->getId(), SnapshotCodec::FIELD]);
            $current = $invoice->getMetaField(SnapshotCodec::FIELD)?->getValue();
            if (($stored === false && $current !== null) || ($stored !== false && $current !== $stored)) {
                throw new UnprocessableEntityHttpException($this->translator->trans('kanka_notices.snapshot_immutable'));
            }
            return;
        }
        // This reserved field can only originate from this render/save lifecycle.
        if ($invoice->getMetaField(SnapshotCodec::FIELD) !== null) {
            throw new UnprocessableEntityHttpException($this->translator->trans('kanka_notices.snapshot_immutable'));
        }
        $key = $this->key($invoice->getCustomer()->getId(), $invoice->getUser()->getId(), $invoice->getInvoiceNumber());
        if (!isset($this->pending[$key])) return; // Uploaded documents have no render event.
        $invoice->setMetaField((new InvoiceMeta())->setName(SnapshotCodec::FIELD)->setIsVisible(false)->setValue($this->pending[$key]));
        unset($this->pending[$key]);
    }

    private function key(?int $customer, ?int $user, string $number): string
    {
        return json_encode([$customer, $user, $number], JSON_THROW_ON_ERROR);
    }
}
