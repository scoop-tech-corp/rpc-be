<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'userName'                       => fake()->unique()->userName(),
            'firstName'                      => fake()->firstName(),
            'middleName'                     => '',
            'lastName'                       => fake()->lastName(),
            'nickName'                       => fake()->firstName(),
            'gender'                         => fake()->randomElement(['Male', 'Female']),
            'status'                         => 1,
            'jobTitleId'                     => 14,
            'startDate'                      => now()->subYear()->toDateString(),
            'endDate'                        => now()->addYear()->toDateString(),
            'joinDate'                       => now()->subYear()->toDateString(),
            'registrationNo'                 => fake()->numerify('##########'),
            'designation'                    => fake()->numerify('##########'),
            'annualSickAllowance'            => 10,
            'annualLeaveAllowance'           => 10,
            'annualSickAllowanceRemaining'   => 10,
            'annualLeaveAllowanceRemaining'  => 10,
            'payPeriodId'                    => 2,
            'payAmount'                      => 5000000,
            'typeId'                         => 0,
            'identificationNumber'           => '',
            'additionalInfo'                 => '',
            'generalCustomerCanSchedule'     => 1,
            'generalCustomerReceiveDailyEmail' => 1,
            'generalAllowMemberToLogUsingEmail' => 1,
            'reminderEmail'                  => 1,
            'reminderWhatsapp'               => 1,
            'roleId'                         => 1,
            'lineManagerId'                  => 1,
            'imageName'                      => 'default.jpg',
            'imagePath'                      => '/UsersProfiles/default.jpg',
            'email'                          => fake()->unique()->safeEmail(),
            'password'                       => bcrypt('password123'),
            'isDeleted'                      => 0,
            'createdBy'                      => 'Test',
            'isLogin'                        => 0,
        ];
    }

    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
