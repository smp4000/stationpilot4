<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'                => $name,
            'slug'                => Str::slug($name) . '-' . Str::random(4),
            'billing_email'       => $this->faker->companyEmail(),
            'billing_address'     => [
                'street'  => $this->faker->streetAddress(),
                'zip'     => $this->faker->postcode(),
                'city'    => $this->faker->city(),
                'country' => 'DE',
            ],
            'tax_id'              => null,
            'ust_id'              => null,
            'subscription_status' => 'trial',
            'trial_ends_at'       => now()->addDays(14),
            'locale'              => 'de',
            'timezone'            => 'Europe/Berlin',
            'is_active'           => true,
        ];
    }

    /** Mandant mit aktivem Abo. */
    public function active(): static
    {
        return $this->state(['subscription_status' => 'active', 'trial_ends_at' => null]);
    }

    /** Mandant mit abgelaufenem Trial. */
    public function trialExpired(): static
    {
        return $this->state([
            'subscription_status' => 'trial',
            'trial_ends_at'       => now()->subDay(),
        ]);
    }

    /** Deaktivierter Mandant. */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
