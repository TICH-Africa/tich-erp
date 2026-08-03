<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => fake()->unique()->regexify('EMP/\d{4}/\d{5}'),
            'title' => fake()->optional()->title(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'surname' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-25 years'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'marital_status' => fake()->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'national_id_number' => fake()->optional()->regexify('\d{8}'),
            'passport_number' => fake()->optional()->regexify('[A-Z]{2}\d{7}'),
            'nationality' => 'Kenyan',
            'home_county' => fake()->optional()->city(),
            'primary_email' => fake()->unique()->safeEmail(),
            'organisation_email' => fn (array $attributes) => Staff::organisationEmailFromName(
                $attributes['first_name'],
                $attributes['surname']
            ),
            'phone_number' => fake()->phoneNumber(),
            'alt_phone_number' => fake()->optional()->phoneNumber(),
            'postal_address' => fake()->optional()->streetAddress(),
            'postal_code' => fake()->optional()->postcode(),
            'physical_address' => fake()->optional()->address(),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->phoneNumber(),
            'emergency_contact_relationship' => fake()->optional()->word(),
            'photo_path' => null,
            'department_id' => Department::inRandomOrder()->value('id') ?? 1,
            'campus_id' => null,
            'job_title' => fake()->jobTitle(),
            'job_grade' => fake()->optional()->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']),
            'employment_category' => fake()->randomElement(['permanent', 'contract', 'intern', 'visiting', 'casual']),
            'employment_start_date' => fake()->date('Y-m-d', '-2 years'),
            'contract_end_date' => fake()->optional()->date('Y-m-d', '+1 year'),
            'is_on_probation' => fake()->boolean(30),
            'probation_end_date' => fake()->optional()->date('Y-m-d', '+6 months'),
            'confirmation_date' => fake()->optional()->date('Y-m-d', '-1 year'),
            'gross_monthly_salary' => fake()->numberBetween(20000, 150000),
            'allowances_json' => null,
            'bank_id' => null,
            'kra_pin' => fake()->optional()->regexify('\d{11}[A-Z]'),
            'nssf_number' => fake()->optional()->regexify('\d{13}'),
            'sha_number' => fake()->optional()->regexify('\d{13}'),
            'helb_number' => fake()->optional()->regexify('\d{10}'),
            'pension_scheme_id' => null,
            'employment_status' => 'active',
            'exit_date' => null,
            'exit_reason' => null,
            'user_id' => null,
            'is_teaching_staff' => fake()->boolean(40),
            'is_nursing_license_required' => fake()->boolean(20),
            'line_manager_id' => null,
            'salary_scale' => fake()->optional()->randomElement(['Scale 1', 'Scale 2', 'Scale 3', 'Scale 4']),
            'incremental_date' => fake()->optional()->date('Y-m-d', '+1 year'),
            'project_code' => fake()->optional()->regexify('PRJ-\d{4}'),
            'is_profile_locked' => fake()->boolean(80),
            'onboarding_completed_at' => fake()->optional()->dateTime(),
            'created_by' => null,
        ];
    }

    public function onboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'onboarding',
            'is_profile_locked' => 0,
            'onboarding_completed_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'active',
            'is_profile_locked' => 1,
        ]);
    }
}
