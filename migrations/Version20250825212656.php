<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825212656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE publisher_collection (id INT AUTO_INCREMENT NOT NULL, publisher_id INT DEFAULT NULL, cover_image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, birth_date DATETIME DEFAULT NULL, death_date DATETIME DEFAULT NULL, language VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, INDEX IDX_E10280A640C86FCE (publisher_id), INDEX IDX_E10280A6E5A0E336 (cover_image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publisher_collection_uploaded_image (publisher_collection_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_C5B6F9C56E391D64 (publisher_collection_id), INDEX IDX_C5B6F9C5AFC309FC (uploaded_image_id), PRIMARY KEY(publisher_collection_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE publisher_collection ADD CONSTRAINT FK_E10280A640C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id)');
        $this->addSql('ALTER TABLE publisher_collection ADD CONSTRAINT FK_E10280A6E5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('ALTER TABLE publisher_collection_uploaded_image ADD CONSTRAINT FK_C5B6F9C56E391D64 FOREIGN KEY (publisher_collection_id) REFERENCES publisher_collection (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publisher_collection_uploaded_image ADD CONSTRAINT FK_C5B6F9C5AFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title ADD collection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE title ADD CONSTRAINT FK_2B36786B514956FD FOREIGN KEY (collection_id) REFERENCES publisher_collection (id)');
        $this->addSql('CREATE INDEX IDX_2B36786B514956FD ON title (collection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE title DROP FOREIGN KEY FK_2B36786B514956FD');
        $this->addSql('ALTER TABLE publisher_collection DROP FOREIGN KEY FK_E10280A640C86FCE');
        $this->addSql('ALTER TABLE publisher_collection DROP FOREIGN KEY FK_E10280A6E5A0E336');
        $this->addSql('ALTER TABLE publisher_collection_uploaded_image DROP FOREIGN KEY FK_C5B6F9C56E391D64');
        $this->addSql('ALTER TABLE publisher_collection_uploaded_image DROP FOREIGN KEY FK_C5B6F9C5AFC309FC');
        $this->addSql('DROP TABLE publisher_collection');
        $this->addSql('DROP TABLE publisher_collection_uploaded_image');
        $this->addSql('DROP INDEX IDX_2B36786B514956FD ON title');
        $this->addSql('ALTER TABLE title DROP collection_id');
    }
}
