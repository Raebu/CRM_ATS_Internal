<?php
namespace App\Services\Talent;

use Illuminate\Validation\ValidationException;

/** Internal vacancy state machine. Publishing requires a separate release gate. */
final class VacancyTransitions
{
 private const ALLOWED=[
  'draft'=>['ready','archived'],
  'ready'=>['draft','published','archived'],
  'published'=>['paused','closed'],
  'paused'=>['published','closed'],
  'closed'=>['archived'],
  'archived'=>[],
 ];

 public static function enforce(string $from,string $to):void
 {
  if (!in_array($to,self::ALLOWED[$from]??[],true)) {
   throw ValidationException::withMessages([
    'status'=>"Vacancy transition from {$from} to {$to} is not permitted.",
   ]);
  }
 }
}
