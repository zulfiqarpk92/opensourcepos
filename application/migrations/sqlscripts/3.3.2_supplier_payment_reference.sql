ALTER TABLE `ospos_suppliers_payments` CHANGE COLUMN `date` `payment_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `ospos_suppliers_payments` ADD COLUMN `reference` VARCHAR(32);
ALTER TABLE `ospos_suppliers_payments` ADD COLUMN `comments` TEXT;
