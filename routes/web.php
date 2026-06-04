<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'show'])->name('employee.invitation.show');
Route::post('/einladung/{token}', [App\Http\Controllers\EmployeeInvitationController::class, 'submit'])->name('employee.invitation.submit');

// Altes Mitarbeiter-Portal → auf App-Panel umleiten
Route::get('/mitarbeiter/{any?}', fn () => redirect('/app'))->where('any', '.*');

// PDF-Drucke (geschützt, nur eingeloggte Partner/Steuerberater)
Route::middleware(['web', 'auth'])->prefix('pdf')->name('pdf.')->group(function () {
    Route::get('/employee/{id}/mitarbeiterdaten', [App\Http\Controllers\EmployeePdfController::class, 'mitarbeiterdaten'])
        ->name('employee.mitarbeiterdaten');
    Route::get('/contract/{id}/download', [App\Http\Controllers\ContractSigningController::class, 'download'])
        ->name('contract.download');
    Route::get('/contract/{id}/preview', [App\Http\Controllers\ContractSigningController::class, 'previewAdmin'])
        ->name('contract.preview');
    Route::get('/document/{id}/download', [App\Http\Controllers\DocumentSigningController::class, 'download'])
        ->name('document.download');
});

// Mitarbeiter: eigenen Vertrag als PDF herunterladen (nur eigene Verträge)
Route::middleware(['web', 'auth'])->get('/mein-vertrag/{id}/pdf', [App\Http\Controllers\ContractSigningController::class, 'downloadEmployee'])
    ->name('employee.contract.pdf');

// Vertrag digital unterschreiben (öffentlich, Token-geschützt)
Route::prefix('vertrag')->name('contract.')->group(function () {
    Route::get('/{token}',        [App\Http\Controllers\ContractSigningController::class, 'show'])->name('sign');
    Route::post('/{token}',       [App\Http\Controllers\ContractSigningController::class, 'submit'])->name('sign.submit');
    Route::get('/{token}/pdf',    [App\Http\Controllers\ContractSigningController::class, 'downloadPublic'])->name('sign.pdf');
});

// GoPilot MDE — Station QR-Code (nur eingeloggte Partner)
Route::middleware(['web', 'auth'])->get('/mde/station/{ulid}/qr', [App\Http\Controllers\Mde\MdeStationQrController::class, 'show'])
    ->name('mde.station.qr');

// Allgemeines Dokument digital unterschreiben (öffentlich, Token-geschützt)
Route::prefix('dokument')->name('document.')->group(function () {
    Route::get('/{token}',     [App\Http\Controllers\DocumentSigningController::class, 'show'])->name('sign');
    Route::post('/{token}',    [App\Http\Controllers\DocumentSigningController::class, 'submit'])->name('sign.submit');
    Route::get('/{token}/pdf', [App\Http\Controllers\DocumentSigningController::class, 'pdf'])->name('sign.pdf');
});
