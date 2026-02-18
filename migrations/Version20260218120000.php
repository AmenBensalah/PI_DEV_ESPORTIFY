<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing columns to equipe table
 */
final class Version20260218120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to equipe table (max_members, is_private, is_active, manager_id)';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns to equipe table
        $this->addSql('ALTER TABLE equipe ADD COLUMN IF NOT EXISTS max_members INT NOT NULL DEFAULT 5');
        $this->addSql('ALTER TABLE equipe ADD COLUMN IF NOT EXISTS is_private TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE equipe ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE equipe ADD COLUMN IF NOT EXISTS manager_id INT DEFAULT NULL');
        
        // Add foreign key constraint if it doesn't exist
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2C536C92783E3463 FOREIGN KEY (manager_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns and constraint
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2C536C92783E3463');
        $this->addSql('ALTER TABLE equipe DROP COLUMN IF EXISTS max_members');
        $this->addSql('ALTER TABLE equipe DROP COLUMN IF EXISTS is_private');
        $this->addSql('ALTER TABLE equipe DROP COLUMN IF EXISTS is_active');
        $this->addSql('ALTER TABLE equipe DROP COLUMN IF EXISTS manager_id');
    }
}
