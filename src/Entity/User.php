<?php

namespace App\Entity;
use App\Enum\Role;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\ManagerRequest;
use App\Entity\Commentaire;
use App\Entity\Like;
use App\Entity\EventParticipant;
use App\Entity\Post;
use App\Entity\Commande;
use App\Entity\Recommendation;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $faceDescriptor = null;

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

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
    private Collection $posts;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $likes;

    /**
     * @var Collection<int, EventParticipant>
     */
    #[ORM\OneToMany(targetEntity: EventParticipant::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $eventParticipations;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'savedBy')]

    #[ORM\JoinTable(name: 'user_saved_posts')]
    private Collection $savedPosts;

    /**
     * @var Collection<int, Recommendation>
     */
    #[ORM\OneToMany(targetEntity: Recommendation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $recommendations;

    // TEMPORAIRE : Relation Commandes désactivée
    // /**
    //  * @var Collection<int, Commande>
    //  */
    // #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
    // private Collection $commandes;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->managerRequests = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->savedPosts = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
        // $this->commandes = new ArrayCollection();
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

    public function setNom(?string $nom): static
    {
        $this->nom = $nom ?? '';

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

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return list<float>|null
     */
    public function getFaceDescriptor(): ?array
    {
        return $this->faceDescriptor;
    }

    /**
     * @param list<float>|null $faceDescriptor
     */
    public function setFaceDescriptor(?array $faceDescriptor): static
    {
        $this->faceDescriptor = $faceDescriptor;

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

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setAuthor($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getAuthor() === $this) {
                $commentaire->setAuthor(null);
            }
        }

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
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipant>
     */
    public function getEventParticipations(): Collection
    {
        return $this->eventParticipations;
    }

    public function addEventParticipation(EventParticipant $eventParticipant): static
    {
        if (!$this->eventParticipations->contains($eventParticipant)) {
            $this->eventParticipations->add($eventParticipant);
            $eventParticipant->setUser($this);
        }

        return $this;
    }

    public function removeEventParticipation(EventParticipant $eventParticipant): static
    {
        if ($this->eventParticipations->removeElement($eventParticipant)) {
            if ($eventParticipant->getUser() === $this) {
                $eventParticipant->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getSavedPosts(): Collection
    {
        return $this->savedPosts;
    }

    public function addSavedPost(Post $post): static
    {
        if (!$this->savedPosts->contains($post)) {
            $this->savedPosts->add($post);
        }

        return $this;
    }

    public function removeSavedPost(Post $post): static
    {
        $this->savedPosts->removeElement($post);

        return $this;
    }

    public function hasSavedPost(Post $post): bool
    {
        return $this->savedPosts->contains($post);
    }

    /**
     * @return Collection<int, Recommendation>
     */
    public function getRecommendations(): Collection
    {
        return $this->recommendations;
    }

    public function addRecommendation(Recommendation $recommendation): static
    {
        if (!$this->recommendations->contains($recommendation)) {
            $this->recommendations->add($recommendation);
            $recommendation->setUser($this);
        }

        return $this;
    }

    public function removeRecommendation(Recommendation $recommendation): static
    {
        if ($this->recommendations->removeElement($recommendation)) {
            // set the owning side to null (unless already changed)
            if ($recommendation->getUser() === $this) {
                $recommendation->setUser(null);
            }
        }

        return $this;
    }

    // TEMPORAIRE : Méthodes Commandes désactivées
    // /**
    //  * @return Collection<int, Commande>
    //  */
    // public function getCommandes(): Collection
    // {
    //     return $this->commandes;
    // }

    // public function addCommande(Commande $commande): static
    // {
    //     if (!$this->commandes->contains($commande)) {
    //         $this->commandes->add($commande);
    //         $commande->setUser($this);
    //     }

    //     return $this;
    // }

    // public function removeCommande(Commande $commande): static
    // {
    //     if ($this->commandes->removeElement($commande)) {
    //         // set the owning side to null (unless already changed)
    //         if ($commande->getUser() === $this) {
    //             $commande->setUser(null);
    //         }
    //     }

    //     return $this;
    // }
}
