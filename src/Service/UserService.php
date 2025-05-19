<?php
namespace App\Service;

use App\Entity\File;
use App\Entity\Notification;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
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

    public function updateUser(User $user, ?string $pseudo, ?string $fullName, ?string $description): void
    {
        if ($pseudo !== null && trim($pseudo) !== '') {
            $user->setPseudo($pseudo);
        }

        if ($fullName !== null && trim($fullName) !== '') {
            $user->setFullName($fullName);
        }

        if ($description !== null && trim($description) !== '') {
            $user->setDescription($description);
        }

        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();
    }

    public function updateUserAvatar(User $user, File $avatar): void
    {
        $user->setAvatar($avatar);
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
            'fullName' => $user->getFullName(),
            'description' => $user->getDescription(),
            'registrationDate' => $user->getRegistrationDate()->format('Y-m-d'),
            'tweets' => array_map(fn($tweet) => [
                'id' => $tweet->getId(),
                'content' => $tweet->getContent(),
                'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
            ], $user->getTweets()->toArray()),

            'followerCount' => $user->getFollowers()->count(),
            'subscriptionCount' => $user->getSubscriptions()->count(),
        ];

        if ($user->getAvatar()) {
            $avatar = $user->getAvatar();
            $data['avatar'] = [
                'path' => $avatar->getPath(),
                'url' => '/uploads/avatars/' . $avatar->getPath(),
            ];
        } else {
            $data['avatar'] = null;
        }

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

            $notif = new Notification();
            $notif->setTarget($subscription->getFollowedUser());
            $notif->setType('follow');
            $notif->setPayload([
                'follower' => $user->getPseudo(),
                'followerId' => $user->getId(),
            ]);
            $notif->setIsRead(false);
            $notif->setCreatedAt(new DateTimeImmutable());
            $this->entityManagerInterface->persist($notif);
        }

        $this->entityManagerInterface->flush();

        return count($user->getSubscriptions());
    }

    public function getFollowers(User $user): array
    {
        return array_map(function (Subscription $subscription) {
            $u = $subscription->getFollowingUser();
            return [
                'id' => $u->getId(),
                'pseudo' => $u->getPseudo(),
                'avatar' => $u->getAvatar()
                    ? [
                        'path' => $u->getAvatar()->getPath(),
                        'url' => '/uploads/avatars/' . $u->getAvatar()->getPath(),
                    ]
                    : null,
            ];
        }, $user->getFollowers()->toArray());
    }

    public function getFollowing(User $user): array
    {
        return array_map(function (Subscription $subscription) {
            $u = $subscription->getFollowedUser();
            return [
                'id' => $u->getId(),
                'pseudo' => $u->getPseudo(),
                'avatar' => $u->getAvatar()
                    ? [
                        'path' => $u->getAvatar()->getPath(),
                        'url' => '/uploads/avatars/' . $u->getAvatar()->getPath(),
                    ]
                    : null,
            ];
        }, $user->getSubscriptions()->toArray());
    }

}
