<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to support soft deletes across core entities.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE copy ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE publisher ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE series ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE title ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE publisher_collection ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE user ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE copy DROP deleted_at');
        $this->addSql('ALTER TABLE publisher DROP deleted_at');
        $this->addSql('ALTER TABLE series DROP deleted_at');
        $this->addSql('ALTER TABLE title DROP deleted_at');
        $this->addSql('ALTER TABLE publisher_collection DROP deleted_at');
        $this->addSql('ALTER TABLE user DROP deleted_at');
    }
}
