# System Architecture — Malaysia Public Holiday API

## Overview

The Malaysia Public Holiday API is a **Laravel-based** backend system that ingests, reviews, and serves verified Malaysian public holiday data. The architecture follows a layered, service-oriented design with a strict admin-gated publish workflow.

---

## High-Level Component Diagram

```mermaid
graph TB
    subgraph Consumers["Consumers"]
        AC[Public API Client / App]
        PU[Public User Browser]
        AD[Admin User Browser]
    end

    subgraph WebLayer["Web / HTTP Layer"]
        PUB[Public API Routes<br/>/api/v1/holidays<br/>/api/v1/states]
        WEB[Admin Web Routes<br/>/admin/*<br/>Livewire / Blade]
    end

    subgraph ServiceLayer["Service Layer (PHP)"]
        HSS[HolidaySourceService]
        IMP[HolidayCsvImportService]
        PDF[HolidayPdfParserService]
        REV[HolidayReviewService]
        OVR[HolidayOverrideService]
        API[HolidayApiService]
    end

    subgraph DataLayer["Data Layer"]
        DB[(MySQL Database)]
        FS[File Storage<br/>PDFs / CSVs]
        CQ[Cache Layer<br/>Redis / DB]
    end

    subgraph CrossCutting["Cross-Cutting"]
        AUTH[Web Auth & RBAC<br/>Session + Role Gate]
        AUD[Audit Log]
    end

    PU --> PUB
    AC --> PUB
    AD --> WEB

    PUB --> API
    WEB --> AUTH

    AUTH --> HSS
    AUTH --> IMP
    AUTH --> PDF
    AUTH --> REV
    AUTH --> OVR

    HSS --> DB
    HSS --> FS
    IMP --> DB
    PDF --> FS
    REV --> DB
    OVR --> DB
    API --> CQ
    CQ --> DB

    HSS --> AUD
    IMP --> AUD
    REV --> AUD
    OVR --> AUD
    AUD --> DB
```

---

## Deployment Diagram (Shared Hosting)

```mermaid
graph TB
    subgraph Internet["Internet"]
        CLT[Client / Browser]
    end

    subgraph SharedHosting["Shared Hosting Server"]
        direction TB
        subgraph WebRoot["Web Root (public/)"]
            PHP[PHP 8.2+ / Apache-mod_php]
        end

        subgraph AppCode["Laravel Application"]
            APP[app/ — Controllers, Services, Models]
            RTE[routes/ — api.php, web.php]
            CFG[config/ — auth, services, cache]
        end

        subgraph Storage["Local Storage"]
            UPL[storage/app/sources/<br/>PDFs, CSVs]
            LOG[storage/logs/<br/>Application Logs]
        end

        subgraph Database["Database"]
            MDB[(MySQL 8+)]
        end
    end

    CLT -->|HTTPS| PHP
    PHP --> APP
    APP --> RTE
    APP --> CFG
    APP --> UPL
    APP --> MDB
    APP --> LOG
```

> **Note for VPS/Cloud:** Add a Redis cache node, a queue worker process, and an object storage bucket (e.g. S3) for uploaded files.

---

## Application Layer Breakdown

| Layer | Components | Responsibility |
|---|---|---|
| **HTTP / Route** | `api.php`, `web.php` | Route binding, middleware groups |
| **Web Controller** | `Admin\HolidaySourceController`, `Admin\HolidayImportController`, `Admin\HolidayImportBatchController`, `Admin\HolidayController`, `Admin\HolidayOverrideController` | Admin dashboard requests, validation, upload/import/review/publish/override workflow |
| **API Controller** | `Api\HolidayController`, `Api\StateController` | Public JSON holiday and state responses |
| **Form Request** | `StoreHolidaySourceRequest`, `ImportCsvRequest`, etc. | Input validation & authorization |
| **Service** | `HolidaySourceService`, `HolidayCsvImportService`, `HolidayReviewService`, `HolidayOverrideService`, `HolidayApiService` | Business logic |
| **Model / Eloquent** | `HolidaySource`, `HolidayImportBatch`, `Holiday`, `HolidayOverride` | ORM & query scopes |
| **API Resource** | `HolidayResource`, `HolidayCollection`, `StateResource` | Response shaping |
| **Middleware** | `auth`, `verified`, `role:super_admin,data_admin` | Web session auth and admin role gate |
| **Observers / Events** | `HolidayObserver`, `AuditLogger` | Side-effects, audit log |

---

## Data Flow — Holiday Publish Workflow

```mermaid
flowchart LR
    A[Upload Source File] --> B[Store Source Metadata]
    B --> C[Import CSV / PDF]
    C --> D[Create Draft Holidays]
    D --> E{Admin Review}
    E -->|Approve| F[Mark as Confirmed]
    E -->|Reject| G[Mark as Rejected]
    F --> H[Publish Batch]
    H --> I[Holidays Status = published]
    I --> J[Available via Public API]
```

---

## Security Architecture

```mermaid
flowchart TB
    subgraph Public["Public Endpoints"]
        PE[GET /api/v1/holidays<br/>GET /api/v1/states<br/>GET /api/v1/holidays/check]
    end

    subgraph Admin["Admin Dashboard"]
        AE[GET/POST /admin/*]
    end

    PE --> PF[Published Filter<br/>Only status=published]

    AE --> SA[Web Session Auth<br/>auth middleware]
    SA --> VE[Verified Email<br/>verified middleware]
    VE --> RB[Role Gate<br/>super_admin / data_admin]
    RB --> BL[Business Logic]
    BL --> AL[Audit Log Entry]
```

---

## Caching Strategy

| Data | Cache Key Pattern | TTL | Invalidation Trigger |
|---|---|---|---|
| Holidays by year | `holidays.{year}` | 1 hour | Batch published |
| Holidays by year + state | `holidays.{year}.{state}` | 1 hour | Batch published or override applied |
| State list | `states.all` | 24 hours | Manual cache clear |
| Holiday check | `holiday.check.{date}.{state}` | 1 hour | Override applied |
