<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing updated_at column to payout_task for timestampable trait support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('UPDATE payout_task SET updated_at = created_at');
        $this->addSql('ALTER TABLE payout_task MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task DROP updated_at');
    }
}
