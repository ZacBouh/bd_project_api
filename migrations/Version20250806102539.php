<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806102539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds foreign key constraint on `title_id` and ON DELETE CASCADE to `artist_title_contribution` table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_title_contribution ADD CONSTRAINT FK_77E46C3FA9F87BD FOREIGN KEY (title_id) REFERENCES title (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_title_contribution DROP FOREIGN KEY FK_77E46C3FA9F87BD');
    }
}
