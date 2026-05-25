<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'show'])->name('employee.invitation.show');
Route::post('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'submit'])->name('employee.invitation.submit');

// Altes Mitarbeiter-Portal → auf App-Panel umleiten
Route::get('/mitarbeiter/{any?}', fn () => redirect('/app'))->where('any', '.*');
