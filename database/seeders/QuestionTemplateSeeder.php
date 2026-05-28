<?php

namespace Database\Seeders;

use App\Models\QuestionTemplate;
use Illuminate\Database\Seeder;

class QuestionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Basic Demographics',
                'category' => 'demographic',
                'questions' => [
                    [
                        'id' => 'q1',
                        'text' => 'What is your current gender identity?',
                        'type' => 'mcq',
                        'options' => ['Female', 'Male', 'Non-binary', 'Prefer not to say'],
                        'required' => true,
                    ],
                    [
                        'id' => 'q2',
                        'text' => 'Which age band do you fall into?',
                        'type' => 'mcq',
                        'options' => ['18-24', '25-34', '35-44', '45-54', '55+'],
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'Product Usage',
                'category' => 'behavior',
                'questions' => [
                    [
                        'id' => 'q1',
                        'text' => 'How often do you use our products?',
                        'type' => 'mcq',
                        'options' => ['Daily', 'Weekly', 'Monthly', 'Rarely', 'Never'],
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'Service Satisfaction',
                'category' => 'satisfaction',
                'questions' => [
                    [
                        'id' => 'q1',
                        'text' => 'On a scale of 1-5, how satisfied are you with our service?',
                        'type' => 'scale',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'General Feedback',
                'category' => 'general',
                'questions' => [
                    [
                        'id' => 'q1',
                        'text' => 'Do you have any additional feedback for us?',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
            ],
        ];

        foreach ($templates as $template) {
            QuestionTemplate::create($template);
        }
    }
}
