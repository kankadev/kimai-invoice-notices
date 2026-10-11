<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Explicit template integration fails closed if the model lacks notice data. */
final class NoticeTwigExtension extends AbstractExtension
{
    public function __construct(private SnapshotCodec $codec) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('invoice_notices', [$this, 'notices'])];
    }

    public function notices(array $invoice): array
    {
        if (!is_array($invoice['kanka_notices'] ?? null)) throw new \InvalidArgumentException('Invoice Notices model data is missing. Check the plugin and native template integration.');
        return $this->codec->validate($invoice['kanka_notices']);
    }
}
