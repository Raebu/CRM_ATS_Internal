<?php
namespace Tests\Unit\Talent;

use App\Services\Talent\CvAiAssistant;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CvAiAssistantTest extends TestCase
{
    public function test_ai_is_off_even_when_endpoint_is_configured(): void
    {
        config()->set('talent.cv_ai_enabled',false);
        config()->set('talent.cv_ai_endpoint','https://example.invalid/extract');
        Http::fake();
        $this->expectException(\RuntimeException::class);
        try {
            app(CvAiAssistant::class)->suggest('Synthetic candidate CV');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_provider_host_must_be_allowlisted(): void
    {
        config()->set('talent.cv_ai_enabled',true);
        config()->set('talent.cv_ai_endpoint','https://unapproved.invalid/extract');
        config()->set('talent.cv_ai_token','synthetic-token');
        config()->set('talent.cv_ai_allowed_hosts',['approved.invalid']);
        Http::fake();
        $this->expectException(\RuntimeException::class);
        try {
            app(CvAiAssistant::class)->suggest('Synthetic CV');
        } finally {
            Http::assertNothingSent();
        }
    }
}
