<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add applicant_name and applicant_email to participation_request';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('participation_request')) {
            return;
        }

        $table = $schema->getTable('participation_request');
        if (!$table->hasColumn('applicant_name')) {
            $this->addSql('ALTER TABLE participation_request ADD COLUMN applicant_name VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('applicant_email')) {
            $this->addSql('ALTER TABLE participation_request ADD COLUMN applicant_email VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('participation_request')) {
            return;
        }

        $table = $schema->getTable('participation_request');
        if ($table->hasColumn('applicant_name')) {
            $this->addSql('ALTER TABLE participation_request DROP COLUMN applicant_name');
        }
        if ($table->hasColumn('applicant_email')) {
            $this->addSql('ALTER TABLE participation_request DROP COLUMN applicant_email');
        }
    }
}
