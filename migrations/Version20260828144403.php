<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828144403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, platform VARCHAR(32) NOT NULL, external_id VARCHAR(128) NOT NULL, author_name VARCHAR(180) NOT NULL, rating SMALLINT NOT NULL, content CLOB NOT NULL, reviewed_at DATETIME NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX idx_platform ON review (platform)');
        $this->addSql('CREATE UNIQUE INDEX uniq_platform_external ON review (platform, external_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE review');
    }
}
