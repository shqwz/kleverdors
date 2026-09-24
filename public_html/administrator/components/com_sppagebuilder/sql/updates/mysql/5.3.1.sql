SET SQL_MODE = "";

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_image_shapes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `shape` TEXT,
  `created` DATETIME NOT NULL,
  `created_by` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;