<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create posts and announcements tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT DEFAULT NULL, media_type VARCHAR(255) NOT NULL, media_filename VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE announcements (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, content LONGTEXT DEFAULT NULL, tag VARCHAR(60) NOT NULL, link VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE announcements');
        $this->addSql('DROP TABLE posts');
    }
}
