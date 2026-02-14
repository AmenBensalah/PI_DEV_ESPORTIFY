<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team_reports table and equipe.is_active soft delete flag';
    }

    public function up(Schema $schema): void
    {
        $columnExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipe' AND COLUMN_NAME = 'is_active'"
        );
        if ($columnExists === 0) {
            $this->addSql('ALTER TABLE equipe ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        }

        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_reports'"
        );
        if ($tableExists === 0) {
            $this->addSql("CREATE TABLE team_reports (id INT AUTO_INCREMENT NOT NULL, equipe_id INT NOT NULL, reporter_id INT NOT NULL, reason LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_team_reports_team_created (equipe_id, created_at), INDEX idx_team_reports_reporter (reporter_id), INDEX IDX_TEAM_REPORT_EQUIPE (equipe_id), INDEX IDX_TEAM_REPORT_REPORTER (reporter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        $equipeFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_reports' AND CONSTRAINT_NAME = 'FK_TEAM_REPORT_EQUIPE'"
        );
        if ($equipeFkExists === 0) {
            $this->addSql('ALTER TABLE team_reports ADD CONSTRAINT FK_TEAM_REPORT_EQUIPE FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        }

        $reporterFkExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_reports' AND CONSTRAINT_NAME = 'FK_TEAM_REPORT_REPORTER'"
        );
        if ($reporterFkExists === 0) {
            $this->addSql('ALTER TABLE team_reports ADD CONSTRAINT FK_TEAM_REPORT_REPORTER FOREIGN KEY (reporter_id) REFERENCES user (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS team_reports');
        $this->addSql('ALTER TABLE equipe DROP COLUMN is_active');
    }
}
