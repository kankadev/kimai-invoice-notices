<?php
declare(strict_types=1);
namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use App\Configuration\LocaleService;
use Doctrine\ORM\EntityManagerInterface;

final class ContentLanguages
{
    public function __construct(private EntityManagerInterface $em, private ProfileRegistry $profiles, private LocaleService $locales)
    {
    }

    public function available(): array
    {
        return $this->locales->getAllLocales();
    }

    public function discover(): array
    {
        $result = [];
        foreach (['customer' => 'Customer', 'template' => 'InvoiceTemplate'] as $kind => $entity) {
            $rows = $this->em->createQuery('SELECT e.language AS language, COUNT(e.id) AS total FROM App\\Entity\\'.$entity.' e GROUP BY e.language')->getArrayResult();
            foreach ($rows as $row) {
                $language = $row['language'];
                if ($language === null || $language === '') {
                    if ($kind === 'template') continue;
                    $language = 'en';
                }
                $result[$language] ??= ['customer' => 0, 'template' => 0, 'configured' => 0];
                $result[$language][$kind] += (int) $row['total'];
            }
        }
        foreach ($this->profiles->all() as $profile) {
            foreach (array_keys($profile->translations) as $language) {
                $result[$language] ??= ['customer' => 0, 'template' => 0, 'configured' => 0];
                ++$result[$language]['configured'];
            }
        }
        ksort($result);
        return $result;
    }
}
