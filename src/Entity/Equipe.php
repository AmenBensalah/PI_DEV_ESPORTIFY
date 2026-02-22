<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomEquipe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(length: 50)]
    private ?string $classement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tag = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(options: ["default" => 5])]
    private ?int $maxMembers = 5;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isPrivate = false;

    #[ORM\Column(options: ["default" => true])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suspensionReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $suspendedUntil = null;

    #[ORM\Column(nullable: true)]
    private ?int $suspensionDurationDays = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $discordInviteUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $manager = null;

    /**
     * @var Collection<int, Recrutement>
     */
    #[ORM\OneToMany(targetEntity: Recrutement::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $recrutements;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $candidatures;

    public function __construct()
    {
        $this->recrutements = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEquipe(): ?string
    {
        return $this->nomEquipe;
    }

    public function setNomEquipe(string $nomEquipe): static
    {
        $this->nomEquipe = $nomEquipe;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getClassement(): ?string
    {
        return $this->classement;
    }

    public function setClassement(string $classement): static
    {
        $this->classement = $classement;

        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return Collection<int, Recrutement>
     */
    public function getRecrutements(): Collection
    {
        return $this->recrutements;
    }

    public function addRecrutement(Recrutement $recrutement): static
    {
        if (!$this->recrutements->contains($recrutement)) {
            $this->recrutements->add($recrutement);
            $recrutement->setEquipe($this);
        }

        return $this;
    }

    public function removeRecrutement(Recrutement $recrutement): static
    {
        if ($this->recrutements->removeElement($recrutement)) {
            // set the owning side to null (unless already changed)
            if ($recrutement->getEquipe() === $this) {
                $recrutement->setEquipe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setEquipe($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            // set the owning side to null (unless already changed)
            if ($candidature->getEquipe() === $this) {
                $candidature->setEquipe(null);
            }
        }

        return $this;
    }

    public function getMaxMembers(): ?int
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $maxMembers): static
    {
        $this->maxMembers = $maxMembers;
        return $this;
    }

    public function isIsPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;
        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSuspensionReason(): ?string
    {
        return $this->suspensionReason;
    }

    public function setSuspensionReason(?string $suspensionReason): static
    {
        $this->suspensionReason = $suspensionReason;
        return $this;
    }

    public function getSuspendedUntil(): ?\DateTimeImmutable
    {
        return $this->suspendedUntil;
    }

    public function setSuspendedUntil(?\DateTimeImmutable $suspendedUntil): static
    {
        $this->suspendedUntil = $suspendedUntil;
        return $this;
    }

    public function getSuspensionDurationDays(): ?int
    {
        return $this->suspensionDurationDays;
    }

    public function setSuspensionDurationDays(?int $suspensionDurationDays): static
    {
        $this->suspensionDurationDays = $suspensionDurationDays;
        return $this;
    }

    public function getDiscordInviteUrl(): ?string
    {
        return $this->discordInviteUrl;
    }

    public function setDiscordInviteUrl(?string $discordInviteUrl): static
    {
        $this->discordInviteUrl = $discordInviteUrl;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembres(): Collection
    {
        $membres = new ArrayCollection();
        
        // Add Manager/Creator if not already in candidatures (usually they are not)
        if ($this->manager) {
            $membres->add($this->manager);
        }

        foreach ($this->candidatures as $candidature) {
            if ($candidature->getStatut() === 'Accepté' && $candidature->getUser()) {
                if (!$membres->contains($candidature->getUser())) {
                    $membres->add($candidature->getUser());
                }
            }
        }

        return $membres;
    }
}
