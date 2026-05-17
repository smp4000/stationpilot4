<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

class StationFactory extends Factory
{
    protected $model = Station::class;

    public function definition(): array
    {
        return [
            'name'            => $this->faker->company() . ' Tankstelle',
            'brand'           => $this->faker->randomElement(['Aral', 'Shell', 'BP', 'Esso', 'Freie Station']),
            'station_number'  => $this->faker->numerify('#######'),
            'street'          => $this->faker->streetName(),
            'house_number'    => $this->faker->buildingNumber(),
            'zip'             => $this->faker->postcode(),
            'city'            => $this->faker->city(),
            'country'         => 'DE',
            'lat'             => $this->faker->latitude(47.3, 54.9),
            'lng'             => $this->faker->longitude(6.0, 15.0),
            'opening_hours'   => Station::defaultOpeningHours(),
            'tank_count'      => $this->faker->numberBetween(2, 8),
            'dispenser_count' => $this->faker->numberBetween(4, 16),
            'has_car_wash'    => $this->faker->boolean(60),
            'has_bistro'      => $this->faker->boolean(40),
            'has_shop'        => $this->faker->boolean(80),
            'is_active'       => true,
        ];
    }

    public function withCoordinates(float $lat, float $lng): static
    {
        return $this->state(['lat' => $lat, 'lng' => $lng]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function aral(): static
    {
        return $this->state(['brand' => 'Aral']);
    }
}
