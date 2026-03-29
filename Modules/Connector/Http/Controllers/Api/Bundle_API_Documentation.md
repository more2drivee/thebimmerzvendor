# Bundle & Generic Spare Parts API Documentation

## Overview
This API provides endpoints for managing bundle selling transactions and generic spare parts catalog. It allows users to create virtual products for bundle parts and automatically generates sell transactions with product_joborder records.

## Authentication
All endpoints require API authentication with `auth:api` and `timezone` middleware.

## Base URL
```
/connector/api
```

---

## Endpoints

### 1. Get Available Bundles

**GET** `/connector/api/bundles`

Retrieves list of active bundles available for the user's location.

#### Parameters
None

#### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "reference_no": "BND_2016M180R-234",
      "price": 1500.00,
      "description": "Front half of Toyota Camry 2016"
    }
  ]
}
```

#### Response Fields
- `id` - Bundle ID
- `reference_no` - Bundle reference number
- `price` - Bundle cost price
- `description` - Bundle description

---

### 2. Get Generic Spare Parts Catalog

**GET** `/connector/api/generic-spare-parts`

Retrieves list of generic spare parts for the user's business.

#### Parameters
None

#### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Front Bumper",
      "description": "Front bumper assembly"
    },
    {
      "id": 2,
      "name": "Headlight Assembly",
      "description": "Complete headlight unit"
    }
  ]
}
```

#### Response Fields
- `id` - Generic spare part ID
- `name` - Part name
- `description` - Part description (nullable)

---

### 3. Create Generic Spare Part

**POST** `/connector/api/generic-spare-parts`

Creates a new generic spare part in the catalog.

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | Part name (max 255 chars) |
| description | string | No | Part description |

#### Request Example
```json
{
  "name": "Rear Bumper",
  "description": "Rear bumper assembly"
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "Rear Bumper",
    "description": "Rear bumper assembly"
  }
}
```

---

### 4. Create Bundle Virtual Product & Transaction

**POST** `/connector/api/bundles/virtual-product`

Creates a virtual product for a bundle part and generates a complete sell transaction with product_joborder record.

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| bundle_id | integer | Yes | Bundle ID |
| name | string | Yes | Product name (max 255 chars) |
| qty | decimal | Yes | Quantity (min 0.01) |
| price | decimal | No | Unit price (default 0) |
| job_order_id | integer | Yes | Job order ID (required to link to existing transaction) |

#### Request Example
```json
{
  "bundle_id": 1,
  "name": "Front Bumper",
  "qty": 2,
  "price": 150.00,
  "job_order_id": 123
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "transaction": {
      "id": 456,
      "invoice_no": 789,
      "final_total": 300.00,
      "transaction_date": "2026-01-12T12:00:00.000000Z"
    },
    "transaction_sell_line": {
      "id": 789,
      "quantity": 2,
      "unit_price": 150.00,
      "line_total": 300.00
    },
    "product": {
      "id": 123,
      "name": "Front Bumper",
      "sku": "BND-1-A1B2C3"
    },
    "variation_id": 456,
    "bundle": {
      "id": 1,
      "reference_no": "BND_2016M180R-234"
    },
    "job_order_id": 123
  }
}
```

#### Response Fields
- **transaction** - Main transaction details
  - `id` - Transaction ID
  - `invoice_no` - Invoice number
  - `final_total` - Total amount
  - `transaction_date` - Transaction date
- **transaction_sell_line** - Sell line details
  - `id` - Sell line ID
  - `quantity` - Quantity sold
  - `unit_price` - Unit price
  - `line_total` - Line total
- **product** - Virtual product details
  - `id` - Product ID
  - `name` - Product name
  - `sku` - Product SKU
- **variation_id** - Product variation ID
- **bundle** - Bundle information
  - `id` - Bundle ID
  - `reference_no` - Bundle reference number
- **job_order_id** - Job order ID (if provided)

#### Process Flow
1. Creates virtual product with `enable_stock = 0` and `virtual_product = 1`
2. Generates SKU: `BND-{bundle_id}-{random}`
3. Creates dummy product variation
4. Finds existing job order transaction by `job_order_id`
5. Creates transaction sell line linked to the existing transaction
6. Updates transaction totals (final_total, total_before_tax)
7. Inserts product_joborder record with status flags
8. Syncs purchase/sell mappings for the job order transaction
9. Returns complete transaction data

#### Product Job Order Record
The system automatically creates a product_joborder record with:
- `delivered_status = 1`
- `out_for_deliver = 1`
- `client_approval = 1`
- `product_status = 'black'`
- `price` and `purchase_price` set to provided price

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "errors": {
    "bundle_id": ["The bundle id field is required."],
    "name": ["The name field is required."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Bundle not found"
}
```

### Unauthorized (403)
```json
{
  "error": {
    "message": "Location not assigned"
  }
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Something went wrong"
}
```

---

## Usage Examples

### Complete Bundle Selling Flow

1. **Get available bundles:**
   ```bash
   GET /connector/api/bundles
   ```

2. **Create virtual product and transaction:**
   ```bash
   POST /connector/api/bundles/virtual-product
   ```

3. **Response contains:**
   - Transaction details for invoice
   - Product information for inventory
   - Job order integration for repair workflow

---

## Integration Notes

- **Virtual Products**: All created products are virtual (`virtual_product = 1`) with stock tracking disabled (`enable_stock = 0`)
- **Bundle Tracking**: TransactionSellLine records include `bundle_id` for tracking bundle parts sales
- **Job Order Integration**: Optional `job_order_id` links to repair workflow and syncs with existing job sheet transactions
- **Invoice Numbers**: Generated using standard POS invoice numbering system
- **Transaction Status**: Created as `final` with `payment_status = 'due'`

---

## Database Tables Affected

- `products` - Virtual product creation
- `product_variations` - Dummy variation records
- `variations` - Product variations with pricing
- `transactions` - Sell transactions
- `transaction_sell_lines` - Transaction line items
- `product_joborder` - Job order product records
