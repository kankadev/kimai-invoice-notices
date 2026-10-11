<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Form;

use App\Form\Toolbar\InvoiceToolbarForm;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvoiceNoticeExtension extends AbstractTypeExtension
{
    public const FIELD = 'kankaNoticeProfile';
    public const CUSTOMER = '__customer__';
    public const NONE = '__none__';

    public function __construct(private ProfileRegistry $profiles, private TranslatorInterface $translator) {}

    public static function getExtendedTypes(): iterable { return [InvoiceToolbarForm::class]; }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [$this->translator->trans('kanka_notices.use_customer') => self::CUSTOMER, $this->translator->trans('kanka_notices.without_notices') => self::NONE];
        foreach ($this->profiles->all() as $profile) {
            if ($profile->active) $choices[$profile->name.' ['.$profile->id.']'] = $profile->id;
        }
        $builder->add(self::FIELD, ChoiceType::class, ['mapped' => false, 'required' => true, 'data' => self::CUSTOMER, 'choices' => $choices, 'choice_translation_domain' => false, 'label' => 'kanka_notices.invoice_override', 'help' => 'kanka_notices.invoice_override_help']);
    }
}
