<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attributes to tournoi and resultat_tournoi tables with one-to-one relationship';
    }

    public function up(Schema $schema): void
    {
        // Rename id to id_tournoi in tournoi table
        $this->addSql('ALTER TABLE tournoi CHANGE COLUMN id id_tournoi INT AUTO_INCREMENT NOT NULL');
        
        // Add new columns to tournoi table
        $this->addSql('ALTER TABLE tournoi ADD COLUMN name VARCHAR(255) NOT NULL, ADD COLUMN type_tournoi VARCHAR(50) NOT NULL, ADD COLUMN type_game VARCHAR(50) NOT NULL, ADD COLUMN game VARCHAR(255) NOT NULL, ADD COLUMN start_date DATETIME NOT NULL, ADD COLUMN end_date DATETIME NOT NULL, ADD COLUMN status VARCHAR(50) NOT NULL, ADD COLUMN prize_won DOUBLE PRECISION NOT NULL');

        // Rename id to id_resultat in resultat_tournoi table
        $this->addSql('ALTER TABLE resultat_tournoi CHANGE COLUMN id id_resultat INT AUTO_INCREMENT NOT NULL');
        
        // Drop old id_tournoi column if it exists
        if ($schema->getTable('resultat_tournoi')->hasColumn('id_tournoi')) {
            $this->addSql('ALTER TABLE resultat_tournoi DROP COLUMN id_tournoi');
        }
        
        // Add rank and score columns to resultat_tournoi if they don't exist
        $table = $schema->getTable('resultat_tournoi');
        if (!$table->hasColumn('rank')) {
            $this->addSql('ALTER TABLE resultat_tournoi ADD COLUMN rank INT NOT NULL');
        }
        if (!$table->hasColumn('score')) {
            $this->addSql('ALTER TABLE resultat_tournoi ADD COLUMN score DOUBLE PRECISION NOT NULL');
        }
        
        // Add id_tournoi column with foreign key
        $this->addSql('ALTER TABLE resultat_tournoi ADD COLUMN id_tournoi INT NOT NULL, ADD UNIQUE INDEX UNIQ_EC3E38FF7E0950D9 (id_tournoi)');
        $this->addSql('ALTER TABLE resultat_tournoi ADD CONSTRAINT FK_EC3E38FF7E0950D9 FOREIGN KEY (id_tournoi) REFERENCES tournoi (id_tournoi)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resultat_tournoi DROP FOREIGN KEY FK_EC3E38FF7E0950D9');
        $this->addSql('ALTER TABLE resultat_tournoi DROP INDEX UNIQ_EC3E38FF7E0950D9');
        $this->addSql('ALTER TABLE resultat_tournoi DROP COLUMN id_tournoi, DROP COLUMN rank, DROP COLUMN score');
        $this->addSql('ALTER TABLE resultat_tournoi CHANGE COLUMN id_resultat id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE tournoi DROP COLUMN name, DROP COLUMN type_tournoi, DROP COLUMN type_game, DROP COLUMN game, DROP COLUMN start_date, DROP COLUMN end_date, DROP COLUMN status, DROP COLUMN prize_won');
        $this->addSql('ALTER TABLE tournoi CHANGE COLUMN id_tournoi id INT AUTO_INCREMENT NOT NULL');
    }
}
