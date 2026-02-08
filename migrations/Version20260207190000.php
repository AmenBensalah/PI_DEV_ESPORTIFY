<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categorie and produit tables and add owner relations to produit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, owner_user_id INT DEFAULT NULL, owner_equipe_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, stock INT NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_PRODUIT_CATEGORIE (categorie_id), INDEX IDX_PRODUIT_USER (owner_user_id), INDEX IDX_PRODUIT_EQUIPE (owner_equipe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_CATEGORIE FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_USER FOREIGN KEY (owner_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_PRODUIT_EQUIPE FOREIGN KEY (owner_equipe_id) REFERENCES equipe (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_CATEGORIE');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_USER');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_PRODUIT_EQUIPE');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE categorie');
    }
}
