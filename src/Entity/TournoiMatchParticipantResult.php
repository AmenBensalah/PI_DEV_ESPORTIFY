<?php

namespace App\Entity;

use App\Repository\TournoiMatchParticipantResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournoiMatchParticipantResultRepository::class)]
#[ORM\Table(name: 'tournoi_match_participant_result')]
#[ORM\UniqueConstraint(name: 'uniq_match_participant', columns: ['match_id', 'participant_id'])]
class TournoiMatchParticipantResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TournoiMatch::class, inversedBy: 'participantResults')]
    #[ORM\JoinColumn(name: 'match_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TournoiMatch $match = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $participant = null;

    #[ORM\Column(length: 20)]
    private string $placement = 'first';

    #[ORM\Column(type: 'integer')]
    private int $points = 3;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatch(): ?TournoiMatch
    {
        return $this->match;
    }

    public function setMatch(?TournoiMatch $match): static
    {
        $this->match = $match;

        return $this;
    }

    public function getParticipant(): ?User
    {
        return $this->participant;
    }

    public function setParticipant(?User $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getPlacement(): string
    {
        return $this->placement;
    }

    public function setPlacement(string $placement): static
    {
        $this->placement = $placement;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

