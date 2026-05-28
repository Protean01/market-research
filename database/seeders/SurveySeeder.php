<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have a user to own the survey
        $admin = User::where('phone_number', '+260972829811')->first() ?? User::create([
            'name' => 'Main Admin',
            'email' => 'admin@mrp.local',
            'password' => bcrypt('password'),
            'phone_number' => '+260972829811',
            'phone_verified_at' => now(),
            'is_admin' => true,
            'role' => 'admin',
        ]);

        // Survey 1 – Location + Age targeted
        Survey::firstOrCreate(
            ['title' => 'Consumer Habits in Lusaka'],
            [
                'user_id' => $admin->id,
                'description' => 'A short survey about shopping habits and preferences for household products.',
                'reward_amount' => 5.00,
                'reward_points' => 50,
                'status' => 'published',
                'is_active' => true,
                'response_cap' => 100,
                'questions' => [
                    [
                        'id' => 1,
                        'text' => 'How often do you shop for groceries?',
                        'type' => 'multiple_choice',
                        'options' => ['Daily', 'Weekly', 'Bi-weekly', 'Monthly'],
                        'required' => true,
                    ],
                    [
                        'id' => 2,
                        'text' => 'Which supermarket do you visit most often?',
                        'type' => 'multiple_choice',
                        'options' => ['Shoprite', 'Pick n Pay', 'Choppies', 'Local Market'],
                        'required' => true,
                    ],
                    [
                        'id' => 3,
                        'text' => 'What is your primary mode of transport when shopping?',
                        'type' => 'multiple_choice',
                        'options' => ['Personal Car', 'Public Bus', 'Taxis/Yango', 'Walking'],
                        'required' => false,
                    ],
                    [
                        'id' => 4,
                        'text' => 'Any other comments on your shopping experience?',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'target_location' => 'Lusaka',
                'target_age_band' => '18-35',
            ]
        );

        // Survey 2 – No targeting (open to all)
        Survey::firstOrCreate(
            ['title' => 'Mobile Network Preference Study'],
            [
                'user_id' => $admin->id,
                'description' => 'Help us understand which mobile networks provide the best service in your area.',
                'reward_amount' => 10.00,
                'reward_points' => 100,
                'status' => 'published',
                'is_active' => true,
                'response_cap' => 500,
                'questions' => [
                    [
                        'id' => 1,
                        'text' => 'Which mobile network do you use primarily?',
                        'type' => 'multiple_choice',
                        'options' => ['Airtel', 'MTN', 'Zamtel'],
                        'required' => true,
                    ],
                    [
                        'id' => 2,
                        'text' => 'Rate your network coverage from 1 to 5.',
                        'type' => 'range',
                        'min' => 1,
                        'max' => 5,
                        'required' => true,
                    ],
                ],
            ]
        );

        // Survey 3 – Open to all (no targeting) — ensures all users always see something
        Survey::firstOrCreate(
            ['title' => 'General Lifestyle & Wellbeing'],
            [
                'user_id' => $admin->id,
                'description' => 'Tell us about your daily lifestyle, health habits, and general wellbeing.',
                'reward_amount' => 3.00,
                'reward_points' => 30,
                'status' => 'published',
                'is_active' => true,
                'response_cap' => 0, // 0 = no cap
                'questions' => [
                    [
                        'id' => 1,
                        'text' => 'How would you rate your overall health?',
                        'type' => 'scale',
                        'min' => 1,
                        'max' => 5,
                        'required' => true,
                    ],
                    [
                        'id' => 2,
                        'text' => 'How many hours of sleep do you get on average?',
                        'type' => 'multiple_choice',
                        'options' => ['Less than 5', '5-6', '7-8', 'More than 8'],
                        'required' => true,
                    ],
                    [
                        'id' => 3,
                        'text' => 'Do you exercise regularly?',
                        'type' => 'multiple_choice',
                        'options' => ['Yes, daily', 'Yes, a few times a week', 'Rarely', 'Never'],
                        'required' => false,
                    ],
                ],
                // No targeting — visible to ALL users
            ]
        );

        // Survey 4 – Open to all (no targeting)
        Survey::firstOrCreate(
            ['title' => 'Digital Payment Habits Survey'],
            [
                'user_id' => $admin->id,
                'description' => 'How do you pay for things? Help us understand mobile money and digital payment trends.',
                'reward_amount' => 7.00,
                'reward_points' => 70,
                'status' => 'published',
                'is_active' => true,
                'response_cap' => 0, // 0 = no cap
                'questions' => [
                    [
                        'id' => 1,
                        'text' => 'Which mobile money service do you use?',
                        'type' => 'multiple_choice',
                        'options' => ['Airtel Money', 'MTN MoMo', 'Zamtel Kwacha', 'None'],
                        'required' => true,
                    ],
                    [
                        'id' => 2,
                        'text' => 'How often do you use mobile money in a week?',
                        'type' => 'multiple_choice',
                        'options' => ['Never', '1-2 times', '3-5 times', 'Daily'],
                        'required' => true,
                    ],
                    [
                        'id' => 3,
                        'text' => 'What do you use mobile money for most?',
                        'type' => 'multiple_choice',
                        'options' => ['Buying airtime', 'Paying bills', 'Sending money', 'Shopping'],
                        'required' => false,
                    ],
                ],
                // No targeting — visible to ALL users
            ]
        );

        // Survey 5 – Open to all (no targeting)
        Survey::firstOrCreate(
            ['title' => 'Social Media Usage Study'],
            [
                'user_id' => $admin->id,
                'description' => 'How do you use social media in your daily life?',
                'reward_amount' => 5.00,
                'reward_points' => 50,
                'status' => 'published',
                'is_active' => true,
                'response_cap' => 0, // 0 = no cap
                'questions' => [
                    [
                        'id' => 1,
                        'text' => 'Which social media platform do you use most?',
                        'type' => 'multiple_choice',
                        'options' => ['Facebook', 'WhatsApp', 'Instagram', 'TikTok', 'Twitter/X'],
                        'required' => true,
                    ],
                    [
                        'id' => 2,
                        'text' => 'How many hours per day do you spend on social media?',
                        'type' => 'multiple_choice',
                        'options' => ['Less than 1 hour', '1-2 hours', '3-4 hours', 'More than 4 hours'],
                        'required' => true,
                    ],
                    [
                        'id' => 3,
                        'text' => 'Have you ever purchased something because of a social media ad?',
                        'type' => 'multiple_choice',
                        'options' => ['Yes, frequently', 'Yes, occasionally', 'No'],
                        'required' => true,
                    ],
                ],
                // No targeting — visible to ALL users
            ]
        );
    }
}
