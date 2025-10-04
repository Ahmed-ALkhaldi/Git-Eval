<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController as APIAuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\GitHubRepositoryController;
use App\Http\Controllers\SonarWebhookController;

// ✅ API: تسجيل/دخول "طلاب فقط"
Route::post('/register', [APIAuthController::class, 'registerStudent']); // يسجّل طالباً فقط بحالة pending (لا توكن)
Route::post('/login',    [APIAuthController::class, 'login']);          // يصدر توكن فقط إذا الطالب approved

// ✅ احمِ المسارات بـ Sanctum + منع الطلاب غير المعتمدين
Route::middleware(['auth:sanctum', \App\Http\Middleware\BlockUnverifiedStudents::class])->group(function () {

    // ملاحظة: ProjectController يجب أن يرجع JSON للـ API (انظر الملاحظة أدناه)
    Route::get('/projects',  [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);

    //Route::get('/repository-info/{project}', [GitHubRepositoryController::class, 'fetch']);

    // (اختياري) API logout (حذف التوكن الحالي)
    Route::post('/logout', [APIAuthController::class, 'logout']);
});

// SonarQube Webhook - مسار منفصل بدون حماية Sanctum
Route::post('/webhooks/sonar', [SonarWebhookController::class, 'handle'])
    ->middleware('throttle:sonar-webhook');
