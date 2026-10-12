<?php

use App\Http\Controllers\Talent\TalentController;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(TalentController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/jobs', 'createJob')->name('jobs.store');
    Route::patch('/jobs/{id}', 'updateJob')->name('jobs.update');
    Route::post('/candidates', 'createCandidate')->name('candidates.store');
    Route::post('/applications', 'createApplication')->name('applications.store');
    Route::patch('/applications/{id}', 'moveApplication')->name('applications.update');
});

// Interviews and offers: authenticated Krayin admin routes with dedicated ACL entries.
Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\RecruitmentOperationsController::class)->group(function () {
    Route::post('/applications/{id}/interviews', 'storeInterview')->name('interviews.store');
    Route::patch('/interviews/{id}', 'updateInterview')->name('interviews.update');
    Route::post('/applications/{id}/offers', 'storeOffer')->name('offers.store');
    Route::patch('/offers/{id}', 'updateOffer')->name('offers.update');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\TalentPoolController::class)->group(function () {
    Route::post('/pools','store')->name('pools.store');
    Route::post('/pools/{id}/candidates','addCandidate')->name('pools.candidates.store');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->group(function () {
    Route::get('/documents/{id}/download', [\App\Http\Controllers\Talent\DocumentController::class, 'download'])->name('documents.download');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\PrivacyRequestController::class)->group(function () {
 Route::get('/privacy','index')->name('privacy.index');
 Route::post('/privacy','store')->name('privacy.store');
 Route::patch('/privacy/{id}','update')->name('privacy.update');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\NotificationInboxController::class)->group(function () {
 Route::get('/notifications','index')->name('notifications.index');
 Route::patch('/notifications/{id}/acknowledge','acknowledge')->name('notifications.acknowledge');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->get('talent/analytics', [\App\Http\Controllers\Talent\TalentAnalyticsController::class, 'index'])->name('admin.talent.analytics.index');

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent/email')->name('admin.talent.email.')->controller(\App\Http\Controllers\Talent\EmailReviewController::class)->group(function(){
 Route::get('/review','index')->name('index');
 Route::post('/drafts','draft')->name('draft');
 Route::post('/drafts/{id}/approve','approve')->name('approve');
});

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->get('talent/applications/{id}',[\App\Http\Controllers\Talent\ApplicationWorkspaceController::class,'show'])->name('admin.talent.applications.show');

Route::middleware(\App\Http\Middleware\TalentAuthorization::class)->prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\PlacementController::class)->group(function(){
 Route::post('/applications/{id}/placements','store')->name('placements.store');
 Route::patch('/placements/{id}/tasks/{taskId}','task')->name('placements.tasks.update');
});
