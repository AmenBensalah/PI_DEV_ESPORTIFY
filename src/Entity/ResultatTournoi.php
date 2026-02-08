<?php

namespace App\Entity;

use App\Repository\ResultatTournoiRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatTournoiRepository::class)]
class ResultatTournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_resultat = null;

    #[ORM\OneToOne(targetEntity: Tournoi::class, inversedBy: 'resultat')]
    #[ORM\JoinColumn(name: 'id_tournoi', referencedColumnName: 'id_tournoi', nullable: false)]
    private ?Tournoi $tournoi = null;

    #[ORM\Column(type: 'integer')]
    private ?int $rank = null;

    #[ORM\Column(type: 'float')]
    private ?float $score = null;

    public function getIdResultat(): ?int
    {
        return $this->id_resultat;
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

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(int $rank): static
    {
        $this->rank = $rank;
        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;
        return $this;
    }
}
