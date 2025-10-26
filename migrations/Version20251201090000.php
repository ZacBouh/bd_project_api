<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_type column to payout_task table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payout_task ADD payment_type VARCHAR(16) NOT NULL DEFAULT 'ORDER'");
        $this->addSql("UPDATE payout_task SET payment_type = 'ORDER' WHERE payment_type IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task DROP payment_type');
    }
}
