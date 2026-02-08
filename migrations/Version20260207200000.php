<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id foreign key to candidature table for ownership';
    }

    public function up(Schema $schema): void
    {
        // Check if the constraint already exists
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_CANDIDATURE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_CANDIDATURE_USER');
    }
}
