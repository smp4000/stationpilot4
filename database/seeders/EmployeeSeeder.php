<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId  = 1;
        $stationId = 1;

        $employees = [
            [
                'first_name'       => 'Christian',
                'last_name'        => 'Welle',
                'birth_name'       => 'Welle',
                'gender'           => 'm',
                'date_of_birth'    => '1980-03-15',
                'place_of_birth'   => 'Fulda',
                'country_of_birth' => 'Deutschland',
                'nationality'      => 'deutsch',
                'marital_status'   => 'verheiratet',
                'street'           => 'Am Spielrain',
                'house_number'     => '7a',
                'zip'              => '36100',
                'city'             => 'Petersberg',
                'country'          => 'Deutschland',
                'phone_mobile'     => '01712816216',
                'email'            => 'christian.welle@aral-welle.de',
                'job_title'        => 'Geschäftsführer',
                'employment_type'  => 'vollzeit',
                'employment_start' => '2002-02-01',
                'wage_type'        => 'gehalt',
                'wage_amount'      => '4500',
                'payment_interval' => 'monatlich',
                'mde_pin'          => '1234',
                'scan_code'        => 'CW-001',
                'status'           => 'aktiv',
                'employee_status'  => 'aktiv',
            ],
            [
                'first_name'       => 'Alexandra',
                'last_name'        => 'Welle',
                'birth_name'       => 'Müller',
                'gender'           => 'w',
                'date_of_birth'    => '1983-07-22',
                'place_of_birth'   => 'Kassel',
                'country_of_birth' => 'Deutschland',
                'nationality'      => 'deutsch',
                'marital_status'   => 'verheiratet',
                'street'           => 'Am Spielrain',
                'house_number'     => '7a',
                'zip'              => '36100',
                'city'             => 'Petersberg',
                'country'          => 'Deutschland',
                'phone_mobile'     => '01712816217',
                'email'            => 'alexandra.welle@aral-welle.de',
                'job_title'        => 'Stationsleiterin',
                'employment_type'  => 'vollzeit',
                'employment_start' => '2005-06-01',
                'wage_type'        => 'gehalt',
                'wage_amount'      => '3200',
                'payment_interval' => 'monatlich',
                'mde_pin'          => '2345',
                'scan_code'        => 'AW-002',
                'status'           => 'aktiv',
                'employee_status'  => 'aktiv',
            ],
            [
                'first_name'       => 'Lara Sophie',
                'last_name'        => 'Welle',
                'birth_name'       => 'Welle',
                'gender'           => 'w',
                'date_of_birth'    => '2003-11-08',
                'place_of_birth'   => 'Fulda',
                'country_of_birth' => 'Deutschland',
                'nationality'      => 'deutsch',
                'marital_status'   => 'ledig',
                'street'           => 'Am Spielrain',
                'house_number'     => '7a',
                'zip'              => '36100',
                'city'             => 'Petersberg',
                'country'          => 'Deutschland',
                'phone_mobile'     => '01712816218',
                'email'            => 'lara.welle@aral-welle.de',
                'job_title'        => 'Kassiererin',
                'employment_type'  => 'teilzeit',
                'employment_start' => '2022-08-01',
                'wage_type'        => 'stundenlohn',
                'wage_amount'      => '13.50',
                'payment_interval' => 'monatlich',
                'mde_pin'          => '3456',
                'scan_code'        => 'LSW-003',
                'status'           => 'aktiv',
                'employee_status'  => 'aktiv',
            ],
            [
                'first_name'       => 'Max',
                'last_name'        => 'Mustermann',
                'birth_name'       => 'Mustermann',
                'gender'           => 'm',
                'date_of_birth'    => '1995-05-20',
                'place_of_birth'   => 'Frankfurt',
                'country_of_birth' => 'Deutschland',
                'nationality'      => 'deutsch',
                'marital_status'   => 'ledig',
                'street'           => 'Musterstraße',
                'house_number'     => '1',
                'zip'              => '36037',
                'city'             => 'Fulda',
                'country'          => 'Deutschland',
                'phone_mobile'     => '01601234567',
                'email'            => 'max.mustermann@aral-welle.de',
                'job_title'        => 'Tankwart',
                'employment_type'  => 'vollzeit',
                'employment_start' => '2020-01-15',
                'wage_type'        => 'stundenlohn',
                'wage_amount'      => '14.00',
                'payment_interval' => 'monatlich',
                'mde_pin'          => '4567',
                'scan_code'        => 'MM-004',
                'status'           => 'aktiv',
                'employee_status'  => 'aktiv',
            ],
            [
                'first_name'       => 'Erika',
                'last_name'        => 'Musterfrau',
                'birth_name'       => 'Schmidt',
                'gender'           => 'w',
                'date_of_birth'    => '1990-09-12',
                'place_of_birth'   => 'Hanau',
                'country_of_birth' => 'Deutschland',
                'nationality'      => 'deutsch',
                'marital_status'   => 'geschieden',
                'street'           => 'Beispielweg',
                'house_number'     => '5',
                'zip'              => '36043',
                'city'             => 'Fulda',
                'country'          => 'Deutschland',
                'phone_mobile'     => '01709876543',
                'email'            => 'erika.musterfrau@aral-welle.de',
                'job_title'        => 'Verkäuferin Shop',
                'employment_type'  => 'minijob',
                'employment_start' => '2023-03-01',
                'wage_type'        => 'stundenlohn',
                'wage_amount'      => '13.00',
                'payment_interval' => 'monatlich',
                'mde_pin'          => '5678',
                'scan_code'        => 'EM-005',
                'status'           => 'aktiv',
                'employee_status'  => 'aktiv',
            ],
        ];

        foreach ($employees as $data) {
            // Bereits vorhanden? Überspringen
            if (Employee::where('email', $data['email'])->exists()) {
                $this->command->info("Übersprungen: {$data['first_name']} {$data['last_name']}");
                continue;
            }

            Employee::create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'station_id' => $stationId,
            ]));

            $this->command->info("Erstellt: {$data['first_name']} {$data['last_name']}");
        }
    }
}

