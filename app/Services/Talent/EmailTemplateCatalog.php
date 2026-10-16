<?php
namespace App\Services\Talent;
/** Fixed, approved-for-review drafts. No automatic external delivery. */
final class EmailTemplateCatalog
{
 public static function templates():array
 {
  return [
   'application_acknowledgement'=>[
    'version'=>1,
    'subject'=>'Your application has been received',
    'body'=>"Thank you for applying. Your application has been recorded. We will contact you if there is an update.\n\nRaeburn Recruitment",
   ],
   'interview_reminder'=>[
    'version'=>1,
    'subject'=>'Reminder about your scheduled interview',
    'body'=>"This is a reminder of your scheduled interview. Please refer to the confirmed interview details previously shared with you.\n\nRaeburn Recruitment",
   ],
  ];
 }
 public static function get(string $key):array
 {
  return self::templates()[$key] ?? throw new \InvalidArgumentException('Unknown email template.');
 }
}
