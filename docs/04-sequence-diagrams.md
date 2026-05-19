# Sequence Diagrams — Malaysia Public Holiday API

## 1. CSV Import Flow

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Admin UI
    participant SC as HolidayImportController
    participant IMP as HolidayCsvImportService
    participant HSS as HolidaySourceService
    participant DB as Database
    participant AL as AuditLog

    Admin->>UI: Upload CSV + select source
    UI->>SC: POST /api/v1/admin/holiday-imports/csv
    SC->>SC: Validate request (ImportCsvRequest)
    SC->>HSS: getSource(source_id)
    HSS->>DB: SELECT * FROM holiday_sources WHERE id=?
    DB-->>HSS: source record
    HSS-->>SC: HolidaySource

    SC->>IMP: importCsv(source, file, year)
    IMP->>IMP: validateCsvHeaders()
    alt Headers invalid
        IMP-->>SC: throw InvalidSourceFileException
        SC-->>UI: 422 INVALID_SOURCE_FILE
    end

    IMP->>DB: INSERT holiday_import_batches (status=draft)
    DB-->>IMP: batch record

    loop For each CSV row
        IMP->>IMP: validateRow(row)
        IMP->>DB: CHECK UNIQUE (year, state_codes, date, name)
        alt Duplicate
            IMP->>IMP: Mark row as warning/skip
        else Valid
            IMP->>DB: INSERT holidays (status=draft, batch_id=?)
        end
    end

    IMP->>DB: UPDATE holiday_import_batches SET status=review_required, total_rows=?, valid_rows=?, invalid_rows=?
    IMP->>AL: log(csv_import_completed, batch_id)
    IMP-->>SC: batch summary

    SC-->>UI: 200 { batch_id, status, total_rows, valid_rows, invalid_rows }
```

---

## 2. Admin Review and Publish Flow

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Admin UI
    participant RC as HolidayImportController
    participant RS as HolidayReviewService
    participant DB as Database
    participant CACHE as Cache
    participant AL as AuditLog

    Admin->>UI: Open batch preview
    UI->>RC: GET /api/v1/admin/holiday-import-batches/{batch_id}
    RC->>DB: SELECT holidays WHERE batch_id=? AND status=draft
    DB-->>RC: draft holidays list
    RC-->>UI: draft holidays

    loop Review each holiday
        alt Edit draft
            Admin->>UI: Edit name / date / scope
            UI->>RC: PATCH /api/v1/admin/holidays/{id}
            RC->>RS: updateDraft(holiday, data)
            RS->>DB: UPDATE holidays SET ...
            RS->>AL: log(holiday_updated)
        else Reject
            Admin->>UI: Reject holiday
            UI->>RC: POST /api/v1/admin/holidays/{id}/reject
            RC->>RS: rejectHoliday(holiday)
            RS->>DB: UPDATE holidays SET status=rejected
            RS->>AL: log(holiday_updated)
        end
    end

    Admin->>UI: Publish batch
    UI->>RC: POST /api/v1/admin/holiday-import-batches/{batch_id}/publish
    RC->>RS: publishBatch(batch)

    RS->>DB: SELECT COUNT(*) FROM holidays WHERE batch_id=? AND status=draft AND invalid=true
    alt Invalid rows remain
        RS-->>RC: throw ImportBatchNotReadyException
        RC-->>UI: 422 IMPORT_BATCH_NOT_READY
    else All clear
        RS->>DB: UPDATE holidays SET status=published WHERE batch_id=? AND status IN (draft, confirmed)
        RS->>DB: UPDATE holiday_import_batches SET status=published, published_by=?, published_at=NOW()
        RS->>CACHE: invalidate(holidays.{year}.*)
        RS->>AL: log(holiday_published, batch_id)
        RS-->>RC: published rows count
        RC-->>UI: 200 { batch_id, status=published, published_rows }
    end
```

---

## 3. Holiday Override Flow

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Admin UI
    participant OC as HolidayOverrideController
    participant OS as HolidayOverrideService
    participant DB as Database
    participant CACHE as Cache
    participant AL as AuditLog

    Admin->>UI: Create override (e.g. rename holiday)
    UI->>OC: POST /api/v1/admin/holiday-overrides
    Note right of OC: Payload: holiday_id, action=rename, name=new_name, reason, source_url

    OC->>OC: Validate request
    OC->>OS: createOverride(data)

    OS->>DB: SELECT * FROM holidays WHERE id=?
    DB-->>OS: holiday record

    OS->>DB: INSERT holiday_overrides (action=rename, ...)

    alt action = rename
        OS->>DB: UPDATE holidays SET name=new_name WHERE id=?
    else action = remove
        OS->>DB: UPDATE holidays SET status=cancelled WHERE id=?
    else action = add
        OS->>DB: INSERT holidays (status=published, ...)
    else action = replace
        OS->>DB: UPDATE holidays SET date=new_date WHERE id=?
    end

    OS->>CACHE: invalidate(holidays.{year}.{state_codes})
    OS->>AL: log(override_created, override_id)
    OS-->>OC: override record
    OC-->>UI: 201 { id, action, message }
```

---

## 4. Public API — Get Holidays by Year & State

```mermaid
sequenceDiagram
    actor Client
    participant MW as Middleware
    participant HC as HolidayController
    participant RL as RateLimiter
    participant AS as HolidayApiService
    participant CACHE as Cache
    participant DB as Database

    Client->>MW: GET /api/v1/holidays?year=2026&state=SBH
    MW->>RL: checkRateLimit(ip / api_key)
    alt Rate limit exceeded
        RL-->>Client: 429 RATE_LIMIT_EXCEEDED
    end

    MW->>HC: index(year=2026, state=SBH)
    HC->>HC: Validate query params
    HC->>AS: getHolidays(year=2026, state=SBH)

    AS->>CACHE: get(holidays.2026.SBH)
    alt Cache hit
        CACHE-->>AS: cached holidays
    else Cache miss
        AS->>DB: SELECT * FROM holidays WHERE year=2026 AND state_codes=SBH AND status=published
        DB-->>AS: holiday records
        AS->>DB: SELECT * FROM holiday_overrides WHERE year=2026 AND state_codes=SBH
        DB-->>AS: active overrides
        AS->>AS: applyOverrides(holidays, overrides)
        AS->>CACHE: put(holidays.2026.SBH, result, ttl=3600)
    end

    AS-->>HC: holiday collection
    HC-->>Client: 200 { year, state_codes, data: [...] }
```

---

## 5. Public API — Check if Date is Holiday

```mermaid
sequenceDiagram
    actor Client
    participant HC as HolidayController
    participant AS as HolidayApiService
    participant CACHE as Cache
    participant DB as Database

    Client->>HC: GET /api/v1/holidays/check?date=2026-05-30&state=SBH
    HC->>HC: Validate date format + state code
    HC->>AS: checkDate(date=2026-05-30, state=SBH)

    AS->>CACHE: get(holiday.check.2026-05-30.SBH)
    alt Cache hit
        CACHE-->>AS: cached result
    else Cache miss
        AS->>DB: SELECT * FROM holidays WHERE date=2026-05-30 AND state_codes IN ('SBH', 'ALL') AND status=published
        DB-->>AS: matching holidays
        AS->>CACHE: put(...)
    end

    AS-->>HC: { is_holiday: true, holidays: [...] }
    HC-->>Client: 200 { date, state_codes, is_holiday, holidays }
```

---

## 6. Source Upload Flow

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Admin UI
    participant SC as HolidaySourceController
    participant HSS as HolidaySourceService
    participant FS as File Storage
    participant DB as Database
    participant AL as AuditLog

    Admin->>UI: Fill form: year, name, type, URL, file
    UI->>SC: POST /api/v1/admin/holiday-sources (multipart)
    SC->>SC: Validate (StoreHolidaySourceRequest)
    Note right of SC: ext in [pdf, csv], size ≤ 10MB

    SC->>HSS: createSource(data, file)
    HSS->>HSS: calculateChecksum(file) → SHA-256
    HSS->>DB: SELECT id FROM holiday_sources WHERE checksum=?
    alt Duplicate file
        DB-->>HSS: existing source
        HSS-->>SC: throw DuplicateSourceException
        SC-->>UI: 409 { error: DUPLICATE_SOURCE }
    else New file
        HSS->>FS: store file → storage/app/sources/{year}/{filename}
        FS-->>HSS: file_path
        HSS->>DB: INSERT holiday_sources (status=draft, checksum, file_path, ...)
        DB-->>HSS: source record
        HSS->>AL: log(source_uploaded, source_id)
        HSS-->>SC: source record
        SC-->>UI: 201 { id, status=draft, message }
    end
```

---

## 7. Public API Request

```mermaid
sequenceDiagram
    actor Consumer
    participant API as Public API Route
    participant HC as HolidayController
    participant DB as Database

    Consumer->>API: GET /api/v1/holidays?year=2026&state=SBH
    API->>HC: Dispatch request without authentication
    HC->>DB: Query published holidays for filters
    DB-->>HC: Published holiday records
    HC-->>Consumer: 200 JSON response
```
