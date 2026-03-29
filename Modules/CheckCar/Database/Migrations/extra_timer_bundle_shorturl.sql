-- Extra DB objects: bundles, virtual_product, buyer_contact_id, is_inspection_service,
-- short_urls, timer_pre_phrases, timer_stop_reasons

-- =========================================
-- DROP / CLEANUP (optional - run only if needed)
-- =========================================
-- ALTER TABLE `transaction_sell_lines` DROP FOREIGN KEY `transaction_sell_lines_bundle_id_foreign`;
-- ALTER TABLE `transaction_sell_lines` DROP COLUMN `bundle_id`;
-- ALTER TABLE `bookings` DROP FOREIGN KEY `bookings_buyer_contact_id_foreign`;
-- ALTER TABLE `bookings` DROP COLUMN `buyer_contact_id`;
-- ALTER TABLE `types_of_services` DROP COLUMN `is_inspection_service`;
-- ALTER TABLE `products` DROP COLUMN `virtual_product`;
-- DROP TABLE IF EXISTS `timer_stop_reasons`;
-- DROP TABLE IF EXISTS `timer_pre_phrases`;
-- DROP TABLE IF EXISTS `short_urls`;
-- DROP TABLE IF EXISTS `bundles`;

-- =========================================
-- 1) bundles
-- =========================================
CREATE TABLE `bundles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(255) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `repair_device_model_id` bigint(20) unsigned DEFAULT NULL,
  `manufacturing_year` smallint(5) unsigned DEFAULT NULL,
  `side_type` enum('front_half','rear_half','left_quarter','right_quarter','full_body','other') NOT NULL DEFAULT 'other',
  `price` decimal(22,4) NOT NULL DEFAULT '0.0000',
  `has_parts_left` tinyint(1) NOT NULL DEFAULT '1',
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `bundles_reference_no_unique` (`reference_no`),
  KEY `bundles_device_id_index` (`device_id`),
  KEY `bundles_repair_device_model_id_index` (`repair_device_model_id`),
  KEY `bundles_location_id_index` (`location_id`),
  KEY `bundles_side_type_index` (`side_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2) add bundle_id to transaction_sell_lines (BIGINT to match bundles.id)
-- =========================================
ALTER TABLE `transaction_sell_lines`
  ADD COLUMN `bundle_id` BIGINT(20) UNSIGNED NULL AFTER `variation_id`;

ALTER TABLE `transaction_sell_lines`
  ADD CONSTRAINT `transaction_sell_lines_bundle_id_foreign`
    FOREIGN KEY (`bundle_id`) REFERENCES `bundles`(`id`)
    ON DELETE SET NULL;

-- =========================================
-- 3) add virtual_product to products
-- =========================================
ALTER TABLE `products`
  ADD COLUMN `virtual_product` TINYINT(1) NOT NULL DEFAULT '0'
  AFTER `enable_stock`;

-- =========================================
-- 4) add buyer_contact_id to bookings
-- =========================================
ALTER TABLE `bookings`
  ADD COLUMN `buyer_contact_id` INT(10) UNSIGNED NULL AFTER `contact_id`;

ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_buyer_contact_id_foreign`
    FOREIGN KEY (`buyer_contact_id`) REFERENCES `contacts`(`id`)
    ON DELETE SET NULL;

-- =========================================
-- 5) add is_inspection_service to types_of_services
-- =========================================
ALTER TABLE `types_of_services`
  ADD COLUMN `is_inspection_service` TINYINT(1) NOT NULL DEFAULT '0'
  AFTER `enable_custom_fields`;

-- =========================================
-- 6) short_urls
-- =========================================
CREATE TABLE `short_urls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `long_url` text NOT NULL,
  `clicks` bigint(20) unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `short_urls_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7) timer_pre_phrases
-- =========================================
CREATE TABLE `timer_pre_phrases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,

  `reason_type` enum('record_reason','finishtimer','ignore') DEFAULT NULL,

  `body` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `timer_pre_phrases_business_id_reason_type_index` (`business_id`, `reason_type`),
  KEY `timer_pre_phrases_location_id_reason_type_index` (`location_id`, `reason_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8) timer_stop_reasons
-- =========================================
CREATE TABLE `timer_stop_reasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,

  `timer_id` bigint(20) unsigned DEFAULT NULL,
  `phrase_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,

  `reason_type` enum('record_reason','finishtimer','ignore') DEFAULT NULL,

  `body` text DEFAULT NULL,
  `pause_start` timestamp NULL DEFAULT NULL,
  `pause_end` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `timer_stop_reasons_is_active_index` (`is_active`),
  KEY `timer_stop_reasons_timer_id_index` (`timer_id`),
  KEY `timer_stop_reasons_phrase_id_index` (`phrase_id`),
  KEY `timer_stop_reasons_location_is_active_index` (`location_id`,`is_active`),

  CONSTRAINT `timer_stop_reasons_phrase_id_foreign`
    FOREIGN KEY (`phrase_id`) REFERENCES `timer_pre_phrases`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;