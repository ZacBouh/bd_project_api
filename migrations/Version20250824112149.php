<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824112149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates `series` table and alters `title` table in consequence';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE series (id INT AUTO_INCREMENT NOT NULL, publisher_id INT NOT NULL, cover_image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, on_going_status VARCHAR(9) DEFAULT NULL, language VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, INDEX IDX_3A10012D40C86FCE (publisher_id), INDEX IDX_3A10012DE5A0E336 (cover_image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series_uploaded_image (series_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_B2CC23675278319C (series_id), INDEX IDX_B2CC2367AFC309FC (uploaded_image_id), PRIMARY KEY(series_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012D40C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012DE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('ALTER TABLE series_uploaded_image ADD CONSTRAINT FK_B2CC23675278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE series_uploaded_image ADD CONSTRAINT FK_B2CC2367AFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title ADD series_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE title ADD CONSTRAINT FK_2B36786B5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('CREATE INDEX IDX_2B36786B5278319C ON title (series_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE title DROP FOREIGN KEY FK_2B36786B5278319C');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012D40C86FCE');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012DE5A0E336');
        $this->addSql('ALTER TABLE series_uploaded_image DROP FOREIGN KEY FK_B2CC23675278319C');
        $this->addSql('ALTER TABLE series_uploaded_image DROP FOREIGN KEY FK_B2CC2367AFC309FC');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE series_uploaded_image');
        $this->addSql('DROP INDEX IDX_2B36786B5278319C ON title');
        $this->addSql('ALTER TABLE title DROP series_id');
    }
}
