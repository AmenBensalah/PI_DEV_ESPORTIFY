<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add face_descriptor column to user table for Face ID login support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS face_descriptor JSON DEFAULT NULL COMMENT "(DC2Type:json)"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS face_descriptor');
    }
}
