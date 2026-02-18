<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218134543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to produit table (statut, owner_user_id, owner_equipe_id)';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns to produit table
        $this->addSql('ALTER TABLE produit ADD COLUMN IF NOT EXISTS statut VARCHAR(50) NOT NULL DEFAULT "disponible"');
        $this->addSql('ALTER TABLE produit ADD COLUMN IF NOT EXISTS owner_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD COLUMN IF NOT EXISTS owner_equipe_id INT DEFAULT NULL');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_USER FOREIGN KEY (owner_user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_EQUIPE FOREIGN KEY (owner_equipe_id) REFERENCES equipe (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the foreign keys and columns
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_USER');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_EQUIPE');
        $this->addSql('ALTER TABLE produit DROP COLUMN IF EXISTS statut');
        $this->addSql('ALTER TABLE produit DROP COLUMN IF EXISTS owner_user_id');
        $this->addSql('ALTER TABLE produit DROP COLUMN IF EXISTS owner_equipe_id');
    }
}
