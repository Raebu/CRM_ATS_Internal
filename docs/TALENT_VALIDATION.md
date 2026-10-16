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

## Analytics reporting
- Tenant-scoped vacancy totals, published vacancies, application stages and source attribution added.
- Integration test checks fixed Raeburn reporting excludes records from a second tenant; policy test checks `talent.analytics.view` is separate from base Talent access.
- Reporting does not imply general multitenancy: user membership is still fixed to the Raeburn tenant. Conversion funnels, time-to-hire and attribution quality checks remain outstanding.

## Funnel analytics extension
- Native reports now include tenant-scoped interview count, offer count and accepted-offer count.
- Tenant isolation test creates matching interview and offer data in two separate tenants and expects only the Raeburn records in reporting.
- These metrics count records, not distinct candidates, funnel conversion percentages, time-to-hire or confirmed external acceptances. Production reporting remains disabled until release review.

## Conversion and duration metrics
- Interview/application and offer/application conversion count distinct applications, preventing multiple interview/offer versions from inflating their numerators.
- Accepted-offer ratio uses distinct applications with an accepted offer divided by distinct offered applications.
- Average duration uses submitted_at to accepted_at for accepted offer rows with chronological timestamps. It measures time to accepted offer, **not** time-to-hire or actual employment start. The same application can contribute multiple rows if multiple offers are marked accepted; that requires a later data-integrity rule.
- Zero denominator shows N/A. New analytics regression assertions are covered by the MySQL feature test pipeline.

## Interview reminder foundation
- Added `php artisan talent:interview-reminders --hours=24` to create **internal inbox reminders** for scheduled upcoming interviews.
- Stable identifiers prevent duplicate reminders on repeated command runs; rescheduled interviews generate a new identifier, so stale-reminder cancellation remains outstanding.
- No scheduler has been enabled; no emails, texts or calendar invitations are sent.
- Must still add recipient assignment, cancellation/rescheduling handling, timing tests, and authenticated permissions before production automation.

## Reminder CI repair / stale reminder protection
- Fixed UUIDv5 generation to use Ramsey Uuid directly (`Str::uuid5` does not exist).
- Reminder command now cancels pending inbox reminders for cancelled/completed/deleted or rescheduled interviews, or interviews already started.
- Cancellation is applied when the command next runs; scheduler, email delivery and real-time rescind are not yet available.
- MySQL integration test now exercises repeat runs and cancellation; awaiting fresh CI result.

## Interview rescheduling safeguards
- The internal interview update endpoint now accepts paired start/end times, validates ordering, and emits a distinct `interview.rescheduled.v1` record when the start changes.
- Reminder integration test now checks that a reschedule cancels the old pending reminder and creates one for the new start time, then cancellation clears the remaining pending reminder.
- No automatic external communications or scheduler are enabled. The new CI run must pass before relying on these changes.

## Draft communications foundation
- Added static candidate-acknowledgement and interview-reminder email template drafts and an idempotent, tenant-scoped email delivery draft planner.
- No sending function or provider API is wired; `TALENT_EXTERNAL_EMAIL_ENABLED` defaults false. Delivery rows remain `draft`, not `sent`.
- MySQL tests assert draft deduplication and tenant isolation. Before rollout: verified recipients, lawful messaging rules, human approval, content review, send adapter with provider idempotency, retry/error audit, opt-outs where required, and actual end-to-end tests.

## Human email review and approval
- Added restricted email-draft listing, draft creation, and approval actions with separate read/create/approve ACL entries.
- Approval stores an entered recipient address, approving administrator and approval timestamp. Input validation checks email format, NOT ownership, identity or candidate association.
- No outbound send code is connected and the external email feature flag remains false.
- Before rollout: bind verified candidate contact details to the correct notification/application, prohibit arbitrary recipient input, lock template versions, audit approvals, handle revocation and sender restrictions, and test full authenticated HTTP authorisation.

## Recipient-binding hardening (12 October)
- Removed free-form recipient entry from draft approval. Application acknowledgement approvals now derive the recipient email from the existing tenant-scoped application and its tenant-scoped candidate.
- Newly created application events now carry the canonical application_id; notification projection retains it for recipient resolution.
- Other template/kind combinations, legacy notification rows without a canonical application id, and invalid candidate emails fail closed at approval. External delivery still disabled.
- Remaining: candidate email verification/ownership proof, consent and lawful-basis review, template version locking, approval revocation, email send adapter, and comprehensive authenticated HTTP approval tests.

## Email approval integrity gate
- Approval now verifies the stored versioned template snapshot and SHA-256 checksum; missing or modified snapshots are rejected before a draft can be approved.
- Database feature tests assert that untampered drafts validate, and modified snapshot subjects fail validation.
- Snapshot hashes are integrity checks against accidental or unauthorised field edits, not digital signatures or tamper-proof audit records. Approval auditing, verified contact ownership and external email delivery are still unfinished.

## Release-consolidation hardening
- Added `talent.candidates.view` as a distinct personal-data permission. The main Talent dashboard now returns empty candidate/application collections unless the user has this permission (or all-access role).
- Remaining sensitive routes and linked views need a complete authenticated permission matrix; this one safeguard does not establish GDPR readiness.
- See `docs/TALENT_RELEASE_READINESS.md` for complete stop-ship controls and full-spec backlog. Do not merge or deploy based solely on green syntax and feature CI.
