<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_level and rules_accepted to participation_request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_request ADD COLUMN player_level VARCHAR(20) DEFAULT NULL, ADD COLUMN rules_accepted TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_request DROP COLUMN player_level, DROP COLUMN rules_accepted');
    }
}
