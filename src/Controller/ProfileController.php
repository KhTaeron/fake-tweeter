<?php
namespace App\Controller;

use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/me', name: 'profile_me')]
    public function me(SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $user = $api->getMe();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'profile_show')]
    public function show(int $id, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $user = $api->getUser($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}
