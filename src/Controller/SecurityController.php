<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use App\Service\AuthApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login_form')]
    public function login(Request $request): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => null,
        ]);
    }

    #[Route('/login/submit', name: 'login_submit', methods: ['POST'])]
    public function loginSubmit(AuthApiClientService $auth, Request $request, HttpClientInterface $client, SessionInterface $session): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $ok = $auth->login($username, $password, $session);

        if (!$ok) {
            return new Response('Erreur de connexion', 401);
        }


        return $this->redirectToRoute('tweets_home');
    }

    #[Route('/register', name: 'register_form')]
    public function registerForm(): Response
    {
        return $this->render('security/register.html.twig');
    }

    #[Route('/register/submit', name: 'register_submit', methods: ['POST'])]
    public function registerSubmit(Request $request, AuthApiClientService $auth): Response
    {
        $data = [
            'pseudo' => $request->request->get('pseudo'),
            'password' => $request->request->get('password'),
        ];

        $result = $auth->register($data);

        if (!$result['success']) {
            $error = $result['data']['error'] ?? 'Inscription échouée.';
            $this->addFlash('error', $error);
            return $this->redirectToRoute('register_form');
        }

        $this->addFlash('success', 'Compte créé ! Vous pouvez vous connecter.');
        return $this->redirectToRoute('login_form');
    }


}
