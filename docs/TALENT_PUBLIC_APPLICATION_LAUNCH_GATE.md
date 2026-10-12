# Public application / CV intake launch gate

Default: `TALENT_PUBLIC_APPLICATIONS_ENABLED=false`. Do not enable on production until all gates are met.

- [ ] Publish accurate recruitment privacy notice with controller identity, lawful basis, purposes, retention, candidate rights and international processing disclosure where applicable.
- [ ] Replace the placeholder consent/acknowledgement version with a published and versioned notice; record notice served, not substitute an arbitrary consent for a valid lawful basis.
- [ ] Add malware scanning before any file can be opened or downloaded, strict quarantined storage and document access audit trails.
- [ ] Add dedicated tenant-scoped, permission-checked CV streaming/download endpoint after clean scan.
- [ ] Verify private disk path outside public root (never use public URLs for CVs).
- [ ] Add bot protection, abuse/rate-limit tests and submission monitoring.
- [ ] Add privacy retention/deletion and DSAR handling; do not silently overwrite candidate profiles.
- [ ] Add candidate identity verification for repeat applications and account recovery, avoiding enumeration of existing applications.
- [ ] Add job approval workflow and ensure only truly approved, published vacancies are visible.
- [ ] Establish delivery worker for transactional outbox; sending failures must not roll back applications.
- [ ] Test concurrent duplicate submissions and cross-tenant isolation.
- [ ] Run on staging, review access controls, automated tests and back up before production release.

The current public page is basic scaffolding. These controls are prerequisites to collecting real candidate data.

## October 12 implementation update
- Added a private local-disk document download controller scoped to the fixed Raeburn tenant.
- Document downloads require the dedicated `talent.documents.view` permission and `scan_status=clean`. Pending, infected, scan-failed and missing documents are rejected.
- A document access event records the acting administrator. This is groundwork, not a fully immutable audit system.
- No malware scanner or asynchronous scan worker has been implemented; do not mark uploads clean manually or activate intake.

## ClamAV worker foundation (12 October 2026)
- Added TCP INSTREAM ClamAV client and queued `ScanCandidateDocument` job on queue `talent-scans`.
- CV upload dispatches the scan job after the application transaction commits; files stay `pending` until an explicit clean result.
- Scanner returns `clean` only on ClamAV `OK`; malware results become `infected`, failures ultimately become `scan_failed`.
- Tests cover path rejection and a scanner-connection failure, not a real ClamAV end-to-end scan.
- BEFORE rollout: install and harden clamd listening on localhost only, configure a dedicated queue worker, monitor failures, test with EICAR and benign sample files, ensure private file directory permissions, define retention/deletion, and confirm no unscanned documents can be downloaded.
- Public applications remain disabled, and this work has not been deployed to the VPS.
