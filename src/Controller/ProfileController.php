<?php
namespace App\Controller;

use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

        return $this->render('profile/me-profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'profile_edit')]
    public function edit(SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);
        $user = $api->getMe();

        if (!$user) {
            return $this->redirectToRoute('form_login');
        }

        // Tu pourrais pré-remplir un formulaire ici
        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update', name: 'profile_update', methods: ['POST'])]
    public function update(Request $request, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $data = [
            'pseudo' => $request->request->get('pseudo'),
        ];

        $success = $api->updateMe($data);

        if ($success) {
            $this->addFlash('success', 'Profil mis à jour.');
        } else {
            $this->addFlash('error', 'Échec de la mise à jour.');
        }

        return $this->redirectToRoute('profile_me');
    }

    #[Route(path: '/delete', name: 'profile_delete', methods: ['DELETE'])]
    public function deleteAccount(SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setJwtToken($session->get('jwt_token'));

        if ($api->deleteMe()) {
            $session->invalidate();
            $this->addFlash('success', 'Compte supprimé.');
        } else {
            $this->addFlash('error', 'Impossible de supprimer le compte.');
        }

        return $this->redirectToRoute('login_form');
    }

    #[Route('/{id}', name: 'profile_show', requirements: ['id' => '\d+'])]
    public function show(int $id, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $me = $api->getMe();
        if ($me && $me['id'] === $id) {
            return $this->redirectToRoute('profile_me');
        }

        $user = $api->getUser($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'selfProfile' => false,
        ]);
    }

    #[Route('/{id}/follow', name: 'profile_follow', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function follow(int $id, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $success = $api->toggleSubscription($id);

        if ($success) {
            $this->addFlash('success', 'Action de suivi mise à jour.');
        } else {
            $this->addFlash('error', 'Impossible de mettre à jour le suivi.');
        }

        return $this->redirectToRoute('profile_show', ['id' => $id]);
    }

}
