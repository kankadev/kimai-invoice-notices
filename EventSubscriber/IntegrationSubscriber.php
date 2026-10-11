<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\EventSubscriber;

use App\Entity\CustomerMeta;
use App\Event\ConfigureMainMenuEvent;
use App\Event\CustomerMetaDefinitionEvent;
use App\Utils\MenuItemModel;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IntegrationSubscriber implements EventSubscriberInterface
{
    public function __construct(private ProfileRegistry $profiles, private AuthorizationCheckerInterface $auth, private TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConfigureMainMenuEvent::class => 'menu', CustomerMetaDefinitionEvent::class => 'fields'];
    }

    public function menu(ConfigureMainMenuEvent $event): void
    {
        if ($this->auth->isGranted('system_configuration')) {
            $event->getSystemMenu()->addChild(new MenuItemModel('kanka_invoice_notices', 'kanka_notices.title', 'kanka_invoice_notices_settings', [], 'invoice'));
        }
    }

    public function fields(CustomerMetaDefinitionEvent $event): void
    {
        $selected = $event->getEntity()->getMetaField(ProfileRegistry::FIELD)?->getValue();
        $choices = [];
        foreach ($this->profiles->all() as $profile) {
            if ($profile->active || $selected === $profile->id) {
                $label = $profile->name.' ['.$profile->id.']';
                if (!$profile->active) {
                    $label .= ' — '.$this->translator->trans('kanka_notices.inactive');
                }
                $choices[$label] = $profile->id;
            }
        }
        if (is_string($selected) && $selected !== '' && !in_array($selected, $choices, true)) {
            $choices[$this->translator->trans('kanka_notices.missing').' ['.$selected.']'] = $selected;
        }
        $field = (new CustomerMeta())->setName(ProfileRegistry::FIELD)->setLabel('kanka_notices.customer_profile')->setType(ChoiceType::class)->setIsRequired(false)->setIsVisible(false)->setOrder(110)->setOptions([
            'choices' => $choices, 'choice_translation_domain' => false, 'translation_domain' => 'messages',
            'placeholder' => 'kanka_notices.unassigned', 'help' => 'kanka_notices.customer_help',
        ]);
        $event->getEntity()->setMetaField($field);
    }
}
