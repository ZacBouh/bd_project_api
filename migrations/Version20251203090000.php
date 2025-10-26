<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to artist table for soft deletes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE artist ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist DROP deleted_at');
    }
}
