<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop region column from equipe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP COLUMN region');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe ADD region VARCHAR(255) DEFAULT NULL');
    }
}
