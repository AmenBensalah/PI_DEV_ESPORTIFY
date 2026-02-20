<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link commande to user and add enriched identity_key for abuse detection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD COLUMN IF NOT EXISTS identity_key VARCHAR(190) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_COMMANDE_USER ON commande (user_id)');
        $this->addSql('CREATE INDEX IDX_COMMANDE_IDENTITY_KEY ON commande (identity_key)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_COMMANDE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_COMMANDE_USER');
        $this->addSql('DROP INDEX IDX_COMMANDE_USER ON commande');
        $this->addSql('DROP INDEX IDX_COMMANDE_IDENTITY_KEY ON commande');
        $this->addSql('ALTER TABLE commande DROP COLUMN IF EXISTS user_id');
        $this->addSql('ALTER TABLE commande DROP COLUMN IF EXISTS identity_key');
    }
}
