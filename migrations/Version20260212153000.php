<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_messages table for messenger feature';
    }

    public function up(Schema $schema): void
    {
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages'"
        );
        if ($tableExists === 0) {
            $this->addSql("CREATE TABLE chat_messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, body LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL DEFAULT 'text', call_url VARCHAR(255) DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, INDEX idx_chat_recipient_read (recipient_id, is_read), INDEX idx_chat_sender_recipient_created (sender_id, recipient_id, created_at), INDEX idx_chat_recipient_sender_created (recipient_id, sender_id, created_at), INDEX IDX_chat_sender (sender_id), INDEX IDX_chat_recipient (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        $senderFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages' AND CONSTRAINT_NAME = 'FK_chat_sender'"
        );
        if ($senderFkExists === 0) {
            $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_chat_sender FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        }

        $recipientFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages' AND CONSTRAINT_NAME = 'FK_chat_recipient'"
        );
        if ($recipientFkExists === 0) {
            $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_chat_recipient FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat_messages');
    }
}
