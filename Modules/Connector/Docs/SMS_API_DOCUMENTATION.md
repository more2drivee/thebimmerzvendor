# SMS API Documentation

## Overview
The SMS API provides endpoints to retrieve SMS messages, job sheet details, and send SMS notifications using the SmsUtil class with Epusheg provider.

## Base URL
```
/connector/api/sms
```

## Authentication
All endpoints require API authentication via Bearer token:
```
Authorization: Bearer {token}
```

## Endpoints

### 1. Get All SMS Messages
**GET** `/connector/api/sms/messages`

Retrieves all active SMS message templates with their assigned roles.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Order Confirmation",
      "message_template": "Hello {{customer_name}}, your order {{order_id}} has been confirmed.",
      "description": "Sent when order is confirmed",
      "roles": ["customer", "admin"]
    }
  ],
  "message": "SMS messages retrieved successfully"
}
```

**Status Codes:**
- `200` - Success
- `500` - Server error

---

### 2. Get Message Template
**GET** `/connector/api/sms/message-template`

Retrieves a specific SMS message template by ID.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| message_id | integer | Yes | SMS message ID |

**Example Request:**
```
GET /connector/api/sms/message-template?message_id=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Order Confirmation",
    "template": "Hello {{customer_name}}, your order {{order_id}} has been confirmed.",
    "description": "Sent when order is confirmed",
    "status": true
  },
  "message": "Message template retrieved successfully"
}
```

**Status Codes:**
- `200` - Success
- `422` - Validation error
- `500` - Server error

---

### 3. Get Job Sheet Details
**GET** `/connector/api/sms/job-sheet`

Retrieves job sheet information including contact and device details.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| job_sheet_id | integer | Yes | Job sheet ID |

**Example Request:**
```
GET /connector/api/sms/job-sheet?job_sheet_id=5
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "job_sheet_no": "JS2025/0001",
    "status": "In Progress",
    "booking": {
      "id": 10,
      "booking_no": "BK2025/0001"
    },
    "contact": {
      "id": 15,
      "name": "Ahmed Hassan",
      "mobile": "201090555070",
      "email": "ahmed@example.com"
    },
    "device": {
      "id": 20,
      "device_name": "iPhone 14",
      "model_name": "Pro Max"
    },
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T14:45:00Z"
  },
  "message": "Job sheet retrieved successfully"
}
```

**Status Codes:**
- `200` - Success
- `422` - Validation error
- `500` - Server error

---

### 4. Send SMS to Single Contact
**POST** `/connector/api/sms/send`

Sends an SMS message to a single contact using a predefined template or custom message.

**Request Body:**
```json
{
  "contact_id": 15,
  "message_id": 1,
  "job_sheet_id": 5,
  "custom_message": null,
  "variables": {
    "order_id": "ORD2025/001",
    "amount": "500 EGP"
  }
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| contact_id | integer | Yes | Contact ID to send SMS to |
| message_id | integer | Yes | SMS message template ID |
| job_sheet_id | integer | No | Job sheet ID (for auto-variable replacement) |
| custom_message | string | No | Custom message (overrides template) |
| variables | object | No | Custom variables to replace in template |

**Available Template Variables:**
- `{{customer_name}}` - Customer name
- `{{job_sheet_no}}` - Job sheet number
- `{{booking_no}}` - Booking number
- `{{device_name}}` - Device name
- `{{model_name}}` - Device model
- `{{status}}` - Job sheet status
- Custom variables via `variables` parameter

**Response (Success):**
```json
{
  "success": true,
  "message": "SMS sent successfully",
  "data": {
    "contact_id": 15,
    "contact_name": "Ahmed Hassan",
    "contact_mobile": "201090555070",
    "message_id": 1,
    "message_name": "Order Confirmation",
    "message_content": "Hello Ahmed Hassan, your job sheet JS2025/0001 is in progress.",
    "job_sheet_id": 5,
    "sent_at": "2025-01-15T15:30:00Z"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Contact does not have a mobile number"
}
```

**Status Codes:**
- `200` - Success
- `422` - Validation error (missing contact mobile, inactive message, etc.)
- `500` - Server error

---

### 5. Send Bulk SMS
**POST** `/connector/api/sms/send-bulk`

Sends SMS to multiple contacts at once.

**Request Body:**
```json
{
  "contact_ids": [15, 16, 17],
  "message_id": 1,
  "job_sheet_id": 5,
  "custom_message": null,
  "variables": {
    "order_id": "ORD2025/001"
  }
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| contact_ids | array | Yes | Array of contact IDs |
| message_id | integer | Yes | SMS message template ID |
| job_sheet_id | integer | No | Job sheet ID (for auto-variable replacement) |
| custom_message | string | No | Custom message (overrides template) |
| variables | object | No | Custom variables to replace in template |

**Response:**
```json
{
  "success": true,
  "message": "Bulk SMS sending completed",
  "data": {
    "sent": 2,
    "failed": 1,
    "details": [
      {
        "contact_id": 15,
        "contact_name": "Ahmed Hassan",
        "contact_mobile": "201090555070",
        "status": "sent"
      },
      {
        "contact_id": 16,
        "contact_name": "Fatima Ali",
        "contact_mobile": "201234567890",
        "status": "sent"
      },
      {
        "contact_id": 17,
        "contact_name": "Mohammed Karim",
        "status": "failed",
        "reason": "No mobile number"
      }
    ]
  }
}
```

**Status Codes:**
- `200` - Success (partial or complete)
- `422` - Validation error
- `500` - Server error

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "contact_id": ["The contact_id field is required."],
    "message_id": ["The message_id field must exist in sms_messages table."]
  }
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Error sending SMS",
  "error": "SMS provider error or database error"
}
```

---

## Usage Examples

### Example 1: Send SMS using template variables
```bash
curl -X POST http://localhost/connector/api/sms/send \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_id": 15,
    "message_id": 1,
    "job_sheet_id": 5
  }'
```

### Example 2: Send SMS with custom message
```bash
curl -X POST http://localhost/connector/api/sms/send \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_id": 15,
    "message_id": 1,
    "custom_message": "Hello, your repair is ready for pickup!"
  }'
```

### Example 3: Send bulk SMS
```bash
curl -X POST http://localhost/connector/api/sms/send-bulk \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_ids": [15, 16, 17],
    "message_id": 1,
    "job_sheet_id": 5,
    "variables": {
      "pickup_date": "2025-01-20"
    }
  }'
```

### Example 4: Get job sheet details for SMS
```bash
curl -X GET "http://localhost/connector/api/sms/job-sheet?job_sheet_id=5" \
  -H "Authorization: Bearer {token}"
```

---

## SMS Provider Configuration

SMS sending uses the **Epusheg** provider via `SmsUtil::sendEpusheg()`.

**Required Business Settings:**
- SMS API URL
- Username/Password or API Key
- Sender ID (from)
- Request method (GET/POST)

These are configured in the Business model's `sms_settings` JSON field.

---

## Logging

All SMS operations are logged:
- **Success**: `Log::info('SMS sent successfully', [...])`
- **Failure**: `Log::warning('SMS sending failed', [...])`
- **Error**: `Log::error('Error sending SMS', [...])`

Check logs at: `storage/logs/laravel.log`

---

## Rate Limiting

No built-in rate limiting. Implement as needed based on your SMS provider's limits.

---

## Notes

- SMS messages must have `status = true` to be sent
- Contact must have a valid mobile number
- Template variables are case-sensitive
- Unreplaced variables are automatically removed from final message
- Bulk SMS continues even if individual sends fail
