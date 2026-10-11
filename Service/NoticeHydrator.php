<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;
use KimaiPlugin\KankaInvoiceNoticesBundle\Form\InvoiceNoticeExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NoticeHydrator implements InvoiceModelHydrator
{
    public function __construct(private ProfileRegistry $profiles, private NoticeResolver $resolver, private TranslatorInterface $translator)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $frozen = $model->getOptions()[SnapshotCodec::OPTION] ?? null;
        return ['kanka_notices' => is_array($frozen) ? (new SnapshotCodec())->validate($frozen) : $this->resolve($model)];
    }

    public function resolve(InvoiceModel $model, ?string $override = null): array
    {
        $value = $model->getCustomer()->getMetaField(ProfileRegistry::FIELD)?->getValue();
        if ($override === InvoiceNoticeExtension::NONE) $value = null;
        elseif ($override !== null && $override !== InvoiceNoticeExtension::CUSTOMER) $value = $override;
        try { $profile = $value === null || $value === '' ? null : $this->profiles->get((string) $value); }
        catch (\InvalidArgumentException|\JsonException) {
            throw new \InvalidArgumentException($this->translator->trans('kanka_notices.profile_unavailable'));
        }
        if ($profile !== null && !$profile->active) throw new \InvalidArgumentException($this->translator->trans('kanka_notices.profile_inactive', ['%name%' => $profile->name]));
        if ($profile !== null && !isset($profile->translations[$model->getFormatter()->getLocale()])) throw new \InvalidArgumentException($this->translator->trans('kanka_notices.translation_missing', ['%name%' => $profile->name, '%language%' => $model->getFormatter()->getLocale()]));
        return $this->resolver->resolve($profile, $model->getFormatter()->getLocale());
    }
}
