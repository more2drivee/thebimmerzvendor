# Vendors Products API Documentation

## Base URL
```
/api/vendors/product-by-vendor
```

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/vendors/product-by-vendor` | List all vendor products |
| POST | `/api/vendors/product-by-vendor/store` | Create vendor product |
| GET | `/api/vendors/product-by-vendor/{id}` | Show vendor product details |
| PUT | `/api/vendors/product-by-vendor/{id}` | Update vendor product |
| DELETE | `/api/vendors/product-by-vendor/{id}` | Delete vendor product |

---

## 1. Get All Vendor Products

```
GET /api/vendors/product-by-vendor
```

**Response:**
```json
[
    {
        "id": 1,
        "product_id": 10,
        "Vendor_id": 5,
        "Product_price": 1500.00,
        "warranty_id": 2,
        "shipping_information": "Free shipping",
        "Return_policy": "30 days return",
        "Country_of_Origin": "China",
        "product_specifications": "CPU: Intel i7, RAM: 16GB",
        "key_features": "15.6 inch display, 10hr battery",
        "product_condition": "New",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "product": {
            "id": 10,
            "name": "Laptop Dell XPS 15",
            "sku": "DELL-XPS-15"
        },
        "warranty": {
            "id": 2,
            "name": "2 Years Warranty"
        }
    }
]
```

---

## 2. Create Vendor Product (STORE)

```
POST /api/vendors/product-by-vendor/store
```

**Request Body:**
```json
{
    "product_id": 10,
    "Vendor_id": 5,
    "Product_price": 1500.00,
    "warranty_id": 2,
    "shipping_information": "Free shipping within 3 days",
    "Return_policy": "30 days money back guarantee",
    "Country_of_Origin": "China",
    "product_specifications": "CPU: Intel i7, RAM: 16GB, Storage: 512GB SSD",
    "key_features": "15.6 inch 4K display, 10 hours battery",
    "product_condition": "Brand New"
}
```

**Required Fields:**
| Field | Type | Description |
|-------|------|-------------|
| product_id | integer | Product ID from products table |
| Vendor_id | integer | Vendor ID |
| Product_price | numeric | Price (decimal) |

**Optional Fields:**
| Field | Type | Description |
|-------|------|-------------|
| warranty_id | integer | Warranty ID |
| shipping_information | string | Shipping details |
| Return_policy | string | Return policy text |
| Country_of_Origin | string | Origin country |
| product_specifications | text | Product specs (saved as string) |
| key_features | text | Key features (saved as string) |
| product_condition | varchar(255) | Product condition (saved as string) |

**Success Response (201):**
```json
{
    "id": 25,
    "product_id": 10,
    "Vendor_id": 5,
    "Product_price": 1500.00,
    "warranty_id": 2,
    "shipping_information": "Free shipping within 3 days",
    "Return_policy": "30 days money back guarantee",
    "Country_of_Origin": "China",
    "product_specifications": "CPU: Intel i7, RAM: 16GB, Storage: 512GB SSD",
    "key_features": "15.6 inch 4K display, 10 hours battery",
    "product_condition": "Brand New",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

**Note:** Also saves to `products` table based on `product_id`.

---

## 3. Get Single Vendor Product

```
GET /api/vendors/product-by-vendor/{id}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | ProductByVendor record ID |

**Response:**
```json
{
    "id": 25,
    "product_id": 10,
    "Vendor_id": 5,
    "Product_price": 1500.00,
    "warranty_id": 2,
    "shipping_information": "Free shipping within 3 days",
    "Return_policy": "30 days money back guarantee",
    "Country_of_Origin": "China",
    "product_specifications": "CPU: Intel i7, RAM: 16GB, Storage: 512GB SSD",
    "key_features": "15.6 inch 4K display, 10 hours battery",
    "product_condition": "Brand New",
    "product": {
        "id": 10,
        "name": "Laptop Dell XPS 15",
        "sku": "DELL-XPS-15"
    },
    "warranty": {
        "id": 2,
        "name": "2 Years Warranty",
        "duration": 24,
        "duration_type": "months"
    }
}
```

---

## 4. Update Vendor Product

```
PUT /api/vendors/product-by-vendor/{id}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | ProductByVendor record ID |

**Request Body:**
```json
{
    "product_id": 10,
    "Vendor_id": 5,
    "Product_price": 1400.00,
    "warranty_id": 3,
    "shipping_information": "Updated shipping info",
    "Return_policy": "Updated return policy",
    "Country_of_Origin": "USA",
    "product_specifications": "Updated specs: CPU Intel i9",
    "key_features": "Updated features: OLED display",
    "product_condition": "Refurbished"
}
```

**Fields:** All fields are optional. Only send fields you want to update.

**Success Response:**
```json
{
    "id": 25,
    "product_id": 10,
    "Vendor_id": 5,
    "Product_price": 1400.00,
    "warranty_id": 3,
    "shipping_information": "Updated shipping info",
    "Return_policy": "Updated return policy",
    "Country_of_Origin": "USA",
    "product_specifications": "Updated specs: CPU Intel i9",
    "key_features": "Updated features: OLED display",
    "product_condition": "Refurbished",
    "updated_at": "2024-01-15T12:00:00.000000Z"
}
```

**Note:** Also updates the `products` table.

---

## 5. Delete Vendor Product

```
DELETE /api/vendors/product-by-vendor/{id}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | ProductByVendor record ID |

**Success Response (204):**
```json
{
    "message": "Deleted successfully"
}
```

---

## Error Responses

### Validation Error (422):
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "product_id": ["The product id field is required."],
        "Vendor_id": ["The Vendor id field is required."],
        "Product_price": ["The Product price field is required."]
    }
}
```

### Not Found (404):
```json
{
    "message": "No query results for model [App\\Models\\ProductToVendor]"
}
```

### Server Error (500):
```json
{
    "message": "Internal Server Error"
}
```

---

## Headers Required

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {your_token}
```

---

## Database Table: productstovendor

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_id | bigint | Foreign key to products table |
| Vendor_id | bigint | Vendor ID |
| Product_price | decimal | Product price |
| warranty_id | bigint | Foreign key to warranties table |
| shipping_information | text | Shipping details |
| Return_policy | text | Return policy |
| Country_of_Origin | varchar(255) | Origin country |
| product_specifications | text | Product specifications (string) |
| key_features | text | Key features (string) |
| product_condition | varchar(255) | Product condition (string) |
| created_at | timestamp | Created timestamp |
| updated_at | timestamp | Updated timestamp |
