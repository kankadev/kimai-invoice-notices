<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

/** A versioned, bounded copy of the resolved text, independent of profile edits. */
final class SnapshotCodec
{
    public const FIELD = 'kanka_notice_snapshot';
    public const OPTION = 'kanka_notice_resolved';
    public const MAX_BYTES = 60000;

    public function validate(array $notice): array
    {
        $keys = array_keys($notice);
        sort($keys);
        $expected = ['blocks', 'language', 'profile_id', 'revision', 'short', 'status'];
        if ($keys !== $expected || !is_string($notice['language']) || !preg_match('/\A[a-z]{2,3}(?:_[A-Za-z0-9]{2,8}){0,2}\z/D', $notice['language'])) {
            throw new \InvalidArgumentException('Invalid invoice notice snapshot.');
        }
        if ($notice['status'] === 'unassigned') {
            if ($notice['profile_id'] !== null || $notice['revision'] !== null || $notice['short'] !== '' || $notice['blocks'] !== []) {
                throw new \InvalidArgumentException('Invalid unassigned notice snapshot.');
            }
        } elseif ($notice['status'] === 'resolved') {
            Profile::fromArray(['id' => $notice['profile_id'], 'name' => 'Snapshot', 'description' => '', 'active' => true, 'revision' => $notice['revision'], 'translations' => [$notice['language'] => ['short' => $notice['short'], 'blocks' => $notice['blocks']]]]);
        } else {
            throw new \InvalidArgumentException('Unknown notice snapshot state.');
        }
        return $notice;
    }

    public function encode(array $notice, string $renderer, string $pdfHash): string
    {
        $this->validate($notice);
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $pdfHash) || strlen($renderer) > 255 || !mb_check_encoding($renderer, 'UTF-8')) {
            throw new \InvalidArgumentException('Invalid snapshot provenance.');
        }
        $json = json_encode(['schema' => 1, 'notices' => $notice, 'renderer' => $renderer, 'document_sha256' => $pdfHash], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (strlen($json) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Invoice notice snapshot exceeds its storage limit.');
        }
        return $json;
    }

    public function decode(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) throw new \InvalidArgumentException('Oversized invoice notice snapshot.');
        $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || count($data) !== 4 || ($data['schema'] ?? null) !== 1 || !is_array($data['notices'] ?? null) || !is_string($data['renderer'] ?? null) || !is_string($data['document_sha256'] ?? null)) {
            throw new \InvalidArgumentException('Invalid invoice notice snapshot.');
        }
        $this->encode($data['notices'], $data['renderer'], $data['document_sha256']);
        return $data;
    }
}
