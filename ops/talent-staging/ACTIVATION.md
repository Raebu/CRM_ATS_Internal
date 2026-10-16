# Raeburn Talent: Contabo staging activation and P0 checklist

**Status:** preparation only. Do not infer that TLS, queue workers, clamd, off-host backups or integrations are running. Never run this against the live `/var/www/raeburn-crm` application or `raeburn_crm` database.

## Confirmed operator evidence (13 October 2026)
- Contabo `157.173.108.147`, Ubuntu 24.04, nginx, PHP-FPM 8.3.
- Staging root `/var/www/raeburn-talent-staging` owner `raeburnstaging`; staging MySQL `raeburn_talent_staging` only.
- Dedicated FPM socket `/run/php/raeburn-talent-staging.sock`.
- Loopback-only Nginx `127.0.0.1:8088`, SSH-tunnel browser login succeeded, synthetic vacancy/candidate/application created.
- Staging DNS resolved to **Vercel 216.198.79.65**, not Contabo; no staging certificate evidenced.
- Local production DB dump restored to temporary database with 60 tables. This is **not** an off-site application recovery plan.
- The public recruitment and external email switches are disabled. No Google/Microsoft provider authorisation demonstrated.

## Deployment order — no shortcuts
1. Run `bash ops/talent-staging/p0-preflight.sh` on the Contabo host after pulling the branch. Stop at any FAIL.
2. Arrange and **verify off-host encrypted backup** of the *production* database, uploads/private storage, `.env`, server configuration, and TLS materials as applicable. Encrypted at rest and in transfer. Never commit keys, database dumps or candidate records. Restore into a disposable isolated test location to prove recovery. A dump kept on the same VPS is insufficient.
3. Back up staging before provisioning additional services. Validate database and file restoration separately.
4. Provision unique `SESSION_COOKIE=raeburn_talent_staging_session`, `SESSION_DOMAIN=staging-crm.theraeburngroup.com` and `CACHE_PREFIX=raeburn_talent_staging_` in **staging `.env` only**. Review Laravel's session and cache config and verify no shared Redis, production queue or cross-subdomain cookies. Run `php artisan config:clear` as `raeburnstaging` after safe edits. Do not publish session domain to `.theraeburngroup.com`.
5. Access policy: SSH tunnel is the preferred private test route. For permanent access, configure HTTPS + an actual login barrier (**VPN/Zero Trust identity proxy or IP allowlist**, not just robots headers) *before making the upstream publicly reachable*. Keep `/install` blocked at proxy level and test direct unauthenticated requests. Keep 127.0.0.1:8088 loopback-only.
6. DNS owner must explicitly update **only** the staging hostname. Current A/alias must stop resolving to Vercel before certbot HTTP validation. Verify `getent ahostsv4 staging-crm.theraeburngroup.com` equals `157.173.108.147`. Do **not** overwrite `crm.theraeburngroup.com` or any unrelated record.
7. Create a **separate staging-only** HTTPS server block that forwards to `/run/php/raeburn-talent-staging.sock`, root `/var/www/raeburn-talent-staging/public`, and enforces the chosen external access barrier. Validate `nginx -t`; review entire `nginx -T` result for collisions and default-vhost fallthrough. Reconfigure Certbot only for staging. Set HSTS only after HTTPS is confirmed, avoiding inheritance issues on the live domain. Do not expose the HTTP installer.
8. Queue: after validating `QUEUE_CONNECTION=database`, isolated DB and an actual `jobs` table in staging, use the supplied `raeburn-talent-staging-queue.service` template. Install it under `/etc/systemd/system/` with root ownership and `0644` permissions; run `systemd-analyze verify`; then `systemctl daemon-reload`, `systemctl enable --now raeburn-talent-staging-queue.service`. Check `journalctl -u raeburn-talent-staging-queue.service`, worker permissions and resource use; do not start before synthetic queue idempotency/side-effect tests.
9. Malware scanning: install `clamav-daemon` and signature-updater via approved maintenance process, ensure latest definitions and adequate RAM, configure **only localhost TCP 127.0.0.1:3310** (`TCPSocket 3310` + `TCPAddr 127.0.0.1`) to match app `TALENT_CLAMAV_HOST`/`TALENT_CLAMAV_PORT`. Disable any externally bound scanner port. Verify scanner status and test with a clean file and official EICAR test string (synthetic only). `ClamAvScanner` is fail-closed; do not enable CV intake without worker test + quarantine/rejection verification.
10. Verify job state transitions, privacy data export/erasure, email approval/idempotency + Resend sandbox, offer acceptance ownership and expiry, calendar timezones/DST/conflict behaviour, access controls, audit logging, and restore procedure using synthetic candidates. Keep external email, published vacancies, public applications and candidate offer acceptance off until their individual launch gates pass.

## Known limitations
- This repository currently provides an ICS interview calendar link, **not demonstrated bidirectional Google and Microsoft calendar synchronisation**. Each requires a company-owned application registration, OAuth consent, secure token refresh storage and end-to-end tests. ChatGPT calendar connections are not the application's OAuth tokens.
- Resend sandbox/testing and webhook receipt verification have not been proved on VPS.
- Backup destination/key distribution and domain DNS management require operator configuration; never place them in committed scripts.
- Public candidate application intake and verified offer acceptance remain disabled pending the P0 readiness gate.
- A successful synthetic CRM form is not a P0 production sign-off.

## Next operator evidence
Provide redacted outputs of preflight, `systemctl status` on the staging queue, `ss -ltn` for clamd localhost socket, certificate validity, protected-site 401/403 for anonymous access and 200 for authorised access, off-site backup + restoration receipt, and synthetic P0 test results. **Never post generated passwords or `.env` values.**
