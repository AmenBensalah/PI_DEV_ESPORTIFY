<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212012000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create post_media table for multiple media attachments on posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE post_media (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, type VARCHAR(20) NOT NULL, path VARCHAR(255) NOT NULL, position INT NOT NULL, INDEX IDX_post_media_post (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_media ADD CONSTRAINT FK_post_media_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE post_media');
    }
}

