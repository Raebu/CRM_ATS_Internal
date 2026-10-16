<?php
return [
 'cv_uploads_enabled' => env('TALENT_CV_UPLOADS_ENABLED', false),
 'cv_ai_enabled' => false,
 'cv_ai_endpoint' => '',
 'cv_ai_token' => '',
 'cv_ai_allowed_hosts' => [],
 'offer_delivery_enabled' => env('TALENT_OFFER_DELIVERY_ENABLED', false),
 'resend_api_key' => env('TALENT_RESEND_API_KEY'),
 'resend_from' => env('TALENT_RESEND_FROM'),
 'external_email_enabled' => env('TALENT_EXTERNAL_EMAIL_ENABLED', false),
 'clamav_host' => env('TALENT_CLAMAV_HOST', '127.0.0.1'),
 'clamav_port' => (int) env('TALENT_CLAMAV_PORT', 3310),
 // Do not enable until published privacy notice, DSAR path, malware scanning and security tests pass.
 'public_applications_enabled' => env('TALENT_PUBLIC_APPLICATIONS_ENABLED', false),
 'job_publishing_enabled' => env('TALENT_JOB_PUBLISHING_ENABLED', false),
 'verified_offer_acceptance_enabled' => env('TALENT_VERIFIED_OFFER_ACCEPTANCE_ENABLED', false),
];