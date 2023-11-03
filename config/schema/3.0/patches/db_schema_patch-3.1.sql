

ALTER TABLE `tmtraits` 
  ADD COLUMN `displayName` VARCHAR(100) NULL DEFAULT NULL AFTER `traitname`;
  
ALTER TABLE `tmtraits` 
  DROP FOREIGN KEY `FK_traits_uidcreated`,
  DROP FOREIGN KEY `FK_traits_uidmodified`;

ALTER TABLE `tmtraits` 
  CHANGE COLUMN `traitid` `traitID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `traitname` `traitName` VARCHAR(100) NOT NULL ,
  CHANGE COLUMN `traittype` `traitType` VARCHAR(2) NOT NULL DEFAULT 'UM' ,
  CHANGE COLUMN `refurl` `refUrl` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `modifieduid` `modifiedUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `createduid` `createdUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `tmtraits` 
  ADD CONSTRAINT `FK_traits_uidcreated`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_traits_uidmodified`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;
  
ALTER TABLE `tmstates` 
  DROP FOREIGN KEY `FK_tmstates_traits`,
  DROP FOREIGN KEY `FK_tmstates_uidcreated`,
  DROP FOREIGN KEY `FK_tmstates_uidmodified`;

ALTER TABLE `tmstates` 
  CHANGE COLUMN `stateid` `stateID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `traitid` `traitID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `statecode` `stateCode` VARCHAR(2) NOT NULL ,
  CHANGE COLUMN `statename` `stateName` VARCHAR(75) NOT NULL ,
  CHANGE COLUMN `refurl` `refUrl` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `sortseq` `sortSeq` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `modifieduid` `modifiedUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `createduid` `createdUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `tmstates` 
  ADD CONSTRAINT `FK_tmstates_traits`  FOREIGN KEY (`traitID`)  REFERENCES `tmtraits` (`traitID`)  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tmstates_uidcreated`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tmstates_uidmodified`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `tmtraitdependencies` 
  DROP FOREIGN KEY `FK_tmdepend_stateid`,
  DROP FOREIGN KEY `FK_tmdepend_traitid`;

ALTER TABLE `tmtraitdependencies` 
  CHANGE COLUMN `traitid` `traitID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `parentstateid` `parentStateID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `tmtraitdependencies` 
  ADD CONSTRAINT `FK_tmdepend_stateid`  FOREIGN KEY (`parentStateID`)  REFERENCES `tmstates` (`stateID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tmdepend_traitid`  FOREIGN KEY (`traitID`)  REFERENCES `tmtraits` (`traitID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `tmtraittaxalink` 
  DROP FOREIGN KEY `FK_traittaxalink_traitid`;

ALTER TABLE `tmtraittaxalink` 
  CHANGE COLUMN `traitid` `traitID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `tmtraittaxalink` 
  ADD CONSTRAINT `FK_traittaxalink_traitid`  FOREIGN KEY (`traitID`)  REFERENCES `tmtraits` (`traitID`)  ON DELETE CASCADE  ON UPDATE CASCADE;
  
ALTER TABLE `tmattributes` 
  DROP FOREIGN KEY `FK_tmattr_stateid`,
  DROP FOREIGN KEY `FK_tmattr_uidcreate`,
  DROP FOREIGN KEY `FK_tmattr_uidmodified`;

ALTER TABLE `tmattributes` 
  CHANGE COLUMN `stateid` `stateID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `xvalue` `xValue` DOUBLE NULL DEFAULT NULL ,
  CHANGE COLUMN `imagecoordinates` `imageCoordinates` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `statuscode` `statusCode` TINYINT(4) NULL DEFAULT NULL ,
  CHANGE COLUMN `modifieduid` `modifiedUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `createduid` `createdUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `tmattributes` 
  ADD CONSTRAINT `FK_tmattr_stateid`  FOREIGN KEY (`stateID`)  REFERENCES `tmstates` (`stateID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tmattr_uidcreate`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tmattr_uidmodified`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;
  