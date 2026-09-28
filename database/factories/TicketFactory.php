<?php

namespace Database\Factories;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'contact_id' => Contact::factory(),
            'type' => TicketType::Question,
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::New,
            'channel' => TicketChannel::Email,
        ];
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
