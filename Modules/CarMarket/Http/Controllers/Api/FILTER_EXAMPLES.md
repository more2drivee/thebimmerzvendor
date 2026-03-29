# CarMarket Filter API Examples

## 1. Brands (Dynamic - Paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=brands&search=toy&per_page=5&page=1
```

**Response:**
```json
{
    "success": true,
    "type": "brands",
    "data": [
        {"id": 1, "name": "Toyota"},
        {"id": 5, "name": "Mitsubishi"},
        {"id": 8, "name": "Matiz"}
    ],
    "pagination": {
        "total": 3,
        "per_page": 5,
        "current_page": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

---

## 2. Models (Dynamic - Paginated, can filter by brand)
**Request:**
```
GET /connector/api/carmarket/filters?type=models&brand_category_id=1&search=cam&per_page=3&page=1
```

**Response:**
```json
{
    "success": true,
    "type": "models",
    "data": [
        {"id": 15, "name": "Camry", "brand_category_id": 1},
        {"id": 16, "name": "Camry Hybrid", "brand_category_id": 1}
    ],
    "pagination": {
        "total": 2,
        "per_page": 3,
        "current_page": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

**Without brand filter (all models):**
```
GET /connector/api/carmarket/filters?type=models&search=cor&per_page=5&page=1
```

---

## 3. Cities (Dynamic - Paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=cities&search=cai&per_page=5&page=1
```

**Response:**
```json
{
    "success": true,
    "type": "cities",
    "data": [
        "Cairo",
        "Cairo New City"
    ],
    "pagination": {
        "total": 2,
        "per_page": 5,
        "current_page": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

---

## 4. Colors (Dynamic - Paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=colors&search=bla&per_page=5&page=1
```

**Response:**
```json
{
    "success": true,
    "type": "colors",
    "data": [
        "Black",
        "Black Metallic"
    ],
    "pagination": {
        "total": 2,
        "per_page": 5,
        "current_page": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

---

## 5. Body Types (Static - Not paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=body_types&search=sed
```

**Response:**
```json
{
    "success": true,
    "type": "body_types",
    "data": [
        "sedan"
    ]
}
```

**Without search:**
```
GET /connector/api/carmarket/filters?type=body_types
```

**Response:**
```json
{
    "success": true,
    "type": "body_types",
    "data": [
        "sedan",
        "suv",
        "coupe",
        "hatchback",
        "truck",
        "van",
        "convertible",
        "wagon",
        "pickup",
        "other"
    ]
}
```

---

## 6. Fuel Types (Static - Not paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=fuel_types
```

**Response:**
```json
{
    "success": true,
    "type": "fuel_types",
    "data": [
        "gas",
        "diesel",
        "electric",
        "hybrid",
        "natural_gas"
    ]
}
```

---

## 7. Transmissions (Static - Not paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=transmissions
```

**Response:**
```json
{
    "success": true,
    "type": "transmissions",
    "data": [
        "automatic",
        "manual"
    ]
}
```

---

## 8. Conditions (Static - Not paginated)
**Request:**
```
GET /connector/api/carmarket/filters?type=conditions
```

**Response:**
```json
{
    "success": true,
    "type": "conditions",
    "data": [
        "new",
        "used"
    ]
}
```

---

## 9. Year Range (Special - Returns min/max)
**Request:**
```
GET /connector/api/carmarket/filters?type=year_range
```

**Response:**
```json
{
    "success": true,
    "type": "year_range",
    "data": {
        "min": 2010,
        "max": 2024
    }
}
```

---

## 10. Price Range (Special - Returns min/max)
**Request:**
```
GET /connector/api/carmarket/filters?type=price_range
```

**Response:**
```json
{
    "success": true,
    "type": "price_range",
    "data": {
        "min": 50000,
        "max": 2500000
    }
}
```

---

## Error Cases

**Invalid filter type:**
```
GET /connector/api/carmarket/filters?type=invalid_type
```

**Response:**
```json
{
    "success": false,
    "msg": "Invalid filter type"
}
```

---

## Query Parameters Summary

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `type` | required | Filter type (brands, models, cities, colors, body_types, fuel_types, transmissions, conditions, year_range, price_range) | - |
| `search` | optional | Search term to filter results | empty string |
| `per_page` | optional | Number of results per page (for dynamic filters) | 15 |
| `page` | optional | Page number (for dynamic filters) | 1 |
| `brand_category_id` | optional | Filter models by brand ID (only for models type) | null |

## Notes

- **Dynamic filters** (brands, models, cities, colors) support pagination and search
- **Static filters** (body_types, fuel_types, transmissions, conditions) return all options with optional search filtering
- **Range filters** (year_range, price_range) return min/max values only
- Search is case-insensitive and uses `LIKE` matching
- Pagination metadata is only included for dynamic filters
