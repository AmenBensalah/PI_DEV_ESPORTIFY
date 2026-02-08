<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participation_request table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE participation_request (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                tournoi_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                message LONGTEXT DEFAULT NULL,
                player_level VARCHAR(20) DEFAULT NULL,
                rules_accepted TINYINT(1) NOT NULL,
                applicant_name VARCHAR(255) DEFAULT NULL,
                applicant_email VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_PR_USER (user_id),
                INDEX IDX_PR_TOURNOI (tournoi_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PR_USER FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PR_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE participation_request');
    }
}
