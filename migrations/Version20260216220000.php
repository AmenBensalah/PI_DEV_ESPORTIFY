<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add face descriptor vector to user table for Face ID login';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD face_descriptor JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP face_descriptor');
    }
}
