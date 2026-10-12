<?php
namespace App\Services\Talent;
use App\Models\Talent\Interview;
final class InterviewCalendarInvite {
 private static function escape(?string $value):string {
  $s=(string)$value;
  return str_replace(["\\","; ",",",";","\r\n","\n","\r"],["\\\\","\\; ", "\\,","\\;","\\n","\\n","\\n"],$s);
 }
 public static function generate(Interview $interview):string {
  if (!$interview->starts_at || !$interview->ends_at) throw new \InvalidArgumentException('Interview must have both timestamps.');
  $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Raeburn Talent//Interviews//EN','CALSCALE:GREGORIAN','METHOD:PUBLISH',
   'BEGIN:VEVENT','UID:talent-interview-'.$interview->tenant_id.'-'.$interview->id.'@theraeburngroup.com',
   'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
   'DTSTART:'.$interview->starts_at->copy()->utc()->format('Ymd\THis\Z'),
   'DTEND:'.$interview->ends_at->copy()->utc()->format('Ymd\THis\Z'),
   'SUMMARY:'.self::escape($interview->title),
   'LOCATION:'.self::escape($interview->location),
   'STATUS:'.($interview->status==='cancelled'?'CANCELLED':'CONFIRMED'),
   'END:VEVENT','END:VCALENDAR'];
  return implode("\r\n",$lines)."\r\n";
 }
}
