CREATE TABLE IF NOT EXISTS `ospos_suppliers_payments` (
  `supplier_payment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) NOT NULL DEFAULT 0,
  `receiving_id` INT NOT NULL DEFAULT 0,
  `amount_tendered` double NOT NULL DEFAULT 0,
  `payment_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `reference` VARCHAR(32),
  `comments` TEXT,
  PRIMARY KEY (`supplier_payment_id`),
  KEY `supplier_id` (`supplier_id`)
  KEY `receiving_id` (`receiving_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;