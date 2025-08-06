<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806194725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alters `cover_image_id` to refer to foreign key on `uploaded_image` table for `artist`, `publisher` and `title` tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687E5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('CREATE INDEX IDX_1599687E5A0E336 ON artist (cover_image_id)');
        $this->addSql('ALTER TABLE publisher ADD CONSTRAINT FK_9CE8D546E5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('CREATE INDEX IDX_9CE8D546E5A0E336 ON publisher (cover_image_id)');
        $this->addSql('ALTER TABLE title ADD CONSTRAINT FK_2B36786BE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES uploaded_image (id)');
        $this->addSql('CREATE INDEX IDX_2B36786BE5A0E336 ON title (cover_image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE publisher DROP FOREIGN KEY FK_9CE8D546E5A0E336');
        $this->addSql('DROP INDEX IDX_9CE8D546E5A0E336 ON publisher');
        $this->addSql('ALTER TABLE title DROP FOREIGN KEY FK_2B36786BE5A0E336');
        $this->addSql('DROP INDEX IDX_2B36786BE5A0E336 ON title');
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687E5A0E336');
        $this->addSql('DROP INDEX IDX_1599687E5A0E336 ON artist');
    }
}
