-- Adminer 4.7.6 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ospos_supplier_payment`;
CREATE TABLE `ospos_supplier_payment` (
  `supplier_payment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_id` int(10) NOT NULL,
  `amount_tendered` double NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_payment_id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `ospos_supplier_payment_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `ospos_people` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2020-09-16 18:05:07
