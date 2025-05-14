<?php

namespace App\Service;

use App\Entity\Tweet;
use App\Entity\User;
use App\Entity\Like;
use App\Repository\TweetRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TweetService
{
    public function __construct(
        private TweetRepository       $tweets,
        private EntityManagerInterface $em,
        private ValidatorInterface     $validator
    ) {}

    public function list(): array
    {
        return $this->tweets->fetchAllOrdered();
    }

    public function get(int $id): ?array
    {
        return $this->tweets->fetchOneById($id);
    }

    public function create(array $payload, User $author): array{
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
            'id'              => $tweet->getId(),
            'content'         => $tweet->getContent(),
            'publicationDate' => $tweet->getPublicationDate()->format(\DateTimeInterface::ATOM),
            'tweeterId'       => $author->getId(),
        ];
    }

    public function delete(int $id): void {
        /** @var ?Tweet $tweet */
        $tweet = $this->em->getRepository(Tweet::class)->find($id);

        if (!$tweet) {
            throw new \RuntimeException('Tweet introuvable : '.$id);
        }

        // Ajouter l'obligation d'être auteur / admin pour supprimer

        $this->em->remove($tweet);
        $this->em->flush();
    }

    public function getLikes(int $tweetId): array {
        $tweet = $this->em->getRepository(\App\Entity\Tweet::class)->find($tweetId);

        if (!$tweet) {
            throw new \RuntimeException("Tweet introuvable");
        }

        $likes = $tweet->getLikes();

        return array_map(function(\App\Entity\Like $like) {
            $user = $like->getTweeter();
            return [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
            ];
        }, $likes->toArray());
    }

    public function update(int $id, array $payload): array {
        $tweet = $this->em->getRepository(\App\Entity\Tweet::class)->find($id);

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


}
