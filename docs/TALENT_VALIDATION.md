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

## Tenant isolation progress
- Added a common tenant-scoped resolver for jobs, candidates, applications and talent pools.
- Recruitment operations and talent-pool membership controllers now use the resolver for related records.
- Added a regression test asserting records belonging to another tenant cannot be resolved. This integration test has not yet been executed in CI.
- IMPORTANT: This is defense-in-depth, not full multi-tenancy. The application still uses a fixed Raeburn tenant; authenticated tenant membership, comprehensive authorisation, and constraints across every relation must be implemented before commercial tenant onboarding.

## Dedicated recruitment permissions
- Added fail-closed route middleware on each internal Talent route, independently of Krayin's menu ACL.
- Admin roles with `permission_type=all` retain access; custom roles must carry the *exact* permission for each operation.
- Unit tests cover simple allowed/denied role permission combinations, but complete authenticated HTTP denial tests are still required.
- The Talent dashboard currently exposes names/emails to the `talent` permission; refine view permissions, field-level access and tenant membership before production recruitment data.

## Privacy request tracker
- Internal, restricted tracking of access, correction, erasure, objection, portability and restriction requests is now scaffolded.
- Requests start pending identity verification; status updates are controlled and emit records to the outbox.
- This does NOT fulfil DSARs, export files, erase data, determine lawful bases, calculate statutory deadlines, or implement consent/retention compliance.
- Before production: privacy-reviewer permissions, secure identity verification, full request/decision audit, legal holds, response deadlines, actual data export/erasure pipeline and human review are required.

## Durable event delivery foundation
- Added an at-least-once local Laravel-event outbox dispatcher and `talent:outbox-dispatch` command, with persisted retries, claim leases and dead-letter status.
- Events are marked published after the local Laravel dispatcher returns. This is NOT proof of delivery by external services or asynchronously queued listeners.
- Event consumers must be idempotent by event_id; consumer deduplication, production scheduling, real notifications, alerting and external delivery are NOT implemented.
- Do not enable an automatic scheduler until duplicate processing and error paths are integration-tested.

## Notification projection foundation
- Application, interview and privacy request events can now create idempotent **internal** notification records in `talent_notifications`.
- A unique event ID/kind constraint prevents repeated outbox delivery from duplicating a notification.
- The notification projector runs before the outbox event is marked published. It does not send external email, SMS or WhatsApp.
- Remaining: notification inbox UI, recipients and access policies, scheduled alerts, email templates and delivery, full observability and production verification.

## Internal notification inbox
- Added a paginated, tenant-scoped notification inbox with separate `talent.notifications.view` and `talent.notifications.manage` permissions.
- Acknowledge is an idempotent restricted POST/PATCH action and is not an external delivery confirmation.
- No recipient-based routing, external email, escalations, scheduling or notification delivery confirmation yet.
- Production rollout still blocked pending end-to-end access control tests and complete security review.
