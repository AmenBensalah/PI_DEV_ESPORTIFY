<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feed_ai_analysis table for smart feed moderation, summaries and recommendations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE feed_ai_analysis (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(20) NOT NULL, entity_id INT NOT NULL, source_hash VARCHAR(64) DEFAULT NULL, summary_short LONGTEXT DEFAULT NULL, summary_long LONGTEXT DEFAULT NULL, hashtags JSON DEFAULT NULL, category VARCHAR(50) DEFAULT NULL, toxicity_score INT NOT NULL DEFAULT 0, hate_speech_score INT NOT NULL DEFAULT 0, spam_score INT NOT NULL DEFAULT 0, duplicate_score INT NOT NULL DEFAULT 0, media_risk_score INT NOT NULL DEFAULT 0, auto_action VARCHAR(20) NOT NULL DEFAULT \'allow\', flags JSON DEFAULT NULL, translations JSON DEFAULT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_feed_ai_entity (entity_type, entity_id), INDEX idx_feed_ai_action (auto_action), INDEX idx_feed_ai_risk (toxicity_score, spam_score, hate_speech_score), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feed_ai_analysis');
    }
}
