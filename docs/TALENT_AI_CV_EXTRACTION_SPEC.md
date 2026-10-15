# Raeburn Talent — layout-aware CV intelligence (staging design and first implementation)

## Status
Development branch, **not deployed**. Existing PDF rasterisation + text extraction is not a layout-aware or OCR-capable parser. The committed `CvEvidenceReconciler` is the first usable safeguard for AI-generated extraction proposals, not a claim that a model provider is wired up. Neither candidate files nor personal data may be sent to any external AI service by default.

## Required staged pipeline
1. Quarantine PDF/other approved format (PDF only for now); ClamAV scan, reject dangerous PDF features and encrypted/invalid files.
2. Under sandbox, extract **per-page** text with coordinates and page images; if little text exists, run OCR locally, preserving page references. Keep reading-order alternatives for columns and tables.
3. A layout parser associates lines/blocks with employer, role and timeline; don't flatten repeatable roles into one text area. Extract full `CvFieldSchema::GROUPS`, `REPEATABLE`, and `SETTINGS` data.
4. Optional local/approved-provider AI receives strictly bounded document-derived text/layout, not unsanitised files. Treat everything in the CV as untrusted instructions. Force a strict JSON schema, no generated biography, qualifications, achievements, or dates.
5. Every proposed field and employment/education entry contains a source excerpt, page number, extraction method and confidence indicator. The `CvEvidenceReconciler` rejects field proposals lacking source excerpt or with conflicting values. Page numbers remain unverified until the page-indexed OCR/text corpus is implemented; don't show them as verified evidence yet.
6. Coverage audit: list unassigned page blocks and text spans; an item must either map to a field or appear under 'Unclassified source information'. Check that employment dates attach to the correct company; validate overlapping history as a review flag (not an automatic rejection). Flag out-of-order history.
7. Human review: present mapped data and source excerpts side by side; ensure the person can add and remove unlimited history entries. No automatic approval. Reviewer acknowledges the actual extracted evidence and resolves ambiguities.
8. Generate a brand-consistent ATS-readable PDF from the verified schema only, scan it, record reviewer/time/version, and publish it only after all checks pass.
9. Delete original and processing images at the documented review/approval boundary, including abandoned-job expiry; encrypted backups have defined expiration. Don't promise irreversible removal from immutable backups.

## Safeguards
- Explicit UK GDPR transparency, legitimate purpose and retention/erasure process; processor contract, residency, provider training opt-out and DPIA as appropriate before provider-based processing.
- Default AI off. Prefer isolated local inference; if remote provider is later approved, implement explicit config allowlist, strict egress, minimisation, observability without personal-data logging, provider error fail-closed.
- Never infer protected traits or rank candidates using personal characteristics.
- Model refusal, timeouts and low confidence fall back to manual review (not upload rejection where sanitized document is safe).
- Validate schema server-side; rate limit, restrict payload sizes, escape rendered values.
- No assertion of 100% extraction accuracy. Benchmark against consented diverse CV samples including multi-column, multilingual, image-based, graphics, tables and overlapping jobs.

## Open engineering work
- Implement OCR, bounding box/source-page storage and layout reading order.
- Wire a reviewed AI client and strict structured JSON contract, plus monitoring and budget.
- Complete repeatable form frontend and validators, render all schema fields, and acceptance tests.
- Resolve all CI failures and verify real staging sandbox permissions.
- Preserve a safe temporary evidence representation while review is pending. Never destroy the only reviewable evidence before approval without an alternative.

Do not enable staging CV processing or merge into production until end-to-end testing and security review pass.
