<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecruitmentApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_number' => fake()->unique()->regexify('APP/\d{4}/\d{5}'),
            'vacancy_id' => null,
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'postal_address' => fake()->optional()->streetAddress(),
            'highest_qualification' => fake()->randomElement(['Certificate', 'Diploma', 'Degree', 'Masters', 'PhD']),
            'current_organization' => fake()->optional()->company(),
            'area_of_specialization' => fake()->optional()->jobTitle(),
            'years_of_experience' => fake()->numberBetween(0, 20),
            'cv_file_path' => fake()->filePath(),
            'cover_letter_file_path' => fake()->optional()->filePath(),
            'certificates_file_paths' => null,
            'is_shortlisted' => fake()->boolean(50),
            'shortlist_status' => fake()->randomElement(['pending', 'shortlisted', 'rejected']),
            'interview_date' => fake()->optional()->dateTime(),
            'interview_panel_ids' => null,
            'interview_score' => fake()->optional()->randomFloat(2, 50, 100),
            'interview_notes' => fake()->optional()->paragraph(),
            'offer_made' => fake()->boolean(50),
            'offer_accepted' => fake()->boolean(30),
            'new_staff_id' => null,
            'is_onboarded' => fake()->boolean(20),
            'rejection_reason' => fake()->optional()->sentence(),
            'application_source' => fake()->randomElement(['portal', 'referral', 'walk_in']),
        ];
    }

    public function offered(): static
    {
        return $this->state(fn (array $attributes) => [
            'offer_made' => 1,
            'offer_accepted' => 1,
            'is_onboarded' => 0,
        ]);
    }
}
