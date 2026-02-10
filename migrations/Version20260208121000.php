<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add media fields to announcements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE announcements ADD media_type VARCHAR(255) NOT NULL DEFAULT 'text', ADD media_filename VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcements DROP media_type, DROP media_filename');
    }
}
