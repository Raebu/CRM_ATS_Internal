# Talent validation and release checklist

## Automated checks
`.github/workflows/talent-php-checks.yml` checks PHP syntax and executes the transition policy unit test when relevant files change. This workflow is not equivalent to full end-to-end test coverage.

## Required before merge
- [ ] CI succeeds and remaining integration tests run against MySQL 8, matching production.
- [ ] Verify Krayin ACL resolves the Talent route map correctly for administrators and restricted roles.
- [ ] Confirm app admin layout and Tailwind styling compile.
- [ ] Verify the full migration sequence and rollback on a disposable database.
- [ ] Exercise cross-tenant and role-denied requests for every endpoint; do not enable future tenant onboarding without enforced authorisation.
- [ ] Review file upload behaviour: quarantined storage, scanning, retention, and disclosure-safe application errors.
- [ ] Verify outbox publisher/consumers are implemented and idempotent before relying on events for notifications.
- [ ] Confirm privacy notice, candidate rights and signed-off recruitment processing before enabling public form.
- [ ] Confirm tested encrypted offsite backup/restore for the live Contabo database.
- [ ] Rotate the previously exposed admin password.

No tests from this checklist should be described as passed unless CI or a recorded local run verifies them.
