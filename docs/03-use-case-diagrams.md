# Use Case Diagrams — Malaysia Public Holiday API

## 1. Actor Overview

| Actor | Description |
|---|---|
| **Super Admin** | Full system access: users, sources, publish, override, audit |
| **Data Admin** | Manages holiday data: upload, import, review, publish |
| **API Client** | Authenticated external app consuming the API |
| **Public User** | Unauthenticated user reading public holiday data |
| **System** | Automated actions (checksum, duplicate detection, validation) |

---

## 2. Master Use Case Diagram

```mermaid
graph LR
    SA(["👑 Super Admin"])
    DA(["🗂️ Data Admin"])
    AC(["🔌 API Client"])
    PU(["🌐 Public User"])
    SYS(["⚙️ System"])

    subgraph Source Management
        UC1[Upload Source File]
        UC2[Store Source URL]
        UC3[View Source History]
    end

    subgraph Holiday Import
        UC4[Import CSV File]
        UC5[Import PDF File]
        UC6[View Import Batch Results]
        UC7[View Validation Errors]
    end

    subgraph Review & Publishing
        UC8[Preview Draft Holidays]
        UC9[Edit Draft Holiday]
        UC10[Reject Draft Holiday]
        UC11[Publish Import Batch]
    end

    subgraph Override Management
        UC12[Add Override Holiday]
        UC13[Remove Holiday via Override]
        UC14[Replace Holiday Date]
        UC15[Rename Holiday]
    end

    subgraph Public API
        UC16[Get Holidays by Year]
        UC17[Get Holidays by Year and State]
        UC18[Check if Date is Holiday]
        UC19[Get Supported States]
    end

    subgraph User & Client Management
        UC20[Manage Users]
        UC21[Create API Client]
        UC22[Disable API Client]
        UC23[View Audit Logs]
    end

    subgraph Automated System
        UC24[Calculate File Checksum]
        UC25[Detect Duplicate Holidays]
        UC26[Validate CSV Rows]
        UC27[Log Audit Events]
    end

    SA --> UC1
    SA --> UC2
    SA --> UC3
    SA --> UC4
    SA --> UC5
    SA --> UC6
    SA --> UC7
    SA --> UC8
    SA --> UC9
    SA --> UC10
    SA --> UC11
    SA --> UC12
    SA --> UC13
    SA --> UC14
    SA --> UC15
    SA --> UC20
    SA --> UC21
    SA --> UC22
    SA --> UC23

    DA --> UC1
    DA --> UC2
    DA --> UC3
    DA --> UC4
    DA --> UC5
    DA --> UC6
    DA --> UC7
    DA --> UC8
    DA --> UC9
    DA --> UC10
    DA --> UC11
    DA --> UC12

    AC --> UC16
    AC --> UC17
    AC --> UC18
    AC --> UC19

    PU --> UC16
    PU --> UC17
    PU --> UC18
    PU --> UC19

    SYS --> UC24
    SYS --> UC25
    SYS --> UC26
    SYS --> UC27
```

---

## 3. Use Case: Source Management

```mermaid
flowchart TD
    A([Admin]) -->|Chooses file + year| UC1[Upload Source File]
    UC1 --> SYS1[System: Validate extension & size]
    SYS1 --> SYS2[System: Calculate SHA-256 checksum]
    SYS2 --> SYS3[System: Check for duplicate checksum]
    SYS3 -->|Unique| SYS4[System: Store file + metadata]
    SYS3 -->|Duplicate| ERR1[Return DUPLICATE_SOURCE error]
    SYS4 --> OK1[Source created — status: draft]

    A -->|Enters URL| UC2[Store Source URL]
    UC2 --> SYS5[Store URL in source record]

    A -->|Views list| UC3[View Source History]
    UC3 --> SYS6[Return paginated source list]
```

---

## 4. Use Case: Holiday Import

```mermaid
flowchart TD
    A([Admin]) -->|Uploads CSV| UC4[Import CSV]
    UC4 --> SYS1[System: Validate headers]
    SYS1 -->|Headers invalid| ERR1[Return INVALID_SOURCE_FILE]
    SYS1 -->|Headers valid| SYS2[System: Validate each row]
    SYS2 --> SYS3[System: Detect duplicates]
    SYS3 --> SYS4[Create import batch — status: review_required]
    SYS4 --> SYS5[Create draft holiday records]
    SYS5 --> OK1[Return batch summary]

    A -->|Uploads PDF| UC5[Import PDF]
    UC5 --> SYS6[System: Extract text from PDF]
    SYS6 --> SYS7[Produce draft candidate rows with warnings]
    SYS7 --> SYS8[Admin must confirm each row]
```

---

## 5. Use Case: Review and Publishing

```mermaid
flowchart TD
    A([Admin]) -->|Opens batch| UC8[Preview Draft Holidays]
    UC8 --> V1{For each draft}

    V1 -->|Edit needed| UC9[Edit Draft Holiday]
    UC9 --> SYS1[Update record — still draft]

    V1 -->|Invalid| UC10[Reject Draft]
    UC10 --> SYS2[Mark holiday status = rejected]

    V1 -->|All OK| UC11[Publish Batch]
    UC11 --> SYS3[System: Check no invalid rows remain]
    SYS3 -->|Invalid rows exist| ERR1[Return IMPORT_BATCH_NOT_READY]
    SYS3 -->|All clear| SYS4[Set all confirmed holidays to published]
    SYS4 --> SYS5[Set batch status = published]
    SYS5 --> SYS6[Log audit event: holiday_published]
    SYS6 --> OK1[Holidays available via API]
```

---

## 6. Use Case: Holiday Override

```mermaid
flowchart TD
    A([Admin]) --> CHOICE{Override Action}

    CHOICE -->|Add| UC12A[Add Override Holiday]
    UC12A --> F1[Fill: name, date, state, scope, reason, source_url]
    F1 --> SYS1[Create override record — action: add]
    SYS1 --> SYS2[Log audit: override_created]

    CHOICE -->|Remove| UC13[Remove Holiday via Override]
    UC13 --> F2[Select existing published holiday]
    F2 --> SYS3[Create override — action: remove]
    SYS3 --> SYS4[Set holiday status = cancelled]

    CHOICE -->|Replace date| UC14[Replace Holiday Date]
    UC14 --> F3[Select holiday, enter new date + reason]
    F3 --> SYS5[Create override — action: replace]
    SYS5 --> SYS6[Update holiday date]

    CHOICE -->|Rename| UC15[Rename Holiday]
    UC15 --> F4[Enter new name + reason + source]
    F4 --> SYS7[Create override — action: rename]
    SYS7 --> SYS8[Update holiday name, keep old value in override]
```

---

## 7. Use Case: Public API Access

```mermaid
flowchart TD
    C([Client]) --> CHOICE{API Request}

    CHOICE -->|GET /api/v1/holidays?year=2026| UC16[Get Holidays by Year]
    UC16 --> F1{Check Cache}
    F1 -->|Hit| R1[Return cached response]
    F1 -->|Miss| Q1[Query: holidays WHERE year=2026 AND status=published]
    Q1 --> AO1[Apply active overrides]
    AO1 --> CACHE1[Cache result]
    CACHE1 --> R1

    CHOICE -->|GET /api/v1/holidays?year=2026&state=SBH| UC17[Get by Year + State]
    UC17 --> Q2[Query: + WHERE state_codes=SBH]
    Q2 --> AO2[Apply state overrides]
    AO2 --> R2[Return scoped response]

    CHOICE -->|GET /api/v1/holidays/check?date=...&state=...| UC18[Check Date]
    UC18 --> Q3[Query: WHERE date=? AND state IN state AND published]
    Q3 --> R3[Return is_holiday: true/false + matching holidays]

    CHOICE -->|GET /api/v1/states| UC19[Get States]
    UC19 --> R4[Return static state code list]
```

---

## 8. CRUD Permission Matrix

| Use Case | Super Admin | Data Admin | API Client | Public User |
|---|---|---|---|---|
| Upload Source | ✅ | ✅ | ❌ | ❌ |
| Import CSV | ✅ | ✅ | ❌ | ❌ |
| Import PDF | ✅ | ✅ | ❌ | ❌ |
| Edit Draft | ✅ | ✅ | ❌ | ❌ |
| Reject Draft | ✅ | ✅ | ❌ | ❌ |
| Publish Batch | ✅ | ✅ | ❌ | ❌ |
| Add Override | ✅ | ✅ | ❌ | ❌ |
| Remove Override | ✅ | ❌ | ❌ | ❌ |
| Manage Users | ✅ | ❌ | ❌ | ❌ |
| Create API Client | ✅ | ❌ | ❌ | ❌ |
| View Audit Logs | ✅ | ❌ | ❌ | ❌ |
| Get Holidays (API) | ✅ | ✅ | ✅ | ✅ |
| Check Date (API) | ✅ | ✅ | ✅ | ✅ |
