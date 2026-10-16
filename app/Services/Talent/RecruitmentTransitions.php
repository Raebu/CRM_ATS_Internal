<?php

namespace App\Services\Talent;

use Illuminate\Validation\ValidationException;

/**
 * Explicit domain workflow guard. No automatic selection decisions.
 */
final class RecruitmentTransitions
{
    private const APPLICATION = [
        'applied' => ['screening', 'review', 'rejected', 'withdrawn', 'on_hold'],
        'screening' => ['review', 'shortlisted', 'rejected', 'withdrawn', 'on_hold'],
        'review' => ['shortlisted', 'interview', 'rejected', 'withdrawn', 'on_hold'],
        'shortlisted' => ['interview', 'rejected', 'withdrawn', 'on_hold', 'talent_pool'],
        'interview' => ['final_interview', 'offer', 'rejected', 'withdrawn', 'on_hold'],
        'final_interview' => ['offer', 'rejected', 'withdrawn', 'on_hold'],
        'offer' => ['hired', 'rejected', 'withdrawn', 'on_hold'],
        'on_hold' => ['screening', 'review', 'shortlisted', 'interview', 'rejected', 'withdrawn'],
        'talent_pool' => ['screening', 'review', 'withdrawn'],
        'hired' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    private const OFFER = [
        'draft' => ['pending_approval', 'withdrawn'],
        'pending_approval' => ['approved', 'draft', 'withdrawn'],
        'approved' => ['sent', 'withdrawn'],
        'sent' => ['accepted', 'declined', 'withdrawn'],
        'accepted' => [],
        'declined' => [],
        'withdrawn' => [],
    ];

    public static function application(string $from, string $to): void
    {
        self::enforce(self::APPLICATION, $from, $to);
    }

    public static function offer(string $from, string $to): void
    {
        self::enforce(self::OFFER, $from, $to);
    }

    private static function enforce(array $graph, string $from, string $to): void
    {
        if (!in_array($to, $graph[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Transition from {$from} to {$to} is not permitted.",
            ]);
        }
    }
}
