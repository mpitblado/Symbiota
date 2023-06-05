INSERT INTO schemaversion (versionnumber) values ("3.1");

CREATE TABLE `customdocuments` (
  `cdid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) NOT NULL,
  `description` VARCHAR(250) NULL,
  `type` VARCHAR(45) NOT NULL,
  `category` VARCHAR(45) NULL,
  `css` VARCHAR(150) NULL,
  `js` VARCHAR(150) NULL,
  `collid` INT UNSIGNED NULL,
  `uid` INT UNSIGNED NULL,
  `portalGuid` VARCHAR(45) NULL,
  `htmlTemplate` TEXT NULL,
  `jsonDefinition` TEXT NULL,
  `modules` TEXT NULL,
  `access` INT NULL DEFAULT 0,
  `dynProps` TEXT NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`cdid`),
  INDEX `FK_customdocs_collid_idx` (`collid` ASC),
  INDEX `FK_customdocs_uid_idx` (`uid` ASC),
  CONSTRAINT `FK_customdocs_collid`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`collID`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_customdocs_uid`  FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_customdocs_modUid`  FOREIGN KEY ()  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE
);

