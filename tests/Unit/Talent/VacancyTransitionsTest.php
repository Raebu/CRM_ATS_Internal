<?php
namespace Tests\Unit\Talent;

use App\Services\Talent\VacancyTransitions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VacancyTransitionsTest extends TestCase
{
 public function test_valid_vacancy_workflow():void
 {
  VacancyTransitions::enforce('draft','ready');
  VacancyTransitions::enforce('ready','published');
  VacancyTransitions::enforce('published','paused');
  VacancyTransitions::enforce('paused','closed');
  VacancyTransitions::enforce('closed','archived');
  $this->assertTrue(true);
 }
 public function test_invalid_vacancy_skip_is_rejected():void
 {
  $this->expectException(ValidationException::class);
  VacancyTransitions::enforce('draft','published');
 }
 public function test_archived_vacancy_cannot_be_reopened():void
 {
  $this->expectException(ValidationException::class);
  VacancyTransitions::enforce('archived','ready');
 }
}
