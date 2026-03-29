# Public E-commerce API Examples

## 1. List E-commerce Products (No Authentication Required)

### Endpoint
```
GET /connector/api/public/ecom-products
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| business_id | integer | Yes | Business ID to filter products |
| category_id | integer | No | Filter by category |
| brand_id | integer | No | Filter by brand |
| q | string | No | Search term for name or SKU (max 191 chars) |
| per_page | integer | No | Items per page (1-200, default: 20) |

### Example Request
```bash
curl -X GET "http://your-domain.com/connector/api/public/ecom-products?business_id=1&per_page=20"
```

### Example Response
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 42,
      "name": "Brake Pad Set Front",
      "sku": "BP-001",
      "variation_id": 15,
      "default_sell_price": 150.00,
      "brand_id": 5,
      "category_id": 12,
      "sub_category_id": null,
      "weight": "2.5",
      "shipping_details": "Fragile item",
      "image": "brake_pad_front.jpg",
      "image_url": "http://your-domain.com/uploads/img/brake_pad_front.jpg"
    },
    {
      "id": 43,
      "name": "Oil Filter",
      "sku": "OF-002",
      "variation_id": 16,
      "default_sell_price": 25.00,
      "brand_id": 7,
      "category_id": 12,
      "sub_category_id": null,
      "weight": "0.5",
      "shipping_details": null,
      "image": "oil_filter.jpg",
      "image_url": "http://your-domain.com/uploads/img/oil_filter.jpg"
    }
  ],
  "first_page_url": "http://your-domain.com/connector/api/public/ecom-products?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "http://your-domain.com/connector/api/public/ecom-products?page=3",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "http://your-domain.com/connector/api/public/ecom-products?page=1",
      "label": "1",
      "active": true
    },
    {
      "url": "http://your-domain.com/connector/api/public/ecom-products?page=2",
      "label": "2",
      "active": false
    },
    {
      "url": "http://your-domain.com/connector/api/public/ecom-products?page=3",
      "label": "3",
      "active": false
    },
    {
      "url": "http://your-domain.com/connector/api/public/ecom-products?page=2",
      "label": "Next &raquo;",
      "active": false
    }
  ],
  "next_page_url": "http://your-domain.com/connector/api/public/ecom-products?page=2",
  "path": "http://your-domain.com/connector/api/public/ecom-products",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 45
}
```

### Search Example
```bash
curl -X GET "http://your-domain.com/connector/api/public/ecom-products?business_id=1&q=brake&per_page=10"
```

### Filter by Category
```bash
curl -X GET "http://your-domain.com/connector/api/public/ecom-products?business_id=1&category_id=12"
```

---

## 2. Create Proforma Transaction (Authentication Required)

### Endpoint
```
POST /connector/api/sell/proforma
```

### Headers
```
Authorization: Bearer {your_access_token}
Content-Type: application/json
```

### Request Body Structure
```json
{
  "sells": [
    {
      "contact_id": 123,
      "transaction_date": "2026-02-18 12:00:00",
      "shipping_details": "Express delivery",
      "shipping_address": "123 Main St, City, Country",
      "shipping_status": "ordered",
      "delivered_to": "John Doe",
      "shipping_charges": 15.00,
      "products": [
        {
          "product_id": 42,
          "variation_id": 15,
          "quantity": 2,
          "unit_price": 150.00,
          "discount_amount": 0,
          "discount_type": "fixed"
        }
      ]
    }
  ]
}
```

### Example Request
```bash
curl -X POST "http://your-domain.com/connector/api/sell/proforma" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "sells": [
      {
        "contact_id": 123,
        "transaction_date": "2026-02-18 12:00:00",
        "shipping_details": "Express delivery",
        "shipping_address": "123 Main St, City, Country",
        "shipping_status": "ordered",
        "delivered_to": "John Doe",
        "shipping_charges": 15.00,
        "products": [
          {
            "product_id": 42,
            "variation_id": 15,
            "quantity": 2,
            "unit_price": 150.00,
            "discount_amount": 0,
            "discount_type": "fixed"
          }
        ]
      }
    ]
  }'
```

### Example Response
```json
[
  {
    "id": 789,
    "business_id": 1,
    "location_id": 1,
    "type": "sell",
    "status": "draft",
    "sub_status": "proforma",
    "payment_status": "due",
    "contact_id": 123,
    "invoice_no": "AS0078",
    "transaction_date": "2026-02-18 12:00:00",
    "final_total": "300.0000",
    "created_by": 5,
    "sell_lines": [
      {
        "id": 1567,
        "product_id": 42,
        "variation_id": 15,
        "quantity": "2.0000",
        "unit_price": "150.0000"
      }
    ],
    "payment_lines": [],
    "invoice_url": "http://your-domain.com/invoice/abc123xyz",
    "payment_link": "http://your-domain.com/pay/abc123xyz"
  }
]
```

### Multiple Products Example
```json
{
  "sells": [
    {
      "contact_id": 123,
      "transaction_date": "2026-02-18 12:00:00",
      "shipping_details": "Standard delivery",
      "shipping_address": "456 Oak Ave, Town, Country",
      "shipping_status": "packed",
      "delivered_to": "Jane Smith",
      "shipping_charges": 10.00,
      "products": [
        {
          "product_id": 42,
          "variation_id": 15,
          "quantity": 2,
          "unit_price": 150.00
        },
        {
          "product_id": 43,
          "variation_id": 16,
          "quantity": 5,
          "unit_price": 25.00,
          "discount_amount": 10,
          "discount_type": "fixed"
        }
      ]
    }
  ]
}
```

### Validation Errors Example
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "sells.0.contact_id": [
      "The sells.0.contact_id field is required."
    ],
    "sells.0.products": [
      "The sells.0.products field is required."
    ]
  }
}
```

---

## Notes

### E-commerce Flag Column Detection
The `products()` endpoint automatically detects which column marks products as e-commerce:
- Checks for `is_ecom` column first
- Falls back to `ecom_active_in_store` if `is_ecom` doesn't exist
- If neither exists, no e-commerce filter is applied

### Proforma Transaction Behavior
- **Status:** `draft`
- **Sub-status:** `proforma`
- **Stock Impact:** None (stock is only decreased for `final` or `under processing` transactions)
- **Payments:** Not processed (empty array)
- **Payment Status:** `due`

### Authentication
- Products endpoint: No authentication required
- Proforma sell endpoint: Requires valid Bearer token (auth:api middleware)

### Pagination
- Default: 20 items per page
- Max: 200 items per page
- Use `page` query parameter for pagination
