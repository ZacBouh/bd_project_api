<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure timestamp columns exist on user table for TimestampableTrait compatibility.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
    }
}
