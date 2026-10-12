<?php
namespace Tests\Unit\Talent;
use App\Models\Talent\EmailDelivery;
use App\Services\Talent\RecruitmentEmailSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentEmailSenderTest extends TestCase {
 private function approvedDelivery():EmailDelivery {
  $subject='Test acknowledgement';$body='Test message';
  $d=new EmailDelivery([
   'status'=>'approved','recipient_email'=>'applicant@example.invalid',
   'subject_snapshot'=>$subject,'body_snapshot'=>$body,'template_version'=>1,
   'template_checksum'=>hash('sha256',$subject."\n".$body),
   'approved_by'=>7,'approved_at'=>now(),
  ]);
  $d->id=42;
  return $d;
 }
 public function test_external_email_is_disabled_by_default():void {
  Http::fake();
  $this->expectException(ValidationException::class);
  app(RecruitmentEmailSender::class)->submit($this->approvedDelivery());
 }
 public function test_approved_delivery_submits_once_to_fake_provider():void {
  config()->set('talent.external_email_enabled',true);
  config()->set('talent.resend_api_key','test-key');
  config()->set('talent.resend_from','recruitment@example.invalid');
  Http::fake(['api.resend.com/emails'=>Http::response(['id'=>'test-provider-id'],200)]);
  $result=app(RecruitmentEmailSender::class)->submit($this->approvedDelivery());
  $this->assertSame('test-provider-id',$result);
  Http::assertSent(fn($request)=>$request->hasHeader('Idempotency-Key')
    && $request['to'][0]==='applicant@example.invalid');
 }
 public function test_unapproved_message_is_not_submitted():void {
  config()->set('talent.external_email_enabled',true);
  config()->set('talent.resend_api_key','test-key');
  config()->set('talent.resend_from','recruitment@example.invalid');
  Http::fake();
  $delivery=$this->approvedDelivery();$delivery->status='draft';
  $this->expectException(ValidationException::class);
  app(RecruitmentEmailSender::class)->submit($delivery);
 }
}
