<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829184348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs nécessaires au mécanisme de retry pour les webhooks Paddle';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // D'abord ajouter la colonne comme nullable
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ADD retry_count INT DEFAULT NULL
        SQL);
        
        // Mettre à jour les enregistrements existants avec une valeur par défaut
        $this->addSql(<<<'SQL'
            UPDATE paddle_webhook_event SET retry_count = 0 WHERE retry_count IS NULL
        SQL);
        
        // Puis modifier la colonne pour la rendre non nullable
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ALTER COLUMN retry_count SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ADD last_attempt_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ADD next_retry_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ADD error_message TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event ADD payload TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN paddle_webhook_event.last_attempt_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN paddle_webhook_event.next_retry_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_paddle_event_retry ON paddle_webhook_event (status, retry_count)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_paddle_event_retry
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event DROP retry_count
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event DROP last_attempt_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event DROP next_retry_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event DROP error_message
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paddle_webhook_event DROP payload
        SQL);
    }
}
