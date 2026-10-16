# Raeburn Talent — Native Laravel implementation

## Source of requirements
The Raeburn Talent functional specification supplied 12 October 2026 is the requirements baseline. This repository implements Talent as native Laravel/Krayin modules, **not** as the original Next.js microservices monorepo. The replacement architectural decision is a modular monolith with domain separation and versioned events.

## Current first implementation (not production-ready)
- Dedicated tenant, vacancy, candidate and application tables.
- Separate candidates and applications, foreign-key constraints and per-tenant indexes.
- Single fixed Raeburn Group tenant bootstrap; request-supplied tenant IDs are not trusted.
- Authenticated internal Talent page, vacancy draft/status workflow, candidate creation, application creation/stage updates.
- First-party event outbox entries in the same database transaction as writes; no dispatcher yet.
- Basic first-touch source field on applications.

## Additional development committed (not tested or deployed)
- Versioned draft offers, salary/currency fields, internal interview scheduling and status changes.
- Stage transition history with user actor IDs.
- Event-outbox writes for interview and offer operations.
- Basic internal recruitment forms and Krayin ACL route mappings.

These controls are scaffolding, not a full offer-approval/acceptance workflow. Scheduling currently creates internal records only, not calendar invitations. Before enabling production use, enforce permitted stage transitions, role-based authorisation, cross-tenant tests, offer approval rules, concurrency-safe versioning and complete activity audit trails.

## Candidate search and talent pools
- Tenant-scoped candidate name/email search in internal recruitment workspace.
- Named talent pools with tenant-scoped membership validation and duplicate-safe insertion.
- Basic pool membership regression test added (not executed).
- Further privacy, granular membership authorization, pagination and retrieval controls are required.

## NOT implemented yet
External candidate application submission, public careers pages, approvals, public vacancy publication, complete interview scheduling integrations, complete offer approvals/acceptance, assessments, private CV storage and malware scanning, tenant-specific access/RBAC, robust organisation-level tenant context, board integrations, attribution history, integrations, queue consumers, notifications, audit actors, granular permissions, privacy/DSAR/retention, analytics, AI, feature flags, billing, and full security tests.

## Release safety
Never run migrations automatically on the live VPS. Deploy through a reviewable PR. Back up the production database and files before applying migrations. Validate tenant authorisation and private-record access before adding real candidate data. The CRM administrator password was exposed in an earlier chat and must be rotated before rollout.

## Next engineering sequence
1. Refine tenant context and RBAC with Krayin ACL.
2. Add unit/feature tests and CI for recruitment modules.
3. Private document upload and candidate privacy permissions.
4. Public jobs and secure application flow with atomic event writes.
5. Workflow history, interviews, assessments and offers.
6. Event publisher/worker, retries and notifications.
7. Distribution adapters and comprehensive multi-touch attribution.
8. Retention, DSAR, consent, auditable decisions, metrics and AI assistance.
