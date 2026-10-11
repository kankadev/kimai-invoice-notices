<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use App\Configuration\ConfigurationService;
use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use KimaiPlugin\KankaInvoiceNoticesBundle\Exception\ProfileConflict;

/** Stores only this plugin's configuration with compare-and-swap protection. */
final class ProfileRegistry
{
    public const KEY = 'kanka_invoice_notices.profiles';
    public const FIELD = 'kanka_notice_profile';
    public const MAX_BYTES = 60000;

    public function __construct(private ConfigurationService $configuration, private EntityManagerInterface $em)
    {
    }

    /** @return array<string, Profile> */
    public function all(): array
    {
        return $this->decode($this->raw());
    }

    private function decode(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }
        if (strlen($raw) > self::MAX_BYTES) {
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

    public function token(): string
    {
        return hash('sha256', $this->raw() ?? '');
    }

    public function usage(): int
    {
        return strlen($this->raw() ?? '');
    }

    private function table(): string
    {
        return $this->em->getConnection()->quoteIdentifier($this->em->getClassMetadata(Configuration::class)->getTableName());
    }

    private function raw(bool $lock = false): ?string
    {
        $db = $this->em->getConnection();
        $query = 'SELECT '.$db->quoteIdentifier('value').' FROM '.$this->table().' WHERE '.$db->quoteIdentifier('name').' = ?';
        if ($lock) $query .= ' '.$db->getDatabasePlatform()->getWriteLockSQL();
        $value = $db->fetchOne($query, [self::KEY]);
        return $value === false || $value === null ? null : (string) $value;
    }

    public function save(?string $id, array $data, string $token): Profile
    {
        $db = $this->em->getConnection();
        $db->beginTransaction();
        try {
            $raw = $this->raw(true);
            if (!hash_equals(hash('sha256', $raw ?? ''), $token)) {
                throw new ProfileConflict();
            }
            $profiles = $this->decode($raw);
            $existing = $id === null ? null : ($profiles[$id] ?? throw new ProfileConflict());
            $data['id'] = $id ?? 'p_'.bin2hex(random_bytes(16));
            $data['revision'] = ($existing?->revision ?? 0) + 1;
            $profile = Profile::fromArray($data);
            $profiles[$profile->id] = $profile;
            if (count($profiles) > 200) {
                throw new \InvalidArgumentException('Profile limit reached.');
            }
            $value = json_encode(['schema' => 1, 'profiles' => array_values(array_map(static fn (Profile $p) => $p->toArray(), $profiles))], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            if (strlen($value) > self::MAX_BYTES) {
                throw new \InvalidArgumentException('kanka_notices.storage_limit');
            }
            if ($raw === null) {
                $exists = $db->fetchOne('SELECT COUNT(*) FROM '.$this->table().' WHERE '.$db->quoteIdentifier('name').' = ?', [self::KEY]);
                if ((int) $exists === 0) {
                    $db->insert($this->em->getClassMetadata(Configuration::class)->getTableName(), ['name' => self::KEY, 'value' => $value]);
                } elseif ($db->executeStatement('UPDATE '.$this->table().' SET '.$db->quoteIdentifier('value').' = ? WHERE '.$db->quoteIdentifier('name').' = ? AND '.$db->quoteIdentifier('value').' IS NULL', [$value, self::KEY]) !== 1) {
                    throw new ProfileConflict();
                }
            } else {
                $affected = $db->executeStatement('UPDATE '.$this->table().' SET '.$db->quoteIdentifier('value').' = ? WHERE '.$db->quoteIdentifier('name').' = ? AND '.$db->quoteIdentifier('value').' = ?', [$value, self::KEY, $raw]);
                if ($affected !== 1) {
                    throw new ProfileConflict();
                }
            }
            $db->commit();
        } catch (\Throwable $error) {
            $db->rollBack();
            if ($error instanceof UniqueConstraintViolationException) {
                throw new ProfileConflict(previous: $error);
            }
            throw $error;
        }
        $this->configuration->clearCache();
        return $profile;
    }
}
