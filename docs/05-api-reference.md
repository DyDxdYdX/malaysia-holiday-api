# API Reference - Malaysia Public Holiday API

Base URL: `http://malaysia-holday-api.test/api/v1`  
Response format: `application/json`  
Version: `v1`

## Authentication

No authentication is required for public holiday data.

- `GET /states` is public.
- `GET /holidays` is public.
- `GET /holidays/check` is public.

Admin screens remain protected by web authentication and admin roles, but public API consumers do not need accounts or API keys.

## Error Envelope

All API errors return this shape:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "year": ["The year field is required."]
    }
  }
}
```

## Error Codes

| Code | HTTP Status | Meaning |
|---|---|---|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `NOT_FOUND` | 404 | Route or resource was not found |

## Endpoints

### GET `/states`
Returns all supported Malaysia state and federal territory codes.

Auth: not required.

Response `200 OK`:

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
    { "code": "KUL", "name": "Wilayah Persekutuan Kuala Lumpur" },
    { "code": "LBN", "name": "Wilayah Persekutuan Labuan" },
    { "code": "PJY", "name": "Wilayah Persekutuan Putrajaya" }
  ]
}
```

### GET `/holidays`
Returns published holidays filtered by year and optional filters.

Auth: not required.

Query parameters:

| Parameter | Type | Required | Allowed values | Description |
|---|---|---|---|---|
| `year` | integer | Yes | `2000..2100` | Holiday year |
| `state` | string | No | `JHR`,`KDH`,`KTN`,`MLK`,`NSN`,`PHG`,`PRK`,`PLS`,`PNG`,`SBH`,`SWK`,`SGR`,`TRG`,`KUL`,`LBN`,`PJY`,`FED` | Filter by state code |
| `scope` | string | No | `federal`,`state`,`custom` | Filter by scope |
| `type` | string | No | `federal`,`state`,`replacement`,`additional`,`custom` | Filter by type |
| `include_source` | boolean | No | `true`/`false` | Include source object |

Request example:

```http
GET /api/v1/holidays?year=2026&state=SBH&include_source=1
```

Response `200 OK`:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Pesta Kaamatan",
      "date": "2026-05-30",
      "day_name": "Saturday",
      "year": 2026,
      "state_codes": ["SBH"],
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false,
      "source_note": null,
      "source": {
        "source_name": "JPM HKA 2026",
        "source_type": "federal_pdf",
        "source_url": "https://example.gov.my/hka2026.pdf",
        "year": 2026,
        "uploaded_at": "2026-05-19T08:00:00+08:00"
      }
    }
  ],
  "year": 2026,
  "state_code": "SBH"
}
```

Notes:
- Only `published` holidays are returned.
- If no records match, `data` is an empty array.

### GET `/holidays/check`
Checks whether a date is a published holiday, optionally scoped to a state.

Auth: not required.

Query parameters:

| Parameter | Type | Required | Allowed values | Description |
|---|---|---|---|---|
| `date` | string | Yes | `YYYY-MM-DD` | Date to check |
| `state` | string | No | Same state code list as `/holidays` | Filter holiday check by state |

Request example:

```http
GET /api/v1/holidays/check?date=2026-05-30&state=SBH
```

Response `200 OK` (holiday found):

```json
{
  "date": "2026-05-30",
  "state_code": "SBH",
  "is_holiday": true,
  "holidays": [
    {
      "name": "Pesta Kaamatan",
      "state_codes": ["SBH"],
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false
    }
  ]
}
```

Response `200 OK` (not a holiday):

```json
{
  "date": "2026-05-04",
  "state_code": "SBH",
  "is_holiday": false,
  "holidays": []
}
```

## Status Codes

| HTTP Status | Meaning |
|---|---|
| `200 OK` | Request succeeded |
| `404 Not Found` | Endpoint not found |
| `422 Unprocessable Entity` | Validation failed |
