<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add all missing Esportify tables to synchronize databases';
    }

    public function up(Schema $schema): void
    {
        // 1. Create posts table first (required for many relations)
        $this->addSql('CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT NOT NULL,
            content LONGTEXT,
            media_type VARCHAR(255) NOT NULL DEFAULT "text",
            media_filename VARCHAR(255),
            created_at DATETIME NOT NULL,
            image_path VARCHAR(255),
            video_url VARCHAR(255),
            is_event TINYINT(1) NOT NULL DEFAULT 0,
            event_title VARCHAR(180),
            event_date DATETIME,
            event_location VARCHAR(255),
            max_participants INT,
            author_id INT,
            PRIMARY KEY (id),
            KEY IDX_posts_author (author_id),
            CONSTRAINT FK_posts_author FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 2. Create announcements table
        $this->addSql('CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(180) NOT NULL,
            content LONGTEXT,
            tag VARCHAR(60) NOT NULL,
            link VARCHAR(255),
            created_at DATETIME NOT NULL,
            media_type VARCHAR(255) NOT NULL,
            media_filename VARCHAR(255),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 3. Create candidature table  
        $this->addSql('CREATE TABLE IF NOT EXISTS candidature (
            id INT AUTO_INCREMENT NOT NULL,
            niveau VARCHAR(50) NOT NULL,
            motivation LONGTEXT NOT NULL,
            statut VARCHAR(20) NOT NULL,
            date_candidature DATETIME NOT NULL,
            reason VARCHAR(255) NOT NULL,
            play_style VARCHAR(100) NOT NULL,
            equipe_id INT NOT NULL,
            user_id INT,
            PRIMARY KEY (id),
            KEY IDX_E33BD3B86D861B89 (equipe_id),
            CONSTRAINT FK_E33BD3B86D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 4. Create chat_message table
        $this->addSql('CREATE TABLE IF NOT EXISTS chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            message LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            is_read TINYINT(4) NOT NULL,
            user_id INT,
            equipe_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_FAB3FC16A76ED395 (user_id),
            KEY IDX_FAB3FC166D861B89 (equipe_id),
            CONSTRAINT FK_FAB3FC166D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id),
            CONSTRAINT FK_FAB3FC16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 5. Create chat_messages table for direct messaging
        $this->addSql('CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT NOT NULL,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            body LONGTEXT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT "text",
            call_url VARCHAR(255),
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_chat_sender (sender_id),
            KEY IDX_chat_recipient (recipient_id),
            CONSTRAINT FK_chat_sender FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_chat_recipient FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 6. Create commentaires table
        $this->addSql('CREATE TABLE IF NOT EXISTS commentaires (
            id INT AUTO_INCREMENT NOT NULL,
            author_id INT NOT NULL,
            post_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_commentaires_author (author_id),
            KEY IDX_commentaires_post (post_id),
            CONSTRAINT FK_D9BEC0C4F675F31B FOREIGN KEY (author_id) REFERENCES user (id),
            CONSTRAINT FK_D9BEC0C44B89032C FOREIGN KEY (post_id) REFERENCES posts (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 7. Create event_participants table
        $this->addSql('CREATE TABLE IF NOT EXISTS event_participants (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_event_participant (user_id, post_id),
            KEY IDX_event_participants_post (post_id),
            CONSTRAINT FK_event_participants_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_event_participants_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 8. Create likes table
        $this->addSql('CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_like_user_post (user_id, post_id),
            KEY IDX_likes_post (post_id),
            CONSTRAINT FK_likes_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_likes_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 9. Create manager_request table
        $this->addSql('CREATE TABLE IF NOT EXISTS manager_request (
            id INT AUTO_INCREMENT NOT NULL,
            motivation LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            user_id INT NOT NULL,
            nom VARCHAR(255),
            experience LONGTEXT,
            PRIMARY KEY (id),
            KEY IDX_855ABA89A76ED395 (user_id),
            CONSTRAINT FK_855ABA89A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 10. Create notifications table
        $this->addSql('CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT NOT NULL,
            type VARCHAR(80) NOT NULL,
            title VARCHAR(180) NOT NULL,
            message LONGTEXT NOT NULL,
            link VARCHAR(255),
            is_read TINYINT(4) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            recipient_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_6000B0D3E92F8F78 (recipient_id),
            CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 11. Create password_reset_codes table
        $this->addSql('CREATE TABLE IF NOT EXISTS password_reset_codes (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 12. Create post_media table
        $this->addSql('CREATE TABLE IF NOT EXISTS post_media (
            id INT AUTO_INCREMENT NOT NULL,
            type VARCHAR(20) NOT NULL,
            path VARCHAR(255) NOT NULL,
            position INT NOT NULL,
            post_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_FD372DE34B89032C (post_id),
            CONSTRAINT FK_FD372DE34B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 13. Create recommendation table
        $this->addSql('CREATE TABLE IF NOT EXISTS recommendation (
            id INT AUTO_INCREMENT NOT NULL,
            score DOUBLE DEFAULT NULL,
            created_at DATETIME NOT NULL,
            user_id INT NOT NULL,
            produit_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_433224D2A76ED395 (user_id),
            KEY IDX_433224D2F347EFB (produit_id),
            CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id),
            CONSTRAINT FK_433224D2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 14. Create team_reports table
        $this->addSql('CREATE TABLE IF NOT EXISTS team_reports (
            id INT AUTO_INCREMENT NOT NULL,
            reason LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            equipe_id INT NOT NULL,
            reporter_id INT NOT NULL,
            PRIMARY KEY (id),
            KEY IDX_E66D6726D861B89 (equipe_id),
            KEY idx_team_reports_reporter (reporter_id),
            CONSTRAINT FK_E66D6726D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE,
            CONSTRAINT FK_E66D672E1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES user (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci');

        // 15. Create tournoi_match table
        $this->addSql('CREATE TABLE IF NOT EXISTS tournoi_match (
            id INT AUTO_INCREMENT NOT NULL,
            tournoi_id INT NOT NULL,
            player_a_id INT,
            player_b_id INT,
            scheduled_at DATETIME,
            status VARCHAR(30) NOT NULL,
            score_a INT,
            score_b INT,
            created_at DATETIME NOT NULL,
            home_name VARCHAR(255),
            away_name VARCHAR(255),
            PRIMARY KEY (id),
            KEY IDX_TOURNOI_MATCH_TOURNOI (tournoi_id),
            KEY IDX_TOURNOI_MATCH_PLAYER_A (player_a_id),
            KEY IDX_TOURNOI_MATCH_PLAYER_B (player_b_id),
            CONSTRAINT FK_TOURNOI_MATCH_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id_tournoi) ON DELETE CASCADE,
            CONSTRAINT FK_TOURNOI_MATCH_PLAYER_A FOREIGN KEY (player_a_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_TOURNOI_MATCH_PLAYER_B FOREIGN KEY (player_b_id) REFERENCES user (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 16. Create tournoi_match_participant_result table
        $this->addSql('CREATE TABLE IF NOT EXISTS tournoi_match_participant_result (
            id INT AUTO_INCREMENT NOT NULL,
            match_id INT NOT NULL,
            participant_id INT NOT NULL,
            placement VARCHAR(20) NOT NULL,
            points INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_match_participant (match_id, participant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');

        // 17. Create user_saved_posts junction table
        $this->addSql('CREATE TABLE IF NOT EXISTS user_saved_posts (
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            PRIMARY KEY (user_id, post_id),
            CONSTRAINT FK_saved_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_saved_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        // Drop all newly created tables
        $tables = [
            'user_saved_posts',
            'tournoi_match_participant_result',
            'tournoi_match',
            'team_reports',
            'recommendation',
            'post_media',
            'password_reset_codes',
            'notifications',
            'manager_request',
            'likes',
            'event_participants',
            'commentaires',
            'chat_messages',
            'chat_message',
            'candidature',
            'announcements',
            'posts'
        ];

        foreach ($tables as $table) {
            $this->addSql("DROP TABLE IF EXISTS $table");
        }
    }
}
