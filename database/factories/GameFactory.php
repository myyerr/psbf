<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_one_id' => User::factory(),
            'player_two_id' => null,
            'player_one_credit' => 1,
            'player_two_credit' => 1,
            'match_reward' => 2,
            'match_fee' => 0,
            'status' => false,
            'state' => null,
        ];
    }
}
