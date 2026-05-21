# State Machine Diagrams — Malaysia Public Holiday API

This document describes the lifecycle state machines for all key entities in the system.

---

## 1. Holiday Source — Lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft : Admin uploads file

    draft --> active : Admin activates source
    draft --> deleted : Admin deletes

    active --> deleted : Admin removes source

    deleted --> [*]

    note right of draft
        File is stored, checksum calculated.
        Not yet associated with a published batch.
    end note

    note right of active
        Source has been used in at least one published batch.
    end note
```

---

## 2. Holiday Import Batch — Lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft : Batch record created

    draft --> parsed : CSV headers validated
    parsed --> review_required : Rows parsed & draft holidays created

    review_required --> approved : Admin reviews all rows (no invalid left)
    review_required --> rejected : Admin rejects entire batch

    approved --> published : Admin triggers publish
    published --> [*]
    rejected --> [*]

    note right of review_required
        Admin must resolve all invalid rows
        before approving or publish is blocked.
    end note

    note right of published
        All confirmed holidays in batch
        become status=published.
    end note
```

---

## 3. Holiday Record — Lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft : Created by CSV import or PDF parse

    draft --> confirmed : Admin approves individual record
    draft --> rejected : Admin rejects record

    confirmed --> published : Batch published
    published --> overridden : Override applied (rename/replace)
    published --> cancelled : Override action = remove

    overridden --> published : Override reverted (future feature)
    cancelled --> [*]
    rejected --> [*]

    note right of draft
        Not visible via public API.
        Can be edited or rejected by admin.
    end note

    note right of published
        Returned by GET /api/v1/holidays.
        Only this status is public.
    end note

    note right of overridden
        Original record kept for audit.
        Override values take precedence.
    end note
```

---

## 4. Holiday Override — Lifecycle

```mermaid
stateDiagram-v2
    [*] --> pending : Admin creates override

    pending --> approved : Admin approves
    pending --> rejected_override : Approver rejects

    approved --> applied : System applies override to holiday record
    applied --> [*]
    rejected_override --> [*]

    note right of pending
        Stores: action, reason, source_url.
        Holiday not yet modified.
    end note

    note right of approved
        approved_by and approved_at are recorded.
        Override action is executed on the holiday.
    end note
```

> **Note:** Admins can approve their own overrides.

---

## 5. Public API Access

```mermaid
stateDiagram-v2
    [*] --> request_received : Consumer calls public endpoint
    request_received --> validated : Query parameters pass validation
    request_received --> rejected : Query parameters fail validation
    validated --> published_data_returned : Published records queried
    rejected --> [*] : 422 validation error
    published_data_returned --> [*] : 200 JSON response
```

---

## 6. Data Trust and Publish Flow (State Machine Summary)

```mermaid
stateDiagram-v2
    direction LR

    [*] --> SOURCE_UPLOADED : Admin uploads PDF/CSV

    SOURCE_UPLOADED --> IMPORT_PARSED : CSV import runs
    IMPORT_PARSED --> DRAFT_HOLIDAYS : Draft records created

    DRAFT_HOLIDAYS --> UNDER_REVIEW : Admin opens review page
    UNDER_REVIEW --> SOME_REJECTED : Admin rejects invalid rows
    UNDER_REVIEW --> ALL_APPROVED : All rows reviewed and confirmed

    SOME_REJECTED --> UNDER_REVIEW : Admin continues reviewing rest
    ALL_APPROVED --> PUBLISHED : Admin publishes batch

    PUBLISHED --> API_SERVED : Holidays returned by GET /api/v1/holidays

    API_SERVED --> OVERRIDE_APPLIED : Admin creates override
    OVERRIDE_APPLIED --> API_SERVED : Updated data served

    note right of DRAFT_HOLIDAYS
        status = draft
        NOT visible in API
    end note

    note right of PUBLISHED
        status = published
        Cached and served to clients
    end note
```

---

## 7. Override Action Effects on Holiday Record

| Override `action` | Effect on `holidays` table |
|---|---|
| `add` | New `holidays` row inserted with `status=published` |
| `remove` | `holidays.status` → `cancelled` |
| `replace` | `holidays.date` updated to new date |
| `rename` | `holidays.name` updated to new name |
| `mark_subject_to_change` | `holidays.is_subject_to_change` → `true` |

---

## 8. CSV Validation State Machine

```mermaid
stateDiagram-v2
    [*] --> CHECKING_HEADERS : File uploaded

    CHECKING_HEADERS --> HEADERS_INVALID : Missing required columns
    CHECKING_HEADERS --> VALIDATING_ROWS : Headers OK

    HEADERS_INVALID --> [*] : Return INVALID_SOURCE_FILE error

    VALIDATING_ROWS --> ROW_VALID : All fields valid + unique
    VALIDATING_ROWS --> ROW_WARNING : Valid but may be duplicate
    VALIDATING_ROWS --> ROW_INVALID : Missing required field / bad format

    ROW_VALID --> DRAFT_CREATED : Insert holiday draft
    ROW_WARNING --> DRAFT_CREATED_WITH_FLAG : Insert with warning flag
    ROW_INVALID --> SKIPPED : Skip row, increment invalid_rows

    DRAFT_CREATED --> [*]
    DRAFT_CREATED_WITH_FLAG --> [*]
    SKIPPED --> [*]
```
