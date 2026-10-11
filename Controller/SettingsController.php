<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Controller;

use KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry;
use KimaiPlugin\KankaInvoiceNoticesBundle\Service\ContentLanguages;
use KimaiPlugin\KankaInvoiceNoticesBundle\Exception\ProfileConflict;
use KimaiPlugin\KankaInvoiceNoticesBundle\Form\NoticeBlockType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\{FormType, TextType, TextareaType, CheckboxType, HiddenType, ChoiceType, CollectionType};
use Symfony\Component\Validator\Constraints\{Length, NotBlank, Regex};
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{_locale}/invoice-notices', requirements: ['_locale' => '%app_locales%'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('system_configuration')]
final class SettingsController extends AbstractController
{
    public function __construct(private ProfileRegistry $profiles, private ContentLanguages $languages, private FormFactoryInterface $forms, private TranslatorInterface $translator, private LoggerInterface $logger)
    {
    }

    #[Route('/settings', name: 'kanka_invoice_notices_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('@KankaInvoiceNotices/settings.html.twig', ['profiles' => $this->profiles->all(), 'languages' => $this->languages->discover(), 'usage' => $this->profiles->usage(), 'limit' => ProfileRegistry::MAX_BYTES]);
    }

    #[Route('/profiles/new', name: 'kanka_invoice_notices_new', methods: ['GET', 'POST'])]
    #[Route('/profiles/{id}', name: 'kanka_invoice_notices_edit', requirements: ['id' => '[a-z][a-z0-9_-]{0,63}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, ?string $id = null): Response
    {
        try { $profile = $id === null ? null : $this->profiles->get($id); }
        catch (\InvalidArgumentException) { throw $this->createNotFoundException(); }
        $sections = $this->languages->discover();
        $data = ['name' => $profile?->name ?? '', 'description' => $profile?->description ?? '', 'active' => $profile?->active ?? true, 'token' => $this->profiles->token(), 'additional_language' => null];
        foreach ($sections as $code => $counts) {
            $content = $profile?->translations[$code] ?? null;
            $data['lang_'.$code] = ['enabled' => $content !== null, 'short' => $content['short'] ?? '', 'blocks' => $content['blocks'] ?? []];
        }
        $submitted = $request->isMethod('POST') ? $request->request->all('notice_profile') : [];
        // Preserve manually added language sections while processing the unsaved form.
        foreach ($submitted as $key => $value) {
            if (str_starts_with((string) $key, 'lang_')) {
                $code = substr($key, 5);
                if (in_array($code, $this->languages->available(), true) && !isset($sections[$code])) {
                    $sections[$code] = ['customer' => 0, 'template' => 0, 'configured' => 0];
                    $data[$key] = ['enabled' => false, 'short' => '', 'blocks' => []];
                }
                if (!is_array($value) || !is_array($value['blocks'] ?? []) || count($value['blocks'] ?? []) > 20) {
                    throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid block collection.');
                }
            }
        }
        $action = $request->request->getString('action', 'save');
        $form = $this->form($data, $sections, $request, $action === 'save');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($action === 'save') {
                $translations = [];
                foreach ($sections as $code => $counts) {
                    $content = $data['lang_'.$code];
                    if (!$content['enabled']) {
                        $hasText = trim($content['short'] ?? '') !== '';
                        foreach ($content['blocks'] as $block) $hasText = $hasText || trim(($block['title'] ?? '').($block['text'] ?? '')) !== '';
                        if ($hasText) $form->get('lang_'.$code)->addError(new FormError($this->translator->trans('kanka_notices.enable_translation')));
                        continue;
                    }
                    $blocks = array_values(array_map(static fn ($block) => ['title' => $block['title'] ?? '', 'text' => $block['text'] ?? ''], $content['blocks']));
                    $translations[$code] = ['short' => $content['short'] ?? '', 'blocks' => $blocks];
                }
                try {
                    if (!$form->isValid()) {
                        return $this->render('@KankaInvoiceNotices/edit.html.twig', ['form' => $form->createView(), 'sections' => $sections, 'profile' => $profile]);
                    }
                    $saved = $this->profiles->save($id, ['name' => $data['name'], 'description' => $data['description'] ?? '', 'active' => $data['active'], 'translations' => $translations], $data['token']);
                    $this->addFlash('notices_saved', $this->translator->trans('kanka_notices.saved', ['%name%' => $saved->name]));
                    return $this->redirectToRoute('kanka_invoice_notices_settings');
                } catch (ProfileConflict) {
                    $form->addError(new FormError($this->translator->trans('kanka_notices.conflict')));
                } catch (\InvalidArgumentException $error) {
                    $key = $error->getMessage() === 'kanka_notices.storage_limit' ? $error->getMessage() : 'kanka_notices.invalid';
                    $form->addError(new FormError($this->translator->trans($key)));
                } catch (\Doctrine\DBAL\Exception $error) {
                    $this->logger->error('Invoice Notices configuration write failed.', ['exception' => $error]);
                    $form->addError(new FormError($this->translator->trans('kanka_notices.storage_error')));
                }
            } elseif ($action === 'language') {
                $code = $data['additional_language'];
                if ($code !== null && !isset($sections[$code])) {
                    $sections[$code] = ['customer' => 0, 'template' => 0, 'configured' => 0];
                    $data['lang_'.$code] = ['enabled' => false, 'short' => '', 'blocks' => []];
                }
                $form = $this->form($data, $sections, $request, true);
            } elseif (preg_match('/\A(add|up|down|remove):([a-z]{2,3}(?:_[A-Za-z0-9]{2,8}){0,2})(?::([0-9]{1,2}))?\z/D', $action, $match) && isset($sections[$match[2]])) {
                $key = 'lang_'.$match[2];
                $blocks = array_values($data[$key]['blocks']);
                $index = isset($match[3]) ? (int) $match[3] : -1;
                if ($match[1] === 'add' && count($blocks) < 20) $blocks[] = ['title' => '', 'text' => ''];
                if (isset($blocks[$index])) {
                    if ($match[1] === 'remove') array_splice($blocks, $index, 1);
                    $other = $match[1] === 'up' ? $index - 1 : ($match[1] === 'down' ? $index + 1 : -1);
                    if (isset($blocks[$other])) [$blocks[$index], $blocks[$other]] = [$blocks[$other], $blocks[$index]];
                }
                $data[$key]['blocks'] = $blocks;
                $form = $this->form($data, $sections, $request, true);
            } else {
                $form->addError(new FormError($this->translator->trans('kanka_notices.invalid')));
            }
        }
        return $this->render('@KankaInvoiceNotices/edit.html.twig', ['form' => $form->createView(), 'sections' => $sections, 'profile' => $profile]);
    }

    private function form(array $data, array $sections, Request $request, bool $validate): \Symfony\Component\Form\FormInterface
    {
        $builder = $this->forms->createNamedBuilder('notice_profile', FormType::class, $data, ['validation_groups' => $validate ? ['Default'] : false])
            ->add('name', TextType::class, ['label' => 'kanka_notices.name', 'required' => false, 'constraints' => [new NotBlank(), new Length(max: 120)]])
            ->add('description', TextareaType::class, ['label' => 'kanka_notices.description', 'required' => false, 'attr' => ['rows' => 2], 'constraints' => [new Length(max: 2000)]])
            ->add('active', CheckboxType::class, ['label' => 'kanka_notices.active', 'required' => false])
            ->add('token', HiddenType::class, ['constraints' => [new NotBlank(), new Regex('/\A[a-f0-9]{64}\z/D')]]);
        foreach ($sections as $code => $counts) {
            $section = $builder->create('lang_'.$code, FormType::class)
                ->add('enabled', CheckboxType::class, ['label' => 'kanka_notices.translation_enabled', 'required' => false])
                ->add('short', TextareaType::class, ['label' => 'kanka_notices.short', 'required' => false, 'attr' => ['rows' => 3], 'constraints' => [new Length(max: 1000)]])
                ->add('blocks', CollectionType::class, ['entry_type' => NoticeBlockType::class, 'allow_add' => true, 'allow_delete' => true, 'prototype' => false, 'required' => false, 'label' => false]);
            $builder->add($section);
        }
        $choices = [];
        foreach ($this->languages->available() as $code) {
            $choices[\Locale::getDisplayName($code, $request->getLocale()).' ('.$code.')'] = $code;
        }
        $builder->add('additional_language', ChoiceType::class, ['label' => 'kanka_notices.additional_language', 'choices' => $choices, 'choice_translation_domain' => false, 'required' => false, 'placeholder' => 'kanka_notices.choose_language']);
        return $builder->getForm();
    }
}
