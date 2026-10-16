<?php
namespace Tests\Unit\Talent;
use App\Models\Talent\Interview;
use App\Services\Talent\InterviewCalendarInvite;
use Tests\TestCase;
class InterviewCalendarInviteTest extends TestCase {
 public function test_calendar_invite_contains_utc_times_and_escaped_text():void {
  $interview=new Interview(['tenant_id'=>1,'title'=>'Screening, phase 1','location'=>'Remote','status'=>'scheduled',
   'starts_at'=>'2026-10-15 10:00:00','ends_at'=>'2026-10-15 11:00:00']);
  $interview->id=15;
  $body=InterviewCalendarInvite::generate($interview);
  $this->assertStringContainsString('BEGIN:VCALENDAR',$body);
  $this->assertStringContainsString('SUMMARY:Screening\\, phase 1',$body);
  $this->assertStringContainsString('UID:talent-interview-1-15@theraeburngroup.com',$body);
  $this->assertStringContainsString('DTSTART:20261015T',$body);
 }
}
