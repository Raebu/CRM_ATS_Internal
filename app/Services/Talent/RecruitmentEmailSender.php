<?php
namespace App\Services\Talent;

use App\Models\Talent\EmailDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Provider submission only. A Resend API acceptance is not evidence of inbox delivery.
 * All outbound sending is disabled by default and requires separate admin approval.
 */
final class RecruitmentEmailSender {
 public function submit(EmailDelivery $delivery):string {
  $key=config('talent.resend_api_key');
  $sender=config('talent.resend_from');
  if (!config('talent.external_email_enabled',false)
   || !is_string($key) || $key==='' || !is_string($sender) || !filter_var($sender,FILTER_VALIDATE_EMAIL)) {
   throw ValidationException::withMessages(['email'=>'Recruitment email delivery is not configured or enabled.']);
  }
  if ($delivery->status!=='approved' || !$delivery->approved_at || !$delivery->approved_by
   || !EmailSnapshotIntegrity::valid($delivery) || !filter_var($delivery->recipient_email,FILTER_VALIDATE_EMAIL)) {
   throw ValidationException::withMessages(['email'=>'An intact, approved delivery with a verified destination is required.']);
  }
  $response=Http::withToken($key)->timeout(15)->acceptJson()->post('https://api.resend.com/emails',[
   'from'=>$sender,'to'=>[$delivery->recipient_email],
   'subject'=>$delivery->subject_snapshot,'text'=>$delivery->body_snapshot,
  ]);
  if (!$response->successful() || !is_string($response->json('id')) || $response->json('id')==='') {
   throw new \RuntimeException('Email provider did not confirm acceptance of the request.');
  }
  return $response->json('id');
 }
}
