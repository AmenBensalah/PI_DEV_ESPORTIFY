<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: Payment::class, orphanRemoval: true)]
    private Collection $payments;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, orphanRemoval: true)]
    private Collection $lignesCommande;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = 'draft';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gouvernerat = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresseDetail = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantite = null;

    #[ORM\Column(nullable: true)]
    private ?int $numtel = null;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $identityKey = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $aiBlocked = false;

    #[ORM\Column(nullable: true)]
    private ?float $aiRiskScore = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $aiBlockReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $aiBlockedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $aiBlockUntil = null;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->lignesCommande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getGouvernerat(): ?string
    {
        return $this->gouvernerat;
    }

    public function setGouvernerat(?string $gouvernerat): static
    {
        $this->gouvernerat = $gouvernerat;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getAdresseDetail(): ?string
    {
        return $this->adresseDetail;
    }

    public function setAdresseDetail(?string $adresseDetail): static
    {
        $this->adresseDetail = $adresseDetail;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getNumtel(): ?int
    {
        return $this->numtel;
    }

    public function setNumtel(int $numtel): static
    {
        $this->numtel = $numtel;

        return $this;
    }

    public function getIdentityKey(): ?string
    {
        return $this->identityKey;
    }

    public function setIdentityKey(?string $identityKey): static
    {
        $this->identityKey = $identityKey;

        return $this;
    }

    public function isAiBlocked(): bool
    {
        return $this->aiBlocked;
    }

    public function setAiBlocked(bool $aiBlocked): static
    {
        $this->aiBlocked = $aiBlocked;

        return $this;
    }

    public function getAiRiskScore(): ?float
    {
        return $this->aiRiskScore;
    }

    public function setAiRiskScore(?float $aiRiskScore): static
    {
        $this->aiRiskScore = $aiRiskScore;

        return $this;
    }

    public function getAiBlockReason(): ?string
    {
        return $this->aiBlockReason;
    }

    public function setAiBlockReason(?string $aiBlockReason): static
    {
        $this->aiBlockReason = $aiBlockReason;

        return $this;
    }

    public function getAiBlockedAt(): ?\DateTimeImmutable
    {
        return $this->aiBlockedAt;
    }

    public function setAiBlockedAt(?\DateTimeImmutable $aiBlockedAt): static
    {
        $this->aiBlockedAt = $aiBlockedAt;

        return $this;
    }

    public function getAiBlockUntil(): ?\DateTimeImmutable
    {
        return $this->aiBlockUntil;
    }

    public function setAiBlockUntil(?\DateTimeImmutable $aiBlockUntil): static
    {
        $this->aiBlockUntil = $aiBlockUntil;

        return $this;
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

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setCommande($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getCommande() === $this) {
                $payment->setCommande(null);
            }
        }

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignesCommande(): Collection
    {
        return $this->lignesCommande;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): static
    {
        if (!$this->lignesCommande->contains($ligneCommande)) {
            $this->lignesCommande->add($ligneCommande);
            $ligneCommande->setCommande($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): static
    {
        if ($this->lignesCommande->removeElement($ligneCommande)) {
            if ($ligneCommande->getCommande() === $this) {
                $ligneCommande->setCommande(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function syncDerivedFields(): void
    {
        $nom = mb_strtolower(trim((string) ($this->nom ?? '')));
        $prenom = mb_strtolower(trim((string) ($this->prenom ?? '')));
        $numtel = (int) ($this->numtel ?? 0);
        $this->identityKey = $nom . '|' . $prenom . '|' . $numtel;

        if ((string) $this->statut === 'paid') {
            $this->aiBlocked = false;
            $this->aiRiskScore = null;
            $this->aiBlockReason = null;
            $this->aiBlockedAt = null;
            $this->aiBlockUntil = null;
        }
    }
}
