<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218134920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix produit table: replace categorie varchar with categorie_id foreign key';
    }

    public function up(Schema $schema): void
    {
        // Drop the old categorie varchar column if it exists
        $this->addSql('ALTER TABLE produit DROP COLUMN IF EXISTS categorie');
        
        // Add the proper categorie_id foreign key column
        $this->addSql('ALTER TABLE produit ADD COLUMN IF NOT EXISTS categorie_id INT DEFAULT NULL');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_CATEGORIE FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert: drop the foreign key and categorie_id column, add back categorie varchar
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_CATEGORIE');
        $this->addSql('ALTER TABLE produit DROP COLUMN IF EXISTS categorie_id');
        $this->addSql('ALTER TABLE produit ADD COLUMN categorie VARCHAR(255) DEFAULT NULL');
    }
}
