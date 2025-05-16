<?php
namespace App\Service;

use App\Entity\Subscription;
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

    public function getById(int $id, ?User $currentUser = null): ?array
    {
        $user = $this->userRepository->find($id);
        return $user ? $this->formatUser($user, false, $currentUser) : null;
    }


    // Récupérer l'entité d'User
    public function getUserEntityById(int $id): ?User
    {
        return $this->userRepository->find($id);
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

    public function deleteUser(User $user): void
    {
        $this->entityManagerInterface->remove($user);
        $this->entityManagerInterface->flush();
    }

    private function formatUser(User $user, bool $includeApiKey = false, ?User $currentUser = null): array
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

        if ($currentUser) {
            $data['isFollowed'] =
                $user->getId() !== $currentUser->getId()
                && $user->isFollowedBy($currentUser);
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

    public function toggleSubscription(int $userToFollowId, User $user): int
    {
        if ($user->getId() === $userToFollowId) {
            throw new \LogicException("Vous ne pouvez pas vous suivre vous-même.");
        }

        $userToFollow = $this->entityManagerInterface->getRepository(User::class)->find($userToFollowId);

        if (!$userToFollow) {
            throw new \RuntimeException('Utilisateur à suivre introuvable.');
        }

        $repo = $this->entityManagerInterface->getRepository(Subscription::class);

        $subscription = $repo->findOneBy([
            'followedUser' => $userToFollow,
            'followingUser' => $user,
        ]);

        if ($subscription) {
            $this->entityManagerInterface->remove($subscription);
        } else {
            $subscription = new Subscription();
            $subscription->setFollowedUser($userToFollow);
            $subscription->setFollowingUser($user);
            $subscription->setSubscriptionDate(new \DateTime());

            $this->entityManagerInterface->persist($subscription);
        }

        $this->entityManagerInterface->flush();

        return count($user->getSubscriptions());
    }

}
