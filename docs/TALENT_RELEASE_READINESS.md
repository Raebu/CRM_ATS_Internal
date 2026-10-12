# Raeburn Talent — release readiness and remaining implementation

**Status: NOT RELEASE READY.** This checklist is the authoritative go/no-go for the draft native Laravel/Krayin Talent module. An Actions green check covers only the tests configured in CI, not product completion.

## Stop-ship controls
- [ ] Perform full authenticated route/role matrix tests for every Talent endpoint, including negative tests for cross-tenant record IDs and parent/child RBAC.
- [ ] Implement real tenant membership and organisation hierarchy, or explicitly constrain and test a *single Raeburn-only installation* before first release.
- [ ] Verify candidate/application access: base Talent access must not disclose names, emails or application details; only `talent.candidates.view` permits this on the main page.
- [ ] Finalise UK GDPR notices, lawful bases, retention schedules, DSAR export/erasure, identity verification and legal holds with human review.
- [ ] Install and secure clamd, configure monitored worker and scan clean/infected/error samples; never release pending files.
- [ ] Verify backups with an actual restoration drill; document rollback including DB schema considerations.
- [ ] Validate email recipient ownership and send approvals; keep `TALENT_EXTERNAL_EMAIL_ENABLED=false` until production-grade delivery, feedback and duplicate suppression.
- [ ] Keep public applications, publishing and verified offer acceptance disabled until separate launch gates pass.
- [ ] Run staging migration, end-to-end recruiter browser and accessibility/mobile workflows, performance tests, and security review.

## Core recruiter workflows still to build
- [ ] Vacancy versions and approval lifecycle, job details and hiring-team assignments.
- [ ] Candidate deduplication/merge, imports and provenance.
- [ ] Screening questions, structured assessments and interview evaluations.
- [ ] Offer approval, verified candidate acceptance, placement and onboarding handovers.
- [ ] Tasks, ownership, notifications, SLAs and communications.
- [ ] Recruiter dashboard UX polish, empty states, filters and accessibility.

## Full platform scope (after internal MVP)
- [ ] Public inclusive careers portals and specialised gateways.
- [ ] Google JobPosting schema and authorised job board feeds/connectors.
- [ ] Email/calendar/provider webhooks and callback verification.
- [ ] Multi-touch attribution and vacancy-level analytics.
- [ ] Advisory AI with fairness controls and human decisions.
- [ ] Optional commercial tenancy, branding, invoicing and onboarding.
- [ ] API versioning, observability, incident response, alerts and production runbooks.

## Release sequence
1. Complete and greenlight **security/privacy/operational stop-ship controls**.
2. Verify full internal recruitment journey on staging with non-real synthetic candidates.
3. Cut a release candidate; perform independent review and backup restore test.
4. Merge only after approval; migrate and deploy with rollback capability.
5. Verify production health, audit logs, workers and private file security before any real candidate intake.

There is no claim that this checklist or the earlier scaffold completes the product.
