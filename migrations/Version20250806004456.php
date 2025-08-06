<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806004456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds `artist_title_contribution` table. Removes `title_artist` relation table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_title_contribution (id INT AUTO_INCREMENT NOT NULL, artist_id INT NOT NULL, title_id INT NOT NULL, skill_name VARCHAR(255) NOT NULL, INDEX IDX_77E46C3FB7970CF8 (artist_id), INDEX IDX_77E46C3FA9F87BD (title_id), INDEX IDX_77E46C3F1962E2B4 (skill_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE artist_title_contribution ADD CONSTRAINT FK_77E46C3FB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE artist_title_contribution ADD CONSTRAINT FK_77E46C3F1962E2B4 FOREIGN KEY (skill_name) REFERENCES skill (name)');
        $this->addSql('ALTER TABLE title_artist DROP FOREIGN KEY FK_CFF883AEA9F87BD');
        $this->addSql('ALTER TABLE title_artist DROP FOREIGN KEY FK_CFF883AEB7970CF8');
        $this->addSql('DROP TABLE title_artist');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE title_artist (title_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_CFF883AEB7970CF8 (artist_id), INDEX IDX_CFF883AEA9F87BD (title_id), PRIMARY KEY(title_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE title_artist ADD CONSTRAINT FK_CFF883AEA9F87BD FOREIGN KEY (title_id) REFERENCES title (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title_artist ADD CONSTRAINT FK_CFF883AEB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist_title_contribution DROP FOREIGN KEY FK_77E46C3FB7970CF8');
        $this->addSql('ALTER TABLE artist_title_contribution DROP FOREIGN KEY FK_77E46C3F1962E2B4');
        $this->addSql('DROP TABLE artist_title_contribution');
    }
}
