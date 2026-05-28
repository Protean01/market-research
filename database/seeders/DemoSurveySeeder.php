<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSurveySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();
        if (! $admin) {
            return;
        }

        // 1. QUICK SURVEY: Instant Points
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'Quick Daily Pulse: Coffee vs Tea',
            'description' => 'A 2-question survey to understand the morning caffeine habits of our users. Instant reward!',
            'reward_type' => 'points',
            'reward_points' => 50,
            'estimated_time' => 1,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 500,
            'questions' => [
                [
                    'id' => 'pulse_1',
                    'type' => 'mcq',
                    'text' => 'What is your preferred morning beverage?',
                    'options' => ['Coffee', 'Tea', 'Water', 'Energy Drink', 'None'],
                    'required' => true,
                ],
                [
                    'id' => 'pulse_2',
                    'type' => 'scale',
                    'text' => 'How alert do you feel before your first drink? (1 = Zombie, 5 = Fully Awake)',
                    'required' => true,
                ],
            ],
        ]);

        // 2. MEDIUM SURVEY: Slot Machine / Prize Draw
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'Tech & Lifestyle 2026',
            'description' => 'Help us understand how technology is changing your daily routine. Completion enters you into a Jackpot Draw for a chance to win 2,000 points!',
            'reward_type' => 'prize_draw',
            'prize_name' => '2,000 Point Mega Jackpot',
            'reward_points' => 2000,
            'estimated_time' => 5,
            'status' => 'active',
            'is_active' => true,
            'prize_draw_enabled' => true,
            'response_cap' => 100,
            'questions' => [
                [
                    'id' => 'tech_1',
                    'type' => 'mcq',
                    'text' => 'Which device do you use most for browsing?',
                    'options' => ['Smartphone', 'Laptop', 'Tablet', 'Desktop'],
                    'required' => true,
                ],
                [
                    'id' => 'tech_2',
                    'type' => 'mcq',
                    'text' => 'Do you use AI tools (like ChatGPT) in your daily work/study?',
                    'options' => ['Every day', 'Occasionally', 'Rarely', 'Never'],
                    'required' => true,
                ],
                [
                    'id' => 'tech_3',
                    'type' => 'scale',
                    'text' => 'Rate your optimism about the future of technology in Zambia.',
                    'required' => true,
                ],
                [
                    'id' => 'tech_4',
                    'type' => 'mcq',
                    'text' => 'How many hours a day do you spend on social media?',
                    'options' => ['0-1', '2-4', '5-7', '8+'],
                    'required' => true,
                ],
            ],
        ]);

        // 3. LONG SURVEY: High-Value Airtime Reward
        Survey::create([
            'user_id' => $admin->id,
            'title' => 'Deep-Dive: Banking & Financial Inclusion',
            'description' => 'A detailed study on financial habits. Complete all questions to receive K50 Airtime sent directly to your phone number.',
            'reward_type' => 'airtime',
            'reward_amount' => 50.00,
            'reward_points' => 0,
            'estimated_time' => 12,
            'status' => 'active',
            'is_active' => true,
            'response_cap' => 50,
            'questions' => [
                [
                    'id' => 'fin_1',
                    'type' => 'mcq',
                    'text' => 'Which mobile money service do you use most?',
                    'options' => ['Airtel Money', 'MTN MoMo', 'Zamtel Kwacha', 'None'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_2',
                    'type' => 'mcq',
                    'text' => 'What is the primary way you pay for groceries?',
                    'options' => ['Cash', 'Mobile Money', 'Bank Card', 'Credit'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_3',
                    'type' => 'mcq',
                    'text' => 'Have you ever taken a loan from a digital lending app?',
                    'options' => ['Yes', 'No'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_4',
                    'type' => 'scale',
                    'text' => 'How much do you trust traditional banks compared to mobile money providers?',
                    'required' => true,
                ],
                [
                    'id' => 'fin_5',
                    'type' => 'mcq',
                    'text' => 'Do you save a portion of your income every month?',
                    'options' => ['Always', 'Usually', 'Sometimes', 'Never'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_6',
                    'type' => 'mcq',
                    'text' => 'What is your main financial goal for the next 12 months?',
                    'options' => ['Buying Land', 'Starting Business', 'Education', 'Emergency Fund'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_7',
                    'type' => 'mcq',
                    'text' => 'Do you use insurance products (Health, Life, Car)?',
                    'options' => ['Yes, multiple', 'Only one', 'No'],
                    'required' => true,
                ],
                [
                    'id' => 'fin_8',
                    'type' => 'scale',
                    'text' => 'Rate your understanding of investment options available in Zambia.',
                    'required' => true,
                ],
            ],
        ]);
    }
}
