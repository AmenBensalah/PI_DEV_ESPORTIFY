<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI block tracking fields on commande for admin monitoring';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande ADD ai_blocked TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE commande ADD ai_risk_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD ai_block_reason VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD ai_blocked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commande ADD ai_block_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_COMMANDE_AI_BLOCKED ON commande (ai_blocked)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_COMMANDE_AI_BLOCKED ON commande');
        $this->addSql('ALTER TABLE commande DROP ai_blocked');
        $this->addSql('ALTER TABLE commande DROP ai_risk_score');
        $this->addSql('ALTER TABLE commande DROP ai_block_reason');
        $this->addSql('ALTER TABLE commande DROP ai_blocked_at');
        $this->addSql('ALTER TABLE commande DROP ai_block_until');
    }
}

