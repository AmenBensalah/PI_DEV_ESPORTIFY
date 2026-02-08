<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create User table and add creator relationship to tournoi table';
    }

    public function up(Schema $schema): void
    {
        // Create user table (only if missing)
        if (!$schema->hasTable('user')) {
            $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Add creator column to tournoi table
        if ($schema->hasTable('tournoi')) {
            $tournoi = $schema->getTable('tournoi');
            if (!$tournoi->hasColumn('creator_id')) {
                $this->addSql('ALTER TABLE tournoi ADD COLUMN creator_id INT NOT NULL AFTER id_tournoi');
            }
            if (!$tournoi->hasIndex('IDX_TOURNOI_CREATOR')) {
                $this->addSql('CREATE INDEX IDX_TOURNOI_CREATOR ON tournoi (creator_id)');
            }
            // Foreign keys are not introspected by name reliably; attempt to add only if column exists.
            if ($tournoi->hasColumn('creator_id')) {
                $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_TOURNOI_CREATOR FOREIGN KEY (creator_id) REFERENCES `user` (id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('tournoi')) {
            $tournoi = $schema->getTable('tournoi');
            $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_TOURNOI_CREATOR');
            if ($tournoi->hasIndex('IDX_TOURNOI_CREATOR')) {
                $this->addSql('DROP INDEX IDX_TOURNOI_CREATOR ON tournoi');
            }
            if ($tournoi->hasColumn('creator_id')) {
                $this->addSql('ALTER TABLE tournoi DROP COLUMN creator_id');
            }
        }
        if ($schema->hasTable('user')) {
            $this->addSql('DROP TABLE `user`');
        }
    }
}
