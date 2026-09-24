-- version: 5.7.0

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_collection_imports` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `data` TEXT,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;