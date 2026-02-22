<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add region and disponibilite fields to candidature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ADD region VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature ADD disponibilite VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP region');
        $this->addSql('ALTER TABLE candidature DROP disponibilite');
    }
}

