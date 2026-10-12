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
