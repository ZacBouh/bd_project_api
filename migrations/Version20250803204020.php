<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803204020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE title (id INT AUTO_INCREMENT NOT NULL, publisher_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, release_date DATE DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, language VARCHAR(2) DEFAULT NULL, INDEX IDX_2B36786B40C86FCE (publisher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE title_artist (title_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_CFF883AEA9F87BD (title_id), INDEX IDX_CFF883AEB7970CF8 (artist_id), PRIMARY KEY(title_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE title ADD CONSTRAINT FK_2B36786B40C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id)');
        $this->addSql('ALTER TABLE title_artist ADD CONSTRAINT FK_CFF883AEA9F87BD FOREIGN KEY (title_id) REFERENCES title (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title_artist ADD CONSTRAINT FK_CFF883AEB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publisher ADD country VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE title DROP FOREIGN KEY FK_2B36786B40C86FCE');
        $this->addSql('ALTER TABLE title_artist DROP FOREIGN KEY FK_CFF883AEA9F87BD');
        $this->addSql('ALTER TABLE title_artist DROP FOREIGN KEY FK_CFF883AEB7970CF8');
        $this->addSql('DROP TABLE title');
        $this->addSql('DROP TABLE title_artist');
        $this->addSql('ALTER TABLE publisher DROP country');
    }
}
