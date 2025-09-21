<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823170141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alters `copy` table to add constraints on `owner_id` and `title_id`';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE copy CHANGE owner_id owner_id INT DEFAULT NULL, CHANGE title_id title_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE copy ADD CONSTRAINT FK_4DBABB827E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE copy ADD CONSTRAINT FK_4DBABB82A9F87BD FOREIGN KEY (title_id) REFERENCES title (id)');
        $this->addSql('CREATE INDEX IDX_4DBABB827E3C61F9 ON copy (owner_id)');
        $this->addSql('CREATE INDEX IDX_4DBABB82A9F87BD ON copy (title_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE copy DROP FOREIGN KEY FK_4DBABB827E3C61F9');
        $this->addSql('ALTER TABLE copy DROP FOREIGN KEY FK_4DBABB82A9F87BD');
        $this->addSql('DROP INDEX IDX_4DBABB827E3C61F9 ON copy');
        $this->addSql('DROP INDEX IDX_4DBABB82A9F87BD ON copy');
        $this->addSql('ALTER TABLE copy CHANGE owner_id owner_id INT NOT NULL, CHANGE title_id title_id INT NOT NULL');
    }
}
