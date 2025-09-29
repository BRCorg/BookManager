<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Book
{
    public const GENRES = ['Roman','Essai','Science-Fiction','Fantasy','Polar','Jeunesse','Manga','BD','Poésie'];

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min:2, max:255)]
    #[ORM\Column(length:255)]
    private ?string $title = null;

    #[Assert\NotBlank]
    #[ORM\Column(length:255)]
    private ?string $author = null;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    #[Assert\Choice(choices:self::GENRES)]
    #[ORM\Column(length:50)]
    private ?string $genre = null;

    // chemin relatif (ex: uploads/covers/xxx.jpg)
    #[ORM\Column(length:255)]
    private ?string $coverImage = null;

    // slug unique basé sur le titre
    #[ORM\Column(length:255, unique:true)]
    private ?string $slug = null;

    #[ORM\Column(type:'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type:'datetime')]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy:'books')]
    #[ORM\JoinColumn(nullable:false)]
    private ?User $user = null;

    #[ORM\PrePersist] public function prePersist(): void {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }
    #[ORM\PreUpdate] public function preUpdate(): void {
        $this->updatedAt = new \DateTime();
    }

    // Getters / setters
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title=$title; return $this; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(string $author): static { $this->author=$author; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description=$d; return $this; }
    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(string $g): static { $this->genre=$g; return $this; }
    public function getCoverImage(): ?string { return $this->coverImage; }
    public function setCoverImage(string $p): static { $this->coverImage=$p; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $s): static { $this->slug=$s; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user=$u; return $this; }
}
