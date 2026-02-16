<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournoi_match table for manual tournament match CRUD';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tournoi_match (
            id INT AUTO_INCREMENT NOT NULL,
            tournoi_id INT NOT NULL,
            player_a_id INT NOT NULL,
            player_b_id INT DEFAULT NULL,
            scheduled_at DATETIME DEFAULT NULL,
            status VARCHAR(30) NOT NULL,
            score_a INT DEFAULT NULL,
            score_b INT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TOURNOI_MATCH_TOURNOI (tournoi_id),
            INDEX IDX_TOURNOI_MATCH_PLAYER_A (player_a_id),
            INDEX IDX_TOURNOI_MATCH_PLAYER_B (player_b_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE tournoi_match ADD CONSTRAINT FK_TOURNOI_MATCH_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_match ADD CONSTRAINT FK_TOURNOI_MATCH_PLAYER_A FOREIGN KEY (player_a_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_match ADD CONSTRAINT FK_TOURNOI_MATCH_PLAYER_B FOREIGN KEY (player_b_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi_match DROP FOREIGN KEY FK_TOURNOI_MATCH_TOURNOI');
        $this->addSql('ALTER TABLE tournoi_match DROP FOREIGN KEY FK_TOURNOI_MATCH_PLAYER_A');
        $this->addSql('ALTER TABLE tournoi_match DROP FOREIGN KEY FK_TOURNOI_MATCH_PLAYER_B');
        $this->addSql('DROP TABLE tournoi_match');
    }
}
