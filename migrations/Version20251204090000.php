<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at column to payout_task table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payout_task ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task DROP updated_at');
    }
}
