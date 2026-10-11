<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

/** Validated plain-text configuration; content is never evaluated as template code. */
final class Profile
{
    private function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly bool $active,
        public readonly int $revision,
        public readonly array $translations,
    ) {
    }

    public static function fromArray(array $data): self
    {
        self::keys($data, ['id', 'name', 'description', 'active', 'revision', 'translations']);
        if (!is_string($data['id']) || !preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $data['id'])) {
            throw new \InvalidArgumentException('Invalid profile identifier.');
        }
        self::text($data['name'], 120);
        self::text($data['description'], 2000);
        if (trim($data['name']) === '' || !is_bool($data['active']) || !is_int($data['revision']) || $data['revision'] < 1) {
            throw new \InvalidArgumentException('Invalid profile metadata.');
        }
        if (!is_array($data['translations']) || count($data['translations']) > 100) {
            throw new \InvalidArgumentException('Invalid profile translations.');
        }
        foreach ($data['translations'] as $language => $content) {
            if (!is_string($language) || !preg_match('/\A[a-z]{2,3}(?:_[A-Za-z0-9]{2,8}){0,2}\z/D', $language) || !is_array($content)) {
                throw new \InvalidArgumentException('Invalid content language.');
            }
            self::keys($content, ['short', 'blocks']);
            self::text($content['short'], 1000);
            if (!is_array($content['blocks']) || !array_is_list($content['blocks']) || count($content['blocks']) > 20) {
                throw new \InvalidArgumentException('Invalid explanation blocks.');
            }
            foreach ($content['blocks'] as $block) {
                if (!is_array($block)) {
                    throw new \InvalidArgumentException('Invalid explanation block.');
                }
                self::keys($block, ['title', 'text']);
                self::text($block['title'], 200);
                self::text($block['text'], 10000);
            }
        }
        return new self($data['id'], $data['name'], $data['description'], $data['active'], $data['revision'], $data['translations']);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'active' => $this->active, 'revision' => $this->revision, 'translations' => $this->translations];
    }

    private static function keys(array $data, array $keys): void
    {
        $actual = array_keys($data);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            throw new \InvalidArgumentException('Unexpected or missing profile fields.');
        }
    }

    private static function text(mixed $value, int $limit): void
    {
        if (!is_string($value) || !preg_match('//u', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) || mb_strlen($value, 'UTF-8') > $limit) {
            throw new \InvalidArgumentException('Invalid or oversized notice text.');
        }
    }
}
