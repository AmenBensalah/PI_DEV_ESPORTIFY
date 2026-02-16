<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215114000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manual home/away names on tournoi_match and make player_a_id nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi_match ADD home_name VARCHAR(255) DEFAULT NULL, ADD away_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tournoi_match CHANGE player_a_id player_a_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi_match DROP home_name, DROP away_name');
        $this->addSql('ALTER TABLE tournoi_match CHANGE player_a_id player_a_id INT NOT NULL');
    }
}
