# CheckCar API Documentation

> Base URL: `/api/connector`  
> Authentication: Required (Bearer token / session)

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| GET | `/checkcar/structure` | Get full inspection structure (categories → subcategories → elements) |
| GET | `/checkcar/inspections` | List inspections (supports status filter) |
| GET | `/checkcar/inspections/{id}` | Get single inspection with items and documents |
| POST | `/checkcar/inspections` | Create a new inspection |
| PUT | `/checkcar/inspections/{id}` | Update an existing inspection |
| DELETE | `/checkcar/inspections/{id}` | Delete an inspection |
| POST | `/checkcar/inspections/{id}/share` | Generate share link/token |
| POST | `/checkcar/inspections/{id}/complete` | Mark inspection as completed |

---

## 1. Get Inspection Structure

**GET** `/checkcar/structure`

Returns the full hierarchy of categories, subcategories, elements, their options, and phrase presets.

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
            "id": 12,
            "name": "Oil Level",
            "type": "single",
            "required": true,
            "max_options": 0,
            "options": [
              {"id": 1, "label": "Good", "sort_order": 0},
              {"id": 2, "label": "Low", "sort_order": 1}
            ],
            "presets": [
              {"id": 3, "phrase": "Oil level is within normal range"}
            ]
          }
        ],
        "subcategories": [
          {
            "id": 2,
            "name": "Oil System",
            "elements": [...]
          }
        ]
      }
    ]
  }
}
```

---

## 2. List Inspections

**GET** `/checkcar/inspections`

Query parameters:

| Name | Type | Example | Description |
|------|------|---------|-------------|
| status | string | `draft` | Filter by status (`draft`, `in_progress`, `completed`, `cancelled`) |
| per_page | integer | `20` | Number of results per page (default: 20) |

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "buyer_full_name": "John Doe",
      "car_brand": "Toyota",
      "status": "draft",
      "created_at": "2025-11-27T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 35
  }
}
```

---

## 3. Get Single Inspection

**GET** `/checkcar/inspections/{id}`

Returns full inspection details, grouped by categories, plus document URLs.

---

### Alternative: Get Inspection by Job Sheet

**GET** `/checkcar/inspections/by-jobsheet/{job_sheet_id}`

Returns the inspection associated with the specified job sheet ID. If multiple inspections exist for the job sheet, returns the most recent one.

### Response

Same as Get Single Inspection response above.

### Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "buyer_full_name": "John Doe",
    "buyer_phone": "+123456789",
    "buyer_id_number": "ID12345",
    "seller_full_name": "Jane Smith",
    "seller_phone": "+987654321",
    "seller_id_number": "ID98765",
    "car_brand": "Toyota",
    "car_model": "Corolla",
    "car_color": "Silver",
    "car_year": "2022",
    "car_chassis_number": "ABC123XYZ",
    "car_plate_number": "XYZ-123",
    "car_kilometers": 15000,
    "inspection_team": ["inspector1"],
    "final_summary": "Overall good condition.",
    "overall_rating": 4,
    "status": "draft",
    "share_token": "abc123def456",
    "created_at": "2025-11-27T10:00:00Z",
    "updated_at": "2025-11-27T10:30:00Z",
    "creator": {
      "id": 5,
      "name": "Admin User"
    },
    "categories": [
      {
        "category": "Engine",
        "items": [
          {
            "subcategory": "Oil System",
            "elements": [
              {
                "element_id": 12,
                "element_name": "Oil Level",
                "element_type": "single",
                "selected_options": [
                  {"id": 1, "label": "Good", "value": "good"}
                ],
                "note": "Checked",
                "images": [
                  {
                    "type": "0",
                    "file_path": "checkcar/inspections/1/inspection_1_element_0_1732694400.jpg",
                    "mime_type": "image/jpeg"
                  }
                ]
              }
            ]
          }
        ]
      }
    ],
    "documents": {
      "buyer": [
        {
          "type": "id_front",
          "url": "/storage/checkcar/inspections/1/inspection_1_buyer_id_front_1732694400.jpg"
        }
      ],
      "seller": [
        {
          "type": "id_front",
          "url": "/storage/checkcar/inspections/1/inspection_1_seller_id_front_1732694400.jpg"
        }
      ]
    }
  }
}
```

---

## 4. Create Inspection

**POST** `/checkcar/inspections`

### Request Body

All image/document fields accept **base64 strings** (either plain base64 or `data:image/...;base64,...`).

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| buyer_contact_id | integer | Yes | Existing contact ID for buyer |
| seller_contact_id | integer | Yes | Existing contact ID for seller |
| car_brand | string | No | Car brand |
| car_model | string | No | Car model |
| car_year | string | No | Car year |
| car_color | string | No | Car color |
| car_chassis_number | string | No | Chassis number |
| car_plate_number | string | No | Plate number |
| car_kilometers | integer | No | Odometer reading |
| inspection_team | array | No | Team member identifiers |
| items | array | No | Inspection items |
| final_summary | string | No | Final summary |
| overall_rating | integer | No | Overall rating 1–5 |
| documents | object | No | Documents (buyer/seller) |

#### Items (`items[]`)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| element_id | integer | Yes | Element ID |
| option_ids | array | No | Selected option IDs |
| note | string | No | Note for this element |
| images | array | No | Array of base64 strings |

#### Documents (`documents`)

```json
{
  "buyer": {
    "id_front": "base64...",
    "id_back": "base64...",
    "signature": "base64..."
  },
  "seller": {
    "id_front": "base64...",
    "id_back": "base64...",
    "car_license_front": "base64...",
    "car_license_back": "base64...",
    "signature": "base64..."
  }
}
```

### Example Request

```json
{
  "buyer_contact_id": 101,
  "seller_contact_id": 102,
  "car_brand": "Toyota",
  "car_model": "Corolla",
  "car_year": "2022",
  "car_color": "Silver",
  "car_chassis_number": "ABC123XYZ",
  "car_plate_number": "XYZ-123",
  "car_kilometers": 15000,
  "inspection_team": ["inspector1"],
  "items": [
    {
      "element_id": 12,
      "option_ids": [1],
      "note": "Oil level is good",
      "images": [
        "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
      ]
    }
  ],
  "final_summary": "Overall good condition.",
  "overall_rating": 4,
  "documents": {
    "buyer": {
      "id_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
      "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUg..."
    },
    "seller": {
      "id_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
      "id_back": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
    }
  }
}
```

### Response

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

**PUT** `/checkcar/inspections/{id}`

All fields are optional. If `items` is provided, existing items are **replaced**.  
If `documents` is provided, existing documents are **replaced**.

### Example 1: Update status only

```json
{
  "status": "completed"
}
```

### Example 2: Update car details only

```json
{
  "car_color": "Blue",
  "car_kilometers": 15200,
  "final_summary": "Updated after recheck."
}
```

### Example 3: Replace items only (all other fields unchanged)

```json
{
  "items": [
    {
      "element_id": 12,
      "option_ids": [2],
      "note": "Oil level low",
      "images": [
        "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
      ]
    },
    {
      "element_id": 15,
      "option_ids": [5, 6],
      "note": "Multiple issues detected"
    }
  ]
}
```

### Example 4: Replace documents only

```json
{
  "documents": {
    "buyer": {
      "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUg..."
    },
    "seller": {
      "car_license_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
      "car_license_back": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
    }
  }
}
```

### Example 5: Full update (all fields except id)

```json
{
  "booking_id": 42,
  "status": "completed",
  "car_make": "Toyota",
  "car_model": "Corolla",
  "car_year": 2021,
  "car_color": "Blue",
  "car_kilometers": 15200,
  "vin": "1HGBH41JXMN109186",
  "plate_number": "ABC-1234",
  "overall_rating": 4,
  "final_summary": "Inspection completed. Minor wear on brake pads, otherwise excellent condition.",
  "items": [
    {
      "element_id": 12,
      "option_ids": [2],
      "note": "Oil level low",
      "images": [
        "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
      ]
    },
    {
      "element_id": 15,
      "option_ids": [5, 6],
      "note": "Multiple issues detected"
    },
    {
      "element_id": 20,
      "option_ids": [9],
      "note": "Brake pads worn",
      "images": [
        "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
        "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
      ]
    }
  ],
  "documents": {
    "buyer": {
      "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...",
      "id_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
      "id_back": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
    },
    "seller": {
      "car_license_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...",
      "car_license_back": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
    }
  }
}
```

### Response (for all examples)

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

**DELETE** `/checkcar/inspections/{id}`

### Response

```json
{
  "success": true,
  "message": "Inspection deleted successfully"
}
```

---

## 7. Generate Share Link

**POST** `/checkcar/inspections/{id}/share`

### Response

```json
{
  "success": true,
  "data": {
    "share_token": "abc123def456",
    "share_url": "/checkcar/inspections/1?token=abc123def456"
  }
}
```

---

## 8. Complete Inspection

**POST** `/checkcar/inspections/{id}/complete`

### Response

```json
{
  "success": true,
  "message": "Inspection marked as completed"
}
```

---

## Image & Document Handling

- **All images/documents are sent as base64 strings.**
- Accepted formats: `data:image/jpeg;base64,...`, `data:image/png;base64,...`, or plain base64.
- Files are stored under `storage/app/public/checkcar/inspections/{inspection_id}/`.
- The database stores only the file path and MIME type.
- Public URLs are returned via `Storage::disk('public')->url($path)`.

### Example Base64 Payload

```json
{
  "documents": {
    "buyer": {
      "id_front": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
    }
  },
  "items": [
    {
      "element_id": 12,
      "images": [
        "iVBORw0KGgoAAAANSUhEUgAA..."
      ]
    }
  ]
}
```

---

## Errors

| Code | Description |
|------|-------------|
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (business/location mismatch) |
| 404 | Not found (inspection/element not found) |
| 422 | Validation error (invalid data) |
| 500 | Server error (migration missing, DB error) |

### Validation Error Example

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "buyer_contact_id": ["The buyer contact id field is required."],
    "items.0.element_id": ["The selected element id is invalid."]
  }
}
```

---

## Notes

- `buyer_contact_id` and `seller_contact_id` must exist in the `contacts` table.
- The API automatically populates `buyer_full_name`, `buyer_phone`, `buyer_id_number`, etc., from the contacts.
- Images are stored with unique filenames (`inspection_{id}_{party}_{type}_{timestamp}.{ext}`).
- Documents are returned grouped by `buyer`/`seller` with URLs to the stored files.

---

*Generated for CheckCar API (`Modules\Connector\Http\Controllers\Api\CheckCarController.php`).*
