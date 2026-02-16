<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tournoi_match_participant_result for battle royale placements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tournoi_match_participant_result (
            id INT AUTO_INCREMENT NOT NULL,
            match_id INT NOT NULL,
            participant_id INT NOT NULL,
            placement VARCHAR(20) NOT NULL,
            points INT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TMPR_MATCH (match_id),
            INDEX IDX_TMPR_PARTICIPANT (participant_id),
            UNIQUE INDEX uniq_match_participant (match_id, participant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournoi_match_participant_result ADD CONSTRAINT FK_TMPR_MATCH FOREIGN KEY (match_id) REFERENCES tournoi_match (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi_match_participant_result ADD CONSTRAINT FK_TMPR_PARTICIPANT FOREIGN KEY (participant_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi_match_participant_result DROP FOREIGN KEY FK_TMPR_MATCH');
        $this->addSql('ALTER TABLE tournoi_match_participant_result DROP FOREIGN KEY FK_TMPR_PARTICIPANT');
        $this->addSql('DROP TABLE tournoi_match_participant_result');
    }
}

