<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the old roles column and keep only role enum
 */
final class Version20260204160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy roles column, keep only role enum column';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('user')) {
            $table = $schema->getTable('user');
            if ($table->hasColumn('roles')) {
                $this->addSql('ALTER TABLE user DROP COLUMN roles');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user')) {
            $table = $schema->getTable('user');
            if (!$table->hasColumn('roles')) {
                $this->addSql('ALTER TABLE user ADD COLUMN roles JSON NOT NULL');
            }
        }
    }
}
