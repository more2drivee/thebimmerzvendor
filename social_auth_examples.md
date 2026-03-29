# Social Auth Examples

## Google Social Login

**Request**
```http
POST /connector/api/auth/social-customer-login
Content-Type: application/json

{
  "medium": "google",
  "token": "ya29.a0AfH6SMD…",         // Flutter-provided access token
  "access_token": "optional_refresh_token",
  "unique_id": "118363507231298712345", // Google sub
  "email": "fatima@example.com",
  "name": "Fatima Zahra"
}
```

**Response**
```json
{
  "success": true,
  "phone_exist": true,
  "token": "eyJ0eXAiOiJKV1QiLCJh…",
  "message": "Account created successfully",
  "user": {
    "id": 342,
    "name": "Fatima Zahra",
    "email": "fatima@example.com",
    "phone": "01012345678"
  }
}
```

## Apple Social Login

**Request**
```http
POST /connector/api/auth/social-customer-login
Content-Type: application/json

{
  "medium": "apple",
  "authorization_code": "c0064d2ce58f049ca8aac0ad2bf15e675.0.rwzs._jEu5a88B_5IDFo9kTno6Q",
  "identity_token": "eyJraWQiOiJKTlN…",
  "unique_id": "000692.b09cceeddd18",
  "email": "hala@example.com",
  "name": "Hala Youssef"
}
```

> **Notes**
> - `authorization_code` is now mandatory when `medium=apple`; it is what gets exchanged with Apple instead of the `unique_id`.
> - `unique_id` remains the Apple user identifier for deduplication.
> - `identity_token` can be used by the client for additional verification but is not sent to Apple in this flow.
> - `redirect_uri` is only included in the token exchange if the stored Apple settings define it, so you can omit the redirect when using a bundle ID (client_id) directly and avoid mismatch issues.

**Response**
```json
{
  "success": true,
  "phone_exist": true,
  "token": "eyJhbGciOiJQUzI1NiJ9…",
  "message": "Account created successfully",
  "user": {
    "id": 187,
    "name": "Hala Youssef",
    "email": "hala@example.com",
    "phone": "01098765432"
  }
}
```

**Error when authorization exchange fails**
```json
{
  "success": false,
  "message": "Invalid Apple authorization code"
}
```
