<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add region column to equipe (restore if previously dropped)';
    }

    public function up(Schema $schema): void
    {
        // Add the region column if it does not exist
        $this->addSql('ALTER TABLE equipe ADD region VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP COLUMN region');
    }
}
