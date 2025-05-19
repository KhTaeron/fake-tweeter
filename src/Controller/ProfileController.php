<?php
namespace App\Controller;

use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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

    #[Route('/{id}/followers', name: 'profile_followers', requirements: ['id' => '\d+'])]
    public function followers(int $id, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $user = $api->getUser($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $followers = $api->getFollowers($id);

        return $this->render('profile/followers.html.twig', [
            'user' => $user,
            'followers' => $followers,
        ]);
    }

    #[Route('/{id}/following', name: 'profile_following', requirements: ['id' => '\d+'])]
    public function following(int $id, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $user = $api->getUser($id);id: 

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $subscriptions = $api->getSubscriptions($id);

        return $this->render('profile/following.html.twig', [
            'user' => $user,
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/edit', name: 'profile_edit')]
    public function edit(SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);
        $user = $api->getMe();

        if (!$user) {
            return $this->redirectToRoute('login_form');
        }

        // Tu pourrais pré-remplir un formulaire ici
        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update/avatar', name: 'profile_update_avatar', methods: ['POST'])]
    public function updateAvatar(Request $request, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $avatarFile = $request->files->get('avatar');

        if (!$avatarFile) {
            $this->addFlash('error', 'Aucun fichier envoyé.');
            return $this->redirectToRoute('profile_me');
        }

        $success = $api->uploadAvatar($avatarFile);

        $this->addFlash($success ? 'success' : 'error', $success ? 'Avatar mis à jour.' : 'Erreur lors de l’envoi de l’avatar.');

        return $this->redirectToRoute('profile_me');
    }

    #[Route('/update', name: 'profile_update', methods: ['POST'])]
    public function update(Request $request, SessionInterface $session, UserApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $data = [
            'pseudo' => $request->request->get('pseudo'),
            'fullName' => $request->request->get('fullName'),
            'description' => $request->request->get('description'),
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
