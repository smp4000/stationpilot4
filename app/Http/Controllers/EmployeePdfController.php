<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class EmployeePdfController extends Controller
{
    public function mitarbeiterdaten(int $id): Response
    {
        $employee = Employee::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', session('tenant_id'))
            ->with(['station', 'emergencyContacts', 'previousEmployment', 'keyHandovers.key'])
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.mitarbeiterdaten', ['employee' => $employee])
            ->setPaper('a4', 'portrait');

        $filename = 'Mitarbeiterdaten_' . $employee->last_name . '_' . $employee->first_name . '.pdf';

        return $pdf->download($filename);
    }
}
