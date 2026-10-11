<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Service;

use Twig\Sandbox\SecurityPolicyInterface;

/** Allows only the plugin's pure validator; all native sandbox checks remain in force. */
final class NoticeSandboxPolicy implements SecurityPolicyInterface
{
    public function __construct(private SecurityPolicyInterface $inner) {}

    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->inner->checkSecurity($tags, $filters, array_values(array_diff($functions, ['invoice_notices'])));
    }

    public function checkMethodAllowed($obj, $method): void { $this->inner->checkMethodAllowed($obj, $method); }
    public function checkPropertyAllowed($obj, $property): void { $this->inner->checkPropertyAllowed($obj, $property); }
}
