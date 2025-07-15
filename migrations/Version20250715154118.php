<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715154118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE content_block_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE seo_metadata_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE seo_page_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_page DROP CONSTRAINT fk_e8dca6f118f9c0d5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE content_block DROP CONSTRAINT fk_68d8c3f0c4663e4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE seo_page
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE seo_metadata
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE content_block
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE content_block_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE seo_metadata_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE seo_page_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE seo_page (id SERIAL NOT NULL, seo_metadata_id INT DEFAULT NULL, slug VARCHAR(255) NOT NULL, page_type VARCHAR(50) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_e8dca6f118f9c0d5 ON seo_page (seo_metadata_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_e8dca6f1989d9b62 ON seo_page (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE seo_metadata (id SERIAL NOT NULL, title VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, keywords JSON DEFAULT NULL, canonical_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE content_block (id SERIAL NOT NULL, page_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, config JSON NOT NULL, "position" INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_68d8c3f0c4663e4 ON content_block (page_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seo_page ADD CONSTRAINT fk_e8dca6f118f9c0d5 FOREIGN KEY (seo_metadata_id) REFERENCES seo_metadata (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE content_block ADD CONSTRAINT fk_68d8c3f0c4663e4 FOREIGN KEY (page_id) REFERENCES seo_page (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }
}
