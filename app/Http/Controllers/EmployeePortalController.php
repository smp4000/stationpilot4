<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeePortalController extends Controller
{
    public function showLogin()
    {
        if (session('employee_id')) return redirect()->route('employee.portal.dashboard');
        return view('employee-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ]);

        $employee = Employee::where('email', $request->email)
            ->whereNotNull('password')
            ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return back()->withErrors(['email' => 'E-Mail-Adresse oder Passwort falsch.'])->withInput();
        }

        session(['employee_id' => $employee->id]);

        if ($employee->must_change_password) {
            return redirect()->route('employee.portal.change-password');
        }

        return redirect()->route('employee.portal.dashboard');
    }

    public function logout()
    {
        session()->forget('employee_id');
        return redirect()->route('employee.portal.login')->with('success', 'Sie wurden abgemeldet.');
    }

    public function showDashboard()
    {
        $employee = Employee::findOrFail(session('employee_id'));
        return view('employee-portal.dashboard', compact('employee'));
    }

    public function showChangePassword()
    {
        $employee = Employee::findOrFail(session('employee_id'));
        return view('employee-portal.change-password', compact('employee'));
    }

    public function changePassword(Request $request)
    {
        $employee = Employee::findOrFail(session('employee_id'));

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.required'  => 'Bitte geben Sie ein neues Passwort ein.',
            'password.min'       => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
        ]);

        $employee->password             = Hash::make($request->password);
        $employee->must_change_password = false;
        $employee->save();

        return redirect()->route('employee.portal.dashboard')->with('success', 'Passwort erfolgreich geändert.');
    }
}
