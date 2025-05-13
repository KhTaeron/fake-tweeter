<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $subscriptionDate = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $followingUser = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    private ?User $followedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscriptionDate(): ?\DateTime
    {
        return $this->subscriptionDate;
    }

    public function setSubscriptionDate(\DateTime $subscriptionDate): static
    {
        $this->subscriptionDate = $subscriptionDate;

        return $this;
    }

    public function getFollowingUser(): ?User
    {
        return $this->followingUser;
    }

    public function setFollowingUser(?User $followingUser): static
    {
        $this->followingUser = $followingUser;

        return $this;
    }

    public function getFollowedUser(): ?User
    {
        return $this->followedUser;
    }

    public function setFollowedUser(?User $followedUser): static
    {
        $this->followedUser = $followedUser;

        return $this;
    }
}
