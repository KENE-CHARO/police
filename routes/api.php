<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/staff', [AuthController::class, 'registerStaff']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::delete('account', [AuthController::class, 'deleteAccount']);
    });
});

// Routes des plaintes (CRUD) protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('agent/plaintes', [App\Http\Controllers\API\AgentController::class, 'storePlainte']);
    Route::apiResource('plaintes', App\Http\Controllers\API\PlainteController::class);
    Route::post('plaintes/{plainte}/attachments', [App\Http\Controllers\API\PlainteController::class, 'uploadAttachment']);
    Route::get('plaintes/{plainte}/attachments/{attachment}', [App\Http\Controllers\API\AttachmentController::class, 'download']);
    Route::put('plaintes/{plainte}/recevabilite', [App\Http\Controllers\API\PlainteController::class, 'setRecevable']);
    Route::post('enquetes/assign', [App\Http\Controllers\API\EnqueteurController::class, 'assign']);
    Route::put('enquetes/{enquete}/status', [App\Http\Controllers\API\EnqueteurController::class, 'updateStatus']);
    Route::post('payments/mobile', [App\Http\Controllers\API\PaymentController::class, 'mobilePay']);
    Route::get('plaintes/{plainte}/historiques', [App\Http\Controllers\API\PlainteController::class, 'historiques']);
    // admin routes
    Route::get('admin/users', [App\Http\Controllers\API\AdminController::class, 'listUsers']);
    Route::get('admin/roles', [App\Http\Controllers\API\AdminController::class, 'listRoles']);
    Route::post('admin/users/{user}/activate', [App\Http\Controllers\API\AdminController::class, 'activateUser']);
    Route::delete('admin/users/{user}', [App\Http\Controllers\API\AdminController::class, 'deleteUser']);
    Route::post('admin/users/{user}/roles', [App\Http\Controllers\API\AdminController::class, 'assignRole']);
    Route::delete('admin/users/{user}/roles', [App\Http\Controllers\API\AdminController::class, 'removeRole']);
    // admin notifications
    Route::get('admin/notifications', [App\Http\Controllers\API\Admin\NotificationAdminController::class, 'index']);
    Route::post('admin/notifications/mark-read', [App\Http\Controllers\API\Admin\NotificationAdminController::class, 'markReadBulk']);
    Route::delete('admin/notifications', [App\Http\Controllers\API\Admin\NotificationAdminController::class, 'deleteBulk']);
    // notifications
    Route::get('notifications', [App\Http\Controllers\API\NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [App\Http\Controllers\API\NotificationController::class, 'markRead']);
});

// Public endpoint for Campay webhook callbacks
Route::post('payments/webhook', [App\Http\Controllers\API\PaymentWebhookController::class, 'handle']);
