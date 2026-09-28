<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'contact_id' => Contact::factory(),
            'value' => fake()->randomFloat(2, 500, 50000),
            'stage' => DealStage::Lead,
            'expected_close_date' => fake()->dateTimeBetween('now', '+3 months'),
            'notes' => fake()->sentence(),
        ];
    }

    public function stage(DealStage $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }
}
