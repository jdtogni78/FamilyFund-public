<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\MatchingReminderLog;
use App\Models\ScheduledJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchingReminderLogFactory extends Factory
{
    protected $model = MatchingReminderLog::class;

    public function definition(): array
    {
        return [
            'scheduled_job_id' => ScheduledJob::factory(),
            'account_id' => Account::factory(),
            'sent_at' => $this->faker->date(),
            'rule_details' => [
                [
                    'rule_id' => $this->faker->randomNumber(),
                    'rule_name' => $this->faker->words(3, true),
                    'remaining' => $this->faker->randomFloat(2, 10, 500),
                    'expires' => $this->faker->date(),
                    'is_expiring' => $this->faker->boolean(),
                ]
            ],
            'rules_count' => 1,
        ];
    }
}
