<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link payout tasks to their related order items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task ADD order_item_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_F123D0AB126F525E ON payout_task (order_item_id)');
        $this->addSql('ALTER TABLE payout_task ADD CONSTRAINT FK_F123D0AB126F525E FOREIGN KEY (order_item_id) REFERENCES order_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task DROP FOREIGN KEY FK_F123D0AB126F525E');
        $this->addSql('DROP INDEX IDX_F123D0AB126F525E ON payout_task');
        $this->addSql('ALTER TABLE payout_task DROP order_item_id');
    }
}
