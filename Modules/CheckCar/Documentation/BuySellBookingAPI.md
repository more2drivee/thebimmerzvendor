# Buy & Sell Booking API Documentation

## Overview

This API provides endpoints for creating buy/sell car inspection bookings and managing contacts. The API follows the same business logic as the web interface but is designed for programmatic access.

## Base URL
```
/connector/api/checkcar
```

## Authentication
All endpoints require API authentication using Laravel's `auth:api` middleware and `timezone` middleware.

---

## 1. Store Contact Endpoint

### Endpoint
```
POST /connector/api/checkcar/buy-sell/store-contact
```

### Description
Creates buyer and/or seller contacts with optional vehicle (contact_device) data. This endpoint can create multiple entities in a single request:
- Buyer contact
- Seller contact  
- Vehicle linked to seller

### Request Headers
```
Content-Type: application/json
Authorization: Bearer {api_token}
```

### Request Body

**Note**: `business_id` and `user_id` are automatically retrieved from the authenticated user. Do not include them in the request body.

#### Optional Fields - Buyer Contact
```json
{
    "buyer_first_name": "John",
    "buyer_last_name": "Doe", 
    "buyer_mobile": "+1234567890",
    "buyer_national_id": "12345678901234"
}
```

#### Optional Fields - Seller Contact
```json
{
    "seller_first_name": "Jane",
    "seller_last_name": "Smith",
    "seller_mobile": "+0987654321", 
    "seller_national_id": "98765432109876",
    "seller_license_number": "DL123456",
    "seller_license_expiry": "2025-12-31"
}
```

#### Optional Fields - Vehicle (Contact Device)
```json
{
    "seller_chassis_number": "1HGCM82633A004352",
    "seller_car_type": "ملاكي",
    "seller_category_id": 15,
    "seller_model_id": 142,
    "seller_manufacturing_year": 2022,
    "seller_brand_origin_variant_id": 8,
    "seller_color": "أبيض",
    "seller_plate_number": "ABC 1234"
}
```

#### Special Field
```json
{
    "seller_contact_id": 123
}
```
Use this when adding a vehicle to an existing seller contact instead of creating a new seller.

### Complete Example - Create Buyer, Seller, and Vehicle

```json
{
    "buyer_first_name": "Ahmed",
    "buyer_last_name": "Mohammed",
    "buyer_mobile": "+966501234567",
    "buyer_national_id": "1000000007",
    "seller_first_name": "Mohammed",
    "seller_last_name": "Ali", 
    "seller_mobile": "+966507654321",
    "seller_national_id": "2000000008",
    "seller_license_number": "SA789012",
    "seller_license_expiry": "2025-06-30",
    "seller_chassis_number": "JTDKB20U993045678",
    "seller_car_type": "ملاكي",
    "seller_category_id": 15,
    "seller_model_id": 142,
    "seller_manufacturing_year": 2021,
    "seller_brand_origin_variant_id": 3,
    "seller_color": "أسود",
    "seller_plate_number": "XYZ 5678"
}
```

### Example - Add Vehicle to Existing Seller

```json
{
    "seller_contact_id": 456,
    "seller_chassis_number": "2HNYD286X1H543210",
    "seller_car_type": "اجرة",
    "seller_category_id": 12,
    "seller_model_id": 89,
    "seller_manufacturing_year": 2020,
    "seller_brand_origin_variant_id": 5,
    "seller_color": "أزرق",
    "seller_plate_number": "LMN 9012"
}
```

### Example - Create Only Buyer

```json
{
    "buyer_first_name": "Sara",
    "buyer_last_name": "Khalid",
    "buyer_mobile": "+966511122233",
    "buyer_national_id": "3000000009"
}
```

### Response Format

#### Success Response (201)
```json
{
    "success": true,
    "message": "Contacts created successfully",
    "buyer": {
        "id": 789,
        "name": "Ahmed Mohammed"
    },
    "seller": {
        "id": 790,
        "name": "Mohammed Ali",
        "contact_device": {
            "id": 345,
            "model_name": "Camry",
            "plate_number": "XYZ 5678",
            "color": "أسود"
        }
    },
    "contact_device": {
        "id": 345,
        "model_name": "Camry", 
        "plate_number": "XYZ 5678",
        "color": "أسود"
    }
}
```

#### Error Response (422)
```json
{
    "success": false,
    "message": "At least one of buyer, seller, or vehicle data must be provided"
}
```

#### Validation Error Response (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "business_id": ["The business id field is required."],
        "user_id": ["The user id field is required."],
        "seller_mobile": ["The seller mobile has already been taken."]
    }
}
```

---

## 2. Store Inspection Booking Endpoint

### Endpoint
```
POST /connector/api/checkcar/buy-sell/store
```

### Description
Creates a complete buy/sell car inspection booking including:
- Booking record
- Job sheet with default image
- Transaction (sell type)
- Product job order and sell lines
- Car inspection record
- SMS notifications (optional)

### Request Headers
```
Content-Type: application/json
Authorization: Bearer {api_token}
```

### Request Body

**Note**: `business_id` and `user_id` are automatically retrieved from the authenticated user. Do not include them in the request body.

#### Optional Fields
```json
{
    "buyer_contact_id": 789,
    "services": 5,
    "booking_note": "Customer requested detailed inspection",
    "service_price": 250.00,
    "verification_required": true,
    "transaction_contact_type": "seller",
    "send_notification": true
}
```

### Complete Example - Full Booking with All Options

```json
{
    "contact_id": 790,
    "buyer_contact_id": 789,
    "model_id": 345,
    "location_id": 2,
    "booking_start": "2024-01-15 14:30:00",
    "services": 5,
    "booking_note": "Customer requested comprehensive inspection with special attention to engine",
    "service_price": 350.00,
    "verification_required": true,
    "transaction_contact_type": "buyer",
    "send_notification": true
}
```

### Example - Minimal Booking

```json
{
    "contact_id": 790,
    "model_id": 345,
    "location_id": 2,
    "booking_start": "2024-01-16 10:00:00"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `contact_id` | integer | Yes | Seller contact ID |
| `buyer_contact_id` | integer | No | Buyer contact ID (optional) |
| `model_id` | integer | Yes | Vehicle/contact_device ID |
| `location_id` | integer | Yes | Business location ID |
| `booking_start` | datetime | Yes | Booking start time |
| `services` | integer | No | Service type ID (falls back to inspection service) |
| `booking_note` | string | No | Customer notes (max 255 chars) |
| `service_price` | decimal | No | Service price (falls back to product price) |
| `verification_required` | boolean | No | Whether verification is required (default: true) |
| `transaction_contact_type` | string | No | 'seller' or 'buyer' (default: 'seller') |
| `send_notification` | boolean | No | Send SMS notifications (default: false) |

### Response Format

#### Success Response (201)
```json
{
    "success": true,
    "message": "Saved successfully",
    "inspection_id": 123,
    "booking_id": 456,
    "job_sheet_id": 789
}
```

#### Error Response (422)
```json
{
    "success": false,
    "message": "Invalid contact, device, or brand"
}
```

#### Duplicate Booking Error (422)
```json
{
    "success": false,
    "message": "A similar booking was already created recently. Please check existing bookings."
}
```

#### Validation Error Response (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "contact_id": ["The contact id field is required."],
        "model_id": ["The model id field is required."],
        "location_id": ["The location id field is required."],
        "booking_start": ["The booking start field is required."]
    }
}
```

---

## Business Logic Notes

### 1. Duplicate Prevention
- System checks for duplicate bookings within 5 minutes
- Same seller, buyer, device, and location combination triggers duplicate detection

### 2. Transaction Contact Logic
- `transaction_contact_type: "seller"` uses seller contact for transaction/job sheet
- `transaction_contact_type: "buyer"` uses buyer contact for transaction/job sheet
- Default is "seller" if not specified

### 3. Service Pricing
- Priority: `service_price` parameter → configured service product price → 0.0
- Uses CheckCar service setting for the business

### 4. Vehicle Validation
- When creating vehicle, validates that model belongs to selected brand
- Prevents invalid model/brand combinations

### 5. SMS Notifications
- Sends to both seller and buyer (if present) when `send_notification: true`
- Uses Arabic message template with booking details
- Logs SMS delivery status in `sms_logs` table

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

## Testing Examples

### cURL Example - Store Contact

```bash
curl -X POST "http://your-domain.com/connector/api/checkcar/buy-sell/store-contact" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-api-token" \
  -d '{
    "buyer_first_name": "Test",
    "buyer_last_name": "User",
    "buyer_mobile": "+966500000000",
    "seller_first_name": "Test",
    "seller_last_name": "Seller", 
    "seller_mobile": "+966511111111",
    "seller_chassis_number": "TEST123456789TEST",
    "seller_car_type": "ملاكي",
    "seller_category_id": 15,
    "seller_model_id": 142,
    "seller_manufacturing_year": 2023,
    "seller_color": "أحمر",
    "seller_plate_number": "TEST 123"
  }'
```

### cURL Example - Store Booking

```bash
curl -X POST "http://your-domain.com/connector/api/checkcar/buy-sell/store" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-api-token" \
  -d '{
    "contact_id": 790,
    "buyer_contact_id": 789,
    "model_id": 345,
    "location_id": 2,
    "booking_start": "2024-01-15 14:30:00",
    "service_price": 300.00,
    "verification_required": true,
    "transaction_contact_type": "seller",
    "send_notification": true
  }'
```

---

## Integration Notes

1. **Sequential Calls**: Call `store-contact` first to get contact IDs, then use those IDs in `store` booking
2. **Error Handling**: Always check the `success` field in responses
3. **ID Management**: Save returned IDs for future reference and updates
4. **Date Format**: Use `YYYY-MM-DD HH:MM:SS` format for datetime fields
5. **Currency**: Prices should be in the business's default currency
