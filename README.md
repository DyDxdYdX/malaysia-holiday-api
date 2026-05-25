# 🇲🇾 Malaysia Public Holiday API

A verified, admin-reviewed REST API for Malaysian public holiday data — built with Laravel.

**API Base URL:** `https://malaysia-holiday.dydxsoft.my/api/v1`

---

## Background

This project is a full revamp of the original [**Sabah Public Holiday Fetcher**](https://github.com/DyDxdYdX/sabah-public-holiday-fetcher) — a Node.js static-file scraper that started as Sabah-only support before expanding to multiple states.

### What was the original?

The original fetcher worked by:
- Scraping holiday data from [Office Holidays](https://www.officeholidays.com/countries/malaysia) via `generate_api.js`
- Generating static JSON files deployed to GitHub Pages
- Serving holidays at `GET /api/{state}/{year}.json`
- Running monthly via GitHub Actions

**Original endpoints (still live at `https://sabah-holiday.dydxsoft.my`):**

| Endpoint | Description |
|---|---|
| `GET /api/states.json` | List of supported states |
| `GET /api/years.json` | Available years (Sabah, for backward compat) |
| `GET /api/{state}/{year}.json` | Holidays for a state and year |
| `GET /api/{year}.json` | Sabah holidays (backward compat) |
| `GET /api/metadata.json` | API metadata with per-state coverage |

Each holiday object contained: `date`, `day_of_week`, `holiday_name`, `is_mandatory`.

### Why revamp?

| Problem | Original Approach | This Revamp |
|---|---|---|
| Data source | Scraped from third-party website | Official government documents (JPM/BKPP) |
| Data accuracy | Keyword-based; not 100% reliable | Admin-reviewed before publishing |
| Corrections | Re-scrape only | Manual overrides with audit trail |
| State coverage | All states via scraper | All 13 states + 3 federal territories |
| Traceability | None | Every record linked to a source |
| API format | Static JSON files | Dynamic versioned REST API (`/api/v1/`) |
| Hosting | GitHub Pages (static) | PHP shared hosting / VPS |

---

## Overview

This Laravel API provides a **centralized, source-traced, admin-approved** holiday dataset. No data is published until a human administrator has reviewed and approved it from an official source.

Official source reference: [www.kabinet.gov.my/hari-kelepasan-am](https://www.kabinet.gov.my/hari-kelepasan-am/)

---

## Features

- 📥 **CSV & PDF import** — bulk-load holidays from official government documents
- ✅ **Admin review workflow** — all imported data stays as draft until approved and published
- 🗓️ **State-level holidays** — supports all 13 states + 3 federal territories
- 🔁 **Manual overrides** — add, remove, rename, or replace published holidays with a full audit trail
- 🔍 **Date check endpoint** — query whether a specific date is a holiday in a given state
- 📋 **Source traceability** — every holiday is linked to its import source or override record
- 🗒️ **Audit logging** — all create, update, publish, and override actions are logged

---

## Tech Stack

| Component | Technology |
|---|---|
| Language | PHP 8.4+ |
| Framework | Laravel |
| Database | MySQL 8+ |
| Auth | Laravel Fortify |
| Frontend | Livewire + Flux UI |
| Testing | Pest |
| Hosting | Shared hosting / VPS |

---

## Getting Started

### Requirements

- PHP 8.4+
- Composer
- MySQL 8+ or PostgreSQL 14+
- Laravel 13
- Node.js (for frontend assets)

### Installation

```bash
git clone https://github.com/DyDxdYdX/malaysia-holiday-api.git
cd malaysia-holiday-api

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure your database in .env, then:
php artisan migrate
php artisan db:seed

npm run build
```

### Running Locally (Laravel Herd)

The app is served automatically by [Laravel Herd](https://herd.laravel.com/) at:

```
https://malaysia-holiday-api.test
```

---

## API Usage

### Get holidays by year

```http
GET https://malaysia-holiday.dydxsoft.my/api/v1/holidays?year=2026
```

### Get holidays by year and state

```http
GET https://malaysia-holiday.dydxsoft.my/api/v1/holidays?year=2026&state=SBH
```

### Check if a date is a holiday

```http
GET https://malaysia-holiday.dydxsoft.my/api/v1/holidays/check?date=2026-05-30&state=SBH
```

### Get supported states

```http
GET https://malaysia-holiday.dydxsoft.my/api/v1/states
```

All responses are JSON. No API key or account is required. Only **published** holidays are returned by public endpoints.

#### JavaScript example

```javascript
fetch("https://malaysia-holiday.dydxsoft.my/api/v1/holidays?year=2026&state=SBH")
  .then((response) => response.json())
  .then((data) => console.log(data));
```

#### PHP example

```php
$response = file_get_contents('https://malaysia-holiday.dydxsoft.my/api/v1/holidays?year=2026&state=SWK');
$holidays = json_decode($response, true);
print_r($holidays);
```

---

## State Codes

| Code | State / Territory |
|---|---|
| `JHR` | Johor |
| `KDH` | Kedah |
| `KTN` | Kelantan |
| `MLK` | Melaka |
| `NSN` | Negeri Sembilan |
| `PHG` | Pahang |
| `PRK` | Perak |
| `PLS` | Perlis |
| `PNG` | Pulau Pinang |
| `SBH` | Sabah |
| `SWK` | Sarawak |
| `SGR` | Selangor |
| `TRG` | Terengganu |
| `KUL` | W.P. Kuala Lumpur |
| `LBN` | W.P. Labuan |
| `PJY` | W.P. Putrajaya |

---

## Admin Workflow

```
Upload Source → Import CSV → Review Drafts → Publish → Serve via API
```

1. **Upload** an official PDF or CSV source (e.g. JPM Hari Kelepasan Am)
2. **Import** holidays from the CSV — creates draft records linked to the source
3. **Review** each draft in the admin panel — edit, reject, or approve rows
4. **Publish** the batch — all approved records become available through the API
5. Use **overrides** at any time to add, rename, replace, or cancel individual holidays

Public registration is disabled. Admin accounts are seeded or created manually.

---

## Running Tests

```bash
php artisan test --compact
```

---

## Documentation

| Document | Description |
|---|---|
| [Software Requirements Specification](docs/00-software-requirements-specification.md) | Full SRS with functional and non-functional requirements |
| [System Architecture](docs/01-system-architecture.md) | Component diagram, deployment diagram, service layer breakdown |
| [Database Design](docs/02-database-design.md) | ERD, table schemas, index strategy |
| [Use Case Diagrams](docs/03-use-case-diagrams.md) | UML use cases for all actors and features |
| [Sequence Diagrams](docs/04-sequence-diagrams.md) | Step-by-step flows: import, review, publish, override, API |
| [API Reference](docs/05-api-reference.md) | Full endpoint docs with request/response examples |
| [State Machine Diagrams](docs/06-state-machines.md) | Entity lifecycle diagrams for holidays, batches, overrides |

---

## License

MIT
