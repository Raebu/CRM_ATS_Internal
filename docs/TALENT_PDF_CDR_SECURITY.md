# Raeburn Talent PDF CV Security Gate (staging only)

## Status

**NOT APPROVED FOR DEPLOYMENT OR PUBLIC UPLOADS.** Development branch only.
CV uploads default to disabled: `TALENT_CV_UPLOADS_ENABLED=false`.
Never merge to main or alter the production CRM without explicit approval.

## Security design

1. Validate MIME, PDF prefix, 4 MiB maximum, and authenticated/authorised route.
2. Place untrusted PDF in tenant-scoped private quarantine; never create a downloadable database record.
3. ClamAV INSTREAM-scan original. Reject malware, errors and outages.
4. Invoke `PdfSanitizer` to run qpdf, pdfinfo, pdftoppm and img2pdf behind bubblewrap's network namespace; require parsing to succeed, reject encryption, active actions and attachments, limit to 20 pages and 45 seconds.
5. Render each page into pixels and rebuild a new PDF. Interactive elements, links, embedded scripts and embedded files are not carried across.
6. Scan the rebuilt PDF again with ClamAV. On ANY failure delete quarantine and output.
7. Store only the sanitized output's path in the document record. Never serve legacy originals (any document path outside `talent-private/sanitized/` returns 404).
8. Return generic user-safe errors and log underlying errors for administrators.

This materially reduces risk; it does not guarantee detection of all malware. The rasterisation toolchain itself handles untrusted content and must be patched, isolated and tested; PDF accessibility/text-selectability and hyperlinks may be lost.

## Staging dependencies and acceptance criteria

Provision **only on Contabo ATS staging**, never the CRM production environment:
`bubblewrap`, `poppler-utils`, `qpdf`, `python3-img2pdf`. Verify paths:
`/usr/bin/bwrap`, `/usr/bin/pdfinfo`, `/usr/bin/pdftoppm`, `/usr/bin/qpdf`, `/usr/bin/python3`.
Verify `bwrap --unshare-net` actually works under the staging service account with the staging process sandbox. On hardened hosts, user-namespace restrictions may block it: that MUST fail closed; do not disable host-wide protections for this feature. Prefer a separate locked-down worker/container if the host cannot support safe bubblewrap execution.

**Before enabling in any environment:** execute automated tests, manually inspect the generated PDF, and run genuine end-to-end tests against clean PDF, EICAR-bearing malformed PDF, embedded attachment, JavaScript action, encrypted PDF, truncated PDF, overly long PDF, scanner outage, missing qpdf/bwrap, reconstruction failure, 2nd scan detection, concurrent uploads, unauthorised user, other tenant, download endpoint, and error handling. Confirm the original is never downloadable or retained. Verify health, load, permissions, audit records and Linux sandbox under real PHP-FPM user.

**Important:** the accepted synthetic EICAR PDFs and any earlier `clean` originals will cease downloading after this update; do not automatically migrate them as sanitised. Review/quarantine/delete them under a documented audit process.

## Release controls

There is an intentionally separate emergency upload block that may exist on the VPS from the earlier incident. Do not remove it before all checks pass. Deploy staging with backup + rollback, and keep `TALENT_CV_UPLOADS_ENABLED=false`. When staging validation is complete, authorise change control before removing the emergency block and separately enabling the feature. Production remains untouched.
