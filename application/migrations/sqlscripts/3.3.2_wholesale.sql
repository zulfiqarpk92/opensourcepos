ALTER TABLE `ospos_items` ADD COLUMN `wsale_price` DECIMAL(15,2) NOT NULL AFTER `cost_price`;
ALTER TABLE `ospos_modules` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `ospos_inventory` ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE `ospos_inventory` ADD COLUMN `source_id` INT NOT NULL DEFAULT '0';
ALTER TABLE `ospos_suppliers` CHANGE COLUMN `category` `category` VARCHAR(50) NOT NULL DEFAULT '0';
