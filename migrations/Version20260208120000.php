<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to participation_request.tournoi_id foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_PR_TOURNOI');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PR_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_PR_TOURNOI');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PR_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi)');
    }
}
