# Analytics and Privacy

## What Is Collected

The application keeps first-party request analytics in `request_logs` for traffic volume, endpoint usage, route type, status code, response time, and anonymous daily visitor estimates.

New analytics records store:

- `visitor_hash`: a daily HMAC-SHA256 identifier derived from the normalized request IP, current UTC date, and `ANALYTICS_HASH_SECRET`.
- `path`: the normalized request path.
- `full_url`: a sanitized relative URL containing only allowlisted UTM parameters.
- `method`, `status_code`, `user_agent`, `duration_ms`, `user_id`, `route_type`, `created_at`, and `expires_at`.

Raw IP addresses are processed temporarily to create `visitor_hash`, but `request_logs.ip_address` is `null` by default. Raw analytics IP storage requires `ANALYTICS_STORE_RAW_IP=true`.

## Query Parameters

Analytics URL storage uses an allowlist. Only these query parameters are preserved:

- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_term`
- `utm_content`

All other query parameters are discarded, including tokens, passwords, API keys, authorization codes, email addresses, phone numbers, and identity numbers.

The analytics middleware never logs request bodies, authorization headers, cookies, session identifiers, or CSRF tokens.

## Anonymous Visitor Identifiers

`visitor_hash` rotates daily. It is suitable for daily anonymous visitor estimates, not for identifying unique people across weekly or monthly ranges.

Limitations:

- The same person can appear as different daily identifiers on different UTC dates.
- Multiple people sharing one public IP can appear as one daily identifier.
- One person using multiple networks can appear as multiple daily identifiers.

`ANALYTICS_HASH_SECRET` must be set to a cryptographically secure server-side secret. The application does not generate a secret automatically and must never expose this value to frontend JavaScript.

If analytics is enabled but `ANALYTICS_HASH_SECRET` is missing, analytics for that request is skipped safely and a rate-limited configuration warning is written without visitor data.

## Retention

`ANALYTICS_RETENTION_DAYS` controls `request_logs.expires_at` for new records. The `expires_at` value is fixed when each record is created. Changing `ANALYTICS_RETENTION_DAYS` later does not recalculate existing rows.

Completed UTC days are rolled up into `analytics_daily_route_stats`, `analytics_daily_path_stats`, and `analytics_daily_visitor_stats`. The admin dashboard reads those summary tables for historical ranges and queries `request_logs` only for today. Run `php artisan analytics:rollup` once after deploy to backfill, then keep it on the daily schedule. Use `--limit=0` for an unbounded backfill from SSH; the scheduled run defaults to 30 days per execution so shared-hosting time limits are not exceeded.

Run `php artisan analytics:prune` to delete expired request logs and daily summary rows older than `ANALYTICS_RETENTION_DAYS`. The commands are scheduled daily in `routes/console.php`, but production must run Laravel's scheduler:

```shell
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Audit logs are separate security records. They are not pruned unless `AUDIT_LOG_PRUNING_ENABLED=true`. When enabled, `AUDIT_LOG_RETENTION_DAYS` controls the audit cutoff.

Historical raw analytics IP cleanup is separate:

```shell
php artisan analytics:anonymize-request-ips --dry-run --before=2026-01-01
php artisan analytics:anonymize-request-ips --force --before=2026-01-01
```

This command affects only `request_logs`, requires an explicit cutoff date, and never deletes Laravel sessions or audit logs.

## Proxy Configuration

By default, no reverse proxy is trusted. Configure only explicit trusted proxy IPs or CIDR ranges:

```dotenv
ANALYTICS_TRUSTED_PROXIES=10.0.0.10,10.0.1.0/24
```

The application does not fetch Cloudflare or provider IP ranges dynamically during requests or application boot. If Cloudflare or a load balancer is used, deployment automation must keep the explicit trusted proxy list current.

## Access

Analytics is available only to authenticated, verified admin users through the admin analytics dashboard. The dashboard displays daily anonymous visitor identifiers using a short hash prefix and does not display raw analytics IP addresses.

Audit log IP addresses are separate from analytics and are retained for admin traceability and security investigations. They are not exposed through public APIs.

## Privacy Notice Draft

This service uses first-party operational analytics to understand traffic volume, endpoint performance, and API usage. We temporarily process your IP address to create a daily rotating anonymous identifier, but we do not store raw IP addresses in normal analytics records by default. Analytics URLs are sanitized and only approved campaign parameters are retained. We do not use third-party analytics, advertising trackers, geolocation services, or request-body tracking.
