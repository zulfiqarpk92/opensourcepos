INSERT INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `module_id`) VALUES ('module_labors', 'module_labors_desc', 'labors');
INSERT INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('labors', 'labors');
CREATE TABLE IF NOT EXISTS `ospos_labors` (
	`person_id` INT(10) NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
	`employee_id` INT(10) NOT NULL,
	`deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	INDEX `person_id` (`person_id`) USING BTREE
)
COLLATE='utf8_general_ci' ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ospos_labors_payments` (
	`payment_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`labor_id` INT(11) NOT NULL DEFAULT '0',
	`credit` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`amount` DOUBLE(22,0) NOT NULL,
	`payment_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`reference` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	`comments` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	PRIMARY KEY (`payment_id`) USING BTREE,
	INDEX `labor_id` (`labor_id`) USING BTREE
)
COLLATE='utf8_general_ci' ENGINE=InnoDB;