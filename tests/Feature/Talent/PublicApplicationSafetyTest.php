<?php
namespace Tests\Feature\Talent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Tests\TestCase;

class PublicApplicationSafetyTest extends TestCase
{
 use RefreshDatabase;

 public function test_public_application_routes_are_disabled_by_default():void
 {
  // The installer redirects all requests on an uninstalled CI instance.
  // Isolate the recruitment flag by bypassing that global installer guard.
  $this->withoutMiddleware(CanInstall::class);
  config()->set('talent.public_applications_enabled',false);
  $this->get('/careers/jobs/nonexistent')->assertNotFound();
  $this->post('/careers/jobs/nonexistent/apply',[])->assertNotFound();
 }
}
