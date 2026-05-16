<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'tenant_id'         => null,
            'is_company'        => false,
            'first_name'        => $this->faker->firstName(),
            'last_name'         => $this->faker->lastName(),
            'company_name'      => null,
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'type'              => 'employee',
            'locale'            => 'de',
            'is_active'         => true,
            'scan_code'         => null,
            'pin_hash'          => null,
        ];
    }

    /** Super-Admin (kein Mandant). */
    public function superAdmin(): static
    {
        return $this->state([
            'type'      => 'super_admin',
            'tenant_id' => null,
        ]);
    }

    /** Partner (braucht noch tenant_id). */
    public function partner(): static
    {
        return $this->state(['type' => 'partner']);
    }

    /** Mitarbeiter. */
    public function employee(): static
    {
        return $this->state(['type' => 'employee']);
    }

    /** Firmenkunde. */
    public function company(): static
    {
        return $this->state([
            'is_company'   => true,
            'first_name'   => null,
            'last_name'    => null,
            'company_name' => $this->faker->company(),
        ]);
    }

    /** E-Mail nicht verifiziert. */
    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
