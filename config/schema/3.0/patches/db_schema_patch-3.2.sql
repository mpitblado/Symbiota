INSERT INTO `schemaversion` (versionnumber) values ("3.2");

ALTER TABLE `omoccurrences` 
  ADD FULLTEXT INDEX `FT_omoccurrence_locality` (`locality`),
  ADD FULLTEXT INDEX `FT_omoccurrence_recordedBy` (`recordedBy`),
  DROP INDEX `Index_locality` ;
  
DROP TABLE `omoccurrencesfulltext`;

DROP TRIGGER IF EXISTS `omoccurrences_insert`;

DROP TRIGGER IF EXISTS `omoccurrences_update`;

DROP TRIGGER IF EXISTS `omoccurrences_delete`;


DELIMITER $$

CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
END$$

CREATE TRIGGER `omoccurrences_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		IF EXISTS (SELECT `occid` FROM omoccurpoints WHERE `occid`=NEW.`occid`) THEN
			UPDATE omoccurpoints 
			SET `point` = Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`)
			WHERE `occid` = NEW.`occid`;
		ELSE 
			INSERT INTO omoccurpoints (`occid`,`point`) 
			VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
		END IF;
	ELSE
		DELETE FROM omoccurpoints WHERE `occid` = NEW.`occid`;
	END IF;
END$$

CREATE TRIGGER `omoccurrences_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
	DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
END$$

DELIMITER ;
