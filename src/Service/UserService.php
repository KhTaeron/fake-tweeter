<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManagerInterface
    ) {
    }

    public function getById(int $id): ?array
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return null;
        }

        return $this->formatUser($user);
    }

    public function getMe(User $user): array
    {
        return $this->formatUser($user, true);
    }

    public function updateUser(User $user, string $pseudo): void
    {
        $user->setPseudo($pseudo);
        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();
    }

    private function formatUser(User $user, bool $includeApiKey = false): array
    {
        $data = [
            'id' => $user->getId(),
            'pseudo' => $user->getPseudo(),
            'registrationDate' => $user->getRegistrationDate()->format('Y-m-d'),
            'tweets' => array_map(fn($tweet) => [
                'id' => $tweet->getId(),
                'content' => $tweet->getContent(),
                'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
            ], $user->getTweets()->toArray()),

            'followers' => array_map(fn($subscription) => [
                'followingUser' => [
                    'id' => $subscription->getFollowingUser()->getId(),
                    'pseudo' => $subscription->getFollowingUser()->getPseudo(),
                ]
            ], $user->getFollowers()->toArray()),

            'subscriptions' => array_map(fn($subscription) => [
                'followedUser' => [
                    'id' => $subscription->getFollowedUser()->getId(),
                    'pseudo' => $subscription->getFollowedUser()->getPseudo(),
                ]
            ], $user->getSubscriptions()->toArray()),
        ];

        if ($includeApiKey) {
            $data['apiKey'] = $user->getApiKey();
        }

        return $data;
    }

    public function register(string $pseudo, string $plainPassword, UserPasswordHasherInterface $hasher): User
    {
        if ($this->userRepository->findOneBy(['pseudo' => $pseudo])) {
            throw new \RuntimeException('Pseudo déjà utilisé');
        }

        $user = new User();
        $user->setPseudo($pseudo);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setRegistrationDate(new \DateTime());

        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();

        return $user;
    }
}
