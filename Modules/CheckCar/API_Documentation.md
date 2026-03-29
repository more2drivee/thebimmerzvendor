# CheckCar API Documentation

## Overview
The CheckCar API provides endpoints for managing car inspections, including retrieving the inspection structure, creating/updating inspections, and viewing inspection reports.

**Base URL**: `{base_url}/connector/api/checkcar`

**Authentication**: All endpoints require API authentication (Bearer token)

---

## 1. Get Inspection Structure

Retrieves the complete inspection structure with categories, subcategories, elements, options, and presets.

### Endpoint
```
GET /connector/api/checkcar/structure
```

### Response
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Engine",
        "elements": [
          {
            "id": 10,
            "name": "Engine Oil Level",
            "required": true,
            "max_options": 1,
            "options": [
              {"id": 1, "type": "dropdown", "label": "Good"},
              {"id": 2, "type": "dropdown", "label": "Low"},
              {"id": 3, "type": "dropdown", "label": "Very Low"}
            ],
            "presets": [
              {"id": 5, "label": "Normal", "phrase": "Oil level is within normal range"},
              {"id": 6, "label": "Needs Attention", "phrase": "Oil level is low, needs to be refilled"}
            ]
          }
        ],
        "subcategories": [
          {
            "id": 2,
            "name": "Cooling System",
            "elements": [
              {
                "id": 15,
                "name": "Coolant Level",
                "required": true,
                "max_options": 0,
                "options": [
                  {"id": 8, "type": "checkbox", "label": "Level OK"},
                  {"id": 9, "type": "checkbox", "label": "Needs Refill"},
                  {"id": 10, "type": "text", "label": "Notes"}
                ],
                "presets": [
                  {"id": 8, "label": "Normal", "phrase": "Coolant level is at the maximum mark"},
                  {"id": 9, "label": "Low", "phrase": "Coolant level is below minimum mark"}
                ]
              }
            ]
          }
        ]
      }
    ]
  }
}
```

### Element Structure
- `id` - Element ID
- `name` - Element name
- `required` - Whether this element is required
- `max_options` - Maximum number of options that can be selected (0 = unlimited)
- `options` - Array of available options for this element
- `presets` - Array of preset phrases for quick fill

### Option Types
Each option has a `type` field that determines how it should be rendered:
- `text` - Text input field
- `number` - Numeric input
- `date` - Date picker
- `dropdown` - Single selection
- `radio` - Radio button
- `checkbox` - Checkbox
- `rating` - Star rating (1-5)
- `status` - Status selection (good/bad/na)
- `textarea` - Multi-line text input

---

## 2. List Inspections

Retrieve a paginated list of inspections for the authenticated user's location.

### Endpoint
```
GET /connector/api/checkcar/inspections
```

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `draft`, `in_progress`, `completed`, `cancelled` |
| `per_page` | integer | Number of results per page (default: 20) |

### Example Request
```bash
curl -X GET "http://example.com/connector/api/checkcar/inspections?status=completed&per_page=10" \
  -H "Authorization: Bearer {token}"
```

### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "buyer_full_name": "John Doe",
      "buyer_phone": "+1234567890",
      "car_brand": "Toyota",
      "car_model": "Camry",
      "car_year": "2020",
      "car_plate_number": "ABC-123",
      "status": "completed",
      "overall_rating": 4,
      "created_at": "2025-01-15T10:30:00Z",
      "creator": {
        "id": 5,
        "name": "John Smith"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25
  }
}
```

---

## 3. Get Single Inspection

Retrieve detailed information about a specific inspection including all element responses.

### Endpoint
```
GET /connector/api/checkcar/inspections/{id}
```

### Example Request
```bash
curl -X GET "http://example.com/connector/api/checkcar/inspections/1" \
  -H "Authorization: Bearer {token}"
```

### Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "buyer_full_name": "John Doe",
    "buyer_phone": "+1234567890",
    "buyer_id_number": "ID123456",
    "seller_full_name": "Jane Smith",
    "seller_phone": "+0987654321",
    "car_brand": "Toyota",
    "car_model": "Camry",
    "car_color": "Silver",
    "car_year": "2020",
    "car_chassis_number": "JTHBE5C21A1234567",
    "car_plate_number": "ABC-123",
    "car_kilometers": 45000,
    "inspection_team": ["John Smith", "Mike Johnson"],
    "final_summary": "Vehicle is in good condition with minor wear",
    "overall_rating": 4,
    "status": "completed",
    "share_token": "abc123def456",
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T11:45:00Z",
    "categories": [
      {
        "category": "Engine",
        "items": [
          {
            "subcategory": null,
            "elements": [
              {
                "element_id": 10,
                "element_name": "Engine Oil Level",
                "element_type": "dropdown",
                "selected_options": [
                  {"id": 1, "label": "Good", "value": "good"}
                ],
                "note": "Oil level is good, recently changed"
              },
              {
                "element_id": 12,
                "element_name": "Engine Noise",
                "element_type": "checkbox",
                "selected_options": [
                  {"id": 5, "label": "Slight Noise", "value": "slight_noise"},
                  {"id": 6, "label": "Cold Start Issue", "value": "cold_start"}
                ],
                "note": "Slight noise on cold start"
              }
            ]
          }
        ]
      },
      {
        "category": "Exterior",
        "items": [
          {
            "subcategory": "Body",
            "elements": [
              {
                "element_id": 25,
                "element_name": "Body Condition",
                "element_type": "dropdown",
                "selected_options": [
                  {"id": 10, "label": "Good", "value": "good"}
                ],
                "note": "Minor scratches on rear bumper"
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

## 4. Create Inspection

Create a new car inspection with element responses.

### Endpoint
```
POST /connector/api/checkcar/inspections
```

### Request Body
```json
{
  "buyer_full_name": "John Doe",
  "buyer_phone": "+1234567890",
  "buyer_id_number": "ID123456",
  "seller_full_name": "Jane Smith",
  "seller_phone": "+0987654321",
  "seller_id_number": "ID789012",
  "car_brand": "Toyota",
  "car_model": "Camry",
  "car_color": "Silver",
  "car_year": "2020",
  "car_chassis_number": "JTHBE5C21A1234567",
  "car_plate_number": "ABC-123",
  "car_kilometers": 45000,
  "inspection_team": ["John Smith", "Mike Johnson"],
  "items": [
    {
      "element_id": 10,
      "option_ids": [1],
      "note": "Oil level is good, recently changed"
    },
    {
      "element_id": 12,
      "option_ids": [5, 6],
      "note": "Multiple issues found"
    },
    {
      "element_id": 25,
      "option_ids": [10],
      "note": "Minor scratches on rear bumper"
    }
  ],
  "final_summary": "Vehicle is in good condition with minor wear",
  "overall_rating": 4
}
```

### Item Structure

Each item in the `items` array represents an element response:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `element_id` | integer | Yes | The element ID from the structure |
| `option_ids` | array | No | Array of selected option IDs (supports single or multiple) |
| `note` | string | No | Inspector's note for this element |

### Examples

#### Single Option Selection (dropdown/radio):
```json
{
  "element_id": 10,
  "option_ids": [1],
  "note": "Oil level is good"
}
```

#### Multiple Option Selection (checkbox):
```json
{
  "element_id": 12,
  "option_ids": [5, 6, 7],
  "note": "Multiple defects found"
}
```

#### Element with Note Only (no options):
```json
{
  "element_id": 30,
  "note": "Checked undercarriage thoroughly, no visible leaks"
}
```

### Response (201 Created)
```json
{
  "success": true,
  "message": "Inspection created successfully",
  "data": {
    "id": 1
  }
}
```

---

## 5. Update Inspection

Update an existing inspection and its element responses.

### Endpoint
```
PUT /connector/api/checkcar/inspections/{id}
```

### Request Body
Same format as create inspection. If `items` array is provided, all existing items will be replaced.

### Response
```json
{
  "success": true,
  "message": "Inspection updated successfully",
  "data": {
    "id": 1
  }
}
```

---

## 6. Delete Inspection

Delete an inspection permanently.

### Endpoint
```
DELETE /connector/api/checkcar/inspections/{id}
```

### Response
```json
{
  "success": true,
  "message": "Inspection deleted successfully"
}
```

---

## 7. Complete Inspection

Mark an inspection as completed.

### Endpoint
```
POST /connector/api/checkcar/inspections/{id}/complete
```

### Response
```json
{
  "success": true,
  "message": "Inspection marked as completed"
}
```

---

## 8. Generate Share Link

Generate a shareable link for the inspection report.

### Endpoint
```
POST /connector/api/checkcar/inspections/{id}/share
```

### Response
```json
{
  "success": true,
  "data": {
    "share_token": "abc123def456",
    "share_url": "http://example.com/checkcar/inspections/1?token=abc123def456"
  }
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "car_year": ["The car year must be a string."],
    "items.0.element_id": ["The selected element id is invalid."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Inspection not found"
}
```

### Unauthorized (403)
```json
{
  "success": false,
  "message": "Unauthorized action."
}
```

---

## Usage Examples

### JavaScript/TypeScript Example

```typescript
// Get inspection structure
async function getInspectionStructure() {
  const response = await fetch('/connector/api/checkcar/structure', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  const data = await response.json();
  return data.data.categories;
}

// Create inspection
async function createInspection(inspectionData) {
  const response = await fetch('/connector/api/checkcar/inspections', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(inspectionData)
  });
  return await response.json();
}

// Usage
const structure = await getInspectionStructure();
const inspection = await createInspection({
  car_brand: 'Toyota',
  car_model: 'Camry',
  items: [
    {
      element_id: 10,
      option_id: 1,
      note: 'Oil level is good'
    }
  ]
});
```

### cURL Example

```bash
# Get structure
curl -X GET "http://example.com/connector/api/checkcar/structure" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create inspection
curl -X POST "http://example.com/connector/api/checkcar/inspections" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "car_brand": "Toyota",
    "car_model": "Camry",
    "items": [
      {
        "element_id": 10,
        "option_id": 1
      }
    ]
  }'
```

---

## Notes

1. **Location Filtering**: All inspections are automatically filtered by the authenticated user's `location_id`
2. **Photos**: Photo URLs should be absolute paths to accessible images
3. **Required Fields**: Elements marked as `required: true` in the structure must have a valid response
4. **Status Flow**: Inspections start as `draft`, can be updated to `in_progress`, and finally `completed`
5. **Share Links**: Share tokens are unique and allow public access to the inspection report
