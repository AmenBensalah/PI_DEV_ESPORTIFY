<?php

namespace App\Entity;

use App\Repository\FeedAiAnalysisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedAiAnalysisRepository::class)]
#[ORM\Table(name: 'feed_ai_analysis')]
#[ORM\UniqueConstraint(name: 'uniq_feed_ai_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_feed_ai_action', columns: ['auto_action'])]
#[ORM\Index(name: 'idx_feed_ai_risk', columns: ['toxicity_score', 'spam_score', 'hate_speech_score'])]
class FeedAiAnalysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $entityType = 'post';

    #[ORM\Column]
    private int $entityId = 0;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sourceHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summaryShort = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summaryLong = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $hashtags = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $toxicityScore = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $hateSpeechScore = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $spamScore = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $duplicateScore = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $mediaRiskScore = 0;

    #[ORM\Column(length: 20, options: ['default' => 'allow'])]
    private string $autoAction = 'allow';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $flags = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $translations = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getSourceHash(): ?string
    {
        return $this->sourceHash;
    }

    public function setSourceHash(?string $sourceHash): static
    {
        $this->sourceHash = $sourceHash;

        return $this;
    }

    public function getSummaryShort(): ?string
    {
        return $this->summaryShort;
    }

    public function setSummaryShort(?string $summaryShort): static
    {
        $this->summaryShort = $summaryShort;

        return $this;
    }

    public function getSummaryLong(): ?string
    {
        return $this->summaryLong;
    }

    public function setSummaryLong(?string $summaryLong): static
    {
        $this->summaryLong = $summaryLong;

        return $this;
    }

    public function getHashtags(): ?array
    {
        return $this->hashtags;
    }

    public function setHashtags(?array $hashtags): static
    {
        $this->hashtags = $hashtags;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getToxicityScore(): int
    {
        return $this->toxicityScore;
    }

    public function setToxicityScore(int $toxicityScore): static
    {
        $this->toxicityScore = max(0, min(100, $toxicityScore));

        return $this;
    }

    public function getHateSpeechScore(): int
    {
        return $this->hateSpeechScore;
    }

    public function setHateSpeechScore(int $hateSpeechScore): static
    {
        $this->hateSpeechScore = max(0, min(100, $hateSpeechScore));

        return $this;
    }

    public function getSpamScore(): int
    {
        return $this->spamScore;
    }

    public function setSpamScore(int $spamScore): static
    {
        $this->spamScore = max(0, min(100, $spamScore));

        return $this;
    }

    public function getDuplicateScore(): int
    {
        return $this->duplicateScore;
    }

    public function setDuplicateScore(int $duplicateScore): static
    {
        $this->duplicateScore = max(0, min(100, $duplicateScore));

        return $this;
    }

    public function getMediaRiskScore(): int
    {
        return $this->mediaRiskScore;
    }

    public function setMediaRiskScore(int $mediaRiskScore): static
    {
        $this->mediaRiskScore = max(0, min(100, $mediaRiskScore));

        return $this;
    }

    public function getAutoAction(): string
    {
        return $this->autoAction;
    }

    public function setAutoAction(string $autoAction): static
    {
        $this->autoAction = $autoAction;

        return $this;
    }

    public function getFlags(): ?array
    {
        return $this->flags;
    }

    public function setFlags(?array $flags): static
    {
        $this->flags = $flags;

        return $this;
    }

    public function getTranslations(): ?array
    {
        return $this->translations;
    }

    public function setTranslations(?array $translations): static
    {
        $this->translations = $translations;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
