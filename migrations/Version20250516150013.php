<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250516150013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD CONSTRAINT FK_8C9F3610A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8C9F3610A76ED395 ON file (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file DROP CONSTRAINT FK_8C9F3610A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8C9F3610A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE file DROP user_id
        SQL);
    }
}
