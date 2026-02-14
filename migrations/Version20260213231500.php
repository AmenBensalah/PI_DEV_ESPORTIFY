<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213231500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_message table for team chat functionality';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            equipe_id INT NOT NULL,
            message LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            INDEX IDX_FAB3FC16A76ED395 (user_id),
            INDEX IDX_FAB3FC166D861B89 (equipe_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC166D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16A76ED395');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC166D861B89');
        $this->addSql('DROP TABLE chat_message');
    }
}
