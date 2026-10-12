<?php

use App\Http\Controllers\Talent\TalentController;
use Illuminate\Support\Facades\Route;

Route::prefix('talent')->name('admin.talent.')->controller(TalentController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/jobs', 'createJob')->name('jobs.store');
    Route::patch('/jobs/{id}', 'updateJob')->name('jobs.update');
    Route::post('/candidates', 'createCandidate')->name('candidates.store');
    Route::post('/applications', 'createApplication')->name('applications.store');
    Route::patch('/applications/{id}', 'moveApplication')->name('applications.update');
});

// Interviews and offers: authenticated Krayin admin routes with dedicated ACL entries.
Route::prefix('talent')->name('admin.talent.')->controller(\App\Http\Controllers\Talent\RecruitmentOperationsController::class)->group(function () {
    Route::post('/applications/{id}/interviews', 'storeInterview')->name('interviews.store');
    Route::patch('/interviews/{id}', 'updateInterview')->name('interviews.update');
    Route::post('/applications/{id}/offers', 'storeOffer')->name('offers.store');
    Route::patch('/offers/{id}', 'updateOffer')->name('offers.update');
});
