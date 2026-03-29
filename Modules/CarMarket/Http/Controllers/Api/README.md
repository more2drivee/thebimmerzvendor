# CarMarket API Controllers

These controllers expose the buyer and seller REST endpoints for the CarMarket module.
All routes are under `/connector/api/carmarket` and require auth:api unless noted.

## BuyerVehicleController
Handles public marketplace browsing and buyer interactions.

- **GET /vehicles** — Search/list active vehicles with filters (make/model/year/price/mileage/body/fuel/transmission/color/location flags) and keyword search; supports sort & pagination with premium-first ordering.
- **GET /vehicles/{id}** — Vehicle detail with media, seller info, counts, view increment, similar vehicles, and `is_favorited` flag.
- **GET /filters** — Get filter options with pagination and search. Query params: `type` (brands|models|cities|colors|body_types|fuel_types|transmissions|conditions|year_range|price_range), `search` (search term), `per_page` (default 15), `page` (default 1), `brand_category_id` (for models only). Static filters return all options; dynamic filters return paginated results with `pagination` metadata.
- **POST /vehicles/{id}/inquiry** — Buyer sends inquiry (message, type, offered_price). Prevents seller inquiring on own listing. 
- **GET /buyer/inquiries** — Paginated list of buyer’s inquiries with vehicle and seller info.
- **POST /vehicles/{id}/favorite** — Toggle favorite; supports `notify_price_change` flag.
- **GET /buyer/favorites** — Paginated favorites with vehicle primary image and seller info.
- **POST /vehicles/{id}/report** — Report a listing (reason + details) -> pending review.
- **POST /buyer/saved-searches** — Save search filters with optional notifications.
- **GET /buyer/saved-searches** — List saved searches.
- **DELETE /buyer/saved-searches/{id}** — Delete saved search.
- **GET /featured** — Featured and latest vehicles for homepage cards.

## SellerVehicleController
Manages seller-owned listings, media, and inquiries.

- **GET /seller/vehicles** — List seller vehicles with counts; filter by `listing_status`; premium-first + pagination.
- **GET /seller/vehicles/{id}** — Seller view of a single vehicle with media, inquiries, favorites counts.
- **POST /seller/vehicles** — Create listing (validates vehicle specs, pricing, location). Sets status `pending` for approval.
- **PUT /seller/vehicles/{id}** — Update listing (blocked if sold). Any actual seller change now forces the listing back to `pending` and clears previous approval until admin approves again.
- **DELETE /seller/vehicles/{id}** — Delete seller listing.
- **POST /seller/vehicles/{id}/media** — Upload images (jpg/png/webp max 5MB each); marks first as primary.
- **DELETE /seller/vehicles/{vehicleId}/media/{mediaId}** — Delete media; reassigns primary if needed.
- **POST /seller/vehicles/{vehicleId}/media/{mediaId}/set-primary** — Set a primary image.
- **GET /seller/inquiries** — List inquiries on seller vehicles (filter by vehicle_id/status).
- **POST /seller/inquiries/{id}/reply** — Reply to inquiry; marks status `contacted`.
- **POST /seller/vehicles/{id}/mark-sold** — Mark listing sold (stores sold_price, buyer_contact_id, timestamps).
- **GET /seller/dashboard** — Seller KPIs (counts, views, inquiries) + recent inquiries.

## Shared Models/Traits Referenced
- `Vehicle` scopes: `forBusiness`, `forSeller`, `active`, `pending`, `featured`, `premiumFirst`; relations `primaryImage`, `media`, `seller`, `favorites`, `inquiries`, `brandCategory`, `deviceModel`, `auditLogs`; helpers like `incrementViews()`, `getSimilarVehicles()`, `getTitle()`, `getBrandName()`, `getModelName()`.
- `VehicleInquiry`, `Favorite`, `VehicleReport`, `SavedSearch`, `VehicleMedia`, `VehicleAuditLog` used for buyer/seller workflows.

## Brand & Model Fields (Cascading Dropdowns)
- **Primary**: `brand_category_id` (FK → `categories.id`) and `repair_device_model_id` (FK → `repair_device_models.id`)
- **Fallback**: `make` and `model_name` text fields for backward compatibility
- Vehicle title is built from: `brandCategory.name + deviceModel.name + year` with fallback to text fields
- Admin and seller forms use cascading dropdowns: brand → model (via `GET /brands/{brandId}/models`)
- API filters accept both new (`brand_category_id`, `repair_device_model_id`) and legacy (`make`, `model_name`) params

## Seller Edit Re-Approval + Change Log

When seller updates a listing via `PUT /seller/vehicles/{id}`:

1. Server computes exact changed fields (old value vs new value).
2. If no effective change is detected, update is skipped and response returns `No changes detected`.
3. If changes exist, listing is always moved to:
   - `listing_status = pending`
   - `approved_at = null`
   - `rejection_reason = null`
4. A new audit entry is written in `cm_vehicle_audit_logs` with:
   - who changed (`changed_by_user_id`, `changed_by_contact_id`)
   - changed field names
   - old/new values snapshots
   - request metadata (IP + user agent)

This ensures edited ads no longer stay mixed with active ads and require fresh admin approval.

### Update Response (seller)

Successful update now returns extra flags:

- `requires_reapproval: true`
- `audit_log: { id, changed_fields, created_at }`

## Admin Review Visibility

- Admin vehicle details page loads recent `auditLogs` so admin can see what seller changed before approving.
- Logs include field-by-field old/new values and change timestamp.

## Notes
- Buyer actions require `crm_contact_id`; unauthorized buyers get 403 responses.
- Sorting whitelist: `created_at`, `listing_price`, `year`, `mileage_km`, `view_count`; premium listings always prioritized.
- Pagination accepts `per_page`; `-1` returns full list where implemented.

## Test Data SQL Seed
Run this SQL to populate test data for testing endpoints. Adjust IDs (business_id, seller_contact_id, buyer_contact_id) to match your actual records.

```sql
-- NOTE: Requires brand_category_id and repair_device_model_id from categories and repair_device_models tables
-- Adjust category IDs (1, 2, 3) and model IDs (10, 20, 30) to match your actual database records

-- 1) Insert test vehicles (business_id=1, seller_contact_id=10)
INSERT INTO cm_vehicles
    (business_id, seller_contact_id, created_by, vin_number, plate_number,
     brand_category_id, repair_device_model_id, `year`, trim_level, body_type, color, mileage_km, engine_capacity_cc, cylinder_count, fuel_type, transmission,
     `condition`, factory_paint, imported_specs, license_type, condition_notes,
     listing_price, min_price, currency,
     license_3year_cost, insurance_annual_cost, insurance_rate_pct,
     location_city, location_area,
     listing_status, is_premium, is_featured, description, view_count,
     buyer_contact_id, rejection_reason,
     make, model_name,
     created_at, updated_at)
VALUES
    (1, 10, 1, 'WVWZZZ1KZ6W000001', 'ABC-1234',
     NULL, NULL, 2021, 'Sport', 'sedan', 'white', 32000, 1800, 4, 'gas', 'automatic',
     'used', 1, 0, 'seller_owned', 'Minor bumper repaint',
     620000.00, 600000.00, 'EGP',
     9500.00, 4800.00, 3.20,
     'Cairo', 'Nasr City',
     'active', 1, 1, 'Clean Corolla, single owner, serviced.', 12,
     NULL, NULL,
     'Toyota', 'Corolla',
     NOW(), NOW()),
    (1, 10, 1, 'JTDKN3DU0A0123456', 'XYZ-5678',
     NULL, NULL, 2019, 'xDrive40i', 'suv', 'black', 67000, 3000, 6, 'gas', 'automatic',
     'used', 0, 1, 'seller_owned', 'Dealer maintained, imported specs',
     1850000.00, 1800000.00, 'EGP',
     18000.00, 9200.00, 4.10,
     'Giza', 'Sheikh Zayed',
     'active', 0, 0, 'Full options, panoramic roof.', 5,
     NULL, NULL,
     'BMW', 'X5',
     NOW(), NOW()),
    (1, 10, 1, '1HGCM82633A123456', 'DEF-9012',
     NULL, NULL, 2022, 'EX', 'sedan', 'silver', 15000, 1500, 4, 'gas', 'automatic',
     'used', 1, 0, 'seller_owned', 'Excellent condition',
     750000.00, 720000.00, 'EGP',
     8800.00, 4500.00, 3.00,
     'Alexandria', 'Smouha',
     'active', 0, 1, 'Low mileage, one owner.', 8,
     NULL, NULL,
     'Honda', 'Accord',
     NOW(), NOW());

-- 2) Insert vehicle media (vehicle IDs assumed 1,2,3 from above)
INSERT INTO cm_vehicle_media (vehicle_id, file_path, is_primary, media_type, display_order, created_at, updated_at)
VALUES
    (1, 'uploads/carmarket/vehicles/1/front.jpg', 1, 'image', 1, NOW(), NOW()),
    (1, 'uploads/carmarket/vehicles/1/interior.jpg', 0, 'image', 2, NOW(), NOW()),
    (1, 'uploads/carmarket/vehicles/1/engine.jpg', 0, 'image', 3, NOW(), NOW()),
    (2, 'uploads/carmarket/vehicles/2/front.jpg', 1, 'image', 1, NOW(), NOW()),
    (2, 'uploads/carmarket/vehicles/2/rear.jpg', 0, 'image', 2, NOW(), NOW()),
    (3, 'uploads/carmarket/vehicles/3/front.jpg', 1, 'image', 1, NOW(), NOW());

-- 3) Insert buyer favorites (buyer_contact_id=20)
INSERT INTO cm_favorites (contact_id, vehicle_id, notify_price_change, created_at, updated_at)
VALUES
    (20, 1, 1, NOW(), NOW()),
    (20, 3, 0, NOW(), NOW());

-- 4) Insert vehicle inquiries (buyer_contact_id=20, business_id=1)
INSERT INTO cm_vehicle_inquiries
    (vehicle_id, buyer_contact_id, business_id, inquiry_type, status, message, offered_price, created_at, updated_at)
VALUES
    (1, 20, 1, 'info', 'new', 'Is the service history available?', 610000.00, NOW(), NOW()),
    (2, 20, 1, 'info', 'new', 'Can I schedule a test drive this weekend?', NULL, NOW(), NOW()),
    (3, 20, 1, 'info', 'contacted', 'Is the price negotiable?', 730000.00, NOW(), NOW());

-- 5) Insert saved searches (contact_id=20)
INSERT INTO cm_saved_searches (contact_id, name, filters, notify_new_matches, created_at, updated_at)
VALUES
    (20, 'Toyota sedans under 700k',
     '{"make":"Toyota","body_type":"sedan","listing_price_max":700000,"condition":"used"}',
     1, NOW(), NOW()),
    (20, 'BMW SUVs in Cairo',
     '{"make":"BMW","body_type":"suv","location_city":"Cairo"}',
     0, NOW(), NOW());

-- 6) Insert a vehicle report (reporter_contact_id=20)
INSERT INTO cm_vehicle_reports
    (vehicle_id, reporter_contact_id, reason, details, status, created_at, updated_at)
VALUES
    (2, 20, 'price_inaccurate', 'Listed price seems too low for this model year and mileage', 'pending', NOW(), NOW());
```

-- 1) cm_vehicles
CREATE TABLE `cm_vehicles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `seller_contact_id` BIGINT UNSIGNED NOT NULL,
  `buyer_contact_id` BIGINT UNSIGNED NULL,
  `vin_number` VARCHAR(80) NULL,
  `plate_number` VARCHAR(80) NULL,
  `brand_category_id` INT UNSIGNED NULL,
  `repair_device_model_id` INT UNSIGNED NULL,
  `make` VARCHAR(120) NOT NULL,
  `model_name` VARCHAR(120) NOT NULL,
  `year` INT UNSIGNED NOT NULL,
  `trim_level` VARCHAR(120) NULL,
  `body_type` VARCHAR(60) NULL,
  `color` VARCHAR(60) NULL,
  `mileage_km` INT UNSIGNED NULL,
  `engine_capacity_cc` INT UNSIGNED NULL,
  `cylinder_count` TINYINT UNSIGNED NULL,
  `fuel_type` VARCHAR(40) NULL,
  `transmission` VARCHAR(40) NULL,
  `condition` ENUM('new','used') DEFAULT 'used',
  `factory_paint` TINYINT(1) DEFAULT 0,
  `imported_specs` TINYINT(1) DEFAULT 0,
  `license_type` VARCHAR(60) NULL,
  `listing_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `min_price` DECIMAL(15,2) NULL,
  `currency` VARCHAR(10) DEFAULT 'EGP',
  `description` TEXT NULL,
  `condition_notes` TEXT NULL,
  `location_city` VARCHAR(120) NULL,
  `location_area` VARCHAR(120) NULL,
  `ownership_costs` JSON NULL,
  `license_3year_cost` DECIMAL(15,2) NULL,
  `insurance_annual_cost` DECIMAL(15,2) NULL,
  `insurance_rate_pct` DECIMAL(8,2) NULL,
  `is_premium` TINYINT(1) DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `listing_status` ENUM('draft','pending','active','sold','reserved','expired','rejected') DEFAULT 'pending',
  `rejection_reason` TEXT NULL,
  `expires_at` DATETIME NULL,
  `view_count` INT UNSIGNED DEFAULT 0,
  `media_count` INT UNSIGNED DEFAULT 0,
  `inquiries_count` INT UNSIGNED DEFAULT 0,
  `favorites_count` INT UNSIGNED DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  KEY `cm_vehicles_business_id_index` (`business_id`),
  KEY `cm_vehicles_seller_contact_id_index` (`seller_contact_id`),
  KEY `cm_vehicles_buyer_contact_id_index` (`buyer_contact_id`),
  KEY `cm_vehicles_listing_status_index` (`listing_status`),
  KEY `cm_vehicles_is_featured_index` (`is_featured`),
  KEY `cm_vehicles_is_premium_index` (`is_premium`),
  KEY `cm_vehicles_expires_at_index` (`expires_at`),
  KEY `cm_vehicles_brand_category_id_index` (`brand_category_id`),
  KEY `cm_vehicles_repair_device_model_id_index` (`repair_device_model_id`),
  CONSTRAINT `cm_vehicles_brand_category_id_foreign` FOREIGN KEY (`brand_category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `cm_vehicles_repair_device_model_id_foreign` FOREIGN KEY (`repair_device_model_id`) REFERENCES `repair_device_models`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) cm_vehicle_media
CREATE TABLE `cm_vehicle_media` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(40) NOT NULL DEFAULT 'image',
  `file_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `display_order` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  KEY `cm_vehicle_media_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `cm_vehicle_media_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `cm_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) cm_vehicle_inquiries
CREATE TABLE `cm_vehicle_inquiries` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `buyer_contact_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `inquiry_type` VARCHAR(60) DEFAULT 'info',
  `status` ENUM('new','contacted','negotiating','closed_won','closed_lost') DEFAULT 'new',
  `message` TEXT NULL,
  `offered_price` DECIMAL(15,2) NULL,
  `seller_reply` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  KEY `cm_vehicle_inquiries_vehicle_id_index` (`vehicle_id`),
  KEY `cm_vehicle_inquiries_buyer_contact_id_index` (`buyer_contact_id`),
  KEY `cm_vehicle_inquiries_status_index` (`status`),
  CONSTRAINT `cm_vehicle_inquiries_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `cm_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) cm_favorites
CREATE TABLE `cm_favorites` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `notify_price_change` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `cm_favorites_vehicle_contact_unique` (`vehicle_id`,`contact_id`),
  KEY `cm_favorites_contact_id_index` (`contact_id`),
  CONSTRAINT `cm_favorites_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `cm_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) cm_vehicle_reports
CREATE TABLE `cm_vehicle_reports` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `reporter_contact_id` BIGINT UNSIGNED NOT NULL,
  `reason` VARCHAR(120) NOT NULL,
  `details` TEXT NULL,
  `status` ENUM('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  KEY `cm_vehicle_reports_vehicle_id_index` (`vehicle_id`),
  KEY `cm_vehicle_reports_reporter_contact_id_index` (`reporter_contact_id`),
  KEY `cm_vehicle_reports_status_index` (`status`),
  CONSTRAINT `cm_vehicle_reports_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `cm_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) cm_saved_searches
CREATE TABLE `cm_saved_searches` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  [filters](cci:1://file:///d:/pos-main/altrapos/new_pos/pos/Modules/CarMarket/Http/Controllers/Api/BuyerVehicleController.php:165:4-209:5) JSON NOT NULL,
  `notify_new_matches` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  KEY `cm_saved_searches_contact_id_index` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `cm_vehicle_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `changed_by_user_id` BIGINT UNSIGNED NULL,
    `changed_by_contact_id` BIGINT UNSIGNED NULL,
    `change_source` VARCHAR(30) NOT NULL DEFAULT 'seller_api',
    `action` VARCHAR(80) NOT NULL DEFAULT 'seller_updated_listing',
    `changed_fields` JSON NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `notes` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_business_vehicle` (`business_id`, `vehicle_id`),
    INDEX `idx_vehicle_id` (`vehicle_id`),
    INDEX `idx_change_source` (`change_source`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
