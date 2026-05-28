<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShowcaseSurveySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();
        if (! $admin) {
            return;
        }

        // ─────────────────────────────────────────────────────────────────────
        // SURVEY 1 — Food & Beverage Brand Study
        //   Reward:     Multi-prize draw (physical + points + airtime prizes)
        //   Questions:  All 5 types incl. image_mcq, skip logic, visibility
        //   Targeting:  Open to all (no demographic restriction)
        //   Enrichment: 3 demographic questions stored for future targeting
        // ─────────────────────────────────────────────────────────────────────
        Survey::create([
            'user_id'       => $admin->id,
            'title'         => 'Zambia Food & Beverage Habits 2026',
            'description'   => 'Help top FMCG brands understand what Zambians eat and drink daily. Complete this survey to enter our Mega Prize Draw — win a K500 grocery voucher, K200 airtime, or bonus points!',
            'reward_type'   => 'prize_draw',
            'reward_points' => 0,
            'reward_amount' => 0,
            'prize_name'    => 'K500 Pick n Pay Grocery Voucher',
            'prizes'        => [
                [
                    'name'   => 'K500 Pick n Pay Grocery Voucher',
                    'amount' => 500.00,
                    'points' => null,
                ],
                [
                    'name'   => 'K200 Airtel Airtime',
                    'amount' => 200.00,
                    'points' => null,
                ],
                [
                    'name'   => 'K100 Hungry Lion Voucher',
                    'amount' => 100.00,
                    'points' => null,
                ],
                [
                    'name'   => '1,000 Bonus Points',
                    'amount' => null,
                    'points' => 1000,
                ],
                [
                    'name'   => '500 Bonus Points',
                    'amount' => null,
                    'points' => 500,
                ],
            ],
            'estimated_time'   => 6,
            'status'           => 'active',
            'is_active'        => true,
            'draw_phase_active' => true,
            'response_cap'     => 500,
            // No demographic restrictions — open to everyone
            'target_gender'       => null,
            'target_age_band'     => null,
            'target_location'     => null,
            'target_employment'   => null,
            'target_income_band'  => null,
            'target_traits'       => [],
            'exclude_traits'      => [],
            'questions' => [
                // Q1 — Image choice: favourite beverage category
                [
                    'id'       => 'fb_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which type of drink do you reach for most during the day?',
                    'required' => true,
                    'options'  => [
                        [
                            'label'     => 'Coffee or Tea',
                            'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Fruit Juice',
                            'image_url' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Fizzy / Soda',
                            'image_url' => 'https://images.unsplash.com/photo-1581006852262-e4307cf6283a?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Water',
                            'image_url' => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400&h=280&fit=crop&auto=format',
                        ],
                    ],
                ],

                // Q2 — MCQ with skip logic: coffee/tea buyers get extra question
                [
                    'id'       => 'fb_q2',
                    'type'     => 'mcq',
                    'text'     => 'How often do you buy groceries from a physical store?',
                    'required' => true,
                    'options'  => ['Every day', '3–5 times a week', 'Once a week', 'Less than once a week'],
                    'logic'    => [
                        'Every day' => 'fb_q4', // Heavy shoppers skip the spending question
                    ],
                ],

                // Q3 — Scale (only shown to non-daily shoppers via logic flow)
                [
                    'id'       => 'fb_q3',
                    'type'     => 'scale',
                    'text'     => 'On a typical shopping trip, how much do you spend on food and drinks? (1 = Under K50, 5 = Over K500)',
                    'hint'     => '1 = Under K50   |   3 = K100–K250   |   5 = Over K500',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],

                // Q4 — Checkbox: factors driving grocery choice
                [
                    'id'       => 'fb_q4',
                    'type'     => 'checkbox',
                    'text'     => 'What factors most influence which food or drink brand you buy? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Price / Value for money',
                        'Brand I grew up with',
                        'Recommended by friends or family',
                        'TV / Radio advertising',
                        'Social media promotion',
                        'Nutritional content',
                        'Local / Zambian brand',
                    ],
                ],

                // Q5 — Visibility: only shown if user selected "Social media promotion" in Q4
                [
                    'id'       => 'fb_q5',
                    'type'     => 'mcq',
                    'text'     => 'Which social media platform has the biggest influence on your buying decisions?',
                    'required' => false,
                    'options'  => ['Facebook', 'TikTok', 'Instagram', 'WhatsApp', 'YouTube'],
                    'visibility' => [
                        'logic'      => 'and',
                        'conditions' => [
                            [
                                'type'     => 'answer',
                                'key'      => 'fb_q4',
                                'operator' => 'eq',
                                'value'    => 'Social media promotion',
                            ],
                        ],
                    ],
                ],

                // Q6 — Text: open feedback
                [
                    'id'       => 'fb_q6',
                    'type'     => 'text',
                    'text'     => 'Name one local Zambian food or drink brand you are proud of and why.',
                    'required' => false,
                ],

                // Q7 — Scale: overall satisfaction with food variety in their area
                [
                    'id'       => 'fb_q7',
                    'type'     => 'scale',
                    'text'     => 'How satisfied are you with the variety of food and drink options available where you live?',
                    'hint'     => '1 = Very poor variety   |   5 = Excellent variety',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_diet',
                    'type'    => 'mcq',
                    'text'    => 'Which best describes your diet?',
                    'options' => ['Omnivore (eat everything)', 'Vegetarian', 'Pescatarian', 'Vegan', 'Other'],
                ],
                [
                    'id'      => 'enrich_household',
                    'type'    => 'mcq',
                    'text'    => 'How many people do you regularly cook or buy food for?',
                    'options' => ['Just myself', '2–3 people', '4–6 people', '7 or more'],
                ],
                [
                    'id'      => 'enrich_income_food',
                    'type'    => 'mcq',
                    'text'    => 'What percentage of your monthly income goes to food and groceries?',
                    'options' => ['Less than 20%', '20–35%', '36–50%', 'More than 50%'],
                ],
            ],
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // SURVEY 2 — Mobile Network & Data Usage Study
        //   Reward:     K150 Airtime (direct top-up on completion)
        //   Questions:  image_mcq for network brand, checkboxes, scale, text
        //   Targeting:  Ages 18–35, all genders, Lusaka + Copperbelt
        //   Enrichment: 2 questions; trait exclusion for already-researched users
        // ─────────────────────────────────────────────────────────────────────
        Survey::create([
            'user_id'       => $admin->id,
            'title'         => 'Mobile Data & Network Experience Survey',
            'description'   => 'Help the top mobile networks improve their service in Zambia. Answer 7 quick questions and receive K150 airtime credited directly to your registered phone number within 24 hours.',
            'reward_type'   => 'airtime',
            'reward_amount' => 150.00,
            'reward_points' => 0,
            'prize_name'    => 'K150 Airtime',
            'prizes'        => [
                [
                    'name'   => 'K150 Airtime Top-Up',
                    'amount' => 150.00,
                    'points' => null,
                ],
            ],
            'estimated_time'   => 5,
            'status'           => 'active',
            'is_active'        => true,
            'draw_phase_active' => true,
            'response_cap'     => 300,
            'target_gender'       => null,
            'target_age_band'     => '18-25,26-35',
            'target_location'     => 'Lusaka,Ndola,Kitwe',
            'target_employment'   => null,
            'target_income_band'  => null,
            'target_traits'       => [],
            // Exclude users who have already been in a telecom survey
            'exclude_traits' => [
                [
                    'key'      => 'completed_telecom_survey',
                    'operator' => 'eq',
                    'value'    => 'Yes',
                ],
            ],
            'questions' => [
                // Q1 — Image choice: primary network logo recognition
                [
                    'id'       => 'net_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which mobile network is your main SIM card?',
                    'required' => true,
                    'options'  => [
                        [
                            'label'     => 'Airtel Zambia',
                            'image_url' => 'https://images.unsplash.com/photo-1529612700005-e35377bf1415?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'MTN Zambia',
                            'image_url' => 'https://images.unsplash.com/photo-1616628188859-7a11abb6fcc9?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Zamtel',
                            'image_url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'I use multiple networks',
                            'image_url' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=280&fit=crop&auto=format',
                        ],
                    ],
                    'logic' => [
                        'I use multiple networks' => 'net_q3', // Skip single-network question
                    ],
                ],

                // Q2 — MCQ: how long on current network
                [
                    'id'       => 'net_q2',
                    'type'     => 'mcq',
                    'text'     => 'How long have you been with your main network?',
                    'required' => true,
                    'options'  => ['Less than 1 year', '1–2 years', '3–5 years', 'More than 5 years'],
                ],

                // Q3 — Checkbox: daily data activities
                [
                    'id'       => 'net_q3',
                    'type'     => 'checkbox',
                    'text'     => 'What do you use mobile data for most? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Social media (Facebook, TikTok, Instagram)',
                        'WhatsApp & messaging',
                        'Streaming video (YouTube, Netflix)',
                        'Online banking & mobile money',
                        'Work email & documents',
                        'Online learning & education',
                        'Gaming',
                    ],
                ],

                // Q4 — Scale: network reliability rating
                [
                    'id'       => 'net_q4',
                    'type'     => 'scale',
                    'text'     => 'How would you rate your network\'s signal reliability in your area?',
                    'hint'     => '1 = Constantly drops   |   5 = Always strong and stable',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],

                // Q5 — Scale: value for money on data bundles
                [
                    'id'       => 'net_q5',
                    'type'     => 'scale',
                    'text'     => 'How would you rate the value for money of your network\'s data bundles?',
                    'hint'     => '1 = Very poor value   |   5 = Excellent value',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],

                // Q6 — MCQ with skip logic: switchers get an extra question
                [
                    'id'       => 'net_q6',
                    'type'     => 'mcq',
                    'text'     => 'Have you switched or considered switching your main network in the last year?',
                    'required' => true,
                    'options'  => ['Yes, I switched', 'Considered but stayed', 'No, very happy with mine'],
                    'logic'    => [
                        'No, very happy with mine' => 'net_q_end',
                    ],
                ],

                // Q7 — Text: reason for switching (visible only to those who switched/considered)
                [
                    'id'       => 'net_q7',
                    'type'     => 'text',
                    'text'     => 'What was the main reason you switched or considered switching networks?',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            [
                                'type'     => 'answer',
                                'key'      => 'net_q6',
                                'operator' => 'eq',
                                'value'    => 'Yes, I switched',
                            ],
                            [
                                'type'     => 'answer',
                                'key'      => 'net_q6',
                                'operator' => 'eq',
                                'value'    => 'Considered but stayed',
                            ],
                        ],
                    ],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_data_spend',
                    'type'    => 'mcq',
                    'text'    => 'How much do you spend on mobile data per month?',
                    'options' => ['Under K50', 'K50–K100', 'K101–K200', 'Over K200'],
                ],
                [
                    'id'      => 'completed_telecom_survey',
                    'type'    => 'mcq',
                    'text'    => 'Have you taken part in a mobile network survey in the past 6 months?',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // SURVEY 3 — Zambia Lifestyle & Spending Habits 2026
        //   Reward:     Multi-prize draw (smartphone, tablet, cash + airtime prizes)
        //   Questions:  All 5 types, 8 questions, deep visibility logic
        //   Targeting:  Employed adults (26–50), any location, mid income band
        //   Enrichment: 3 questions; trait-based targeting for follow-up surveys
        // ─────────────────────────────────────────────────────────────────────
        Survey::create([
            'user_id'       => $admin->id,
            'title'         => 'Zambia Lifestyle & Spending Habits 2026',
            'description'   => 'A comprehensive study for leading consumer brands to understand how working Zambians spend, save, and live. Complete all 8 questions to enter our Grand Prize Draw — prizes include a Samsung Galaxy S25, a tablet, K500 cash voucher, K300 airtime, and more!',
            'reward_type'   => 'prize_draw',
            'reward_points' => 0,
            'reward_amount' => 0,
            'prize_name'    => 'Samsung Galaxy S25 (256GB)',
            'prizes'        => [
                [
                    'name'   => 'Samsung Galaxy S25 (256GB)',
                    'amount' => null,
                    'points' => null,
                ],
                [
                    'name'   => 'Lenovo Tab M11 Tablet',
                    'amount' => null,
                    'points' => null,
                ],
                [
                    'name'   => 'K500 Cash Voucher (Pick n Pay)',
                    'amount' => 500.00,
                    'points' => null,
                ],
                [
                    'name'   => 'K300 MTN Airtime',
                    'amount' => 300.00,
                    'points' => null,
                ],
                [
                    'name'   => 'K200 Airtel Airtime',
                    'amount' => 200.00,
                    'points' => null,
                ],
                [
                    'name'   => '2,000 Bonus Points',
                    'amount' => null,
                    'points' => 2000,
                ],
                [
                    'name'   => '1,000 Bonus Points',
                    'amount' => null,
                    'points' => 1000,
                ],
            ],
            'estimated_time'   => 8,
            'status'           => 'active',
            'is_active'        => true,
            'draw_phase_active' => true,
            'response_cap'     => 250,
            'target_gender'       => null,
            'target_age_band'     => '26-35,36-50',
            'target_location'     => null,
            'target_employment'   => 'Employed full-time,Self-employed',
            'target_income_band'  => '5000 - 9999,10000 - 19999',
            'target_traits'       => [
                // Only target users who have answered the household size enrichment question
                [
                    'key'      => 'enrich_household',
                    'operator' => 'neq',
                    'value'    => '', // Ensures they've provided an answer (not blank)
                ],
            ],
            'exclude_traits' => [],
            'questions' => [
                // Q1 — Image choice: primary spending priority
                [
                    'id'       => 'ls_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Outside of essentials (food, rent, transport), which area gets the biggest share of your monthly spending?',
                    'required' => true,
                    'options'  => [
                        [
                            'label'     => 'Entertainment & Going Out',
                            'image_url' => 'https://images.unsplash.com/photo-1574169208507-84376144848b?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Fitness & Health',
                            'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Fashion & Personal Care',
                            'image_url' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=400&h=280&fit=crop&auto=format',
                        ],
                        [
                            'label'     => 'Family & Education',
                            'image_url' => 'https://images.unsplash.com/photo-1491841550275-ad7854e35ca6?w=400&h=280&fit=crop&auto=format',
                        ],
                    ],
                ],

                // Q2 — MCQ: savings behaviour
                [
                    'id'       => 'ls_q2',
                    'type'     => 'mcq',
                    'text'     => 'Which best describes your saving habit?',
                    'required' => true,
                    'options'  => [
                        'I save a fixed amount every month',
                        'I save whatever is left over',
                        'I try to save but rarely manage',
                        'I am currently not able to save',
                    ],
                    'logic' => [
                        'I save a fixed amount every month' => 'ls_q4', // Disciplined savers skip the barrier question
                    ],
                ],

                // Q3 — MCQ: biggest barrier to saving (shown only to non-disciplined savers)
                [
                    'id'       => 'ls_q3',
                    'type'     => 'mcq',
                    'text'     => 'What is the biggest barrier to saving more money?',
                    'required' => false,
                    'options'  => [
                        'Income not enough after expenses',
                        'Unexpected expenses always come up',
                        'No savings goal or plan',
                        'Supporting extended family',
                        'Too many loan/debt repayments',
                    ],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            [
                                'type'     => 'answer',
                                'key'      => 'ls_q2',
                                'operator' => 'eq',
                                'value'    => 'I save whatever is left over',
                            ],
                            [
                                'type'     => 'answer',
                                'key'      => 'ls_q2',
                                'operator' => 'eq',
                                'value'    => 'I try to save but rarely manage',
                            ],
                            [
                                'type'     => 'answer',
                                'key'      => 'ls_q2',
                                'operator' => 'eq',
                                'value'    => 'I am currently not able to save',
                            ],
                        ],
                    ],
                ],

                // Q4 — Checkbox: where they shop
                [
                    'id'       => 'ls_q4',
                    'type'     => 'checkbox',
                    'text'     => 'Where do you most often shop for clothing and personal care items? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Shoprite / Pick n Pay',
                        'Levy Junction / Manda Hill mall',
                        'Local market (e.g. Soweto Market)',
                        'Online (e.g. Jumia, Facebook Marketplace)',
                        'Brand stores (e.g. Edgars, Mr Price)',
                        'Second-hand / thrift shops',
                    ],
                ],

                // Q5 — Scale: financial stress level
                [
                    'id'       => 'ls_q5',
                    'type'     => 'scale',
                    'text'     => 'How would you rate your current financial stress level?',
                    'hint'     => '1 = No stress at all   |   5 = Extremely stressed',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],

                // Q6 — MCQ: credit / buy-now-pay-later usage
                [
                    'id'       => 'ls_q6',
                    'type'     => 'mcq',
                    'text'     => 'Do you use any buy-now-pay-later or store credit options?',
                    'required' => true,
                    'options'  => [
                        'Yes, regularly',
                        'Occasionally for big purchases',
                        'I have in the past but no longer',
                        'No, I avoid credit',
                    ],
                ],

                // Q7 — Text: conditional — only for credit users
                [
                    'id'       => 'ls_q7',
                    'type'     => 'text',
                    'text'     => 'Which buy-now-pay-later or credit service do you use, and what do you mainly use it for?',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            [
                                'type'     => 'answer',
                                'key'      => 'ls_q6',
                                'operator' => 'eq',
                                'value'    => 'Yes, regularly',
                            ],
                            [
                                'type'     => 'answer',
                                'key'      => 'ls_q6',
                                'operator' => 'eq',
                                'value'    => 'Occasionally for big purchases',
                            ],
                        ],
                    ],
                ],

                // Q8 — Scale: optimism about personal finances in next 12 months
                [
                    'id'       => 'ls_q8',
                    'type'     => 'scale',
                    'text'     => 'How optimistic are you about your financial situation over the next 12 months?',
                    'hint'     => '1 = Very pessimistic   |   5 = Very optimistic',
                    'required' => true,
                    'min'      => 1,
                    'max'      => 5,
                    'step'     => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_employment_type',
                    'type'    => 'mcq',
                    'text'    => 'What is your current employment status?',
                    'options' => ['Employed full-time', 'Employed part-time', 'Self-employed', 'Freelance / Gig work', 'Student', 'Unemployed'],
                ],
                [
                    'id'      => 'enrich_owns_vehicle',
                    'type'    => 'mcq',
                    'text'    => 'Do you own or regularly use a personal vehicle?',
                    'options' => ['Yes, I own one', 'I share a family vehicle', 'No, I use public transport'],
                ],
                [
                    'id'      => 'enrich_has_internet_home',
                    'type'    => 'mcq',
                    'text'    => 'Do you have home Wi-Fi or fibre internet?',
                    'options' => ['Yes', 'No, I rely on mobile data only'],
                ],
            ],
        ]);
    }
}
