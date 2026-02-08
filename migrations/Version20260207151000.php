<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207151000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add max_places to tournoi table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi ADD COLUMN max_places INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi DROP COLUMN max_places');
    }
}
