<?php

namespace App\Entity;

use App\Repository\LikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikeRepository::class)]
#[ORM\Table(name: '`like`')]
class Like
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $likeDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $tweeter = null;

    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tweet $likedTweet = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLikeDate(): ?\DateTime
    {
        return $this->likeDate;
    }

    public function setLikeDate(\DateTime $likeDate): static
    {
        $this->likeDate = $likeDate;

        return $this;
    }

    public function getTweeter(): ?User
    {
        return $this->tweeter;
    }

    public function setTweeter(?User $tweeter): static
    {
        $this->tweeter = $tweeter;

        return $this;
    }

    public function getLikedTweet(): ?Tweet
    {
        return $this->likedTweet;
    }

    public function setLikedTweet(?Tweet $likedTweet): static
    {
        $this->likedTweet = $likedTweet;

        return $this;
    }
}
