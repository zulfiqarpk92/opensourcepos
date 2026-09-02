INSERT INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_warranties', 'module_warranties_desc', 42, 'warranties');

INSERT INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
('warranties', 'warranties');

INSERT INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`)
SELECT 'warranties', `person_id`, 'home' FROM `ospos_employees` WHERE `deleted` = 0;

CREATE TABLE IF NOT EXISTS `ospos_warranties` (
	`warranty_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`serial_number` VARCHAR(64) NOT NULL,
	`item_id` INT(10) NULL DEFAULT NULL,
	`item_name` VARCHAR(255) NULL DEFAULT NULL,
	`customer_id` INT(10) NULL DEFAULT NULL,
	`sale_id` INT(10) NULL DEFAULT NULL,
	`supplier_id` INT(10) NULL DEFAULT NULL,
	`sent_date` DATETIME NOT NULL,
	`received_date` DATETIME NULL DEFAULT NULL,
	`status` VARCHAR(20) NOT NULL DEFAULT 'sent',
	`issue_description` TEXT NULL,
	`claim_reference` VARCHAR(64) NULL DEFAULT NULL,
	`resolution_notes` TEXT NULL,
	`additional_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
	`employee_id` INT(10) NOT NULL,
	`received_employee_id` INT(10) NULL DEFAULT NULL,
	`deleted` TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (`warranty_id`),
	KEY `serial_number` (`serial_number`),
	KEY `status` (`status`),
	KEY `supplier_id` (`supplier_id`),
	KEY `sent_date` (`sent_date`),
	KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
