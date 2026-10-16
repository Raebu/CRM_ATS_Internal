<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * Home routes.
 */
Route::get('/', [Controller::class, 'redirectToLogin'])->name('krayin.home');

// Public Raeburn Talent routes (disabled by default until privacy/security signoff).
require 'talent-routes.php';
