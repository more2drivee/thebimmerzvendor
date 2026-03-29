# SmsController API Documentation

This API allows you to fetch active SMS message templates and send SMS messages to contacts using those templates.

## Endpoints

### 1. Get Active SMS Messages

**GET** `/api/sms/messages`

Returns a list of active SMS message templates available for sending. Optionally accepts a `job_sheet_id` parameter to include information about which templates have already been sent for that job sheet.

**Role-based Authorization:**
Only messages assigned to a role that matches the authenticated user's role are returned. Role-based filtering happens server-side based on the authenticated user's roles.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_sheet_id` | integer | No | Job sheet ID to check which SMS templates have already been sent for this job sheet |

#### Example Request (without job_sheet_id)
```http
GET /api/sms/messages
```

#### Example Request (with job_sheet_id)
```http
GET /api/sms/messages?job_sheet_id=456
```

#### Example Response (without job_sheet_id)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Job Sheet Status",
      "message_template": "Hello {{customer_name}}, your job {{job_sheet_no}} is now {{status}}.",
      "description": "Sent when a job sheet status is updated."
    },
    {
      "id": 2,
      "name": "Job Sheet Created",
      "message_template": "Hi {{customer_name}}, job {{job_sheet_no}} has been created for your vehicle {{device_name}} {{model_name}}.",
      "description": "Sent when a new job sheet is created."
    }
  ],
  "message": "SMS messages retrieved successfully"
}
```

#### Example Response (with job_sheet_id)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Job Sheet Status",
      "message_template": "Hello {{customer_name}}, your job {{job_sheet_no}} is now {{status}}.",
      "description": "Sent when a job sheet status is updated.",
      "sent_for_job_sheet": true
    },
    {
      "id": 2,
      "name": "Job Sheet Created",
      "message_template": "Hi {{customer_name}}, job {{job_sheet_no}} has been created for your vehicle {{device_name}} {{model_name}}.",
      "description": "Sent when a new job sheet is created.",
      "sent_for_job_sheet": false
    }
  ],
  "sent_message_ids": [1],
  "sent_logs": [
    {
      "id": 123,
      "sms_message_id": 1,
      "job_sheet_id": 456,
      "contact_id": 789,
      "contact_name": "John Doe",
      "contact_mobile": "+1234567890",
      "status": "sent",
      "sent_at": "2025-06-20T14:30:00Z",
      "message_content": "Hello John Doe, your job JS-2025-001 is now Completed."
    }
  ],
  "message": "SMS messages retrieved successfully"
}
```

#### Response Fields

When `job_sheet_id` is provided, the response includes additional fields:

| Field | Type | Description |
|-------|------|-------------|
| `sent_for_job_sheet` | boolean | Added to each message template. Indicates if this template has already been sent for the specified job sheet |
| `sent_message_ids` | array | Array of SMS message IDs that have been sent for the specified job sheet |
| `sent_logs` | array | Array of detailed SMS log entries for messages sent to the specified job sheet |

---

### 2. Send SMS to a Contact

**POST** `/api/sms/send`

Sends an SMS to a contact using a selected message template. Placeholders in the template are automatically filled from the related job sheet when `job_sheet_id` is provided.

#### Example Request
```http
POST /api/sms/send
Content-Type: application/json

{
  "contact_id": 123,
  "message_id": 1,
  "job_sheet_id": 456
}
```

#### Example Response (Success)
```json
{
  "success": true,
  "message": "SMS sent successfully",
  "data": {
    "contact_id": 123,
    "contact_name": "John Doe",
    "contact_mobile": "+1234567890",
    "message_id": 1,
    "message_name": "Job Sheet Status",
    "message_content": "Hello John Doe, your job JS-2025-001 is now Completed.",
    "job_sheet_id": 456,
    "sent_at": "2025-11-23T10:00:00Z"
  }
}
```

#### Example Response (Failure)
```json
{
  "success": false,
  "message": "Failed to send SMS. Please check SMS settings."
}
```

---

### 3. Send Bulk SMS

**POST** `/api/sms/send-bulk`

Sends an SMS to multiple contacts using a selected message template. Placeholders in the template are automatically filled from the related job sheet when `job_sheet_id` is provided.

#### Example Request
```http
POST /api/sms/send-bulk
Content-Type: application/json

{
  "contact_ids": [123, 124, 125],
  "message_id": 2,
  "job_sheet_id": 456
}
```

#### Example Response
```json
{
  "success": true,
  "message": "Bulk SMS sending completed",
  "data": {
    "sent": 2,
    "failed": 1,
    "details": [
      {"contact_id": 123, "status": "sent"},
      {"contact_id": 124, "status": "sent"},
      {"contact_id": 125, "status": "failed", "reason": "No mobile number"}
    ]
  }
}
```

---

## Template Variables

The following placeholders are supported when a `job_sheet_id` is provided. They are automatically derived from the repair job sheet and its related booking:

- `{{customer_name}}`: Customer name from the job sheet booking contact
- `{{job_sheet_no}}`: Job sheet number
- `{{booking_no}}`: Booking number associated with the job sheet
- `{{device_name}}`: Customer vehicle/device name from the booking
- `{{model_name}}`: Vehicle/model name from the booking
- `{{status}}`: Job sheet status name

Any placeholders that cannot be resolved are automatically removed from the final SMS text.

---

## Error Handling

All endpoints return a `success` boolean and a `message`. On validation errors, a 422 status code is returned with details in the `errors` field.

---

## Notes
- Only active messages (`status: true`) are available for sending.
- If a variable is missing, it will be blank in the sent message.
- All SMS sending is logged in the system.
