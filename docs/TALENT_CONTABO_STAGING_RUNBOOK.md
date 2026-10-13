# Raeburn Talent — isolated Contabo staging runbook

## Scope and approvals
- Production: https://crm.theraeburngroup.com. Never edit its release directory, database, credentials, queues or DNS as part of staging setup.
- Proposed staging: https://staging-crm.theraeburngroup.com. This hostname is NOT assumed to exist.
- Source: Raebu/CRM_ATS_Internal, draft PR #2 branch feature/native-raeburn-talent-foundation. Pin an exact SHA at deployment.
- Before acting: obtain read-only inventory of OS, reverse proxy, web root, PHP/FPM, database, disk/RAM/CPU, queue service, current backup policy, firewall and DNS ownership.
- STOP if available capacity is insufficient, production directory or DB cannot be distinguished, or a proven recovery route is absent.
- Do not clone real candidate, client, email, CV or other personal data to staging. Use fresh synthetic records only.

## Isolation model
1. Separate Unix service user with no write access to production files; separate app root such as /srv/raeburn-talent-staging (only after verifying actual layout).
2. Separate database and least-privileged database user, with no grants to the production database. Verify this in MySQL before migrations.
3. Separate APP_KEY, APP_URL, staging session cookie domain, storage directories, cache prefix, Redis database or queue prefix, and process/worker service names.
4. Dedicated reverse proxy virtual host; staging must be denied to the public by default (VPN, source IP allowlist, or SSO plus HTTPS) and not indexed. Validate routing does not fall through to production.
5. Dedicated PHP-FPM pool if feasible, and bounded process/resource limits. Never point a staging queue worker at production messages.
6. Keep all outward-facing switches false, and configure mail to a sandbox or log sink. No real emails, interviews, calendar events, job postings or candidate applications.
7. Staging: APP_ENV=staging, APP_DEBUG=false, HTTPS APP_URL, isolated persistent queue (not sync), separate DB, APP_KEY, CACHE_PREFIX, SESSION_COOKIE, QUEUE_CONNECTION. Confirm Laravel's actual config key support before setting.

## Recommended safe sequence (no automatic deployment)
- Inventory only: host/OS, free -h, df -h, systemctl list-units, PHP/composer versions, php-fpm pools, nginx/apache/Caddy virtual hosts, current backup job and DB privilege scheme. Do NOT paste live secrets into chat.
- Take and verify off-host production backup without modifying existing production deployment; rehearse restore to a disposable isolated database.
- Create isolated staging user, database, limited DB grants, code directory, dedicated .env and private storage. Use test secrets generated locally on the VPS.
- Restrict staging hostname ingress before DNS or HTTPS exposure; provision TLS only for staging hostname.
- Install Composer dependencies and run Laravel config checks and migrations against staging DB only, AFTER independent connection check shows the database name cannot equal production.
- Run talent:production-preflight as a screening tool; it is not a release approval and flags disabled public functionality as safe.
- Run synthetic browser end-to-end checks, ClamAV EICAR/clean-sample worker tests, permission tests, privacy workflows, email sandbox integration, queue retry/idempotency tests.
- Verify separate Google and Microsoft test calendars under an organisation-controlled recruitment identity; authorise minimum scopes. Never connect personal mailboxes by default.
- Verify staging backup/restore, performance limits and process health under contention while checking production uptime.
- Preserve rollback plan: disable staging vhost/processes and database credentials, without touching production.

## Calendar architecture
- Both Google Calendar API and Microsoft Graph Calendar require provider OAuth consent and refresh token lifecycle handling. ChatGPT connector access alone does not give the Laravel app provider tokens.
- Provision separate company-owned recruitment calendar for each provider and store provider IDs per recruiter.
- Use explicit mapping and sync direction; create/update/cancel with idempotency and reconcile provider IDs, timezone, DST, attendees and revoked permissions.
- Never write recruitment test events to an existing personal calendar.
- Existing downloadable ICS does not imply two-way provider synchronisation.

## Launch stop conditions
Human approval of UK GDPR notice, retention and lawful basis; verified candidate authentication and offer-delivery evidence; live email webhooks; clamd and isolated queue daemon operational; independent backup restoration; security and accessibility tests; release rollback drill. Production flags stay disabled until signed off.

## Required operator information
SSH access is not available from the currently connected Github/Calendar tools. A server administrator must provide an approved remote execution route or run the inventory commands themselves. Do not post passwords, SSH private keys or production .env secrets.
