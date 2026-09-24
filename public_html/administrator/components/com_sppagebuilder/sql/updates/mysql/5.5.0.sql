-- Dynamic Content collections
-- v5.5.0
CREATE TABLE IF NOT EXISTS `#__sppagebuilder_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `published` tinyint NOT NULL DEFAULT 1,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_collection_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT 'title, alias, text, rich-text, image, video, datetime, link, number, switch, color, option, file, checkbox, reference, multi-reference, divider, and more',
  `description` TEXT,
  `options` TEXT,
  `max_length` int unsigned DEFAULT NULL,
  `min_length` int unsigned DEFAULT NULL,
  `default_value` TEXT,
  `placeholder` varchar(255) DEFAULT NULL,
  `required` tinyint NOT NULL DEFAULT 0,
  `reference_collection_id` bigint unsigned DEFAULT NULL,
  `is_textarea` tinyint NOT NULL DEFAULT 0,
  `show_time` tinyint NOT NULL DEFAULT 0,
  `file_extensions` varchar(300) DEFAULT NULL COMMENT 'Comma separated extensions for file type',
  `number_format` varchar(100) DEFAULT NULL COMMENT 'Available values: decimal, integer. NULL for allow both.',
  `allow_negative` tinyint NOT NULL DEFAULT 0,
  `number_unit` varchar(100) DEFAULT NULL COMMENT 'Available values: percent, degree. NULL for no unit.',
  `number_step` decimal(13, 2) DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`collection_id`) REFERENCES `#__sppagebuilder_collections`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_collection_id` (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_collection_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL DEFAULT 0,
  `collection_id` bigint unsigned NOT NULL,
  `published` tinyint NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '*',
  `created` datetime NOT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`collection_id`) REFERENCES `#__sppagebuilder_collections`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_collection_id` (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_collection_item_values` (
  `item_id` bigint unsigned NOT NULL,
  `field_id` bigint unsigned NOT NULL,
  `value` TEXT,
  `reference_item_id` bigint unsigned DEFAULT NULL COMMENT 'The reference item id from the #__sppagebuilder_collection_items table.',
  FOREIGN KEY (`item_id`) REFERENCES `#__sppagebuilder_collection_items`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`field_id`) REFERENCES `#__sppagebuilder_collection_fields`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`reference_item_id`) REFERENCES `#__sppagebuilder_collection_items`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;