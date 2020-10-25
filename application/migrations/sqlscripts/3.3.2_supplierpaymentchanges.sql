RENAME TABLE `ospos_supplier_payment` TO `ospos_suppliers_payments`;
ALTER TABLE `ospos_suppliers_payments` CHANGE COLUMN `person_id` `supplier_id` INT NOT NULL DEFAULT 0;
ALTER TABLE `ospos_suppliers_payments` ADD COLUMN `receiving_id` INT NOT NULL DEFAULT 0 AFTER `supplier_id`;
ALTER TABLE `ospos_suppliers_payments` ADD INDEX `receiving_id` (`receiving_id`);
