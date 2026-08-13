<?php

use Illuminate\Support\Facades\Route;

// Laravel is API-only. The Vue client owns all browser pages.
Route::fallback(fn () => response()->json([
    'success' => false,
    'message' => 'Web routes are disabled. Use the Laravel API under /api/v1.',
], 404));
