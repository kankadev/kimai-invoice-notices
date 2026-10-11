<?php
declare(strict_types=1);

namespace KimaiPlugin\KankaInvoiceNoticesBundle\Controller;

use KimaiPlugin\KankaInvoiceNoticesBundle\Service\ProfileRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{_locale}/invoice-notices', requirements: ['_locale' => '%app_locales%'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('system_configuration')]
final class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'kanka_invoice_notices_settings', methods: ['GET'])]
    public function index(ProfileRegistry $profiles): Response
    {
        return $this->render('@KankaInvoiceNotices/settings.html.twig', ['profiles' => $profiles->all()]);
    }
}
