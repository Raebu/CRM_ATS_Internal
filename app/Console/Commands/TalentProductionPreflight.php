<?php
namespace App\Console\Commands;

use App\Services\Talent\ProductionReadiness;
use Illuminate\Console\Command;

final class TalentProductionPreflight extends Command
{
 protected $signature='talent:production-preflight';
 protected $description='Inspect essential ATS safety gates without modifying production state';
 public function handle():int
 {
  $checks=ProductionReadiness::check();
  foreach($checks as $name=>$passed) {
   $this->line(($passed?'PASS':'FAIL').' '.$name);
  }
  $this->warn('Manual approval still required: privacy notice, legal basis, identity controls, ClamAV/queue end-to-end, webhook signatures, backup restoration, operational monitoring, pen test and rollback rehearsal.');
  return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
 }
}
