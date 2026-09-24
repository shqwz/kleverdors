--version 6.0.0

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_comments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `source_type` varchar(50) NOT NULL DEFAULT 'article',
  `item_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `parent_id` bigint DEFAULT NULL,
  `content` text NOT NULL,
  `replies` int NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `access` int unsigned NOT NULL DEFAULT 0,
  `checked_out` int(10) NOT NULL DEFAULT '0',
  `checked_out_time` datetime DEFAULT NULL,
  `language` char(7) NOT NULL DEFAULT '',
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_likes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `comment_id` bigint NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`comment_id`) REFERENCES `#__sppagebuilder_comments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_comment_id` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;