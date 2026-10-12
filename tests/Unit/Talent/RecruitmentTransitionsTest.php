<?php

namespace Tests\Unit\Talent;

use App\Services\Talent\RecruitmentTransitions;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class RecruitmentTransitionsTest extends TestCase
{
    public function test_application_can_move_to_screening(): void
    {
        RecruitmentTransitions::application('applied', 'screening');
        $this->assertTrue(true);
    }

    public function test_hired_candidate_cannot_be_silently_rejected(): void
    {
        $this->expectException(ValidationException::class);
        RecruitmentTransitions::application('hired', 'rejected');
    }

    public function test_offer_cannot_skip_approval_and_sending(): void
    {
        $this->expectException(ValidationException::class);
        RecruitmentTransitions::offer('draft', 'accepted');
    }

    public function test_offer_may_progress_through_approval(): void
    {
        RecruitmentTransitions::offer('draft', 'pending_approval');
        RecruitmentTransitions::offer('pending_approval', 'approved');
        RecruitmentTransitions::offer('approved', 'sent');
        $this->assertTrue(true);
    }
}
