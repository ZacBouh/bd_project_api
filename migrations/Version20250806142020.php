<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806142020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Adds `artist_uploaded_image`, `publisher_uploaded_image`, `title_uploaded_image`, `uploaded_image` relation tables.\nAdds `cover_image_id` to `artist`, `title` and `publisher` tables.";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_uploaded_image (artist_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_CC2EE053B7970CF8 (artist_id), INDEX IDX_CC2EE053AFC309FC (uploaded_image_id), PRIMARY KEY(artist_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publisher_uploaded_image (publisher_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_4484F01840C86FCE (publisher_id), INDEX IDX_4484F018AFC309FC (uploaded_image_id), PRIMARY KEY(publisher_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE title_uploaded_image (title_id INT NOT NULL, uploaded_image_id INT NOT NULL, INDEX IDX_9AD2F556A9F87BD (title_id), INDEX IDX_9AD2F556AFC309FC (uploaded_image_id), PRIMARY KEY(title_id, uploaded_image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE uploaded_image (id INT AUTO_INCREMENT NOT NULL, image_name VARCHAR(255) NOT NULL, file_size INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) NOT NULL, file_mime_type VARCHAR(255) DEFAULT NULL, image_dimensions VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE artist_uploaded_image ADD CONSTRAINT FK_CC2EE053B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist_uploaded_image ADD CONSTRAINT FK_CC2EE053AFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publisher_uploaded_image ADD CONSTRAINT FK_4484F01840C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publisher_uploaded_image ADD CONSTRAINT FK_4484F018AFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title_uploaded_image ADD CONSTRAINT FK_9AD2F556A9F87BD FOREIGN KEY (title_id) REFERENCES title (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title_uploaded_image ADD CONSTRAINT FK_9AD2F556AFC309FC FOREIGN KEY (uploaded_image_id) REFERENCES uploaded_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist ADD cover_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE publisher ADD cover_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE title ADD cover_image_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_uploaded_image DROP FOREIGN KEY FK_CC2EE053B7970CF8');
        $this->addSql('ALTER TABLE artist_uploaded_image DROP FOREIGN KEY FK_CC2EE053AFC309FC');
        $this->addSql('ALTER TABLE publisher_uploaded_image DROP FOREIGN KEY FK_4484F01840C86FCE');
        $this->addSql('ALTER TABLE publisher_uploaded_image DROP FOREIGN KEY FK_4484F018AFC309FC');
        $this->addSql('ALTER TABLE title_uploaded_image DROP FOREIGN KEY FK_9AD2F556A9F87BD');
        $this->addSql('ALTER TABLE title_uploaded_image DROP FOREIGN KEY FK_9AD2F556AFC309FC');
        $this->addSql('DROP TABLE artist_uploaded_image');
        $this->addSql('DROP TABLE publisher_uploaded_image');
        $this->addSql('DROP TABLE title_uploaded_image');
        $this->addSql('DROP TABLE uploaded_image');
        $this->addSql('ALTER TABLE publisher DROP cover_image_id');
        $this->addSql('ALTER TABLE title DROP cover_image_id');
        $this->addSql('ALTER TABLE artist DROP cover_image_id');
    }
}
