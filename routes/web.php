<?php

use App\Models\Service;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'services' => Service::active()->ordered()->get(),
    ]);
});

Route::get('/services', [App\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
Route::post('/services', [App\Http\Controllers\ServiceController::class, 'store'])->middleware(['auth', 'verified'])->name('services.store');
Route::put('/services/{service}', [App\Http\Controllers\ServiceController::class, 'update'])->middleware(['auth', 'verified'])->name('services.update');
Route::patch('/services/{service}/toggle-status', [App\Http\Controllers\ServiceController::class, 'toggleStatus'])->middleware(['auth', 'verified'])->name('services.toggle-status');
Route::delete('/services/{service}', [App\Http\Controllers\ServiceController::class, 'destroy'])->middleware(['auth', 'verified'])->name('services.destroy');

Route::get('/about', function () {
    return redirect('/#about');
});

Route::get('/contact', function () {
    return redirect('/#contact');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::resource('patients', App\Http\Controllers\PatientController::class)->middleware(['auth', 'verified']);
Route::resource('treatments', App\Http\Controllers\TreatmentController::class)->middleware(['auth', 'verified']);
Route::resource('billing', App\Http\Controllers\InvoiceController::class)
    ->parameters(['billing' => 'invoice'])
    ->middleware(['auth', 'verified']);
Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->middleware(['auth', 'verified'])->name('payments.index');
Route::get('/payments/{payment}/receipt', [App\Http\Controllers\PaymentController::class, 'receipt'])->middleware(['auth', 'verified'])->name('payments.receipt');
Route::post('/billing/{invoice}/payments', [App\Http\Controllers\PaymentController::class, 'store'])->middleware(['auth', 'verified'])->name('payments.store');
Route::delete('/payments/{payment}', [App\Http\Controllers\PaymentController::class, 'destroy'])->middleware(['auth', 'verified'])->name('payments.destroy');

// Automated Reports Module (Admin / Staff)
Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->middleware(['auth', 'verified'])->name('reports.index');
Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->middleware(['auth', 'verified'])->name('reports.export');

// Audit Log Module (Admin Only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/audit-log', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-log/export', [App\Http\Controllers\AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::delete('/audit-log/purge', [App\Http\Controllers\AuditLogController::class, 'clearOld'])->name('audit-logs.purge');
});

// User & Access Control Management (Admin Only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
});

// System Backup & Disaster Recovery (Admin Only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup', [App\Http\Controllers\BackupController::class, 'store'])->name('backup.store');
    Route::get('/backup/download/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('backup.download');
    Route::post('/backup/restore', [App\Http\Controllers\BackupController::class, 'restore'])->name('backup.restore');
    Route::post('/backup/restore-upload', [App\Http\Controllers\BackupController::class, 'restoreUpload'])->name('backup.restore-upload');
    Route::delete('/backup/{filename}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('backup.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/appointments', [App\Http\Controllers\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [App\Http\Controllers\AppointmentController::class, 'create'])->name('appointments.create');
    Route::get('/appointments/availability', [App\Http\Controllers\AppointmentController::class, 'getAvailability'])->name('appointments.availability');
    Route::post('/appointments/send-reminders', [App\Http\Controllers\AppointmentController::class, 'sendReminders'])->name('appointments.send-reminders');
    Route::post('/appointments', [App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('/appointments/{appointment}/status', [App\Http\Controllers\AppointmentController::class, 'updateStatus'])->name('appointments.status.update');
    Route::delete('/appointments/{appointment}', [App\Http\Controllers\AppointmentController::class, 'destroy'])->name('appointments.destroy');

    // Medical History Routes
    Route::get('/patients/{patient}/medical-history/edit', [App\Http\Controllers\MedicalHistoryController::class, 'edit'])->name('medical-histories.edit');
    Route::post('/patients/{patient}/medical-history', [App\Http\Controllers\MedicalHistoryController::class, 'storeOrUpdate'])->name('medical-histories.store');
    Route::get('/my-medical-history', [App\Http\Controllers\MedicalHistoryController::class, 'myHistory'])->name('medical-histories.my');

    // Notification Routes
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/clear-read', [App\Http\Controllers\NotificationController::class, 'clearRead'])->name('notifications.clear-read');
});

require __DIR__ . '/auth.php';
