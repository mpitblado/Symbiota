INSERT INTO schemaversion (versionnumber) values ("3.1");

#Set foreign keys for fmchklstcoordinates
ALTER TABLE `fmchklstcoordinates` 
  DROP INDEX `FKchklsttaxalink` ;

ALTER TABLE `fmchklstcoordinates` 
  ADD INDEX `FK_checklistCoord_tid_idx` (`tid` ASC),
  ADD INDEX `FK_checklistCoord_clid_idx` (`clid` ASC);

ALTER TABLE `fmchklstcoordinates` 
  ADD UNIQUE INDEX `UQ_checklistCoord_unique` (`clid` ASC, `tid` ASC, `decimalLatitude` ASC, `decimalLongitude` ASC);

ALTER TABLE `fmchklstcoordinates` 
  ADD CONSTRAINT `FK_checklistCoord_clid`  FOREIGN KEY (`clid`)  REFERENCES `fmchecklists` (`clid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_checklistCoord_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`tid`)  ON DELETE CASCADE  ON UPDATE CASCADE;


# Needed to ensure basisOfRecord values are tagged correctly based on collection type (aka collType field)
UPDATE omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid
  SET o.basisofrecord = "PreservedSpecimen"
  WHERE (o.basisofrecord = "HumanObservation" OR o.basisofrecord IS NULL) AND c.colltype = 'Preserved Specimens'
  AND o.occid NOT IN(SELECT occid FROM omoccuredits WHERE fieldname = "basisofrecord");


CREATE TABLE `dynamicproperties` (
  `dynPropID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tableName` VARCHAR(45) NOT NULL,
  `tablePK` INT NOT NULL,
  `propName` VARCHAR(45) NOT NULL,
  `type` VARCHAR(45) NOT NULL,
  `category` VARCHAR(45) NULL,
  `url` VARCHAR(250) NULL,
  `attributes` VARCHAR(250) NULL,
  `dynamicProperties` TEXT NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `createdUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`dynPropID`),
  INDEX `IX_dynProp_tableName` (`tableName` ASC),
  INDEX `IX_dynProp_tablePK` (`tablePK` ASC),
  INDEX `IX_dynPrpo_type` (`type` ASC),
  INDEX `FK_dynProp_modifiedUid_idx` (`modifiedUid` ASC),
  INDEX `FK_dynProp_createdUid_idx` (`createdUid` ASC),
  CONSTRAINT `FK_dynProp_modifiedUid`   FOREIGN KEY (`modifiedUid`)   REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE,
  CONSTRAINT `FK_dynProp_createdUid`   FOREIGN KEY (`createdUid`)   REFERENCES `usertaxonomy` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE);


