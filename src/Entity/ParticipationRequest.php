<?php

namespace App\Entity;

use App\Repository\ParticipationRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRequestRepository::class)]
class ParticipationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Tournoi::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_tournoi', onDelete: 'CASCADE')]
    private ?Tournoi $tournoi = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $playerLevel = null;

    #[ORM\Column(type: 'boolean')]
    private bool $rulesAccepted = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $applicantName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $applicantEmail = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getApplicantName(): ?string
    {
        return $this->applicantName;
    }

    public function setApplicantName(?string $applicantName): static
    {
        $this->applicantName = $applicantName;
        return $this;
    }

    public function getApplicantEmail(): ?string
    {
        return $this->applicantEmail;
    }

    public function setApplicantEmail(?string $applicantEmail): static
    {
        $this->applicantEmail = $applicantEmail;
        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->tournoi;
    }

    public function setTournoi(?Tournoi $tournoi): static
    {
        $this->tournoi = $tournoi;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getPlayerLevel(): ?string
    {
        return $this->playerLevel;
    }

    public function setPlayerLevel(?string $playerLevel): static
    {
        $this->playerLevel = $playerLevel;
        return $this;
    }

    public function isRulesAccepted(): bool
    {
        return $this->rulesAccepted;
    }

    public function setRulesAccepted(bool $rulesAccepted): static
    {
        $this->rulesAccepted = $rulesAccepted;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }
}
