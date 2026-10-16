<?php
namespace App\Services\Talent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Conservative automated preflight. Passing does not replace security review or restore tests. */
final class ProductionReadiness
{
 public static function check():array
 {
  $checks=[
   'production_mode'=>app()->environment('production') && !config('app.debug'),
   'https_url'=>str_starts_with((string)config('app.url'),'https://'),
   'persistent_queue'=>!in_array(config('queue.default'),['sync','null'],true),
   'private_disk'=>config('filesystems.default')!=='public',
   'public_intake_disabled'=>!config('talent.public_applications_enabled',false),
   'external_email_disabled'=>!config('talent.external_email_enabled',false),
   'offer_acceptance_disabled'=>!config('talent.verified_offer_acceptance_enabled',false),
   'publishing_disabled'=>!config('talent.job_publishing_enabled',false),
  ];
  try { DB::connection()->select('SELECT 1'); $checks['database_connection']=true; }
  catch (\Throwable $e) { $checks['database_connection']=false; }
  try { $checks['private_storage_accessible']=Storage::disk('local')->exists('.'); }
  catch (\Throwable $e) { $checks['private_storage_accessible']=false; }
  return $checks;
 }
}
