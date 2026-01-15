<?php

namespace Database\Factories;

use App\Models\DeviceModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceModel>
 */
class DeviceModelFactory extends Factory
{
    protected $model = DeviceModel::class;

    public function definition(): array
    {
        return [
            'device_names' => $this->faker->word(),
            'mac_devices'  => strtoupper($this->faker->bothify('##:##:##:##:##:##')),
            'rssi'         => $this->faker->numberBetween(-100, -20),
            'tipe_device'  => $this->faker->boolean(),
            'x'            => $this->faker->numberBetween(0, 100),
            'y'            => $this->faker->numberBetween(0, 100),
        ];
    }
}
