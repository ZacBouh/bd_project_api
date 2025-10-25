<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order reference, order items and payout tasks tracking tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD order_ref VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders DROP INDEX UNIQ_E52FFDE161112094');
        $this->addSql('ALTER TABLE orders CHANGE checkout_session_id stripe_checkout_session_id VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDE1B8E1ACF9 ON orders (stripe_checkout_session_id)');
        $this->addSql('ALTER TABLE orders CHANGE amount_total amount_total INT DEFAULT NULL, CHANGE currency currency VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE orders SET order_ref = CONCAT('o_', LPAD(id, 6, '0')) WHERE order_ref IS NULL OR order_ref = ''");
        $this->addSql('ALTER TABLE orders MODIFY order_ref VARCHAR(32) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDE1D9F01F37 ON orders (order_ref)');

        $this->addSql('ALTER TABLE copy CHANGE price price INT DEFAULT NULL, CHANGE bought_for_price bought_for_price INT DEFAULT NULL');

        $this->addSql("CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, copy_id INT DEFAULT NULL, seller_id INT DEFAULT NULL, price INT NOT NULL, currency VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL, buyer_confirmed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_52EA1F09F8A2146 (order_id), INDEX IDX_52EA1F09AFC2B591 (copy_id), INDEX IDX_52EA1F09F603EE73 (seller_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09F8A2146 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09AFC2B591 FOREIGN KEY (copy_id) REFERENCES copy (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09F603EE73 FOREIGN KEY (seller_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql("CREATE TABLE payout_task (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, seller_id INT DEFAULT NULL, amount INT NOT NULL, currency VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL, payment_information JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_F123D0AB9F8A2146 (order_id), INDEX IDX_F123D0ABF603EE73 (seller_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE payout_task ADD CONSTRAINT FK_F123D0AB9F8A2146 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payout_task ADD CONSTRAINT FK_F123D0ABF603EE73 FOREIGN KEY (seller_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payout_task DROP FOREIGN KEY FK_F123D0AB9F8A2146');
        $this->addSql('ALTER TABLE payout_task DROP FOREIGN KEY FK_F123D0ABF603EE73');
        $this->addSql('DROP TABLE payout_task');

        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09F8A2146');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09AFC2B591');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09F603EE73');
        $this->addSql('DROP TABLE order_item');

        $this->addSql('DROP INDEX UNIQ_E52FFDE1D9F01F37 ON orders');
        $this->addSql('DROP INDEX UNIQ_E52FFDE1B8E1ACF9 ON orders');
        $this->addSql('ALTER TABLE orders DROP order_ref');
        $this->addSql('ALTER TABLE orders CHANGE amount_total amount_total DOUBLE PRECISION DEFAULT NULL, CHANGE currency currency VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE copy CHANGE price price DOUBLE PRECISION DEFAULT NULL, CHANGE bought_for_price bought_for_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE orders CHANGE stripe_checkout_session_id checkout_session_id VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDE161112094 ON orders (checkout_session_id)');
    }
}
