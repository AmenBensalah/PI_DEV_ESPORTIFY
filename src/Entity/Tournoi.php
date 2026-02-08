<?php

namespace App\Entity;

use App\Repository\TournoiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_tournoi = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $type_tournoi = null;

    #[ORM\Column(length: 50)]
    private ?string $type_game = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tournois')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column(length: 255)]
    private ?string $game = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $endDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'float')]
    private ?float $prize_won = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxPlaces = null;

    #[ORM\OneToOne(targetEntity: ResultatTournoi::class, mappedBy: 'tournoi', cascade: ['persist', 'remove'])]
    private ?ResultatTournoi $resultat = null;

    #[ORM\ManyToMany(targetEntity: \App\Entity\User::class, inversedBy: 'participatedTournois')]
    #[ORM\JoinTable(name: 'participation', joinColumns: [new ORM\JoinColumn(name: 'tournoi_id', referencedColumnName: 'id_tournoi')], inverseJoinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')])]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getIdTournoi(): ?int
    {
        return $this->id_tournoi;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getTypeTournoi(): ?string
    {
        return $this->type_tournoi;
    }

    public function setTypeTournoi(string $type_tournoi): static
    {
        $this->type_tournoi = $type_tournoi;
        return $this;
    }

    public function getTypeGame(): ?string
    {
        return $this->type_game;
    }

    public function setTypeGame(string $type_game): static
    {
        $this->type_game = $type_game;
        return $this;
    }

    public function getGame(): ?string
    {
        return $this->game;
    }

    public function setGame(string $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPrizeWon(): ?float
    {
        return $this->prize_won;
    }

    public function setPrizeWon(float $prize_won): static
    {
        $this->prize_won = $prize_won;
        return $this;
    }

    public function getMaxPlaces(): ?int
    {
        return $this->maxPlaces;
    }

    public function setMaxPlaces(?int $maxPlaces): static
    {
        $this->maxPlaces = $maxPlaces;
        return $this;
    }

    public function getRemainingPlaces(): ?int
    {
        if ($this->maxPlaces === null) {
            return null; // unlimited
        }

        $count = $this->participants->count();
        $remaining = $this->maxPlaces - $count;
        return $remaining >= 0 ? $remaining : 0;
    }

    public function getCurrentStatus(): string
    {
        $now = new DateTime();
        if ($this->startDate > $now) {
            return 'planned';
        }
        if ($this->endDate < $now) {
            return 'finished';
        }
        return 'ongoing';
    }

    public function getResultat(): ?ResultatTournoi
    {
        return $this->resultat;
    }

    public function setResultat(?ResultatTournoi $resultat): static
    {
        $this->resultat = $resultat;
        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(\App\Entity\User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }

        return $this;
    }

    public function removeParticipant(\App\Entity\User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }
}
