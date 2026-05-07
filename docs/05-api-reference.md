# API Reference — Malaysia Public Holiday API

**Base URL:** `https://malaysia-holiday.dydxsoft.my/api/v1`  
**Response Format:** `application/json`  
**API Version:** `v1`

---

## Authentication

### Public Endpoints
No authentication required. Optional `X-Api-Key` header for higher rate limits.

### Admin Endpoints
All routes under `/admin/*` require a Bearer token via Laravel Sanctum.

```http
Authorization: Bearer {sanctum_token}
```

### API Client Key (optional)
```http
X-Api-Key: {raw_api_key}
```

---

## Rate Limiting

| Tier | Limit |
|---|---|
| Unauthenticated | 30 req/min |
| Authenticated API client | Per-client setting (default 60 req/min) |
| Admin | 120 req/min |

Rate limit headers returned on every response:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
Retry-After: 30   ← only on 429
```

---

## Error Response Format

All errors follow a consistent envelope:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "state": ["The selected state is invalid."]
    }
  }
}
```

### Error Codes

| Code | HTTP Status | Description |
|---|---|---|
| `VALIDATION_ERROR` | 422 | Input validation failed |
| `UNAUTHORIZED` | 401 | Missing or invalid auth token |
| `FORBIDDEN` | 403 | Authenticated but lacking permission |
| `NOT_FOUND` | 404 | Resource not found |
| `DUPLICATE_HOLIDAY` | 409 | Holiday with same year/state/date/name exists |
| `INVALID_SOURCE_FILE` | 422 | File extension, size, or format rejected |
| `IMPORT_BATCH_NOT_READY` | 422 | Batch has unresolved invalid rows |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_SERVER_ERROR` | 500 | Unexpected server error |

---

## Public Endpoints

### GET `/states`

Returns the list of supported Malaysian state and federal territory codes.

**Request:** No parameters required.

**Response `200 OK`:**
```json
{
  "data": [
    { "code": "JHR", "name": "Johor" },
    { "code": "KDH", "name": "Kedah" },
    { "code": "KTN", "name": "Kelantan" },
    { "code": "MLK", "name": "Melaka" },
    { "code": "NSN", "name": "Negeri Sembilan" },
    { "code": "PHG", "name": "Pahang" },
    { "code": "PRK", "name": "Perak" },
    { "code": "PLS", "name": "Perlis" },
    { "code": "PNG", "name": "Pulau Pinang" },
    { "code": "SBH", "name": "Sabah" },
    { "code": "SWK", "name": "Sarawak" },
    { "code": "SGR", "name": "Selangor" },
    { "code": "TRG", "name": "Terengganu" },
    { "code": "KUL", "name": "W.P. Kuala Lumpur" },
    { "code": "LBN", "name": "W.P. Labuan" },
    { "code": "PJY", "name": "W.P. Putrajaya" }
  ]
}
```

---

### GET `/holidays`

Returns published public holidays. Can be filtered by year, state, and scope.

**Query Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `year` | integer | **Yes** | 4-digit year e.g. `2026` |
| `state` | string | No | State code e.g. `SBH`. Omit for all states |
| `scope` | string | No | Filter by `federal`, `state`, or `custom` |
| `type` | string | No | Filter by `federal`, `state`, `replacement`, `additional`, `custom` |
| `include_source` | boolean | No | If `true`, include source metadata per holiday |

**Request Example:**
```http
GET /api/v1/holidays?year=2026&state=SBH
```

**Response `200 OK`:**
```json
{
  "year": 2026,
  "state_code": "SBH",
  "data": [
    {
      "name": "Tahun Baru",
      "date": "2026-01-01",
      "day_name": "Thursday",
      "state_code": "SBH",
      "scope": "federal",
      "type": "federal",
      "is_subject_to_change": false
    },
    {
      "name": "Hari Jadi Yang di-Pertua Negeri Sabah",
      "date": "2026-03-30",
      "day_name": "Monday",
      "state_code": "SBH",
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false
    },
    {
      "name": "Pesta Kaamatan",
      "date": "2026-05-30",
      "day_name": "Saturday",
      "state_code": "SBH",
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false
    }
  ]
}
```

**With `include_source=true`** each holiday object gains:
```json
{
  "source": {
    "source_name": "JPM HKA 2026",
    "source_type": "federal_pdf",
    "source_url": "https://www.kabinet.gov.my/hari-kelepasan-am/"
  }
}
```

---

### GET `/holidays/check`

Checks whether a specific date is a public holiday for a given state.

**Query Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `date` | string (YYYY-MM-DD) | **Yes** | Date to check |
| `state` | string | **Yes** | State code e.g. `SBH` |

**Request Example:**
```http
GET /api/v1/holidays/check?date=2026-05-30&state=SBH
```

**Response `200 OK` — Is a holiday:**
```json
{
  "date": "2026-05-30",
  "state_code": "SBH",
  "is_holiday": true,
  "holidays": [
    {
      "name": "Pesta Kaamatan",
      "scope": "state",
      "type": "state"
    }
  ]
}
```

**Response `200 OK` — Not a holiday:**
```json
{
  "date": "2026-05-04",
  "state_code": "SBH",
  "is_holiday": false,
  "holidays": []
}
```

---

## Admin Endpoints

> All admin endpoints require `Authorization: Bearer {token}` and the caller must have the `super_admin` or `data_admin` role.

---

### POST `/admin/holiday-sources`

Upload a new holiday source document (PDF or CSV).

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|---|---|---|---|
| `year` | integer | **Yes** | Year this source covers |
| `source_name` | string | **Yes** | Human-readable label |
| `source_type` | string | **Yes** | One of: `federal_pdf`, `state_page`, `gazette`, `admin_csv`, `manual_entry`, `third_party_reference` |
| `source_url` | string (URL) | No | Official URL |
| `file` | file | No | PDF or CSV, max 10MB |
| `notes` | string | No | Admin notes |

**Response `201 Created`:**
```json
{
  "id": 1,
  "status": "draft",
  "message": "Holiday source uploaded successfully."
}
```

---

### POST `/admin/holiday-imports/csv`

Import holidays from a validated CSV file linked to a source.

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|---|---|---|---|
| `source_id` | integer | **Yes** | ID of an existing `holiday_sources` record |
| `year` | integer | **Yes** | Year to import |
| `file` | file | **Yes** | CSV file with required columns |

**CSV Required Columns:** `year`, `state_code`, `name`, `date`, `scope`, `type`

**Response `200 OK`:**
```json
{
  "batch_id": 1,
  "status": "review_required",
  "total_rows": 120,
  "valid_rows": 118,
  "invalid_rows": 2,
  "warning_rows": 5
}
```

---

### GET `/admin/holiday-import-batches/{batch_id}`

Retrieve a batch summary with its draft holiday records for review.

**Response `200 OK`:**
```json
{
  "id": 1,
  "year": 2026,
  "status": "review_required",
  "total_rows": 120,
  "valid_rows": 118,
  "invalid_rows": 2,
  "warning_rows": 5,
  "holidays": [
    {
      "id": 10,
      "name": "Hari Pekerja",
      "date": "2026-05-01",
      "state_code": "SBH",
      "status": "draft"
    }
  ]
}
```

---

### PATCH `/admin/holidays/{holiday_id}`

Edit a draft holiday record before publishing.

**Request Body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | No | |
| `date` | string (YYYY-MM-DD) | No | |
| `state_code` | string | No | |
| `scope` | string | No | `federal`, `state`, `custom` |
| `type` | string | No | |
| `is_subject_to_change` | boolean | No | |

**Response `200 OK`:**
```json
{
  "id": 10,
  "status": "draft",
  "message": "Holiday updated."
}
```

---

### POST `/admin/holidays/{holiday_id}/reject`

Reject a draft holiday record.

**Response `200 OK`:**
```json
{
  "id": 10,
  "status": "rejected",
  "message": "Holiday rejected."
}
```

---

### POST `/admin/holiday-import-batches/{batch_id}/publish`

Publish all approved/confirmed holidays in a batch.

**Response `200 OK`:**
```json
{
  "batch_id": 1,
  "status": "published",
  "published_rows": 118
}
```

**Error `422`:**
```json
{
  "error": {
    "code": "IMPORT_BATCH_NOT_READY",
    "message": "Batch still has unresolved invalid rows."
  }
}
```

---

### POST `/admin/holiday-overrides`

Create a manual override on an existing or new holiday.

**Request Body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `holiday_id` | integer | No | Required for `remove`, `replace`, `rename` actions |
| `year` | integer | **Yes** | |
| `state_code` | string | **Yes** | |
| `name` | string | **Yes** | New or existing holiday name |
| `date` | string (YYYY-MM-DD) | **Yes** | |
| `action` | string | **Yes** | `add`, `remove`, `replace`, `rename`, `mark_subject_to_change` |
| `reason` | string | **Yes** | Must explain why |
| `source_url` | string | No | Supporting source URL |

**Response `201 Created`:**
```json
{
  "id": 5,
  "action": "rename",
  "message": "Override applied successfully."
}
```

---

## Holiday Object Schema

| Field | Type | Description |
|---|---|---|
| `name` | string | Holiday display name (may be BM or English) |
| `date` | string (YYYY-MM-DD) | Calendar date |
| `day_name` | string | e.g. `Monday`, `Friday` |
| `state_code` | string | e.g. `SBH`, `KUL` |
| `scope` | string | `federal` · `state` · `custom` |
| `type` | string | `federal` · `state` · `replacement` · `additional` · `custom` |
| `is_subject_to_change` | boolean | `true` if date/observance may change |

---

## Status Codes Summary

| HTTP Status | Meaning |
|---|---|
| `200 OK` | Successful GET or action |
| `201 Created` | Resource successfully created |
| `204 No Content` | Successful deletion |
| `400 Bad Request` | Malformed request |
| `401 Unauthorized` | Missing/invalid auth |
| `403 Forbidden` | Auth valid but permission denied |
| `404 Not Found` | Resource not found |
| `409 Conflict` | Duplicate resource |
| `422 Unprocessable Entity` | Validation or business rule failed |
| `429 Too Many Requests` | Rate limit hit |
| `500 Internal Server Error` | Unexpected error |
