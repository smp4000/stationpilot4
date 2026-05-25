<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAccessLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeInvitationController extends Controller
{
    public function show(string $token): View
    {
        $employee = Employee::where('invitation_token', $token)->firstOrFail();

        if ($employee->invitation_expires_at && $employee->invitation_expires_at->isPast()) {
            return view('employee-invitation', [
                'employee' => $employee,
                'expired'  => true,
                'used'     => false,
            ]);
        }

        if ($employee->status === 'aktiv' && $employee->invitation_token === null) {
            return view('employee-invitation', [
                'employee' => $employee,
                'expired'  => false,
                'used'     => true,
            ]);
        }

        return view('employee-invitation', [
            'employee' => $employee,
            'expired'  => false,
            'used'     => false,
        ]);
    }

    public function submit(string $token, Request $request): View|RedirectResponse
    {
        $employee = Employee::where('invitation_token', $token)->firstOrFail();

        if ($employee->invitation_expires_at && $employee->invitation_expires_at->isPast()) {
            return view('employee-invitation', [
                'employee' => $employee,
                'expired'  => true,
                'used'     => false,
            ]);
        }

        $validated = $request->validate([
            // Schritt 1 – Persönliche Daten
            'first_name'              => 'required|string|max:100',
            'last_name'               => 'required|string|max:100',
            'birth_name'              => 'nullable|string|max:100',
            'date_of_birth'           => 'required|date',
            'place_of_birth'          => 'nullable|string|max:100',
            'country_of_birth'        => 'nullable|string|max:100',
            'nationality'             => 'nullable|string|max:100',
            'gender'                  => 'nullable|in:m,w,d',
            'marital_status'          => 'nullable|string|max:30',
            // Schritt 2 – Anschrift & Kontakt
            'street'                  => 'required|string|max:150',
            'house_number'            => 'required|string|max:20',
            'zip'                     => 'required|string|max:10',
            'city'                    => 'required|string|max:100',
            'country'                 => 'required|string|max:100',
            'phone_private'           => 'nullable|string|max:30',
            'phone_mobile'            => 'nullable|string|max:30',
            'email'                   => 'required|email|max:191',
            // Schritt 3 – Steuer & Soziales
            'tax_id'                  => 'nullable|string|max:11',
            'tax_class'               => 'nullable|integer|min:1|max:6',
            'tax_child_allowance'     => 'nullable|numeric|min:0|max:9',
            'church_tax'              => 'nullable|string|max:10',
            'health_insurance_name'   => 'nullable|string|max:100',
            'health_insurance_type'   => 'nullable|string|max:20',
            'social_security_number'  => 'nullable|string|max:20',
            // Schritt 4 – Bankverbindung
            'iban'                    => 'nullable|string|max:34',
            'bic'                     => 'nullable|string|max:11',
            'account_holder'          => 'nullable|string|max:100',
            'bank_name'               => 'nullable|string|max:100',
            // Schritt 5 – Notfallkontakt
            'emergency_name'          => 'nullable|string|max:100',
            'emergency_relationship'  => 'nullable|string|max:50',
            'emergency_phone'         => 'nullable|string|max:30',
            'emergency_phone_mobile'  => 'nullable|string|max:30',
        ], [
            'first_name.required'    => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required'     => 'Bitte geben Sie Ihren Nachnamen ein.',
            'date_of_birth.required' => 'Bitte geben Sie Ihr Geburtsdatum an.',
            'street.required'        => 'Bitte geben Sie Ihre Straße ein.',
            'house_number.required'  => 'Bitte geben Sie Ihre Hausnummer ein.',
            'zip.required'           => 'Bitte geben Sie Ihre Postleitzahl ein.',
            'city.required'          => 'Bitte geben Sie Ihren Wohnort ein.',
            'country.required'       => 'Bitte geben Sie Ihr Land ein.',
            'email.required'         => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email'            => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ]);

        // Notfallkontakt separat behandeln
        $emergencyData = [];
        if (!empty($validated['emergency_name'])) {
            $emergencyData = [
                'name'         => $validated['emergency_name'],
                'relationship' => $validated['emergency_relationship'] ?? null,
                'phone'        => $validated['emergency_phone'] ?? null,
                'phone_mobile' => $validated['emergency_phone_mobile'] ?? null,
                'priority'     => 1,
            ];
        }
        unset($validated['emergency_name'], $validated['emergency_relationship'],
              $validated['emergency_phone'], $validated['emergency_phone_mobile']);

        $employee->fill($validated);
        $employee->status             = 'aktiv';
        $employee->invitation_token   = null;
        $employee->data_verified_at   = now();
        $employee->save();

        // Notfallkontakt speichern
        if ($emergencyData) {
            $employee->emergencyContacts()->updateOrCreate(
                ['priority' => 1],
                $emergencyData
            );
        }

        EmployeeAccessLog::record(
            $employee->id,
            EmployeeAccessLog::ACTION_EDIT,
            'employee',
            $employee->id,
            array_keys($validated)
        );

        return view('employee-invitation', [
            'employee' => $employee,
            'expired'  => false,
            'used'     => true,
        ]);
    }
}
