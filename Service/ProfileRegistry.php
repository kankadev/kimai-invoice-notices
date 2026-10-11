<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use App\Configuration\ConfigurationService;

/** Read-only first development slice. Profile editing is implemented separately. */
final class ProfileRegistry
{
    public const KEY = 'kanka_invoice_notices.profiles';
    public const FIELD = 'kanka_notice_profile';

    public function __construct(private ConfigurationService $configuration)
    {
    }

    /** @return array<string, Profile> */
    public function all(): array
    {
        $raw = $this->configuration->getConfiguration(self::KEY)?->getValue();
        if ($raw === null) {
            return [];
        }
        if (strlen($raw) > 2000000) {
            throw new \InvalidArgumentException('Notice profile configuration exceeds its size limit.');
        }
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || ($data['schema'] ?? null) !== 1 || !isset($data['profiles']) || !is_array($data['profiles']) || !array_is_list($data['profiles']) || count($data['profiles']) > 200 || count($data) !== 2) {
            throw new \InvalidArgumentException('Invalid notice profile configuration.');
        }
        $profiles = [];
        foreach ($data['profiles'] as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Invalid notice profile.');
            }
            $profile = Profile::fromArray($row);
            if (isset($profiles[$profile->id])) {
                throw new \InvalidArgumentException('Duplicate notice profile identifier.');
            }
            $profiles[$profile->id] = $profile;
        }
        return $profiles;
    }

    public function get(string $id): Profile
    {
        return $this->all()[$id] ?? throw new \InvalidArgumentException('The selected notice profile no longer exists.');
    }
}
