-- =========================================
-- DROP TABLES IF THEY EXIST (ORDERED)
-- =========================================
DROP TABLE IF EXISTS `checkcar_inspection_documents`;
DROP TABLE IF EXISTS `checkcar_inspection_items`;
DROP TABLE IF EXISTS `checkcar_element_options`;
DROP TABLE IF EXISTS `checkcar_elements`;
DROP TABLE IF EXISTS `checkcar_question_subcategories`;
DROP TABLE IF EXISTS `checkcar_question_categories`;
DROP TABLE IF EXISTS `checkcar_phrase_templates`;
DROP TABLE IF EXISTS `checkcar_inspections`;
DROP TABLE IF EXISTS `checkcar_service_settings`;
DROP TABLE IF EXISTS `privacy_policies`;

-- =========================================
-- 1) checkcar_inspections
-- =========================================
CREATE TABLE `checkcar_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,

  -- Location and ownership
  `location_id` int(10) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,

  -- Booking / Job sheet
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `job_sheet_id` bigint(20) unsigned DEFAULT NULL,

  -- Linked contacts & vehicle (FKs only, data fetched from other tables)
  `buyer_contact_id` bigint(20) unsigned DEFAULT NULL,
  `seller_contact_id` bigint(20) unsigned DEFAULT NULL,
  `contact_device_id` int(10) unsigned DEFAULT NULL,

  -- Inspection team & legacy sections JSON
  `inspection_team` json DEFAULT NULL,
  `sections` json DEFAULT NULL,

  -- Final report
  `final_summary` text DEFAULT NULL,
  `overall_rating` tinyint(3) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',

  `share_token` varchar(255) DEFAULT NULL,
  `policy_approved` tinyint(1) NOT NULL DEFAULT '0',

  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `checkcar_inspections_share_token_unique` (`share_token`),
  KEY `checkcar_inspections_location_id_index` (`location_id`),
  KEY `checkcar_inspections_created_by_index` (`created_by`),
  KEY `checkcar_inspections_booking_id_index` (`booking_id`),
  KEY `checkcar_inspections_job_sheet_id_index` (`job_sheet_id`),
  KEY `checkcar_inspections_buyer_contact_id_index` (`buyer_contact_id`),
  KEY `checkcar_inspections_seller_contact_id_index` (`seller_contact_id`),
  KEY `checkcar_inspections_contact_device_id_index` (`contact_device_id`),
  KEY `checkcar_inspections_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2) checkcar_question_categories
-- =========================================
CREATE TABLE `checkcar_question_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `section_key` varchar(50) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_question_categories_section_key_index` (`section_key`),
  KEY `checkcar_question_categories_sort_order_index` (`sort_order`),
  KEY `checkcar_question_categories_sort_order_active_index` (`sort_order`,`active`),
  KEY `checkcar_question_categories_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 3) checkcar_question_subcategories
-- =========================================
CREATE TABLE `checkcar_question_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_question_subcategories_category_id_foreign` (`category_id`),
  KEY `checkcar_question_subcategories_sort_order_index` (`sort_order`),
  KEY `checkcar_question_subcategories_cat_loc_sort_active_index` (`category_id`,`location_id`),

  CONSTRAINT `checkcar_question_subcategories_category_id_foreign`
    FOREIGN KEY (`category_id`) REFERENCES `checkcar_question_categories`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 4) checkcar_elements
-- =========================================
CREATE TABLE `checkcar_elements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'text',
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `subcategory_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `max_options` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_elements_category_id_foreign` (`category_id`),
  KEY `checkcar_elements_subcategory_id_foreign` (`subcategory_id`),
  KEY `checkcar_elements_sort_order_index` (`sort_order`),
  KEY `checkcar_elements_category_sort_active_index` (`category_id`,`sort_order`,`active`),
  KEY `checkcar_elements_subcategory_sort_active_index` (`subcategory_id`,`sort_order`,`active`),
  KEY `checkcar_elements_location_id_index` (`location_id`),

  CONSTRAINT `checkcar_elements_category_id_foreign`
    FOREIGN KEY (`category_id`) REFERENCES `checkcar_question_categories`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `checkcar_elements_subcategory_id_foreign`
    FOREIGN KEY (`subcategory_id`) REFERENCES `checkcar_question_subcategories`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 5) checkcar_phrase_templates
-- =========================================
CREATE TABLE `checkcar_phrase_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,
  `section_key` varchar(50) NOT NULL,
  `phrase` text NOT NULL,
  `element_id` bigint(20) unsigned DEFAULT NULL,
  `preset_key` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_phrase_templates_section_key_index` (`section_key`),
  KEY `checkcar_phrase_templates_element_id_index` (`element_id`),
  KEY `checkcar_phrase_templates_preset_key_index` (`preset_key`),
  KEY `checkcar_phrase_templates_location_id_index` (`location_id`),

  CONSTRAINT `checkcar_phrase_templates_element_id_foreign`
    FOREIGN KEY (`element_id`) REFERENCES `checkcar_elements`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 6) checkcar_element_options
-- =========================================
CREATE TABLE `checkcar_element_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,
  `element_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_element_options_element_id_index` (`element_id`),
  KEY `checkcar_element_options_sort_order_index` (`sort_order`),
  KEY `checkcar_element_options_element_sort_index` (`element_id`,`sort_order`),
  KEY `checkcar_element_options_location_id_index` (`location_id`),

  CONSTRAINT `checkcar_element_options_element_id_foreign`
    FOREIGN KEY (`element_id`) REFERENCES `checkcar_elements`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7) checkcar_inspection_items
-- =========================================
CREATE TABLE `checkcar_inspection_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,

  `inspection_id` bigint(20) unsigned NOT NULL,
  `element_id` bigint(20) unsigned NOT NULL,

  `title` varchar(255) DEFAULT NULL,
  `option_ids` json DEFAULT NULL,
  `note` text DEFAULT NULL,
  `images` json DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_inspection_items_inspection_element_index` (`inspection_id`,`element_id`),
  KEY `checkcar_inspection_items_loc_insp_index` (`location_id`,`inspection_id`),

  CONSTRAINT `checkcar_inspection_items_inspection_id_foreign`
    FOREIGN KEY (`inspection_id`) REFERENCES `checkcar_inspections`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `checkcar_inspection_items_element_id_foreign`
    FOREIGN KEY (`element_id`) REFERENCES `checkcar_elements`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8) checkcar_inspection_documents
-- =========================================
CREATE TABLE `checkcar_inspection_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(10) unsigned DEFAULT NULL,

  `inspection_id` bigint(20) unsigned NOT NULL,
  `party` varchar(20) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(50) DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `checkcar_inspection_documents_insp_party_type_index` (`inspection_id`,`party`,`document_type`),
  KEY `checkcar_inspection_documents_loc_insp_index` (`location_id`,`inspection_id`),

  CONSTRAINT `checkcar_inspection_documents_inspection_id_foreign`
    FOREIGN KEY (`inspection_id`) REFERENCES `checkcar_inspections`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 9) checkcar_service_settings
-- =========================================
CREATE TABLE `checkcar_service_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL COMMENT 'Selected product for checkcar',
  `type` varchar(50) NOT NULL DEFAULT 'service' COMMENT 'Context/type of service setting',
  `value` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `checkcar_service_settings_business_type_unique` (`business_id`,`type`),

  KEY `checkcar_service_settings_business_id_foreign` (`business_id`),
  KEY `checkcar_service_settings_product_id_foreign` (`product_id`),

  CONSTRAINT `checkcar_service_settings_business_id_foreign`
    FOREIGN KEY (`business_id`) REFERENCES `business`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `checkcar_service_settings_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 10) privacy_policies (used by CheckCar)
-- =========================================
CREATE TABLE `privacy_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(10) unsigned NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `privacy_policies_business_id_index` (`business_id`),

  CONSTRAINT `privacy_policies_business_id_foreign`
    FOREIGN KEY (`business_id`) REFERENCES `business`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;