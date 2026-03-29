# CarMarket allFilters API Example

## Endpoint
**GET /connector/api/carmarket/all-filters**

## Description
Returns all available filter options for vehicle search in a single request. This endpoint provides comprehensive filter data including brands, models, cities, colors, year/price ranges, body types, fuel types, transmissions, and conditions.

## Request Example
```
GET /connector/api/carmarket/all-filters?search=toy
```

## Response Example
```json
{
    "success": true,
    "data": {
        "brands": [
            {"id": 1, "name": "Toyota"},
            {"id": 5, "name": "Mitsubishi"}
        ],
        "models": [
            {"id": 15, "name": "Camry", "brand_category_id": 1},
            {"id": 16, "name": "Camry Hybrid", "brand_category_id": 1},
            {"id": 45, "name": "Lancer", "brand_category_id": 5}
        ],
        "cities": [
            "Cairo",
            "Alexandria",
            "Giza"
        ],
        "colors": [
            "Black",
            "White",
            "Silver",
            "Blue"
        ],
        "year_range": {
            "min": 2010,
            "max": 2024
        },
        "price_range": {
            "min": 50000,
            "max": 2500000
        },
        "body_types": [
            "sedan",
            "suv",
            "coupe",
            "hatchback",
            "truck"
        ],
        "fuel_types": [
            "gas",
            "diesel",
            "electric",
            "hybrid"
        ],
        "transmissions": [
            "automatic",
            "manual"
        ],
        "conditions": [
            "new",
            "used"
        ]
    }
}
```

## Query Parameters

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `search` | optional | Search term to filter all results (applies to text-based filters) | empty string |

## Response Structure

- **brands**: Array of brand objects with `id` and `name`
- **models**: Array of model objects with `id`, `name`, and `brand_category_id`
- **cities**: Array of city names (strings)
- **colors**: Array of color names (strings)
- **year_range**: Object with `min` and `max` year values
- **price_range**: Object with `min` and `max` price values
- **body_types**: Array of body type names (strings)
- **fuel_types**: Array of fuel type names (strings)
- **transmissions**: Array of transmission types (strings)
- **conditions**: Array of condition names (strings)

## Usage Notes

- All filters are scoped to the authenticated user's business_id
- Only active vehicles are considered for filter options
- Search parameter applies case-insensitive `LIKE` filtering to text fields
- Range filters (year_range, price_range) return min/max values from active vehicles
- Empty arrays/objects are returned when no matching data exists
- This endpoint is ideal for initializing filter dropdowns in a single API call
