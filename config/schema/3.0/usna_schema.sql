ALTER TABLE `omoccurrences` ADD COLUMN `cultivarname` VARCHAR(150) NULL DEFAULT NULL AFTER `processingStatus`;

ALTER TABLE `omoccurrences` ADD COLUMN `tradename` VARCHAR(150) NULL DEFAULT NULL AFTER `cultivarname`;

ALTER TABLE `uploadspectemp` ADD COLUMN `cultivarname` VARCHAR(150) NULL DEFAULT NULL AFTER `processingStatus`;

ALTER TABLE `uploadspectemp` ADD COLUMN `tradename` VARCHAR(150) NULL DEFAULT NULL AFTER `cultivarname`;