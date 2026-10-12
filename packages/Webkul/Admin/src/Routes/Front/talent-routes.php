<?php
use App\Http\Controllers\Talent\PublicApplicationController;
use Illuminate\Support\Facades\Route;
Route::middleware('throttle:10,1')->prefix('careers')->name('talent.public.')->group(function(){
 Route::get('/jobs/{slug}',[PublicApplicationController::class,'show'])->name('apply.show');
 Route::post('/jobs/{slug}/apply',[PublicApplicationController::class,'store'])->name('apply.store');
 Route::get('/thank-you',[PublicApplicationController::class,'thankYou'])->name('thank_you');
});
