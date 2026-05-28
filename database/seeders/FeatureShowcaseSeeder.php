<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeatureShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            return;
        }

        // ── 1. PRIZE DRAW ─────────────────────────────────────────────────────
        // All question types, deep branching on employment, trait targeting
        Survey::create([
            'user_id'       => $admin->id,
            'title'         => 'Zambia Entrepreneurship & Employment Study',
            'description'   => 'Tell us about your work life and career goals. One lucky respondent wins K500 in the prize draw — everyone else earns points for trying!',
            'reward_type'   => 'prize_draw',
            'reward_amount' => 500.00,
            'reward_points' => 250,
            'prize_name'    => 'K500 Cash Prize',
            'status'        => 'active',
            'is_active'     => true,
            'response_cap'  => 150,
            'estimated_time'=> 10,
            'target_employment' => 'Employed',
            'target_age_band'   => '25-44',
            'target_location'   => 'Lusaka',
            'questions' => [
                [
                    'id'       => 'ent_q1',
                    'type'     => 'mcq',
                    'text'     => 'What best describes your current employment status?',
                    'options'  => ['Formally Employed', 'Self-Employed / Business Owner', 'Freelancer / Contractor', 'Unemployed / Job Seeking'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ent_q2_formal',
                    'type'     => 'mcq',
                    'text'     => 'How satisfied are you with your current employer?',
                    'options'  => ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'],
                    'required' => true,
                    'logic'    => [],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'ent_q1', 'operator' => 'eq', 'value' => 'Formally Employed'],
                        ],
                    ],
                ],
                [
                    'id'       => 'ent_q2_biz',
                    'type'     => 'mcq',
                    'text'     => 'How long have you been running your business?',
                    'options'  => ['Less than 1 year', '1–3 years', '3–5 years', 'More than 5 years'],
                    'required' => true,
                    'logic'    => [],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'ent_q1', 'operator' => 'eq', 'value' => 'Self-Employed / Business Owner'],
                            ['type' => 'answer', 'key' => 'ent_q1', 'operator' => 'eq', 'value' => 'Freelancer / Contractor'],
                        ],
                    ],
                ],
                [
                    'id'       => 'ent_q3',
                    'type'     => 'checkbox',
                    'text'     => 'Which of the following challenges affect your work most? (Select all that apply)',
                    'options'  => ['Unreliable Internet / Load Shedding', 'Access to Finance / Capital', 'High Cost of Living', 'Skills Gap / Training', 'Regulatory Hurdles', 'Finding Clients / Customers'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ent_q4',
                    'type'     => 'scale',
                    'text'     => 'How optimistic are you about Zambia\'s economy in the next 12 months? (1 = Very Pessimistic, 5 = Very Optimistic)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ent_q5',
                    'type'     => 'mcq',
                    'text'     => 'Have you ever applied for a business loan or government grant?',
                    'options'  => ['Yes, and I was approved', 'Yes, but I was rejected', 'No, but I would consider it', 'No, I\'m not interested'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ent_q6',
                    'type'     => 'scale',
                    'text'     => 'How would you rate the quality of business support services (e.g. ZABS, PACRA, ZDA) in Zambia? (1 = Poor, 5 = Excellent)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ent_q7',
                    'type'     => 'text',
                    'text'     => 'In your own words, what one change would most improve employment or business conditions in Zambia?',
                    'required' => false,
                    'logic'    => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'ent_enrich1',
                    'text'    => 'What sector do you work in?',
                    'type'    => 'mcq',
                    'options' => ['Agriculture', 'Mining', 'Construction', 'Retail / Trade', 'Technology', 'Healthcare', 'Education', 'Finance', 'Other'],
                ],
            ],
        ]);

        // ── 2. AIRTIME REWARD ─────────────────────────────────────────────────
        // Every respondent wins — slot machine claim, branching on transport type
        Survey::create([
            'user_id'        => $admin->id,
            'title'          => 'Road Safety & Urban Transport Habits',
            'description'    => 'A 5-minute study on how Zambians get around. Complete it and spin the slot machine to claim your K10 free airtime!',
            'reward_type'    => 'airtime',
            'reward_amount'  => 10.00,
            'reward_points'  => 0,
            'status'         => 'active',
            'is_active'      => true,
            'response_cap'   => 500,
            'estimated_time' => 5,
            'questions' => [
                [
                    'id'       => 'trn_q1',
                    'type'     => 'mcq',
                    'text'     => 'What is your primary mode of transport in the city?',
                    'options'  => ['Personal Car', 'Minibus (Kombis)', 'Motorcycle / Taxi-Moto', 'Walking', 'Bicycle', 'Ride-hailing (e.g. InDrive, Bolt)'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'trn_q2_car',
                    'type'     => 'mcq',
                    'text'     => 'How often do you experience road rage or aggressive driving from other motorists?',
                    'options'  => ['Daily', 'A Few Times a Week', 'Rarely', 'Never'],
                    'required' => true,
                    'logic'    => [],
                    'visibility' => [
                        'logic'      => 'and',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'trn_q1', 'operator' => 'eq', 'value' => 'Personal Car'],
                        ],
                    ],
                ],
                [
                    'id'       => 'trn_q2_pub',
                    'type'     => 'scale',
                    'text'     => 'Rate the safety of public minibuses in your city. (1 = Very Unsafe, 5 = Very Safe)',
                    'required' => true,
                    'logic'    => [],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'trn_q1', 'operator' => 'eq', 'value' => 'Minibus (Kombis)'],
                            ['type' => 'answer', 'key' => 'trn_q1', 'operator' => 'eq', 'value' => 'Motorcycle / Taxi-Moto'],
                        ],
                    ],
                ],
                [
                    'id'       => 'trn_q3',
                    'type'     => 'checkbox',
                    'text'     => 'Which road safety issues concern you most? (Select all that apply)',
                    'options'  => ['Speeding', 'Drunk Driving', 'Poor Road Conditions', 'Lack of Streetlights', 'No Pedestrian Crossings', 'Overloaded Vehicles'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'trn_q4',
                    'type'     => 'scale',
                    'text'     => 'Overall, how safe do you feel commuting in your city? (1 = Very Unsafe, 5 = Very Safe)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'trn_q5',
                    'type'     => 'mcq',
                    'text'     => 'Would you use a dedicated bus rapid transit (BRT) system if it were available in your city?',
                    'options'  => ['Yes, definitely', 'Probably yes', 'Probably not', 'No'],
                    'required' => true,
                    'logic'    => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'trn_enrich1',
                    'text'    => 'Do you own a personal vehicle?',
                    'type'    => 'mcq',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);

        // ── 3. POINTS – FEMALE-TARGETED FMCG BRAND STUDY ─────────────────────
        // Demographic targeting on gender + age, checkbox + text questions
        Survey::create([
            'user_id'        => $admin->id,
            'title'          => 'Women\'s Beauty & Personal Care Brand Study',
            'description'    => 'Share your honest opinions on beauty and personal care brands used in Zambia. Earn 200 points for your time.',
            'reward_type'    => 'points',
            'reward_amount'  => 20.00,
            'reward_points'  => 200,
            'status'         => 'active',
            'is_active'      => true,
            'response_cap'   => 300,
            'estimated_time' => 7,
            'target_gender'  => 'Female',
            'target_age_band'=> '18-44',
            'questions' => [
                [
                    'id'       => 'bty_q1',
                    'type'     => 'checkbox',
                    'text'     => 'Which of the following beauty categories do you regularly spend money on? (Select all that apply)',
                    'options'  => ['Skincare', 'Hair Care', 'Makeup / Cosmetics', 'Fragrances / Perfume', 'Nail Care', 'Body Lotion / Oils'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q2',
                    'type'     => 'mcq',
                    'text'     => 'Where do you most often buy your beauty products?',
                    'options'  => ['Supermarket / Pharmacy', 'Dedicated Beauty Store', 'Online (Social Media / WhatsApp)', 'Market / Street Vendor', 'Salon / Spa'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q3',
                    'type'     => 'mcq',
                    'text'     => 'How important is it to you that a brand uses natural or organic ingredients?',
                    'options'  => ['Essential — I only buy natural products', 'Important — I prefer it', 'Neutral', 'Not important'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q4',
                    'type'     => 'scale',
                    'text'     => 'How much does social media influence your beauty product choices? (1 = No influence, 5 = Very strong influence)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q5',
                    'type'     => 'mcq',
                    'text'     => 'How much do you typically spend per month on beauty and personal care products?',
                    'options'  => ['Under K100', 'K100–K300', 'K300–K600', 'K600–K1000', 'Over K1000'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q6',
                    'type'     => 'scale',
                    'text'     => 'How satisfied are you with the availability of quality beauty products in Zambia? (1 = Not satisfied, 5 = Very satisfied)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'bty_q7',
                    'type'     => 'text',
                    'text'     => 'Name one beauty brand you wish was more easily available in Zambia and why.',
                    'required' => false,
                    'logic'    => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'bty_enrich1',
                    'text'    => 'Do you watch beauty or lifestyle content creators on social media?',
                    'type'    => 'mcq',
                    'options' => ['Yes, daily', 'Occasionally', 'Rarely', 'No'],
                ],
            ],
        ]);

        // ── 4. POINTS – HIGH-CAP QUICK VIRAL SURVEY ──────────────────────────
        // No targeting, very fast, intended for maximum reach + streak building
        Survey::create([
            'user_id'        => $admin->id,
            'title'          => 'Load Shedding Impact: Quick 3-Min Poll',
            'description'    => 'How is load shedding really affecting your daily life? 3 minutes, 50 points, no targeting — everyone is welcome.',
            'reward_type'    => 'points',
            'reward_amount'  => 5.00,
            'reward_points'  => 50,
            'status'         => 'active',
            'is_active'      => true,
            'response_cap'   => 2000,
            'estimated_time' => 3,
            'questions' => [
                [
                    'id'       => 'ls_q1',
                    'type'     => 'mcq',
                    'text'     => 'On average, how many hours of load shedding do you experience per day?',
                    'options'  => ['0–2 hours', '2–4 hours', '4–8 hours', '8+ hours', 'I have a generator / solar and rarely notice'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ls_q2',
                    'type'     => 'checkbox',
                    'text'     => 'Which areas of your life are most impacted by load shedding? (Select all that apply)',
                    'options'  => ['Work / Productivity', 'School / Studies', 'Food Spoilage', 'Security (no lights)', 'Business / Income Loss', 'Mental / Emotional Wellbeing'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ls_q3',
                    'type'     => 'scale',
                    'text'     => 'How would you rate the government\'s handling of the energy crisis? (1 = Very Poor, 5 = Excellent)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'ls_q4',
                    'type'     => 'mcq',
                    'text'     => 'Have you or anyone in your household invested in an alternative power source because of load shedding?',
                    'options'  => ['Yes, solar panels', 'Yes, a generator', 'Yes, inverter / battery backup', 'No, cannot afford it', 'No, load shedding is manageable for us'],
                    'required' => true,
                    'logic'    => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'ls_enrich1',
                    'text'    => 'Do you work from home?',
                    'type'    => 'mcq',
                    'options' => ['Yes, full time', 'Sometimes', 'No'],
                ],
            ],
        ]);

        // ── 5. PRIZE DRAW – HIGH-VALUE YOUTH SURVEY ──────────────────────────
        // Young demographic, trait targeting, full question variety, K1000 prize
        Survey::create([
            'user_id'        => $admin->id,
            'title'          => 'Youth Aspirations & Future of Work Study',
            'description'    => 'Calling all young Zambians! Tell us about your career dreams and digital skills. One winner takes home K1,000 — everyone walks away with 300 points just for entering.',
            'reward_type'    => 'prize_draw',
            'reward_amount'  => 1000.00,
            'reward_points'  => 300,
            'prize_name'     => 'K1,000 Grand Prize',
            'status'         => 'active',
            'is_active'      => true,
            'response_cap'   => 200,
            'estimated_time' => 10,
            'target_age_band'=> '18-30',
            'target_traits'  => [
                ['key' => 'education', 'value' => 'University', 'operator' => 'eq'],
            ],
            'questions' => [
                [
                    'id'       => 'yth_q1',
                    'type'     => 'mcq',
                    'text'     => 'What is your highest level of education completed or currently pursuing?',
                    'options'  => ['Secondary School', 'Certificate / Diploma', 'Bachelor\'s Degree', 'Master\'s or Higher', 'None of the above'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q2',
                    'type'     => 'mcq',
                    'text'     => 'Where do you picture yourself working in 5 years?',
                    'options'  => ['Large Corporate / Multinational', 'Government / Public Sector', 'My Own Business', 'NGO / Charity Sector', 'Freelancing / Remote Work', 'I\'m not sure yet'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q3',
                    'type'     => 'checkbox',
                    'text'     => 'Which digital skills have you taught yourself or studied? (Select all that apply)',
                    'options'  => ['Coding / Programming', 'Graphic Design', 'Digital Marketing', 'Data Analysis', 'Video Editing', 'E-commerce / Online Sales', 'None'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q4',
                    'type'     => 'mcq',
                    'text'     => 'Do you currently earn any income online (e.g. freelancing, selling, content creation)?',
                    'options'  => ['Yes, it\'s my main income', 'Yes, a side income', 'No, but I\'m trying', 'No'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q5',
                    'type'     => 'mcq',
                    'text'     => 'What is the biggest obstacle to your career progress right now?',
                    'options'  => ['Lack of Experience / Portfolio', 'No Connections / Network', 'Financial Constraints', 'Poor Internet / Infrastructure', 'No Jobs in My Field', 'Discrimination'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q6',
                    'type'     => 'scale',
                    'text'     => 'How confident are you in your ability to achieve your career goals? (1 = Not at all, 5 = Extremely confident)',
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q7',
                    'type'     => 'mcq',
                    'text'     => 'Would you relocate to another country for better career opportunities?',
                    'options'  => ['Yes, I\'m actively planning to', 'Yes, if the right opportunity came up', 'Maybe', 'No, I want to build a future in Zambia'],
                    'required' => true,
                    'logic'    => [],
                ],
                [
                    'id'       => 'yth_q8',
                    'type'     => 'text',
                    'text'     => 'If you had K10,000 to invest in yourself right now, what would you spend it on?',
                    'required' => false,
                    'logic'    => [],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'yth_enrich1',
                    'text'    => 'Are you currently enrolled in any form of education or training?',
                    'type'    => 'mcq',
                    'options' => ['Yes, full time', 'Yes, part time', 'No'],
                ],
            ],
        ]);
    }
}
