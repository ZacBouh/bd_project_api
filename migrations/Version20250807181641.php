<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250807181641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE copy (id INT AUTO_INCREMENT NOT NULL, cover_image_id INT DEFAULT NULL, owner_id INT NOT NULL, tile_id INT NOT NULL, copy_condition VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, currency VARCHAR(255) DEFAULT NULL, bought_for_price DOUBLE PRECISION DEFAULT NULL, bought_for_currency VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, INDEX IDX_4DBABB82E5A0E336 (cover_image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE copy_uploaded_image (copy_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_A30D3FADA8752772 (copy_id), INDEX IDX_A30D3FADAFC309FC (uploaded_image_id), PRIMARY KEY(copy_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE copy ADD CONSTRAINT FK_4DBABB82E5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('ALTER TABLE copy_uploaded_image ADD CONSTRAINT FK_A30D3FADA8752772 FOREIGN KEY (copy_id) REFERENCES copy (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE copy_uploaded_image ADD CONSTRAINT FK_A30D3FADAFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE copy DROP FOREIGN KEY FK_4DBABB82E5A0E336');
        $this->addSql('ALTER TABLE copy_uploaded_image DROP FOREIGN KEY FK_A30D3FADA8752772');
        $this->addSql('ALTER TABLE copy_uploaded_image DROP FOREIGN KEY FK_A30D3FADAFC309FC');
        $this->addSql('DROP TABLE copy');
        $this->addSql('DROP TABLE copy_uploaded_image');
    }
}
