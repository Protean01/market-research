<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleSurveySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone_number', '+260972829811')->first() ?? User::where('is_admin', true)->first();
        if (! $admin) {
            return;
        }

        // 1. High-Value Consumer Tech Study (Branching Logic & Prize Draw)
        Survey::create([
            'user_id' => $admin->id,
            'title' => '2026 Smartphone & AI Gadget Trends',
            'description' => 'Help us shape the future of mobile technology. Features branching logic based on your tech habits and a chance to win a 2026 flagship device!',
            'reward_amount' => 15.00,
            'reward_points' => 150,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 100,
            'prize_draw_enabled' => true,
            'estimated_time' => 8,
            'questions' => [
                [
                    'id' => 'q1',
                    'type' => 'mcq',
                    'text' => 'Which operating system do you primarily use?',
                    'options' => ['iOS (iPhone)', 'Android', 'Other'],
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'q2_ios',
                    'type' => 'mcq',
                    'text' => 'What is the main reason you stay with iPhone?',
                    'options' => ['Ecosystem/iMessage', 'Privacy Features', 'Hardware Design', 'Resale Value'],
                    'required' => true,
                    'logic' => [
                        ['field' => 'q1', 'operator' => 'equals', 'value' => 'iOS (iPhone)'],
                    ],
                ],
                [
                    'id' => 'q2_android',
                    'type' => 'mcq',
                    'text' => 'What is the main reason you prefer Android?',
                    'options' => ['Customization', 'Hardware Variety', 'Open Source Nature', 'Affordability'],
                    'required' => true,
                    'logic' => [
                        ['field' => 'q1', 'operator' => 'equals', 'value' => 'Android'],
                    ],
                ],
                [
                    'id' => 'q3',
                    'type' => 'scale',
                    'text' => 'How interested are you in integrated AI features (e.g. real-time translation)? (1 = Not at all, 5 = Essential)',
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'q4',
                    'type' => 'mcq',
                    'text' => 'Which smart home ecosystem do you use most?',
                    'options' => ['Apple Home', 'Google Home', 'Amazon Alexa', 'Samsung SmartThings', 'None'],
                    'required' => true,
                    'logic' => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id' => 'tech_savvy',
                    'text' => 'Do you consider yourself an early adopter of new technology?',
                    'type' => 'mcq',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);

        // 2. Financial Inclusion & Mobile Money (Targeted & Detailed)
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'Mobile Money & Digital Payments Study',
            'description' => 'A deep dive into how mobile money is transforming financial access in Zambia. Specifically looking for active mobile wallet users.',
            'reward_amount' => 12.00,
            'reward_points' => 120,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 250,
            'prize_draw_enabled' => false,
            'estimated_time' => 10,
            'target_location' => 'Lusaka, Copperbelt',
            'target_age_band' => '25-50',
            'questions' => [
                [
                    'id' => 'fin1',
                    'type' => 'mcq',
                    'text' => 'Which mobile money provider do you use most frequently?',
                    'options' => ['Airtel Money', 'MTN MoMo', 'Zamtel Kwacha', 'None'],
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'fin2',
                    'type' => 'mcq',
                    'text' => 'What is your main use for mobile money?',
                    'options' => ['Person-to-Person Transfers', 'Utility Bills', 'Merchant Payments', 'Saving/Interest Accounts', 'Loans'],
                    'required' => true,
                    'logic' => [
                        ['field' => 'fin1', 'operator' => 'not_equals', 'value' => 'None'],
                    ],
                ],
                [
                    'id' => 'fin3',
                    'type' => 'mcq',
                    'text' => 'How often do you encounter issues with agent liquidity (cash out)?',
                    'options' => ['Daily', 'Weekly', 'Rarely', 'Never'],
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'fin4',
                    'type' => 'scale',
                    'text' => 'Rate your overall satisfaction with your mobile money provider (1 = Poor, 5 = Excellent)',
                    'required' => true,
                    'logic' => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id' => 'has_momo_business',
                    'text' => 'Do you use mobile money for business transactions?',
                    'type' => 'mcq',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);

        // 3. Health & Wellness in the Digital Age (Short & Viral)
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'Digital Wellbeing & Screen Time',
            'description' => 'A quick 2-minute survey about how digital habits affect your sleep and mental health.',
            'reward_amount' => 5.00,
            'reward_points' => 50,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 1000,
            'prize_draw_enabled' => false,
            'estimated_time' => 2,
            'questions' => [
                [
                    'id' => 'hw1',
                    'type' => 'mcq',
                    'text' => 'How many hours of screen time do you average per day?',
                    'options' => ['Less than 2', '2-4 hours', '4-8 hours', '8+ hours'],
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'hw2',
                    'type' => 'scale',
                    'text' => 'How much do you feel your phone usage impacts your sleep quality? (1 = No impact, 5 = Severe impact)',
                    'required' => true,
                    'logic' => [],
                ],
                [
                    'id' => 'hw3',
                    'type' => 'mcq',
                    'text' => 'Do you use any "Digital Wellbeing" or focus apps to limit usage?',
                    'options' => ['Yes, frequently', 'Occasionally', 'No, never'],
                    'required' => true,
                    'logic' => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id' => 'uses_dark_mode',
                    'text' => 'Do you use "Dark Mode" on your devices?',
                    'type' => 'mcq',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);
    }
}
