<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdvancedShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone_number', '+260972829811')->first() ?? User::where('is_admin', true)->first();
        if (! $admin) {
            return;
        }

        // 1. FMCG & Consumer Behavior (Deep Branching & Visibility)
        Survey::create([
            'user_id' => $admin->id,
            'title' => '2026 Household Grocery Trends',
            'description' => 'A comprehensive 8-question study on shopping habits in major Zambian cities. Features conditional branching and prize draw entry.',
            'reward_amount' => 30.00,
            'reward_points' => 300,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 200,
            'prize_draw_enabled' => true,
            'estimated_time' => 8,
            'target_location' => 'Lusaka, Ndola, Kitwe',
            'questions' => [
                [
                    'id' => 'gro_q1',
                    'type' => 'mcq',
                    'text' => 'Where do you do most of your grocery shopping?',
                    'options' => ['Shoprite', 'Pick n Pay', 'Choppies', 'Local Market', 'Other'],
                    'required' => true,
                    'logic' => [
                        'Shoprite' => 'gro_q2_super',
                        'Pick n Pay' => 'gro_q2_super',
                        'Local Market' => 'gro_q2_market',
                    ],
                ],
                [
                    'id' => 'gro_q2_super',
                    'type' => 'mcq',
                    'text' => 'What is the main reason you choose large supermarkets?',
                    'options' => ['Price', 'Cleanliness', 'Variety', 'Card Payments'],
                    'required' => true,
                    'visibility' => [
                        'logic' => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'gro_q1', 'operator' => 'eq', 'value' => 'Shoprite'],
                            ['type' => 'answer', 'key' => 'gro_q1', 'operator' => 'eq', 'value' => 'Pick n Pay'],
                        ],
                    ],
                ],
                [
                    'id' => 'gro_q2_market',
                    'type' => 'mcq',
                    'text' => 'What is the main reason you prefer the local market?',
                    'options' => ['Freshness', 'Negotiable Prices', 'Supporting Small Business', 'Proximity'],
                    'required' => true,
                    'visibility' => [
                        'logic' => 'and',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'gro_q1', 'operator' => 'eq', 'value' => 'Local Market'],
                        ],
                    ],
                ],
                [
                    'id' => 'gro_q3',
                    'type' => 'scale',
                    'text' => 'How much has the cost of your basic food basket increased in the last month? (1 = No change, 5 = Significant increase)',
                    'required' => true,
                ],
                [
                    'id' => 'gro_q4',
                    'type' => 'mcq',
                    'text' => 'Which protein source does your household consume most often?',
                    'options' => ['Chicken', 'Beef', 'Fish', 'Beans/Soy', 'Other'],
                    'required' => true,
                ],
                [
                    'id' => 'gro_q5',
                    'type' => 'scale',
                    'text' => 'Rate your satisfaction with the quality of "Made in Zambia" products. (1 = Poor, 5 = Excellent)',
                    'required' => true,
                ],
                [
                    'id' => 'gro_q6',
                    'type' => 'mcq',
                    'text' => 'Do you use mobile apps or websites to compare prices before shopping?',
                    'options' => ['Always', 'Often', 'Rarely', 'Never'],
                    'required' => true,
                ],
                [
                    'id' => 'gro_q7',
                    'type' => 'mcq',
                    'text' => 'If a brand offers a loyalty card, does it influence your choice?',
                    'options' => ['Yes, significantly', 'Somewhat', 'Not at all'],
                    'required' => true,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id' => 'household_size',
                    'text' => 'How many people live in your household?',
                    'type' => 'mcq',
                    'options' => ['1-2', '3-5', '6+'],
                ],
            ],
        ]);

        // 2. Financial Services & Digital Banking (Targeted & Detailed)
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'The Future of Digital Banking in Zambia',
            'description' => 'A high-value study for banking customers. We want to know how you manage your money across physical and digital channels.',
            'reward_amount' => 40.00,
            'reward_points' => 400,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 100,
            'prize_draw_enabled' => false,
            'estimated_time' => 10,
            'target_income_band' => '2000 - 4999, 5000 - 9999, 10000+',
            'questions' => [
                [
                    'id' => 'bank_q1',
                    'type' => 'mcq',
                    'text' => 'Do you have a registered bank account?',
                    'options' => ['Yes', 'No'],
                    'required' => true,
                    'logic' => [
                        'No' => 'end', // Exit logic
                    ],
                ],
                [
                    'id' => 'bank_q2',
                    'type' => 'mcq',
                    'text' => 'Which bank do you use as your primary account?',
                    'options' => ['Zanaco', 'Stanbic', 'Standard Chartered', 'FNB', 'ABSA', 'Other'],
                    'required' => true,
                    'visibility' => [
                        'logic' => 'and',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'bank_q1', 'operator' => 'eq', 'value' => 'Yes'],
                        ],
                    ],
                ],
                [
                    'id' => 'bank_q3',
                    'type' => 'mcq',
                    'text' => 'How often do you visit a physical bank branch?',
                    'options' => ['Weekly', 'Monthly', 'Rarely', 'Never'],
                    'required' => true,
                ],
                [
                    'id' => 'bank_q4',
                    'type' => 'scale',
                    'text' => 'How easy is it to use your bank\'s mobile app? (1 = Very Difficult, 5 = Very Easy)',
                    'required' => true,
                ],
                [
                    'id' => 'bank_q5',
                    'type' => 'mcq',
                    'text' => 'What is the biggest barrier to using digital banking more often?',
                    'options' => ['Data Costs', 'Security Concerns', 'App Performance', 'Lack of Features'],
                    'required' => true,
                ],
                [
                    'id' => 'bank_q6',
                    'type' => 'scale',
                    'text' => 'Rate your trust in digital-only banks (e.g. TymeBank models). (1 = No trust, 5 = Full trust)',
                    'required' => true,
                ],
                [
                    'id' => 'bank_q7',
                    'type' => 'mcq',
                    'text' => 'Do you currently use any international payment services (e.g. PayPal, Visa Direct)?',
                    'options' => ['Yes, frequently', 'Occasionally', 'No'],
                    'required' => true,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id' => 'has_credit_card',
                    'text' => 'Do you own a credit card?',
                    'type' => 'mcq',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);
    }
}
