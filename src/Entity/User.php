<?php

namespace App\Entity;
use App\Enum\Role;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\ManagerRequest;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: "Cet email est deja utilise.")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(enumType: Role::class)]
    private Role $role;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pseudo = null;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $candidatures;

    /**
     * @var Collection<int, ManagerRequest>
     */
    #[ORM\OneToMany(targetEntity: ManagerRequest::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $managerRequests;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->managerRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return [$this->role->value];
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        // This method is required by UserInterface
        // In our case, we use the 'role' enum field instead
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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

    public function getRole(): Role
    {
    return $this->role;
    }

    public function setRole(Role $role): self
    {
    $this->role = $role;
    return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;

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
            $candidature->setUser($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            // set the owning side to null (unless already changed)
            if ($candidature->getUser() === $this) {
                $candidature->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ManagerRequest>
     */
    public function getManagerRequests(): Collection
    {
        return $this->managerRequests;
    }

    public function addManagerRequest(ManagerRequest $managerRequest): static
    {
        if (!$this->managerRequests->contains($managerRequest)) {
            $this->managerRequests->add($managerRequest);
            $managerRequest->setUser($this);
        }

        return $this;
    }

    public function removeManagerRequest(ManagerRequest $managerRequest): static
    {
        if ($this->managerRequests->removeElement($managerRequest)) {
            // set the owning side to null (unless already changed)
            if ($managerRequest->getUser() === $this) {
                $managerRequest->setUser(null);
            }
        }

        return $this;
    }
}
