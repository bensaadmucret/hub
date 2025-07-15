<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715125131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata DROP keywords
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata DROP structured_data
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER title DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER title TYPE VARCHAR(255)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER description TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER description DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER description TYPE TEXT
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ADD keywords VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ADD structured_data JSON NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER title SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER title TYPE VARCHAR(60)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER description TYPE VARCHAR(160)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_metadata ALTER description SET NOT NULL
        SQL);
    }
}
