<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

final class NoticeResolver
{
    public function resolve(?Profile $profile, string $language): array
    {
        if ($profile === null) {
            return ['status' => 'unassigned', 'profile_id' => null, 'revision' => null, 'language' => $language, 'short' => '', 'blocks' => []];
        }
        if (!$profile->active) {
            throw new \InvalidArgumentException('The selected notice profile is inactive.');
        }
        // An exact document locale is required; missing translations never become another language.
        $content = $profile->translations[$language] ?? null;
        if ($content === null) {
            throw new \InvalidArgumentException('The selected notice profile has no translation for '.$language.'.');
        }
        return ['status' => 'resolved', 'profile_id' => $profile->id, 'revision' => $profile->revision, 'language' => $language, 'short' => $content['short'], 'blocks' => $content['blocks']];
    }
}
