<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Client::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $adminId = DB::table('users')->where('is_admin', true)->value('id') ?? 1;

        // =====================================================================
        // CLIENT 1: ZamMart Retail Group
        // Four surveys — prize_draw / points / airtime / draft
        // =====================================================================
        $zammart = Client::create([
            'name'        => 'ZamMart Retail Group',
            'email'       => 'research@zammart.co.zm',
            'description' => "Zambia's leading supermarket chain operating 42 stores across all 10 provinces. Research focuses on consumer shopping behaviour, brand loyalty, and in-store experience.",
            'is_active'   => true,
        ]);

        // ── Survey 1.1: Grocery Shopping Habits — prize_draw, open, all question types ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $zammart->id,
            'title'             => 'ZamMart Grocery Shopping Habits 2026',
            'description'       => 'Help ZamMart understand how Zambians shop for groceries. Share your habits and preferences — complete all questions to enter our K1,000 Grand Prize Draw!',
            'reward_type'       => 'prize_draw',
            'reward_amount'     => 1000,
            'reward_points'     => 0,
            'prize_name'        => 'K1,000 ZamMart Voucher',
            'prizes'            => [
                ['name' => 'Grand Prize — K1,000 ZamMart Voucher', 'amount' => 1000,  'points' => null],
                ['name' => 'Runner-Up — K500 ZamMart Voucher',     'amount' => 500,   'points' => null],
                ['name' => 'Third Place — K200 ZamMart Voucher',   'amount' => 200,   'points' => null],
                ['name' => '500 Bonus Points',                     'amount' => null,  'points' => 500],
            ],
            'estimated_time'    => 7,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => true,
            'response_cap'      => 500,
            'target_gender'     => null,
            'target_age_band'   => null,
            'target_location'   => null,
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                // image_mcq — favourite store type
                [
                    'id'       => 'gsh_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which type of grocery store do you shop at most often?',
                    'required' => true,
                    'options'  => [
                        ['label' => 'Large Supermarket',  'image_url' => 'https://images.unsplash.com/photo-1534723452862-4c874018d66d?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Local Market / Vendor', 'image_url' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Convenience Store', 'image_url' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Online Delivery',   'image_url' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=400&h=280&fit=crop&auto=format'],
                    ],
                    'logic' => [
                        'Online Delivery' => 'gsh_q3', // online shoppers skip the in-store frequency question
                    ],
                ],
                // mcq with skip logic
                [
                    'id'       => 'gsh_q2',
                    'type'     => 'mcq',
                    'text'     => 'How many times per week do you visit a physical grocery store?',
                    'required' => true,
                    'options'  => ['Every day', '4–5 times', '2–3 times', 'Once a week', 'Less than once a week'],
                    'logic'    => ['Every day' => 'gsh_q4'],
                ],
                // mcq — spend per trip
                [
                    'id'       => 'gsh_q3',
                    'type'     => 'mcq',
                    'text'     => 'What is your typical grocery spend per shopping trip?',
                    'required' => true,
                    'options'  => ['Under K100', 'K100–K300', 'K301–K500', 'K501–K1,000', 'Over K1,000'],
                ],
                // checkbox — decision factors
                [
                    'id'       => 'gsh_q4',
                    'type'     => 'checkbox',
                    'text'     => 'What factors most influence where you choose to shop? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Lowest prices',
                        'Proximity to home or work',
                        'Product variety and quality',
                        'Loyalty rewards programme',
                        'Clean and spacious store',
                        'Trusted and familiar brand',
                        'Accepts mobile money',
                    ],
                ],
                // scale — value for money
                [
                    'id'       => 'gsh_q5',
                    'type'     => 'scale',
                    'text'     => 'How would you rate the overall value for money at ZamMart?',
                    'hint'     => '1 = Very poor value  |  5 = Excellent value',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // text — improvement suggestion (shown only to low raters)
                [
                    'id'       => 'gsh_q6',
                    'type'     => 'text',
                    'text'     => 'What would most improve your shopping experience at ZamMart?',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'gsh_q5', 'operator' => 'eq', 'value' => '1'],
                            ['type' => 'answer', 'key' => 'gsh_q5', 'operator' => 'eq', 'value' => '2'],
                        ],
                    ],
                ],
                // scale — NPS
                [
                    'id'       => 'gsh_q7',
                    'type'     => 'scale',
                    'text'     => 'How likely are you to recommend ZamMart to a friend or family member?',
                    'hint'     => '1 = Never would  |  5 = Absolutely would',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_household_size',
                    'type'    => 'mcq',
                    'text'    => 'How many people are in your household?',
                    'options' => ['1 person', '2–3 people', '4–6 people', '7 or more'],
                ],
                [
                    'id'      => 'enrich_primary_shopper',
                    'type'    => 'mcq',
                    'text'    => 'Are you the primary grocery shopper in your household?',
                    'options' => ['Yes, always', 'Mostly me', 'Shared with someone else', 'Rarely me'],
                ],
            ],
        ]);

        // ── Survey 1.2: Private Label vs. Name Brand — points, female targeting ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $zammart->id,
            'title'             => 'Private Label vs. Name Brand Preferences',
            'description'       => 'ZamMart wants to understand how shoppers choose between store-brand and name-brand products. Complete this 5-minute survey and earn 300 points instantly.',
            'reward_type'       => 'points',
            'reward_points'     => 300,
            'reward_amount'     => 0,
            'prize_name'        => null,
            'prizes'            => null,
            'estimated_time'    => 5,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 200,
            'target_gender'     => 'Female',
            'target_age_band'   => '18-25,26-35,36-50',
            'target_location'   => null,
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                [
                    'id'       => 'plb_q1',
                    'type'     => 'mcq',
                    'text'     => 'When choosing between a store-brand and a name-brand product at equal quality, what do you typically do?',
                    'required' => true,
                    'options'  => [
                        'Always choose the store brand (cheaper)',
                        'Usually choose store brand',
                        'Choose based on the specific category',
                        'Usually choose name brand',
                        'Always choose the name brand (more trust)',
                    ],
                ],
                [
                    'id'       => 'plb_q2',
                    'type'     => 'checkbox',
                    'text'     => 'For which categories do you prefer name brands over store brands? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Baby and infant products',
                        'Cooking oil and fats',
                        'Cereals and breakfast foods',
                        'Personal care (soap, shampoo)',
                        'Cleaning products',
                        'Dairy products',
                        'I prefer store brands for everything',
                    ],
                ],
                [
                    'id'       => 'plb_q3',
                    'type'     => 'scale',
                    'text'     => "How much do you trust ZamMart's own-brand products compared to national brands?",
                    'hint'     => '1 = Much less trust  |  5 = Equal or more trust',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                [
                    'id'       => 'plb_q4',
                    'type'     => 'mcq',
                    'text'     => "Have you tried ZamMart's own-brand products in the past 3 months?",
                    'required' => true,
                    'options'  => ['Yes, regularly', 'Yes, once or twice', 'No, but I am open to trying', 'No, I only buy name brands'],
                    'logic'    => ['No, I only buy name brands' => 'plb_q5'],
                ],
                // satisfaction scale — shown only to those who have tried own-brand
                [
                    'id'       => 'plb_q4b',
                    'type'     => 'scale',
                    'text'     => 'How satisfied were you with ZamMart own-brand products you have tried?',
                    'hint'     => '1 = Very disappointed  |  5 = Very satisfied',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'plb_q4', 'operator' => 'eq', 'value' => 'Yes, regularly'],
                            ['type' => 'answer', 'key' => 'plb_q4', 'operator' => 'eq', 'value' => 'Yes, once or twice'],
                        ],
                    ],
                ],
                [
                    'id'       => 'plb_q5',
                    'type'     => 'text',
                    'text'     => 'Is there a specific product you would love ZamMart to launch under its own brand?',
                    'required' => false,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_monthly_grocery_spend',
                    'type'    => 'mcq',
                    'text'    => 'What is your approximate monthly household grocery spend?',
                    'options' => ['Under K500', 'K500–K1,000', 'K1,001–K2,000', 'Over K2,000'],
                ],
            ],
        ]);

        // ── Survey 1.3: Loyalty Programme Feedback — airtime, employed/mid-income ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $zammart->id,
            'title'             => 'ZamMart Loyalty Rewards Programme Feedback',
            'description'       => "Are you a ZamMart Loyalty Card holder? Share your experience with the rewards programme and earn K75 airtime sent directly to your phone.",
            'reward_type'       => 'airtime',
            'reward_amount'     => 75,
            'reward_points'     => 0,
            'prize_name'        => 'K75 Airtime',
            'prizes'            => [['name' => 'K75 Airtime Top-Up', 'amount' => 75, 'points' => null]],
            'estimated_time'    => 4,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 300,
            'target_gender'     => null,
            'target_age_band'   => '26-35,36-50',
            'target_location'   => null,
            'target_employment' => 'Employed full-time,Self-employed',
            'target_income_band' => '5000 - 9999,10000 - 19999',
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                [
                    'id'       => 'loy_q1',
                    'type'     => 'mcq',
                    'text'     => 'Do you currently hold a ZamMart Loyalty Card?',
                    'required' => true,
                    'options'  => ['Yes, I use it every time', 'Yes, but rarely use it', 'No, but I would like one', 'No, I am not interested'],
                    'logic'    => [
                        'No, but I would like one' => 'loy_q5',
                        'No, I am not interested'  => 'loy_q5',
                    ],
                ],
                // scale — satisfaction (card holders only)
                [
                    'id'       => 'loy_q2',
                    'type'     => 'scale',
                    'text'     => 'How satisfied are you with the rewards you earn on the ZamMart Loyalty Card?',
                    'hint'     => '1 = Very unsatisfied  |  5 = Very satisfied',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, I use it every time'],
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, but rarely use it'],
                        ],
                    ],
                ],
                // checkbox — most valued rewards (card holders only)
                [
                    'id'       => 'loy_q3',
                    'type'     => 'checkbox',
                    'text'     => 'Which loyalty rewards do you find most valuable? (Select all that apply)',
                    'required' => false,
                    'options'  => [
                        'Cashback on purchases',
                        'Points redeemable for products',
                        'Exclusive member discounts',
                        'Birthday rewards',
                        'Early access to promotions',
                        'Airtime top-ups',
                    ],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, I use it every time'],
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, but rarely use it'],
                        ],
                    ],
                ],
                // text — open improvement (card holders only)
                [
                    'id'       => 'loy_q4',
                    'type'     => 'text',
                    'text'     => 'What one change would make you use your ZamMart Loyalty Card more often?',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, I use it every time'],
                            ['type' => 'answer', 'key' => 'loy_q1', 'operator' => 'eq', 'value' => 'Yes, but rarely use it'],
                        ],
                    ],
                ],
                // scale — future intent (everyone)
                [
                    'id'       => 'loy_q5',
                    'type'     => 'scale',
                    'text'     => "How likely are you to join or continue using ZamMart's Loyalty Rewards Programme?",
                    'hint'     => '1 = Not at all likely  |  5 = Extremely likely',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_loyalty_program',
                    'type'    => 'mcq',
                    'text'    => 'Do you belong to any other supermarket loyalty programmes?',
                    'options' => ['Yes — Shoprite / Pick n Pay', 'Yes — another retailer', 'No, only ZamMart', 'No loyalty programmes at all'],
                ],
            ],
        ]);

        // ── Survey 1.4: Online vs. In-Store Trends — draft, points, youth ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $zammart->id,
            'title'             => 'Online vs. In-Store Shopping Trends',
            'description'       => 'ZamMart is considering an online delivery service. Help us understand how Zambians are adopting online grocery shopping. Earn 150 points on completion.',
            'reward_type'       => 'points',
            'reward_points'     => 150,
            'reward_amount'     => 0,
            'prize_name'        => null,
            'prizes'            => null,
            'estimated_time'    => 5,
            'status'            => 'draft',
            'is_active'         => false,
            'draw_phase_active' => false,
            'response_cap'      => 400,
            'target_gender'     => null,
            'target_age_band'   => '18-25,26-35',
            'target_location'   => 'Lusaka,Ndola,Kitwe',
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                [
                    'id'       => 'ols_q1',
                    'type'     => 'mcq',
                    'text'     => 'Have you ever bought groceries online in Zambia?',
                    'required' => true,
                    'options'  => ['Yes, regularly (monthly or more)', 'Yes, a few times', 'No, but I am interested', 'No, and not interested'],
                    'logic'    => ['No, and not interested' => 'ols_q4'],
                ],
                [
                    'id'       => 'ols_q2',
                    'type'     => 'checkbox',
                    'text'     => 'Which platforms have you used for online grocery shopping? (Select all that apply)',
                    'required' => false,
                    'options'  => [
                        'Jumia Zambia',
                        'Facebook Marketplace',
                        'WhatsApp groups / informal vendors',
                        'Supermarket website or app',
                        'Other delivery app',
                    ],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'ols_q1', 'operator' => 'eq', 'value' => 'Yes, regularly (monthly or more)'],
                            ['type' => 'answer', 'key' => 'ols_q1', 'operator' => 'eq', 'value' => 'Yes, a few times'],
                        ],
                    ],
                ],
                [
                    'id'       => 'ols_q3',
                    'type'     => 'scale',
                    'text'     => 'How satisfied have you been with online grocery delivery in Zambia overall?',
                    'hint'     => '1 = Very unsatisfied  |  5 = Very satisfied',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'ols_q1', 'operator' => 'eq', 'value' => 'Yes, regularly (monthly or more)'],
                            ['type' => 'answer', 'key' => 'ols_q1', 'operator' => 'eq', 'value' => 'Yes, a few times'],
                        ],
                    ],
                ],
                [
                    'id'       => 'ols_q4',
                    'type'     => 'checkbox',
                    'text'     => 'What would make you more likely to order groceries online? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Same-day delivery option',
                        'Prices matching in-store prices',
                        'A ZamMart official delivery app',
                        'Accept mobile money payment',
                        'Easy returns for damaged goods',
                        'Loyalty points for online orders',
                    ],
                ],
                [
                    'id'       => 'ols_q5',
                    'type'     => 'scale',
                    'text'     => "How likely are you to use ZamMart's online grocery delivery if it were launched?",
                    'hint'     => '1 = Definitely would not  |  5 = Definitely would',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_smartphone_type',
                    'type'    => 'mcq',
                    'text'    => 'What type of smartphone do you primarily use?',
                    'options' => ['Android (Samsung, Tecno, etc.)', 'iPhone (Apple)', 'Basic phone (non-smartphone)', 'No personal phone'],
                ],
            ],
        ]);


        // =====================================================================
        // CLIENT 2: Kwacha Capital Bank
        // Three surveys — prize_draw / airtime / points
        // =====================================================================
        $kwachaBank = Client::create([
            'name'        => 'Kwacha Capital Bank',
            'email'       => 'insights@kwachacapital.co.zm',
            'description' => 'A leading Zambian retail and SME bank with 28 branches nationwide. Research focuses on financial inclusion, digital banking adoption, and understanding the credit needs of working Zambians.',
            'is_active'   => true,
        ]);

        // ── Survey 2.1: Financial Literacy & Banking Access — prize_draw, open, all types ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $kwachaBank->id,
            'title'             => 'Zambia Financial Literacy & Banking Access Study 2026',
            'description'       => 'Kwacha Capital Bank wants to understand the financial habits and banking needs of Zambians. Share your story and enter our Grand Prize Draw — win a K2,000 cash prize or one of three K500 runner-up prizes!',
            'reward_type'       => 'prize_draw',
            'reward_amount'     => 2000,
            'reward_points'     => 0,
            'prize_name'        => 'K2,000 Grand Prize',
            'prizes'            => [
                ['name' => 'Grand Prize — K2,000 Cash',         'amount' => 2000, 'points' => null],
                ['name' => 'Runner-Up (x3) — K500 Cash',        'amount' => 500,  'points' => null],
                ['name' => '1,500 Bonus Points',                'amount' => null, 'points' => 1500],
                ['name' => '750 Bonus Points',                  'amount' => null, 'points' => 750],
            ],
            'estimated_time'    => 8,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => true,
            'response_cap'      => 400,
            'target_gender'     => null,
            'target_age_band'   => null,
            'target_location'   => null,
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                // image_mcq — relationship with banking
                [
                    'id'       => 'fin_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which best describes your relationship with formal banking?',
                    'required' => true,
                    'options'  => [
                        ['label' => 'I have a bank account I use regularly',          'image_url' => 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'I have an account but rarely use it',            'image_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'I only use mobile money (no bank account)',      'image_url' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'I do not use any formal financial service',      'image_url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=400&h=280&fit=crop&auto=format'],
                    ],
                    'logic' => [
                        'I do not use any formal financial service' => 'fin_q3',
                    ],
                ],
                // checkbox — active services (bank users only)
                [
                    'id'       => 'fin_q2',
                    'type'     => 'checkbox',
                    'text'     => 'Which banking services do you actively use? (Select all that apply)',
                    'required' => false,
                    'options'  => [
                        'Savings / current account',
                        'Mobile banking app',
                        'Internet banking (web)',
                        'Personal or business loan',
                        'Fixed deposit or investment account',
                        'Credit or debit card',
                        'Bank ATM',
                    ],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'fin_q1', 'operator' => 'eq', 'value' => 'I have a bank account I use regularly'],
                            ['type' => 'answer', 'key' => 'fin_q1', 'operator' => 'eq', 'value' => 'I have an account but rarely use it'],
                        ],
                    ],
                ],
                // mcq — where savings are kept
                [
                    'id'       => 'fin_q3',
                    'type'     => 'mcq',
                    'text'     => 'Where do you keep most of your savings or money?',
                    'required' => true,
                    'options'  => [
                        'Bank savings account',
                        'Mobile money wallet (Airtel Money, MTN MoMo)',
                        'Cash at home',
                        'Village savings group (chilimba / VSLA)',
                        'I do not save',
                    ],
                ],
                // scale — financial confidence
                [
                    'id'       => 'fin_q4',
                    'type'     => 'scale',
                    'text'     => 'How confident are you in managing your own personal finances?',
                    'hint'     => '1 = Not confident at all  |  5 = Very confident',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // mcq with skip logic — loan history
                [
                    'id'       => 'fin_q5',
                    'type'     => 'mcq',
                    'text'     => 'Have you ever applied for a bank loan or credit facility in Zambia?',
                    'required' => true,
                    'options'  => ['Yes, and was approved', 'Yes, but was declined', 'No, never applied', 'No, not interested in credit'],
                    'logic'    => [
                        'No, never applied'           => 'fin_q7',
                        'No, not interested in credit' => 'fin_q7',
                    ],
                ],
                // checkbox — loan purpose (applicants only)
                [
                    'id'       => 'fin_q6',
                    'type'     => 'checkbox',
                    'text'     => 'What did you use or plan to use the loan for? (Select all that apply)',
                    'required' => false,
                    'options'  => [
                        'Start or grow a business',
                        'Pay school fees / education',
                        'Buy a vehicle',
                        'Build or renovate a home',
                        'Cover emergency expenses',
                        'Consolidate other debts',
                    ],
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'fin_q5', 'operator' => 'eq', 'value' => 'Yes, and was approved'],
                            ['type' => 'answer', 'key' => 'fin_q5', 'operator' => 'eq', 'value' => 'Yes, but was declined'],
                        ],
                    ],
                ],
                // scale — financial wellbeing
                [
                    'id'       => 'fin_q7',
                    'type'     => 'scale',
                    'text'     => 'How would you rate your overall financial wellbeing today?',
                    'hint'     => '1 = Struggling financially  |  5 = Financially secure and comfortable',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // text — wish list
                [
                    'id'       => 'fin_q8',
                    'type'     => 'text',
                    'text'     => 'What one banking product or service would most improve your financial life right now?',
                    'required' => false,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_income_source',
                    'type'    => 'mcq',
                    'text'    => 'What is your primary source of income?',
                    'options' => ['Salary (formal employment)', 'Business income', 'Farming / agriculture', 'Casual / informal work', 'Family support / remittances'],
                ],
                [
                    'id'      => 'enrich_phone_banking',
                    'type'    => 'mcq',
                    'text'    => 'Do you have a smartphone capable of running a banking app?',
                    'options' => ['Yes, and I use banking apps', 'Yes, but no banking app installed', 'No smartphone', 'Not sure'],
                ],
                [
                    'id'      => 'enrich_nearest_branch',
                    'type'    => 'mcq',
                    'text'    => 'How far is the nearest bank branch or ATM from where you live?',
                    'options' => ['Within 1km', '1–5km', '5–15km', 'More than 15km away'],
                ],
            ],
        ]);

        // ── Survey 2.2: Digital Banking App — airtime, youth/urban, trait-targeted ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $kwachaBank->id,
            'title'             => 'Digital Banking Adoption & Mobile App Experience',
            'description'       => 'Help Kwacha Capital Bank build a better mobile app. Share how you currently bank digitally and what you need from a banking app. Earn K100 airtime on completion.',
            'reward_type'       => 'airtime',
            'reward_amount'     => 100,
            'reward_points'     => 0,
            'prize_name'        => 'K100 Airtime',
            'prizes'            => [['name' => 'K100 Airtime Top-Up', 'amount' => 100, 'points' => null]],
            'estimated_time'    => 6,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 250,
            'target_gender'     => null,
            'target_age_band'   => '18-25,26-35',
            'target_location'   => 'Lusaka,Ndola,Kitwe,Livingstone',
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [
                ['key' => 'enrich_phone_banking', 'operator' => 'eq', 'value' => 'Yes, and I use banking apps'],
            ],
            'exclude_traits'    => [],
            'questions'         => [
                [
                    'id'       => 'dba_q1',
                    'type'     => 'mcq',
                    'text'     => 'Which best describes how you primarily do your banking today?',
                    'required' => true,
                    'options'  => [
                        'Mainly via mobile banking app',
                        'Mix of app and branch visits',
                        'Mainly branch / ATM visits',
                        'Mainly mobile money (no bank app)',
                    ],
                ],
                [
                    'id'       => 'dba_q2',
                    'type'     => 'checkbox',
                    'text'     => 'Which tasks do you currently complete on a mobile banking app? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Check account balance',
                        'Transfer money to another account',
                        'Pay utility bills',
                        'Buy airtime or data bundles',
                        'Apply for a loan or overdraft',
                        'Open a savings or investment account',
                        'Send money internationally',
                    ],
                ],
                [
                    'id'       => 'dba_q3',
                    'type'     => 'scale',
                    'text'     => 'How would you rate the ease of use of your current banking app?',
                    'hint'     => '1 = Very difficult to use  |  5 = Extremely easy and intuitive',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // text — frustration (only for low raters)
                [
                    'id'       => 'dba_q4',
                    'type'     => 'text',
                    'text'     => 'What frustrates you most about your current mobile banking experience?',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'dba_q3', 'operator' => 'eq', 'value' => '1'],
                            ['type' => 'answer', 'key' => 'dba_q3', 'operator' => 'eq', 'value' => '2'],
                        ],
                    ],
                ],
                [
                    'id'       => 'dba_q5',
                    'type'     => 'checkbox',
                    'text'     => 'Which features would most improve a banking app for you? (Select your top 3)',
                    'required' => true,
                    'options'  => [
                        'Biometric login (fingerprint / face ID)',
                        'Instant loan applications with quick approval',
                        'Budget tracking and spending insights',
                        'Savings goals and automatic transfers',
                        'Real-time transaction notifications',
                        'In-app customer support chat',
                        'USSD fallback for low connectivity',
                    ],
                ],
                [
                    'id'       => 'dba_q6',
                    'type'     => 'scale',
                    'text'     => 'How likely would you be to switch to Kwacha Capital Bank if it offered the best-rated app in Zambia?',
                    'hint'     => '1 = Would not switch  |  5 = Would definitely switch',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_current_bank',
                    'type'    => 'mcq',
                    'text'    => 'Which bank do you currently use as your main bank?',
                    'options' => ['Zanaco', 'Stanbic', 'FNB Zambia', 'Absa Zambia', 'Kwacha Capital Bank', 'Other', 'No main bank'],
                ],
            ],
        ]);

        // ── Survey 2.3: SME Business Banking — points, self-employed, trait-targeted ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $kwachaBank->id,
            'title'             => 'SME Business Banking Needs Assessment',
            'description'       => 'Are you a small business owner or entrepreneur in Zambia? Help Kwacha Capital Bank understand the financial needs of SMEs. Complete this survey and earn 400 points.',
            'reward_type'       => 'points',
            'reward_points'     => 400,
            'reward_amount'     => 0,
            'prize_name'        => null,
            'prizes'            => null,
            'estimated_time'    => 9,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 150,
            'target_gender'     => null,
            'target_age_band'   => '26-35,36-50',
            'target_location'   => null,
            'target_employment' => 'Self-employed',
            'target_income_band' => null,
            'target_traits'     => [
                ['key' => 'enrich_income_source', 'operator' => 'eq', 'value' => 'Business income'],
            ],
            'exclude_traits'    => [],
            'questions'         => [
                // image_mcq — business size
                [
                    'id'       => 'sme_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which best describes the size of your business?',
                    'required' => true,
                    'options'  => [
                        ['label' => 'Sole trader / one-person operation',   'image_url' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Micro business (2–5 employees)',        'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Small business (6–20 employees)',       'image_url' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Medium business (21–100 employees)',    'image_url' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=400&h=280&fit=crop&auto=format'],
                    ],
                ],
                // checkbox — current banking services
                [
                    'id'       => 'sme_q2',
                    'type'     => 'checkbox',
                    'text'     => 'Which business banking services do you currently use? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Business current or savings account',
                        'Business loan or overdraft',
                        'POS / card payment terminal',
                        'Mobile money merchant account',
                        'Payroll service',
                        'Foreign currency account',
                        'None — I use a personal account for business',
                    ],
                ],
                // mcq — biggest challenge
                [
                    'id'       => 'sme_q3',
                    'type'     => 'mcq',
                    'text'     => 'What is the biggest financial challenge facing your business right now?',
                    'required' => true,
                    'options'  => [
                        'Access to working capital / cash flow',
                        'High bank charges and fees',
                        'Slow loan approval process',
                        'Foreign exchange / import costs',
                        'Customer payment delays',
                        'Lack of financial record-keeping skills',
                    ],
                ],
                // scale — how well banks serve SMEs
                [
                    'id'       => 'sme_q4',
                    'type'     => 'scale',
                    'text'     => 'How well do Zambian banks currently serve the needs of small businesses?',
                    'hint'     => '1 = Very poorly  |  5 = Extremely well',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // mcq with skip logic — loan application history
                [
                    'id'       => 'sme_q5',
                    'type'     => 'mcq',
                    'text'     => 'Have you applied for a business loan in the past 2 years?',
                    'required' => true,
                    'options'  => [
                        'Yes, approved and used it',
                        'Yes, applied but declined',
                        'No — not aware of options',
                        'No — collateral requirements too high',
                        'No — prefer to grow organically',
                    ],
                    'logic' => [
                        'No — not aware of options'  => 'sme_q7',
                        'No — prefer to grow organically' => 'sme_q7',
                    ],
                ],
                // scale — loan satisfaction (applicants only)
                [
                    'id'       => 'sme_q6',
                    'type'     => 'scale',
                    'text'     => 'How satisfied were you with the business loan application process?',
                    'hint'     => '1 = Very unsatisfied  |  5 = Very satisfied',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'sme_q5', 'operator' => 'eq', 'value' => 'Yes, approved and used it'],
                            ['type' => 'answer', 'key' => 'sme_q5', 'operator' => 'eq', 'value' => 'Yes, applied but declined'],
                            ['type' => 'answer', 'key' => 'sme_q5', 'operator' => 'eq', 'value' => 'No — collateral requirements too high'],
                        ],
                    ],
                ],
                // checkbox — desired new products
                [
                    'id'       => 'sme_q7',
                    'type'     => 'checkbox',
                    'text'     => 'Which new banking products would most benefit your business? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Instant microloans (K500–K5,000) via app',
                        'Invoice financing (get paid while waiting for clients)',
                        'Automatic savings sweep to high-interest account',
                        'Free bulk payment service for suppliers',
                        'Affordable cross-border payment tool',
                        'Online accounting integration (QuickBooks, Wave)',
                    ],
                ],
                // text — open innovation
                [
                    'id'       => 'sme_q8',
                    'type'     => 'text',
                    'text'     => 'Describe the single most important financial product Kwacha Capital Bank could build to help your business grow.',
                    'required' => false,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_business_sector',
                    'type'    => 'mcq',
                    'text'    => 'Which sector best describes your business?',
                    'options' => ['Retail / trading', 'Agriculture / agribusiness', 'Construction / real estate', 'Services (transport, cleaning, etc.)', 'Food & hospitality', 'Tech / digital services', 'Other'],
                ],
                [
                    'id'      => 'enrich_years_in_business',
                    'type'    => 'mcq',
                    'text'    => 'How long has your business been operating?',
                    'options' => ['Less than 1 year', '1–2 years', '3–5 years', 'More than 5 years'],
                ],
            ],
        ]);


        // =====================================================================
        // CLIENT 3: ConnectZam Telecom
        // Three surveys — prize_draw / points / airtime
        // =====================================================================
        $connectZam = Client::create([
            'name'        => 'ConnectZam Telecom',
            'email'       => 'cx-research@connectzam.co.zm',
            'description' => "Zambia's third-largest mobile network operator serving 4.2 million subscribers across urban and rural areas. Research focuses on network quality, competitive positioning, and new service adoption.",
            'is_active'   => true,
        ]);

        // ── Survey 3.1: Customer Satisfaction Q2 2026 — prize_draw, open, all types, trait exclusion ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $connectZam->id,
            'title'             => 'ConnectZam Network Satisfaction Study Q2 2026',
            'description'       => "How is your ConnectZam experience? Answer 8 questions about network quality, data, and customer service. Complete to enter our Mega Draw — win an iPhone 16, Samsung Galaxy S25, or one of 10 K500 cash prizes!",
            'reward_type'       => 'prize_draw',
            'reward_amount'     => 0,
            'reward_points'     => 0,
            'prize_name'        => 'iPhone 16 (128GB)',
            'prizes'            => [
                ['name' => 'Grand Prize — iPhone 16 (128GB)',          'amount' => null, 'points' => null],
                ['name' => '2nd Place — Samsung Galaxy S25',           'amount' => null, 'points' => null],
                ['name' => '3rd–12th Place — K500 Cash',               'amount' => 500,  'points' => null],
                ['name' => 'Runner-Up — 2,000 Bonus Points (x20)',     'amount' => null, 'points' => 2000],
            ],
            'estimated_time'    => 8,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => true,
            'response_cap'      => 1000,
            'target_gender'     => null,
            'target_age_band'   => null,
            'target_location'   => null,
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [
                ['key' => 'completed_telecom_survey', 'operator' => 'eq', 'value' => 'Yes'],
            ],
            'questions'         => [
                // image_mcq — primary SIM
                [
                    'id'       => 'czs_q1',
                    'type'     => 'image_mcq',
                    'text'     => 'Which mobile network is your primary SIM?',
                    'required' => true,
                    'options'  => [
                        ['label' => 'ConnectZam',    'image_url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Airtel Zambia', 'image_url' => 'https://images.unsplash.com/photo-1529612700005-e35377bf1415?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'MTN Zambia',    'image_url' => 'https://images.unsplash.com/photo-1616628188859-7a11abb6fcc9?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Zamtel',        'image_url' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=280&fit=crop&auto=format'],
                    ],
                ],
                // scale — overall network quality
                [
                    'id'       => 'czs_q2',
                    'type'     => 'scale',
                    'text'     => "How would you rate ConnectZam's overall network quality where you live?",
                    'hint'     => '1 = Very poor  |  5 = Excellent',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // checkbox — services used
                [
                    'id'       => 'czs_q3',
                    'type'     => 'checkbox',
                    'text'     => 'Which ConnectZam services do you currently use? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Voice calls',
                        'SMS messaging',
                        'Mobile data bundles',
                        'ConnectZam Money (mobile money)',
                        'ConnectZam TV streaming',
                        'International roaming',
                        'Home fibre broadband',
                    ],
                ],
                // mcq with skip logic — outage frequency
                [
                    'id'       => 'czs_q4',
                    'type'     => 'mcq',
                    'text'     => 'How often do you experience network drop-outs or call failures?',
                    'required' => true,
                    'options'  => [
                        'Never — very reliable',
                        'Rarely — maybe once a month',
                        'Occasionally — once a week',
                        'Frequently — several times a week',
                        'Daily — it is a serious problem',
                    ],
                    'logic' => ['Never — very reliable' => 'czs_q6'],
                ],
                // text — problem description (shown only to those with outages)
                [
                    'id'       => 'czs_q5',
                    'type'     => 'text',
                    'text'     => 'Please describe the most common network problem you experience (area, time of day, type of issue).',
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'czs_q4', 'operator' => 'eq', 'value' => 'Occasionally — once a week'],
                            ['type' => 'answer', 'key' => 'czs_q4', 'operator' => 'eq', 'value' => 'Frequently — several times a week'],
                            ['type' => 'answer', 'key' => 'czs_q4', 'operator' => 'eq', 'value' => 'Daily — it is a serious problem'],
                        ],
                    ],
                ],
                // scale — data bundle pricing
                [
                    'id'       => 'czs_q6',
                    'type'     => 'scale',
                    'text'     => "How satisfied are you with ConnectZam's data bundle pricing?",
                    'hint'     => '1 = Very poor value  |  5 = Excellent value',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                // mcq — customer support contact
                [
                    'id'       => 'czs_q7',
                    'type'     => 'mcq',
                    'text'     => 'Have you contacted ConnectZam customer support in the last 6 months?',
                    'required' => true,
                    'options'  => [
                        'Yes, and it was resolved well',
                        'Yes, but the issue was not resolved',
                        'No, I have not needed to',
                        'No, I gave up trying to reach them',
                    ],
                    'logic' => ['No, I have not needed to' => 'czs_q8'],
                ],
                // scale — support quality (shown to those who contacted support)
                [
                    'id'       => 'czs_q7b',
                    'type'     => 'scale',
                    'text'     => 'How would you rate your most recent ConnectZam customer support experience?',
                    'hint'     => '1 = Very poor  |  5 = Excellent',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'czs_q7', 'operator' => 'eq', 'value' => 'Yes, and it was resolved well'],
                            ['type' => 'answer', 'key' => 'czs_q7', 'operator' => 'eq', 'value' => 'Yes, but the issue was not resolved'],
                            ['type' => 'answer', 'key' => 'czs_q7', 'operator' => 'eq', 'value' => 'No, I gave up trying to reach them'],
                        ],
                    ],
                ],
                // scale — NPS
                [
                    'id'       => 'czs_q8',
                    'type'     => 'scale',
                    'text'     => 'How likely are you to recommend ConnectZam to a friend or family member?',
                    'hint'     => '1 = Would not recommend  |  5 = Would definitely recommend',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_network_years',
                    'type'    => 'mcq',
                    'text'    => 'How long have you been a ConnectZam subscriber?',
                    'options' => ['Less than 6 months', '6 months to 2 years', '2–5 years', 'More than 5 years', 'I am not a ConnectZam subscriber'],
                ],
                [
                    'id'      => 'completed_telecom_survey',
                    'type'    => 'mcq',
                    'text'    => 'Have you participated in any mobile network survey in the past 6 months?',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);

        // ── Survey 3.2: 5G Awareness & Smartphone Usage — points, youth/urban ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $connectZam->id,
            'title'             => '5G Awareness & Smartphone Usage Study',
            'description'       => "ConnectZam is planning Zambia's first 5G network rollout. Tell us what you know about 5G, what device you use, and what you'd want from a 5G connection. Earn 250 points instantly on completion.",
            'reward_type'       => 'points',
            'reward_points'     => 250,
            'reward_amount'     => 0,
            'prize_name'        => null,
            'prizes'            => null,
            'estimated_time'    => 5,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 500,
            'target_gender'     => null,
            'target_age_band'   => '18-25,26-35',
            'target_location'   => 'Lusaka,Ndola,Kitwe',
            'target_employment' => null,
            'target_income_band' => null,
            'target_traits'     => [],
            'exclude_traits'    => [],
            'questions'         => [
                [
                    'id'       => '5g_q1',
                    'type'     => 'mcq',
                    'text'     => 'Before this survey, how much did you know about 5G mobile technology?',
                    'required' => true,
                    'options'  => [
                        'A lot — I follow tech news closely',
                        'Somewhat — I know the basics',
                        'A little — heard of it but unsure what it means',
                        'Nothing — first time hearing about it',
                    ],
                ],
                [
                    'id'       => '5g_q2',
                    'type'     => 'image_mcq',
                    'text'     => 'Which type of device do you primarily use for mobile data?',
                    'required' => true,
                    'options'  => [
                        ['label' => 'Flagship Smartphone (K3,000+)',      'image_url' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Mid-range Smartphone (K1,000–K2,999)', 'image_url' => 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Budget Smartphone (Under K1,000)',   'image_url' => 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=400&h=280&fit=crop&auto=format'],
                        ['label' => 'Tablet',                             'image_url' => 'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?w=400&h=280&fit=crop&auto=format'],
                    ],
                ],
                [
                    'id'       => '5g_q3',
                    'type'     => 'checkbox',
                    'text'     => 'What would you mainly use a 5G connection for? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Faster video streaming (YouTube, Netflix)',
                        'Online gaming with no lag',
                        'Work-from-home video calls',
                        'Cloud storage and file transfers',
                        'Smart home devices',
                        'Replacing home Wi-Fi entirely',
                        'I would not change my usage',
                    ],
                ],
                [
                    'id'       => '5g_q4',
                    'type'     => 'scale',
                    'text'     => 'How much extra per month would you pay for a 5G bundle compared to your current 4G plan?',
                    'hint'     => '1 = Nothing extra  |  5 = Significantly more (K100+/month)',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                [
                    'id'       => '5g_q5',
                    'type'     => 'mcq',
                    'text'     => 'Would you buy a new 5G-compatible smartphone specifically for a 5G upgrade?',
                    'required' => true,
                    'options'  => [
                        'Yes, immediately when 5G launches',
                        'Yes, when my current phone needs replacing',
                        'Maybe — depends on price and coverage',
                        'No — not worth the extra cost',
                    ],
                    'logic' => ['No — not worth the extra cost' => 'end'],
                ],
                // text — price point (shown to interested respondents)
                [
                    'id'       => '5g_q6',
                    'type'     => 'text',
                    'text'     => "What device or plan price would make ConnectZam's 5G launch worth it for you?",
                    'required' => false,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => '5g_q5', 'operator' => 'eq', 'value' => 'Yes, immediately when 5G launches'],
                            ['type' => 'answer', 'key' => '5g_q5', 'operator' => 'eq', 'value' => 'Yes, when my current phone needs replacing'],
                            ['type' => 'answer', 'key' => '5g_q5', 'operator' => 'eq', 'value' => 'Maybe — depends on price and coverage'],
                        ],
                    ],
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_current_speed',
                    'type'    => 'mcq',
                    'text'    => 'What mobile generation does your primary SIM mainly use?',
                    'options' => ['4G (LTE)', '3G', '2G', 'Not sure'],
                ],
            ],
        ]);

        // ── Survey 3.3: Rural Connectivity — airtime, rural areas, low income ──
        Survey::create([
            'user_id'           => $adminId,
            'client_id'         => $connectZam->id,
            'title'             => 'Rural Connectivity Experience & Needs Survey',
            'description'       => 'ConnectZam is expanding coverage to underserved rural areas. Help us understand connectivity challenges outside major cities. Complete this survey and earn K50 airtime.',
            'reward_type'       => 'airtime',
            'reward_amount'     => 50,
            'reward_points'     => 0,
            'prize_name'        => 'K50 Airtime',
            'prizes'            => [['name' => 'K50 Airtime Top-Up', 'amount' => 50, 'points' => null]],
            'estimated_time'    => 6,
            'status'            => 'active',
            'is_active'         => true,
            'draw_phase_active' => false,
            'response_cap'      => 300,
            'target_gender'     => null,
            'target_age_band'   => '18-25,26-35,36-50',
            'target_location'   => 'Kabwe,Chipata,Solwezi,Livingstone,Kasama,Mansa,Mongu,Choma',
            'target_employment' => null,
            'target_income_band' => 'Under 2000,2000 - 4999',
            'target_traits'     => [],
            'exclude_traits'    => [
                ['key' => 'enrich_nearest_branch', 'operator' => 'eq', 'value' => 'Within 1km'],
            ],
            'questions'         => [
                [
                    'id'       => 'rce_q1',
                    'type'     => 'mcq',
                    'text'     => 'How would you describe where you live?',
                    'required' => true,
                    'options'  => [
                        'Town centre or major city',
                        'Peri-urban / compound area',
                        'Small town or township',
                        'Rural village or farming area',
                        'Remote area (far from any town)',
                    ],
                ],
                [
                    'id'       => 'rce_q2',
                    'type'     => 'scale',
                    'text'     => 'How strong is your mobile phone signal at home?',
                    'hint'     => '1 = No signal at all  |  5 = Full, strong signal',
                    'required' => true,
                    'min' => 1, 'max' => 5, 'step' => 1,
                ],
                [
                    'id'       => 'rce_q3',
                    'type'     => 'checkbox',
                    'text'     => 'How does poor mobile coverage affect your daily life? (Select all that apply)',
                    'required' => true,
                    'options'  => [
                        'Cannot reach family or emergency services',
                        'Cannot access mobile money for payments',
                        'Children cannot do online schoolwork',
                        'Farming and market price information is unavailable',
                        'Health clinic bookings or results cannot be received',
                        'Cannot grow or run a business properly',
                        'Coverage is fine where I live',
                    ],
                ],
                [
                    'id'       => 'rce_q4',
                    'type'     => 'mcq',
                    'text'     => 'What is the main device you use to access the internet?',
                    'required' => true,
                    'options'  => ['Smartphone', 'Basic feature phone', 'Tablet or laptop', 'I do not access the internet'],
                    'logic'    => ['I do not access the internet' => 'rce_q6'],
                ],
                // scale — economic impact (internet users only)
                [
                    'id'       => 'rce_q5',
                    'type'     => 'scale',
                    'text'     => 'How much does limited mobile internet slow down your ability to earn money or access services?',
                    'hint'     => '1 = No impact at all  |  5 = Severely impacts my livelihood',
                    'required' => false,
                    'min' => 1, 'max' => 5, 'step' => 1,
                    'visibility' => [
                        'logic'      => 'or',
                        'conditions' => [
                            ['type' => 'answer', 'key' => 'rce_q4', 'operator' => 'eq', 'value' => 'Smartphone'],
                            ['type' => 'answer', 'key' => 'rce_q4', 'operator' => 'eq', 'value' => 'Basic feature phone'],
                            ['type' => 'answer', 'key' => 'rce_q4', 'operator' => 'eq', 'value' => 'Tablet or laptop'],
                        ],
                    ],
                ],
                // text — location suggestion (everyone)
                [
                    'id'       => 'rce_q6',
                    'type'     => 'text',
                    'text'     => 'If ConnectZam could install a tower or improve signal in your area, where would it be most needed and why?',
                    'required' => false,
                ],
            ],
            'enrichment_questions' => [
                [
                    'id'      => 'enrich_nearest_tower',
                    'type'    => 'mcq',
                    'text'    => 'Do you know the location of the nearest mobile network tower to you?',
                    'options' => ['Yes, very close (under 2km)', 'Yes, some distance away', 'No, not sure', 'No, there is no tower nearby'],
                ],
                [
                    'id'      => 'enrich_solar_power',
                    'type'    => 'mcq',
                    'text'    => 'Do you have reliable electricity or solar power to charge your phone?',
                    'options' => ['Yes, grid electricity', 'Yes, solar power', 'Inconsistent electricity', 'No — I charge at a neighbour or shop'],
                ],
            ],
        ]);

        $this->command->info('');
        $this->command->info('✓ CLIENT SHOWCASE DATA CREATED');
        $this->command->info('');
        $this->command->info('  [CLIENT 1] ZamMart Retail Group');
        $this->command->info('    → Grocery Shopping Habits 2026        (active  · prize_draw · open targeting · all 5 question types)');
        $this->command->info('    → Private Label vs. Name Brand         (active  · points    · female 18–50)');
        $this->command->info('    → Loyalty Rewards Programme Feedback   (active  · airtime   · employed 26–50, mid-income)');
        $this->command->info('    → Online vs. In-Store Trends           (draft   · points    · youth 18–35, urban)');
        $this->command->info('');
        $this->command->info('  [CLIENT 2] Kwacha Capital Bank');
        $this->command->info('    → Financial Literacy & Banking 2026    (active  · prize_draw · open · all 5 question types)');
        $this->command->info('    → Digital Banking App Experience       (active  · airtime   · youth, urban, trait-targeted)');
        $this->command->info('    → SME Business Banking Needs           (active  · points    · self-employed, trait-targeted)');
        $this->command->info('');
        $this->command->info('  [CLIENT 3] ConnectZam Telecom');
        $this->command->info('    → Network Satisfaction Q2 2026         (active  · prize_draw · trait exclusion · all 5 types)');
        $this->command->info('    → 5G Awareness & Smartphone Study      (active  · points    · youth 18–35, urban)');
        $this->command->info('    → Rural Connectivity Experience        (active  · airtime   · rural, low-income, trait exclusion)');
        $this->command->info('');
    }
}
