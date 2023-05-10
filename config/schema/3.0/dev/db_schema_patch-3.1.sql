INSERT INTO schemaversion (versionnumber) values ("3.1");

CREATE TABLE `omoccurdynamicfields` (
  `dynFieldID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fieldName` VARCHAR(45) NOT NULL,
  `description` VARCHAR(250) NULL,
  `dataType` VARCHAR(45) NOT NULL,
  `dwcExport` INT NULL,
  `symbiotaExport` INT NULL,
  `import` INT NULL,
  `editor` INT NULL DEFAULT 1,
  `publicDisplay` INT NULL,
  `searchable` INT NULL,
  `controlType` VARCHAR(45) NOT NULL,
  `controlledVocabulary` TEXT NULL,
  `limitToList` INT NULL,
  `dynamicProperties` TEXT NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`dynFieldID`),
  INDEX `IX_occurdynfields_fieldName` (`fieldName` ASC),
  INDEX `IX_occurdynfields_dwcExport` (`dwcExport` ASC),
  INDEX `IX_occurdynfields_symbiotaExport` (`symbiotaExport` ASC),
  INDEX `IX_occurdynfields_editor` (`editor` ASC),
  INDEX `IX_occurdynfields_publicDisplay` (`publicDisplay` ASC),
  INDEX `IX_occurdynfields_searhable` (`searchable` ASC),
  INDEX `FK_occurdynfields_uid_idx` (`modifiedUid` ASC),
  CONSTRAINT `FK_occurdynfields_uid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE
);

CREATE TABLE `omoccurdynamicvalues` (
  `dynValueID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dynFieldID` INT UNSIGNED NULL,
  `occid` INT UNSIGNED NULL,
  `stringValue` VARCHAR(45) NULL,
  `integerValue` INT NULL,
  `doubleValue` DOUBLE NULL,
  `dateValue` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`dynValueID`),
  INDEX `FK_occurDynValues_occid_idx` (`occid` ASC),
  INDEX `IX_occurDynValues_string` (`stringValue` ASC),
  INDEX `IX_occurDynValues_integer` (`integerValue` ASC),
  INDEX `IX_occurDynValues_double` (`doubleValue` ASC),
  INDEX `IX_occurDynValues_date` (`dateValue` ASC),
  CONSTRAINT `FK_occurDynValues_dynFieldID`  FOREIGN KEY (`dynFieldID`)  REFERENCES `omoccurdynamicfields` (`dynFieldID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurDynValues_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE
);
    
