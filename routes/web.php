<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'show'])->name('employee.invitation.show');
Route::post('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'submit'])->name('employee.invitation.submit');

// Mitarbeiter-Portal
Route::prefix('mitarbeiter')->name('employee.portal.')->group(function () {
    Route::get('/login',   [App\Http\Controllers\EmployeePortalController::class, 'showLogin'])->name('login');
    Route::post('/login',  [App\Http\Controllers\EmployeePortalController::class, 'login'])->name('login.post');
    Route::post('/abmelden', [App\Http\Controllers\EmployeePortalController::class, 'logout'])->name('logout');
    Route::middleware(\App\Http\Middleware\EnsureEmployeeAuthenticated::class)->group(function () {
        Route::get('/dashboard',         [App\Http\Controllers\EmployeePortalController::class, 'showDashboard'])->name('dashboard');
        Route::get('/passwort-aendern',  [App\Http\Controllers\EmployeePortalController::class, 'showChangePassword'])->name('change-password');
        Route::post('/passwort-aendern', [App\Http\Controllers\EmployeePortalController::class, 'changePassword'])->name('change-password.post');
    });
});
