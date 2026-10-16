# Raeburn Talent staging — post-HTTPS security and functional acceptance

Environment: Cloudflare Access > Cloudflare Tunnel > loopback Nginx (127.0.0.1:8088) > staging Laravel 12.
No production deployment authorised. Do not test with real candidates until privacy and upload protections pass.

## Confirmed on 2026-10-14 (from operator evidence)
- Tunnel named raeburn-talent-staging Healthy; connector attached to Contabo vmi3653681.
- Published route staging-crm.theraeburngroup.com -> http://127.0.0.1:8088, catch-all 404.
- Cloudflare Access login challenge appeared in a private browser.
- Trusted-proxy forwarded-proto HTTPS form action verified.
- Staging administrator logged in and dashboard rendered.
- Production root HTTP 302 is reachability only, not full health.

## Required security sign-off (P0)
- [ ] Inspect Access policy **Raeburn Talent — Authorised Administrators**: Include individual explicitly authorised emails; no broad email-domain or Everyone allow; evaluate policy tester.
- [ ] Rotate exposed remotely managed tunnel token through Cloudflare dashboard; update **only** /etc/cloudflared/token (mode 0600, root-owned), restart cloudflared, confirm Healthy; invalidate existing compromised connector connections as documented by Cloudflare.
- [ ] In private browser, unauthenticated access to /admin/login must first show Access challenge. An email not included in Allow must be rejected.
- [ ] Browser DevTools Network: login POST and redirect Location schemes are HTTPS, no mixed content; test navigation and logout.
- [ ] Browser DevTools Storage: session and Access cookies Secure, HttpOnly where appropriate; verify session isolation from production CRM.
- [ ] Verify direct public HTTP/HTTPS to the origin cannot reach staging; port 8088 must remain bound to 127.0.0.1 only.
- [ ] Check CSRF 419 prevention, admin session expiry, and role-based permission denial.
- [ ] Confirm backup alerts delivered (not yet tested) and independent missed-backup alarm.
- [ ] Confirm off-server disaster recovery including app files, environment/key material and uploaded files.
- [ ] Confirm queue job processed, failed-job and restart tests (not only process active).
- [ ] Deploy only after PR review, CI green, and staging-only verification.

## Recruitment acceptance matrix (synthetic data only)
- [ ] Create/edit/search/duplicate candidate; verify permissions and audit trail.
- [ ] Create/edit draft vacancy; test publish restrictions and validation.
- [ ] Associate candidate to vacancy; move application stages; inspect history.
- [ ] Interview schedule/reschedule/cancel; prevent collisions; calendar integration test.
- [ ] Draft/approve offer without any external send; ensure delivery disabled in staging.
- [ ] CV upload: clean accepted, EICAR fixture rejected/quarantined, fail-closed on scanner failure; verify storage isolation.
- [ ] Email suppression: external recruitment messages remain disabled; Resend templates/webhooks tested in sandbox.
- [ ] GDPR subject access, correction, deletion, retention, export; RBAC separation; audit logs.
- [ ] Automated PHPUnit/Pest and browser E2E regression tests pass.

## Deployment caveats
- Code change to bootstrap/app.php trusts X-Forwarded-Proto only from loopback proxy. Verify staging Nginx maintains this trust boundary; do not forward untrusted incoming X-Forwarded-Proto unchanged.
- Current queue template now uses Restart=always, matching staging server override.
- Do not merge this feature branch into main as a side effect of completing these checks.
