CREATE USER 'routertasks'@'localhost' IDENTIFIED WITH mysql_native_password BY 'routertasks';
CREATE DATABASE routertasks;
GRANT ALL PRIVILEGES ON routertasks.* TO 'routertasks'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE `scheduled` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`taskId` VARCHAR(32) NOT NULL,
	`reason` TEXT NOT NULL,
	`status` ENUM('scheduled', 'started', 'finished', 'failed', 'cancelled') NOT NULL,
	`addedBy` VARCHAR(32) NOT NULL,
	`addedAt` INT unsigned NOT NULL,
	`scheduledFor` INT unsigned NOT NULL,
	`startedAt` INT unsigned NOT NULL DEFAULT 0,
	`finishedAt` INT unsigned NOT NULL DEFAULT 0,
	`output` LONGBLOB,
	PRIMARY KEY (`id`)
);



