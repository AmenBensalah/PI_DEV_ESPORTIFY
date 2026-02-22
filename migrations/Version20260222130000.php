<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suspension metadata to equipe for admin ban workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe ADD suspension_reason LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE equipe ADD suspended_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE equipe ADD suspension_duration_days INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe DROP suspension_reason');
        $this->addSql('ALTER TABLE equipe DROP suspended_until');
        $this->addSql('ALTER TABLE equipe DROP suspension_duration_days');
    }
}

