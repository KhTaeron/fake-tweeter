<?php

namespace App\Entity;

use App\Repository\TweetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TweetRepository::class)]
class Tweet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTime $publicationDate = null;

    #[ORM\ManyToOne(inversedBy: 'tweets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $tweeter = null;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'likedTweet', orphanRemoval: true, cascade: ['remove'])]
    private Collection $likes;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: "retweet_origin_id", referencedColumnName: "id", onDelete: "SET NULL", nullable: true)]
    private ?Tweet $retweetOrigin = null;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getPublicationDate(): ?\DateTime
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTime $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

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

    /**
     * @return Collection<int, Like>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setLikedTweet($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getLikedTweet() === $this) {
                $like->setLikedTweet(null);
            }
        }

        return $this;
    }

        public function getRetweetOrigin(): ?Tweet
    {
        return $this->retweetOrigin;
    }

    public function setRetweetOrigin(?Tweet $tweet): self
    {
        $this->retweetOrigin = $tweet;
        return $this;
    }
}
