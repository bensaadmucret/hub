<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250815155018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE paddle_webhook_event (id SERIAL NOT NULL, event_id VARCHAR(191) NOT NULL, event_type VARCHAR(191) DEFAULT NULL, received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(32) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_paddle_event_id ON paddle_webhook_event (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN paddle_webhook_event.received_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE paddle_webhook_event
        SQL);
    }
}
