<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participation join table for tournoi participants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE participation (
                tournoi_id INT NOT NULL,
                user_id INT NOT NULL,
                INDEX IDX_PARTICIPATION_TOURNOI (tournoi_id),
                INDEX IDX_PARTICIPATION_USER (user_id),
                PRIMARY KEY(tournoi_id, user_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_PARTICIPATION_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_PARTICIPATION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE participation');
    }
}
