<?php
declare(strict_types=1);

require __DIR__.'/../Service/Profile.php';
require __DIR__.'/../Service/NoticeResolver.php';

use KimaiPlugin\KankaInvoiceNoticesBundle\Service\Profile;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\NoticeResolver;

$checks = 0;
function check(bool $condition, string $name): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($name);
    ++$checks;
}
function rejects(callable $action, string $name): void
{
    try { $action(); } catch (InvalidArgumentException) { check(true, $name); return; }
    throw new RuntimeException('Expected rejection: '.$name);
}
$data = ['id' => 'sample-business', 'name' => 'Example business notice', 'description' => 'Synthetic test profile.', 'active' => true, 'revision' => 1, 'translations' => [
    'de' => ['short' => 'Hinweis: 150 € – Grüße', 'blocks' => [['title' => 'Erstens', 'text' => 'Eins'], ['title' => 'Zweitens', 'text' => 'Zwei']]],
    'en' => ['short' => 'Example notice', 'blocks' => []],
]];
$profile = Profile::fromArray($data);
$resolver = new NoticeResolver();
check($resolver->resolve($profile, 'de')['short'] === $data['translations']['de']['short'], 'Unicode unchanged');
check($resolver->resolve($profile, 'en')['short'] === 'Example notice', 'English selection');
check(array_column($resolver->resolve($profile, 'de')['blocks'], 'title') === ['Erstens', 'Zweitens'], 'Block order');
check($resolver->resolve(null, 'de')['status'] === 'unassigned', 'Missing assignment explicit');
rejects(fn () => $resolver->resolve($profile, 'fr'), 'Missing translation');
rejects(fn () => $resolver->resolve($profile, 'de_DE'), 'No implicit locale fallback');
$inactive = $data; $inactive['active'] = false;
rejects(fn () => $resolver->resolve(Profile::fromArray($inactive), 'de'), 'Inactive profile');
foreach (['id' => '../profile', 'name' => '', 'revision' => 0, 'active' => 'yes', 'translations' => 'invalid'] as $key => $value) {
    $invalid = $data; $invalid[$key] = $value;
    rejects(fn () => Profile::fromArray($invalid), 'Invalid '.$key);
}
$extra = $data; $extra['vat'] = 19;
rejects(fn () => Profile::fromArray($extra), 'Unexpected tax input');
$control = $data; $control['translations']['de']['short'] = "Bad\0text";
rejects(fn () => Profile::fromArray($control), 'Control characters');
$oversized = $data; $oversized['translations']['de']['short'] = str_repeat('ä', 1001);
rejects(fn () => Profile::fromArray($oversized), 'Character limit');
$markup = $data; $markup['translations']['de']['short'] = '<script>alert(1)</script> {{ 7 * 7 }}';
check($resolver->resolve(Profile::fromArray($markup), 'de')['short'] === $markup['translations']['de']['short'], 'Content remains inert text');
$empty = $data; $empty['translations']['de'] = ['short' => '', 'blocks' => []];
check($resolver->resolve(Profile::fromArray($empty), 'de')['status'] === 'resolved', 'Explicit empty profile differs from unassigned');
check($profile->toArray() === $data, 'Profile serialization preserves the validated content');
$many = $data; $many['translations']['de']['blocks'] = array_fill(0, 21, ['title' => '', 'text' => '']);
rejects(fn () => Profile::fromArray($many), 'Explanation block limit');
$invalidUtf8 = $data; $invalidUtf8['translations']['de']['short'] = "\xFF";
rejects(fn () => Profile::fromArray($invalidUtf8), 'Invalid UTF-8 rejected');
echo $checks." domain checks passed.\n";
