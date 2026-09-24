<?php

namespace Database\Factories;

use App\Models\Invite;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invite>
 */
class InviteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = Str::random(64);

        return [
            'email' => fake()->unique()->safeEmail(),
            'tenancy_id' => function (): string {
                $tenancy = new Tenancy;
                $tenancy->name = fake()->company();
                $tenancy->abbreviation = fake()->lexify('???');
                $tenancy->save();

                return $tenancy->id;
            },
            'user_id' => User::factory(),
            'role' => 'employee',
            'status' => 'pending',
            'token' => $token,
            'invite_url' => 'http://localhost:3000/invite/'.$token,
            'expires_at' => now()->addDays(7),
        ];
    }
}
