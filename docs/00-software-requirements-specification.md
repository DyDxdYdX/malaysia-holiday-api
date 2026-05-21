# Software Requirements Specification (SRS): Malaysia Public Holiday API

## 1. Purpose

This document defines the software requirements for a Malaysia Public Holiday API.

The API provides public holiday data by year, state, and holiday type. It is intended to use official Malaysian government holiday documents as the primary data source, with support for manual review, correction, and overrides.

---

## 2. System Overview

The system provides a centralized API for Malaysia public holiday data.

The system should:

- Store official public holiday sources
- Import holiday data from CSV or PDF
- Normalize holiday data by year and state
- Classify holidays as federal, state, replacement, additional, or custom
- Allow admin review before publishing
- Allow corrections and overrides
- Expose confirmed holiday data through API endpoints
- Keep source and audit history for traceability

---

## 3. Recommended Technology Stack

```yaml
technology_stack:
  language: PHP
  framework: Laravel
  database: MySQL
  hosting_target:
    - shared_hosting

  preferred_import_format:
    primary: CSV
    secondary: PDF
    fallback: manual_entry
```

---

## 4. Problem Statement

Public holiday data from third-party websites may be inaccurate or incomplete. Malaysia public holidays can also vary by state, federal territory, and official later announcements.

The system must avoid blindly trusting unofficial data and should instead maintain a verified holiday database based on official or admin-approved sources.

---

## 5. Goals

```yaml
goals:
  - provide reliable Malaysia public holiday data
  - support federal and state-level holidays
  - support yearly holiday imports
  - support admin approval before publishing
  - support manual overrides and corrections
  - expose clean API endpoints for external systems
  - maintain audit trail and source traceability
  - open source :)
```

---

## 6. Non-Goals

```yaml
non_goals:
  - not a real-time government monitoring system
  - not a paid API service
```

---

## 7. User Roles

```yaml
roles:
  admin:
    description: "Full system administrator. Manages holiday data through the admin dashboard."
    permissions:
      - manage users
      - upload sources
      - import holidays
      - publish holidays
      - manage overrides
      - delete records
      - access audit logs

  public_user:
    description: "Unauthenticated user or application consuming public API data."
    permissions:
      - read public holiday data only
      - query holidays by year and state
      - check if a date is a holiday
```

---

## 8. Data Source Requirements

### 8.1 Source Priority

```yaml
source_priority:
  priority_1:
    source_type: official_federal_government
    example: "JPM/BKPP Hari Kelepasan Am PDF"
    trust_level: highest

  priority_2:
    source_type: official_state_government
    example: "State government public holiday page"
    trust_level: high

  priority_3:
    source_type: gazette_or_official_announcement
    example: "State gazette, official announcement, chief minister statement"
    trust_level: highest_for_overrides

  priority_4:
    source_type: manual_admin_entry
    example: "Admin-created correction with source URL"
    trust_level: depends_on_approval

  priority_5:
    source_type: third_party_reference
    example: "OfficeHolidays, PublicHolidays.com.my"
    trust_level: reference_only
```

### 8.2 Source Storage

The system shall store source metadata for each import.

```yaml
source_metadata:
  - source_name
  - source_type
  - source_url
  - file_path
  - year
  - checksum
  - uploaded_by
  - uploaded_at
  - notes
```

---

## 9. Functional Requirements

## 9.1 Source Management

```yaml
FR-001:
  title: Upload source file
  description: "The system shall allow admin users to upload a source file such as PDF or CSV."
  priority: must_have

FR-002:
  title: Store source URL
  description: "The system shall allow admin users to store the official source URL for traceability."
  priority: must_have

FR-003:
  title: Calculate source checksum
  description: "The system shall calculate a checksum for uploaded source files to detect duplicate or changed files."
  priority: should_have

FR-004:
  title: View source history
  description: "The system shall allow admin users to view previous uploaded/imported sources."
  priority: should_have
```

---

## 9.2 Holiday Import

```yaml
FR-005:
  title: Import holidays from CSV
  description: "The system shall import holiday records from a validated CSV file."
  priority: must_have

FR-006:
  title: Import holidays from PDF
  description: "The system should attempt to extract holiday data from uploaded PDF files."
  priority: should_have

FR-007:
  title: Create draft records
  description: "Imported holiday data shall be saved as draft records before publishing."
  priority: must_have

FR-008:
  title: Validate imported records
  description: "The system shall validate year, date, state code, holiday name, and holiday type."
  priority: must_have

FR-009:
  title: Detect duplicate holidays
  description: "The system shall detect duplicate holiday records by year, state, date, and name."
  priority: must_have

FR-010:
  title: Mark uncertain holidays
  description: "The system shall support marking holidays as subject to change."
  priority: should_have
```

---

## 9.3 Review and Publishing

```yaml
FR-011:
  title: Preview imported holidays
  description: "The system shall provide an admin preview screen before publishing imported holidays."
  priority: must_have

FR-012:
  title: Edit draft holidays
  description: "The system shall allow admin users to edit draft holiday records."
  priority: must_have

FR-013:
  title: Reject imported records
  description: "The system shall allow admin users to reject invalid imported records."
  priority: must_have

FR-014:
  title: Publish approved holidays
  description: "The system shall publish approved holiday records so they are available through the API."
  priority: must_have

FR-015:
  title: Prevent draft records from API output
  description: "The public/API response shall only return published or confirmed records."
  priority: must_have
```

---

## 9.4 Holiday Override

```yaml
FR-016:
  title: Add override holiday
  description: "The system shall allow admin users to add a new holiday override."
  priority: must_have

FR-017:
  title: Remove holiday through override
  description: "The system shall allow admin users to cancel or remove a previously published holiday."
  priority: should_have

FR-018:
  title: Replace holiday date
  description: "The system shall allow admin users to replace the date of a published holiday."
  priority: should_have

FR-019:
  title: Rename holiday
  description: "The system shall allow admin users to rename a holiday while keeping history."
  priority: should_have

FR-020:
  title: Store override reason
  description: "Every override shall store reason, source URL, approver, and approval timestamp."
  priority: must_have
```

---

## 9.5 API Access

```yaml
FR-021:
  title: Get holidays by year
  description: "The API shall return holidays for a selected year."
  priority: must_have

FR-022:
  title: Get holidays by year and state
  description: "The API shall return holidays for a selected year and Malaysia state or federal territory."
  priority: must_have

FR-023:
  title: Check if date is holiday
  description: "The API shall allow clients to check whether a specific date is a holiday."
  priority: must_have

FR-024:
  title: Filter by holiday scope
  description: "The API shall allow filtering by federal, state, replacement, additional, or custom holiday."
  priority: should_have

FR-025:
  title: Return source information
  description: "The API should optionally return source metadata for each holiday."
  priority: should_have
```

---

## 10. Non-Functional Requirements

```yaml
NFR-001:
  title: Reliability
  requirement: "The API should return only confirmed/published holiday data by default."

NFR-002:
  title: Traceability
  requirement: "Every holiday record should be traceable to an import source or manual override."

NFR-003:
  title: Performance
  requirement: "Holiday list API should respond within 500ms under normal load."

NFR-004:
  title: Security
  requirement: "Admin endpoints must require authentication and authorization."

NFR-005:
  title: Compatibility
  requirement: "The system should run on PHP shared hosting where possible."

NFR-006:
  title: Maintainability
  requirement: "Holiday import, publishing, and API serving logic should be separated into service classes."

NFR-007:
  title: Auditability
  requirement: "All create, update, delete, publish, and override actions should be logged."

NFR-008:
  title: Data Accuracy
  requirement: "Imported records must require admin review before becoming publicly available."
```

---

## 11. State Code Standard

```yaml
malaysia_state_codes:
  JHR: Johor
  KDH: Kedah
  KTN: Kelantan
  MLK: Melaka
  NSN: Negeri Sembilan
  PHG: Pahang
  PRK: Perak
  PLS: Perlis
  PNG: Pulau Pinang
  SBH: Sabah
  SWK: Sarawak
  SGR: Selangor
  TRG: Terengganu
  KUL: Wilayah Persekutuan Kuala Lumpur
  LBN: Wilayah Persekutuan Labuan
  PJY: Wilayah Persekutuan Putrajaya
```

---

## 12. Data Model

### 12.1 `holiday_sources`

```sql
CREATE TABLE holiday_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT NOT NULL,
    source_name VARCHAR(255) NOT NULL,
    source_type VARCHAR(50) NOT NULL,
    source_url TEXT NULL,
    file_path TEXT NULL,
    checksum VARCHAR(128) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    uploaded_by BIGINT UNSIGNED NULL,
    uploaded_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

Recommended `source_type` values:

```yaml
source_type:
  - federal_pdf
  - state_page
  - gazette
  - admin_csv
  - manual_entry
  - third_party_reference
```

---

### 12.2 `holiday_import_batches`

```sql
CREATE TABLE holiday_import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_source_id BIGINT UNSIGNED NOT NULL,
    year SMALLINT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    total_rows INT NOT NULL DEFAULT 0,
    valid_rows INT NOT NULL DEFAULT 0,
    invalid_rows INT NOT NULL DEFAULT 0,
    warning_rows INT NOT NULL DEFAULT 0,
    imported_by BIGINT UNSIGNED NULL,
    imported_at TIMESTAMP NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    published_by BIGINT UNSIGNED NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

Recommended `status` values:

```yaml
import_batch_status:
  - draft
  - parsed
  - review_required
  - approved
  - published
  - rejected
```

---

### 12.3 `holidays`

```sql
CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_source_id BIGINT UNSIGNED NULL,
    holiday_import_batch_id BIGINT UNSIGNED NULL,
    year SMALLINT NOT NULL,
    state_codes VARCHAR(10) NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    day_name VARCHAR(20) NULL,
    scope VARCHAR(30) NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_subject_to_change BOOLEAN NOT NULL DEFAULT FALSE,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    source_note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_holidays_year_state (year, state_codes),
    INDEX idx_holidays_date_state (date, state_codes),
    UNIQUE KEY unique_holiday_record (year, state_codes, date, name)
);
```

Recommended `scope` values:

```yaml
holiday_scope:
  federal: "Federal public holiday"
  state: "State public holiday"
  custom: "Custom holiday"
```

Recommended `type` values:

```yaml
holiday_type:
  - federal
  - state
  - replacement
  - additional
  - custom
```

Recommended `status` values:

```yaml
holiday_status:
  - draft
  - confirmed
  - published
  - overridden
  - cancelled
```

---

### 12.4 `holiday_overrides`

```sql
CREATE TABLE holiday_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_id BIGINT UNSIGNED NULL,
    year SMALLINT NOT NULL,
    state_codes VARCHAR(10) NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    action VARCHAR(30) NOT NULL,
    reason TEXT NULL,
    source_url TEXT NULL,
    source_file_path TEXT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_overrides_year_state (year, state_codes),
    INDEX idx_overrides_date_state (date, state_codes)
);
```

Recommended `action` values:

```yaml
override_action:
  - add
  - remove
  - replace
  - rename
  - mark_subject_to_change
```

---

## 13. API Specification

### 13.1 Get Holidays by Year

```http
GET /api/v1/holidays?year=2026
```

Response:

```json
{
  "year": 2026,
  "data": [
    {
      "name": "Hari Pekerja",
      "date": "2026-05-01",
      "day_name": "Friday",
      "state_codes": "SBH",
      "scope": "federal",
      "type": "federal",
      "is_subject_to_change": false
    }
  ]
}
```

---

### 13.2 Get Holidays by Year and State

```http
GET /api/v1/holidays?year=2026&state=SBH
```

Response:

```json
{
  "year": 2026,
  "state_codes": "SBH",
  "data": [
    {
      "name": "Hari Jadi Yang di-Pertua Negeri Sabah",
      "date": "2026-03-30",
      "day_name": "Monday",
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false
    }
  ]
}
```

---

### 13.3 Check Whether Date Is Holiday

```http
GET /api/v1/holidays/check?date=2026-05-30&state=SBH
```

Response:

```json
{
  "date": "2026-05-30",
  "state_codes": "SBH",
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

---

### 13.4 Get Supported States

```http
GET /api/v1/states
```

Response:

```json
{
  "data": [
    {
      "code": "SBH",
      "name": "Sabah"
    },
    {
      "code": "SWK",
      "name": "Sarawak"
    }
  ]
}
```

---

### 13.5 Upload Holiday Source

```http
POST /api/v1/admin/holiday-sources
Content-Type: multipart/form-data
```

Request:

```yaml
fields:
  year: 2026
  source_name: "JPM HKA 2026"
  source_type: "federal_pdf"
  source_url: "https://www.kabinet.gov.my/hari-kelepasan-am/"
  file: "HKA-2026.pdf"
```

Response:

```json
{
  "id": 1,
  "status": "draft",
  "message": "Holiday source uploaded successfully."
}
```

---

### 13.6 Import Holidays from CSV

```http
POST /api/v1/admin/holiday-imports/csv
Content-Type: multipart/form-data
```

Request:

```yaml
fields:
  source_id: 1
  year: 2026
  file: "holidays-2026.csv"
```

Response:

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

### 13.7 Publish Import Batch

```http
POST /api/v1/admin/holiday-import-batches/{batch_id}/publish
```

Response:

```json
{
  "batch_id": 1,
  "status": "published",
  "published_rows": 118
}
```

---

## 14. CSV Import Format

```csv
year,state_codes,name,date,day_name,scope,type,is_subject_to_change,source_note
2026,SBH,Hari Jadi Yang di-Pertua Negeri Sabah,2026-03-30,Monday,state,state,false,JPM HKA 2026
2026,SBH,Good Friday,2026-04-03,Friday,state,state,false,JPM HKA 2026
2026,SBH,Pesta Kaamatan,2026-05-30,Saturday,state,state,false,JPM HKA 2026
```

CSV validation:

```yaml
csv_validation:
  required_columns:
    - year
    - state_codes
    - name
    - date
    - scope
    - type

  date_format: YYYY-MM-DD

  allowed_scope:
    - federal
    - state
    - custom

  allowed_type:
    - federal
    - state
    - replacement
    - additional
    - custom
```

---

## 15. Business Rules

```yaml
BR-001:
  title: Published data only
  rule: "Only published or confirmed holidays shall be returned by public API endpoints."

BR-002:
  title: Draft isolation
  rule: "Draft imported records shall not affect API responses."

BR-003:
  title: One holiday per state/date/name
  rule: "The system shall prevent duplicate records with the same year, state, date, and name."

BR-004:
  title: Override priority
  rule: "Approved overrides shall take priority over baseline imported holiday records."

BR-005:
  title: Source traceability
  rule: "Every holiday record should be linked to a source or override record."

BR-006:
  title: Subject to change marker
  rule: "Holidays marked as subject to change shall include a warning flag in admin views and optionally in API responses."

BR-007:
  title: Third-party source restriction
  rule: "Third-party sources shall not be auto-published without admin approval."
```

---

## 16. Admin Interface Requirements

```yaml
admin_pages:
  holiday_sources:
    features:
      - upload source file
      - enter source URL
      - view checksum
      - view source status

  holiday_imports:
    features:
      - import CSV
      - parse PDF if supported
      - view import batch result
      - view validation errors

  holiday_review:
    features:
      - preview draft holidays
      - edit draft records
      - reject records
      - approve records
      - publish batch

  holiday_management:
    features:
      - search holidays
      - filter by year
      - filter by state
      - filter by scope
      - add manual holiday
      - create override

  audit_logs:
    features:
      - view create/update/delete history
      - view publisher
      - view approver
      - view old and new values
```

---

## 17. Security Requirements

```yaml
security:
  authentication:
    admin_endpoints: required
    public_endpoints: optional

  authorization:
    source_upload: super_admin_or_data_admin
    import_publish: super_admin_or_data_admin
    override_create: super_admin_or_data_admin
    audit_log_view: super_admin

  api_key:
    required_for_private_api: true
    hash_storage: true
    rate_limit: true

  file_upload:
    allowed_extensions:
      - pdf
      - csv
    max_file_size_mb: 10
    virus_scan: optional
    checksum_required: true

  output_protection:
    - sanitize text fields
    - prevent CSV formula injection during export
    - do not expose draft records
```

---

## 18. Error Response Format

All API errors should use a consistent format.

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

Common error codes:

```yaml
error_codes:
  - VALIDATION_ERROR
  - FORBIDDEN
  - NOT_FOUND
  - DUPLICATE_HOLIDAY
  - INVALID_SOURCE_FILE
  - IMPORT_BATCH_NOT_READY
  - INTERNAL_SERVER_ERROR
```

---

## 19. Audit Log Requirements

```yaml
audit_events:
  - source_uploaded
  - source_updated
  - source_deleted
  - csv_import_started
  - csv_import_completed
  - pdf_parse_started
  - pdf_parse_completed
  - holiday_created
  - holiday_updated
  - holiday_deleted
  - holiday_published
  - override_created
  - override_approved
  - override_rejected
```

Audit fields:

```yaml
audit_fields:
  - id
  - actor_id
  - action
  - entity_type
  - entity_id
  - old_values
  - new_values
  - ip_address
  - user_agent
  - created_at
```

---

## 20. Deployment Requirements

```yaml
deployment:
  minimum:
    php: "8.2+"
    database: "MySQL 8+ or PostgreSQL 14+"
    web_server: "Apache or Nginx"
    storage: "local file storage"

  shared_hosting_notes:
    - prefer CSV import
    - avoid heavy OCR
    - avoid long-running PDF parsing
    - allow admin-triggered import instead of queue worker
    - keep uploaded source files small

  optional:
    queue_worker: "For VPS/cloud only"
    scheduler: "For periodic source checks"
    cache: "Redis or database cache"
```

---

## 21. Suggested Laravel Services

```yaml
services:
  HolidaySourceService:
    responsibility:
      - create source record
      - store uploaded file
      - calculate checksum

  HolidayCsvImportService:
    responsibility:
      - validate CSV headers
      - validate CSV rows
      - create import batch
      - create draft holiday records

  HolidayPdfParserService:
    responsibility:
      - extract text from PDF
      - produce draft candidate rows
      - return parser warnings

  HolidayReviewService:
    responsibility:
      - approve draft records
      - reject invalid records
      - publish import batch

  HolidayOverrideService:
    responsibility:
      - create override
      - apply override priority
      - maintain source traceability

  HolidayApiService:
    responsibility:
      - return published holidays
      - check holiday by date
      - format API response

```

---

## 22. Suggested Laravel Routes

```php
Route::prefix('api/v1')->group(function () {
    Route::get('/states', [StateController::class, 'index']);
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::get('/holidays/check', [HolidayController::class, 'check']);

    Route::middleware(['auth:sanctum', 'can:manage-holidays'])->prefix('admin')->group(function () {
        Route::post('/holiday-sources', [HolidaySourceController::class, 'store']);
        Route::post('/holiday-imports/csv', [HolidayImportController::class, 'importCsv']);
        Route::post('/holiday-import-batches/{batch}/publish', [HolidayImportController::class, 'publish']);
        Route::post('/holiday-overrides', [HolidayOverrideController::class, 'store']);
    });
});
```

---

## 23. MVP Scope

```yaml
mvp:
  must_have:
    - CSV import
    - source metadata storage
    - draft holiday records
    - admin review
    - publish holidays
    - get holidays by year and state API
    - check date API
    - manual override
    - audit log

  should_have:
    - PDF text extraction
    - source checksum
    - export holidays to CSV

  nice_to_have:
    - official page scraper
    - PDF table extraction
    - automatic diff detection
    - email notification for changed holidays
    - public documentation page
```

---

## 24. Future Enhancements

```yaml
future_enhancements:
  data_quality:
    - compare multiple sources
    - confidence score per holiday
    - source diff dashboard

  automation:
    - scheduled source checks
    - official page monitoring
    - admin notification when source changes

  api:
    - versioned API
    - OpenAPI documentation
    - SDK for PHP/JavaScript
    - public developer portal

  infrastructure:
    - queue worker
    - object storage
    - Redis cache
    - optional Python parser service after moving to VPS

  data:
    - support school holidays
    - support replacement holiday rules
    - support custom organization calendars
```

---

## 25. Acceptance Criteria

```yaml
acceptance_criteria:
  - admin can upload CSV holiday data
  - system validates CSV rows before import
  - invalid rows are shown to admin
  - imported rows are saved as draft first
  - draft holidays are not returned by public API
  - admin can publish reviewed holidays
  - published holidays can be queried by year and state
  - API can check whether a specific date is a holiday
  - admin can add an override with source and reason
  - duplicate holiday records are blocked
  - all admin changes are logged
```

---

## 26. Final Recommendation

For the current shared hosting environment, build this as a **Laravel/PHP holiday API with semi-automatic import**.

Recommended MVP approach:

```yaml
recommended_mvp_approach:
  import_method: CSV first
  source_storage: keep official PDF and URL
  review_method: admin approval required
  api_output: published records only
  override_support: required
  pdf_parsing: optional
  third_party_sources: reference only
```

The most important design rule is:

```yaml
golden_rule:
  - "Do not auto-trust scraped or parsed holiday data."
  - "Always store the source."
  - "Always review before publishing."
  - "Only published records should be served by the API."
```
