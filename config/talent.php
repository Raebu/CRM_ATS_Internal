<?php
return [
 // Do not enable until published privacy notice, DSAR path, malware scanning and security tests pass.
 'public_applications_enabled' => env('TALENT_PUBLIC_APPLICATIONS_ENABLED', false),
 'job_publishing_enabled' => env('TALENT_JOB_PUBLISHING_ENABLED', false),
 'verified_offer_acceptance_enabled' => env('TALENT_VERIFIED_OFFER_ACCEPTANCE_ENABLED', false),
];