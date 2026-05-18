# Progress Checklist — Malaysia Public Holiday API

> Last updated: 2026-05-18 (Standard API error envelope complete — checklist fully done)
> Based on [SRS §9 Functional Requirements](./00-software-requirements-specification.md) and [§16 Admin Interface Requirements](./00-software-requirements-specification.md).

Legend: ✅ Done · 🚧 Partial · ❌ Not started

---

## 1. Data Model & Migrations

| # | Item | Status |
|---|------|--------|
| 1.1 | `holiday_sources` table | ✅ |
| 1.2 | `holiday_import_batches` table | ✅ |
| 1.3 | `holiday_import_rows` table | ✅ |
| 1.4 | `holidays` table | ✅ |
| 1.5 | `holiday_overrides` table | ✅ |
| 1.6 | `api_clients` table | ✅ |
| 1.7 | `audit_logs` table | ✅ |
| 1.8 | `role` column on `users` table | ✅ |

---

## 2. Models

| # | Item | Status |
|---|------|--------|
| 2.1 | `HolidaySource` model | ✅ |
| 2.2 | `HolidayImportBatch` model | ✅ |
| 2.3 | `HolidayImportRow` model | ✅ |
| 2.4 | `Holiday` model | ✅ |
| 2.5 | `HolidayOverride` model | ✅ |
| 2.6 | `ApiClient` model | ✅ |
| 2.7 | `AuditLog` model | ✅ |

---

## 3. Source Management (FR-001 – FR-004)

| # | FR | Item | Status |
|---|----|------|--------|
| 3.1 | FR-001 | Upload source file (PDF / CSV) | ✅ |
| 3.2 | FR-002 | Store official source URL | ✅ |
| 3.3 | FR-003 | Calculate file checksum | ✅ `hash_file('sha256')` in `HolidaySourceController::store` |
| 3.4 | FR-004 | View source history list | ✅ |

---

## 4. Holiday Import (FR-005 – FR-010)

| # | FR | Item | Status |
|---|----|------|--------|
| 4.1 | FR-005 | Import holidays from CSV | ✅ |
| 4.2 | FR-006 | Extract holidays from PDF | ✅ |
| 4.3 | FR-007 | Save imported rows as draft | ✅ |
| 4.4 | FR-008 | Validate year, date, state code, name, type | ✅ |
| 4.5 | FR-009 | Detect duplicate holidays | ✅ |
| 4.6 | FR-010 | Mark holidays as subject to change | ✅ `is_subject_to_change` stored, surfaced in API & import warnings |

---

## 5. Review and Publishing (FR-011 – FR-015)

| # | FR | Item | Status |
|---|----|------|--------|
| 5.1 | FR-011 | Admin preview screen for import batch | ✅ |
| 5.2 | FR-012 | Edit draft holiday records | ✅ |
| 5.3 | FR-013 | Reject imported records | ✅ |
| 5.4 | FR-014 | Publish approved holiday batch | ✅ |
| 5.5 | FR-015 | Exclude draft records from API output | ✅ API filters `status = 'published'` |

---

## 6. Holiday Override (FR-016 – FR-020)

| # | FR | Item | Status |
|---|----|------|--------|
| 6.1 | FR-016 | Add a new holiday override | ✅ |
| 6.2 | FR-017 | Cancel / remove a published holiday | ✅ `action = 'remove'` sets status to `cancelled` |
| 6.3 | FR-018 | Replace holiday date via override | ✅ `action = 'replace'` updates date & marks `overridden` |
| 6.4 | FR-019 | Rename holiday via override | ✅ `action = 'rename'` updates name & marks `overridden` |
| 6.5 | FR-020 | Store override reason, source URL, approver, timestamp | ✅ `reason`, `source_url`, `approved_by`, `approved_at` stored |

---

## 7. Public API Endpoints (FR-021 – FR-025)

| # | FR | Endpoint | Status |
|---|----|----------|--------|
| 7.1 | FR-021 | `GET /api/v1/holidays?year=` | ✅ |
| 7.2 | FR-022 | `GET /api/v1/holidays?year=&state=` | ✅ |
| 7.3 | FR-023 | `GET /api/v1/holidays/check?date=&state=` | ✅ |
| 7.4 | — | `GET /api/v1/states` | ✅ |
| 7.5 | FR-024 | Filter by holiday scope (federal / state / replacement / custom) | ✅ |
| 7.6 | FR-025 | Optionally return source metadata per holiday | ✅ |

---

## 8. Admin Interface Pages (§16)

| # | Page | Feature | Status |
|---|------|---------|--------|
| 8.1 | Holiday Sources | Upload source file | ✅ |
| 8.2 | Holiday Sources | Enter / view source URL | ✅ |
| 8.3 | Holiday Sources | View file checksum | ✅ stored & displayed in `show` view |
| 8.4 | Holiday Sources | View source status | ✅ |
| 8.5 | Holiday Imports | Import from CSV | ✅ |
| 8.6 | Holiday Imports | Parse PDF | ✅ |
| 8.7 | Holiday Imports | View import batch result | ✅ |
| 8.8 | Holiday Imports | View validation errors | ✅ |
| 8.9 | Holiday Review | Preview draft holidays | ✅ |
| 8.10 | Holiday Review | Edit draft records | ✅ |
| 8.11 | Holiday Review | Reject records | ✅ |
| 8.12 | Holiday Review | Approve records | ✅ publish action confirms all draft/confirmed rows |
| 8.13 | Holiday Review | Publish batch | ✅ |
| 8.14 | Holiday Management | Holiday list with search | ✅ |
| 8.15 | Holiday Management | Filter by year | ✅ |
| 8.16 | Holiday Management | Filter by state | ✅ |
| 8.17 | Holiday Management | Filter by scope | ✅ |
| 8.18 | Holiday Management | Add manual holiday | ✅ |
| 8.19 | Holiday Management | Create override from list | ✅ |
| 8.20 | Manual Overrides | Override index list | ✅ |
| 8.21 | Manual Overrides | Create override form | ✅ |
| 8.22 | Manual Overrides | Edit / delete override | ✅ |
| 8.23 | Audit Logs | View create/update/delete history | ✅ |
| 8.24 | Audit Logs | View publisher / approver | ✅ |
| 8.25 | Audit Logs | View old and new values | ✅ |

---

## 9. Security & API Clients (§17)

| # | Item | Status |
|---|------|--------|
| 9.1 | Admin routes require authentication + role middleware | ✅ |
| 9.2 | Role-based authorization (`super_admin`, `data_admin`) | ✅ |
| 9.3 | API key creation & management for `api_clients` | ✅ |
| 9.4 | API key authentication middleware on private API routes | ✅ |
| 9.5 | Rate limiting on API endpoints | ✅ |
| 9.6 | File upload type validation (PDF / CSV only, max 10 MB) | ✅ `File::types(['pdf','csv','txt'])->max(10*1024)` enforced |
| 9.7 | Source file checksum enforcement | ✅ SHA-256 computed & stored on upload |

---

## 10. Error Response Format (§18)

| # | Item | Status |
|---|------|--------|
| 10.1 | Standardised `error.code / message / details` envelope | ✅ |
| 10.2 | All defined error codes handled (`VALIDATION_ERROR`, `UNAUTHORIZED`, etc.) | ✅ |

---

## 11. Audit Logging (§19)

| # | Event | Status |
|---|-------|--------|
| 11.1 | `source_uploaded` | ✅ |
| 11.2 | `source_updated` | ✅ |
| 11.3 | `source_deleted` | ✅ |
| 11.4 | `csv_import_started` / `csv_import_completed` | ✅ |
| 11.5 | `pdf_parse_started` / `pdf_parse_completed` | ✅ |
| 11.6 | `holiday_created` / `holiday_updated` / `holiday_deleted` | ✅ |
| 11.7 | `holiday_published` | ✅ |
| 11.8 | `override_created` / `override_approved` / `override_rejected` | ✅ |
| 11.9 | `api_client_created` / `api_client_disabled` | ✅ |

---

## 12. Tests

| # | Item | Status |
|---|------|--------|
| 12.1 | Dashboard test | ✅ |
| 12.2 | Admin routes authorization test | ✅ |
| 12.3 | Holiday import workflow test | ✅ |
| 12.4 | PDF extraction test | ✅ |
| 12.5 | Public API endpoint tests | ✅ |
| 12.6 | Holiday override tests | ✅ |
| 12.7 | Audit log tests | ✅ |
| 12.8 | API key / rate limiting tests | ✅ |

---

## MVP Completion Summary

| Module | Done | Partial | Not Started | Total |
|--------|-----:|--------:|------------:|------:|
| Data Model & Migrations | 8 | 0 | 0 | 8 |
| Models | 7 | 0 | 0 | 7 |
| Source Management | 4 | 0 | 0 | 4 |
| Holiday Import | 6 | 0 | 0 | 6 |
| Review & Publishing | 5 | 0 | 0 | 5 |
| Holiday Override | 5 | 0 | 0 | 5 |
| Public API Endpoints | 6 | 0 | 0 | 6 |
| Admin Interface Pages | 25 | 0 | 0 | 25 |
| Security & API Clients | 7 | 0 | 0 | 7 |
| Error Response Format | 2 | 0 | 0 | 2 |
| Audit Logging | 9 | 0 | 0 | 9 |
| Tests | 8 | 0 | 0 | 8 |
| **Total** | **92** | **0** | **0** | **92** |

> **Overall progress: 100% complete** (92 / 92 items done)

### What's left

None. All checklist items are complete.
