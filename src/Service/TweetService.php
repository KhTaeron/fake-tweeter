<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Tweet;
use App\Entity\User;
use App\Entity\Like;
use App\Repository\TweetRepository;
use App\Repository\LikeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TweetService
{
    public function __construct(
        private TweetRepository $tweetRepository,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    public function list(): array
    {
        $rawTweets = $this->tweetRepository->fetchAllOrdered();

        return array_map(function (Tweet $tweet) {
            return [
                'id' => $tweet->getId(),
                'content' => $tweet->getContent(),
                'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
                'tweeter' => [
                    'id' => $tweet->getTweeter()?->getId(),
                    'pseudo' => $tweet->getTweeter()?->getPseudo(),
                ],
            ];
        }, $rawTweets);
    }



    public function get(int $id): ?array
    {
        return $this->tweetRepository->fetchOneById($id);
    }

    public function create(array $payload, User $author): array
    {
        $violations = $this->validator->validate(
            $payload,
            new Assert\Collection([
                'fields' => [
                    'content' => [new Assert\NotBlank(), new Assert\Length(max: 280)],
                ],
                'allowExtraFields' => true,
            ])
        );

        if (\count($violations) > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }

        $tweet = (new Tweet())
            ->setContent($payload['content'])
            ->setPublicationDate(new \DateTime())
            ->setTweeter($author);

        $this->em->persist($tweet);
        $this->em->flush();

        return [
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
            'tweeterId' => $author->getId(),
        ];
    }

    public function delete(int $id): void
    {
        /** @var ?Tweet $tweet */
        $tweet = $this->tweetRepository->find($id);

        if (!$tweet) {
            throw new \RuntimeException('Tweet introuvable : ' . $id);
        }

        // Ajouter l'obligation d'être auteur / admin pour supprimer

        $this->em->remove($tweet);
        $this->em->flush();
    }

    public function getLikes(int $tweetId): array
    {
        $tweet = $this->em->getRepository(\App\Entity\Tweet::class)->find($tweetId);

        if (!$tweet) {
            throw new \RuntimeException("Tweet introuvable");
        }

        $likes = $tweet->getLikes();

        return array_map(function (\App\Entity\Like $like) {
            $user = $like->getTweeter();
            return [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
            ];
        }, $likes->toArray());
    }

    public function update(int $id, array $payload): array
    {
        $tweet = $this->tweetRepository->find($id);

        if (!$tweet) {
            throw new \RuntimeException('Tweet introuvable');
        }

        $violations = $this->validator->validate(
            $payload,
            new Assert\Collection([
                'content' => [new Assert\NotBlank(), new Assert\Length(max: 280)],
            ])
        );

        if (count($violations) > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }

        $tweet->setContent($payload['content']);
        $this->em->flush();

        return [
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
            'tweeterId' => $tweet->getTweeter()->getId(),
        ];
    }

    public function search(string $keyword = ''): array
    {
        if ($keyword === '') {
            return $this->tweetRepository->fetchAllOrdered();
        }

        return $this->tweetRepository->searchByKeyword($keyword);
    }

    public function getFullEntity(int $id): ?array
    {
        $tweet = $this->tweetRepository->find($id);

        if (!$tweet) {
            return null;
        }

        return $this->formatTweet($tweet);
    }

    public function formatTweet(Tweet $tweet): array
    {
        return [
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'publicationDate' => $tweet->getPublicationDate()->format('Y-m-d H:i:s'),
            'tweeter' => [
                'id' => $tweet->getTweeter()->getId(),
                'pseudo' => $tweet->getTweeter()->getPseudo(),
            ],
            'likeCount' => count($tweet->getLikes()),
        ];
    }

    public function toggleLike(int $tweetId, User $user): int
    {
        $tweet = $this->em->getRepository(Tweet::class)->find($tweetId);

        if (!$tweet) {
            throw new \RuntimeException('Tweet introuvable.');
        }

        $existingLike = $this->em->getRepository(Like::class)->findOneBy([
            'likedTweet' => $tweet,
            'tweeter' => $user,
        ]);

        if ($existingLike) {
            $this->em->remove($existingLike);
        } else {
            $like = new Like();
            $like->setLikedTweet($tweet);
            $like->setTweeter($user);
            $like->setLikeDate(new \DateTime());

            $this->em->persist($like);

            if ($user !== $tweet->getTweeter()) {
                $notif = new Notification();
                $notif->setTarget($tweet->getTweeter());
                $notif->setType('like');
                $notif->setPayload([
                    'tweetId' => $tweet->getId(),
                    'liker' => $user->getPseudo(),
                    'likerId' => $user->getId(),
                ]);
                $notif->setIsRead(false);
                $notif->setCreatedAt(new DateTimeImmutable());
                $this->em->persist($notif);
            }
        }

        $this->em->flush();

        // On retourne le nombre de likes après changement
        return count($tweet->getLikes());
    }
}
