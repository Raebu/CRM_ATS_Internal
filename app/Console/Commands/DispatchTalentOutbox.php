<?php
namespace App\Console\Commands;

use App\Services\Talent\OutboxDispatcher;
use Illuminate\Console\Command;

class DispatchTalentOutbox extends Command
{
 protected $signature='talent:outbox-dispatch {--batch=25 : Maximum number of events per execution}';
 protected $description='Dispatch durable Raeburn Talent domain events (at-least-once delivery)';
 public function handle(OutboxDispatcher $dispatcher):int
 {
  $result=$dispatcher->dispatchBatch(max(1,min(200,(int)$this->option('batch'))));
  $this->components->info("Published {$result['published']}, failed {$result['failed']}.");
  return $result['failed']>0?self::FAILURE:self::SUCCESS;
 }
}
