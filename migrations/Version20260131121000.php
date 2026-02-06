<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tag and region columns to equipe';
    }

    public function up(Schema $schema): void
    {
        // adjust SQL to your platform if needed
        $this->addSql('ALTER TABLE equipe ADD tag VARCHAR(255) DEFAULT NULL, ADD region VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP COLUMN tag, DROP COLUMN region');
    }
}
