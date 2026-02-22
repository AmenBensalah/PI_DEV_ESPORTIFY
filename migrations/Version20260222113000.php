<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI score columns for candidature reason text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ADD reason_ai_score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature ADD reason_ai_label VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP reason_ai_score');
        $this->addSql('ALTER TABLE candidature DROP reason_ai_label');
    }
}

