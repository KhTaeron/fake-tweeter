<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTime $registrationDate = null;

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: File::class, cascade: ['persist'])]
    private ?File $avatar = null;

    /**
     * @var Collection<int, Tweet>
     */
    #[ORM\OneToMany(mappedBy: 'tweeter', targetEntity: Tweet::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $tweets;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'followingUser', orphanRemoval: true, cascade: ['remove'])]
    private Collection $subscriptions;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(mappedBy: 'followedUser', targetEntity: Subscription::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $followers;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'target')]
    private Collection $notifications;

    public function __construct()
    {
        $this->tweets = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getAvatar(): ?File
    {
        return $this->avatar;
    }

    public function setAvatar(?File $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTime
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTime $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    /**
     * @return Collection<int, Tweet>
     */
    public function getTweets(): Collection
    {
        return $this->tweets;
    }

    public function addTweet(Tweet $tweet): static
    {
        if (!$this->tweets->contains($tweet)) {
            $this->tweets->add($tweet);
            $tweet->setTweeter($this);
        }

        return $this;
    }

    public function removeTweet(Tweet $tweet): static
    {
        if ($this->tweets->removeElement($tweet)) {
            // set the owning side to null (unless already changed)
            if ($tweet->getTweeter() === $this) {
                $tweet->setTweeter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setFollowingUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getFollowingUser() === $this) {
                $subscription->setFollowingUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function getUserIdentifier(): string
    {
        return $this->pseudo;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Laisse vide, sauf si tu stockes des informations sensibles que tu veux effacer
    }

    public function isFollowedBy(User $other): bool
    {
        foreach ($this->followers as $subscription) {
            if ($subscription->getFollowingUser()->getId() === $other->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setTarget($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getTarget() === $this) {
                $notification->setTarget(null);
            }
        }

        return $this;
    }

}
