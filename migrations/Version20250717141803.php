<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250717141803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_item (id SERIAL NOT NULL, parent_id INT DEFAULT NULL, page_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, is_active BOOLEAN NOT NULL, is_in_footer BOOLEAN NOT NULL, target VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D754D550727ACA70 ON menu_item (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D754D550C4663E4 ON menu_item (page_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550727ACA70 FOREIGN KEY (parent_id) REFERENCES menu_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550C4663E4 FOREIGN KEY (page_id) REFERENCES page (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_item DROP CONSTRAINT FK_D754D550727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_item DROP CONSTRAINT FK_D754D550C4663E4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu_item
        SQL);
    }
}
