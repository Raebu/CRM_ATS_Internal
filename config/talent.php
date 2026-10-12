<?php
return [
 'clamav_host' => env('TALENT_CLAMAV_HOST', '127.0.0.1'),
 'clamav_port' => (int) env('TALENT_CLAMAV_PORT', 3310),
 // Do not enable until published privacy notice, DSAR path, malware scanning and security tests pass.
 'public_applications_enabled' => env('TALENT_PUBLIC_APPLICATIONS_ENABLED', false),
 'job_publishing_enabled' => env('TALENT_JOB_PUBLISHING_ENABLED', false),
 'verified_offer_acceptance_enabled' => env('TALENT_VERIFIED_OFFER_ACCEPTANCE_ENABLED', false),
];