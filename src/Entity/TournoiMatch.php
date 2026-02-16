<?php

namespace App\Entity;

use App\Repository\TournoiMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournoiMatchRepository::class)]
#[ORM\Table(name: 'tournoi_match')]
class TournoiMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournoi::class)]
    #[ORM\JoinColumn(name: 'tournoi_id', referencedColumnName: 'id_tournoi', nullable: false, onDelete: 'CASCADE')]
    private ?Tournoi $tournoi = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player_a_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $playerA = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player_b_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $playerB = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $awayName = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $scheduledAt = null;

    #[ORM\Column(length: 30)]
    private string $status = 'planned';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $scoreA = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $scoreB = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, TournoiMatchParticipantResult>
     */
    #[ORM\OneToMany(mappedBy: 'match', targetEntity: TournoiMatchParticipantResult::class, orphanRemoval: true)]
    private Collection $participantResults;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->participantResults = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlayerA(): ?User
    {
        return $this->playerA;
    }

    public function setPlayerA(?User $playerA): static
    {
        $this->playerA = $playerA;

        return $this;
    }

    public function getPlayerB(): ?User
    {
        return $this->playerB;
    }

    public function setPlayerB(?User $playerB): static
    {
        $this->playerB = $playerB;

        return $this;
    }

    public function getScheduledAt(): ?\DateTime
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTime $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getHomeName(): ?string
    {
        return $this->homeName;
    }

    public function setHomeName(?string $homeName): static
    {
        $this->homeName = $homeName;

        return $this;
    }

    public function getAwayName(): ?string
    {
        return $this->awayName;
    }

    public function setAwayName(?string $awayName): static
    {
        $this->awayName = $awayName;

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

    public function getScoreA(): ?int
    {
        return $this->scoreA;
    }

    public function setScoreA(?int $scoreA): static
    {
        $this->scoreA = $scoreA;

        return $this;
    }

    public function getScoreB(): ?int
    {
        return $this->scoreB;
    }

    public function setScoreB(?int $scoreB): static
    {
        $this->scoreB = $scoreB;

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

    /**
     * @return Collection<int, TournoiMatchParticipantResult>
     */
    public function getParticipantResults(): Collection
    {
        return $this->participantResults;
    }

    public function addParticipantResult(TournoiMatchParticipantResult $participantResult): static
    {
        if (!$this->participantResults->contains($participantResult)) {
            $this->participantResults->add($participantResult);
            $participantResult->setMatch($this);
        }

        return $this;
    }

    public function removeParticipantResult(TournoiMatchParticipantResult $participantResult): static
    {
        if ($this->participantResults->removeElement($participantResult)) {
            if ($participantResult->getMatch() === $this) {
                $participantResult->setMatch(null);
            }
        }

        return $this;
    }
}
