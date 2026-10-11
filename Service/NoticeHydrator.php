<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

final class NoticeHydrator implements InvoiceModelHydrator
{
    public function __construct(private ProfileRegistry $profiles, private NoticeResolver $resolver)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $value = $model->getCustomer()->getMetaField(ProfileRegistry::FIELD)?->getValue();
        $profile = $value === null || $value === '' ? null : $this->profiles->get((string) $value);
        return ['kanka_notices' => $this->resolver->resolve($profile, $model->getFormatter()->getLocale())];
    }
}
