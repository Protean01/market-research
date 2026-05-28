<?php

namespace Database\Seeders;

use App\Models\PrizeDrawEntry;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FreshShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Wipe all survey-related data ──────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PrizeDrawEntry::truncate();
        Response::truncate();
        Survey::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $adminId = DB::table('users')->where('is_admin', true)->value('id') ?? 1;

        // ── Survey 1: Multi-Prize Draw (3 prizes, instant spin) ──────────────
        Survey::create([
            'user_id'          => $adminId,
            'title'            => 'Zambia Consumer Lifestyle Pulse 2026',
            'description'      => 'Share your everyday spending, food, and transport habits for a chance to win one of THREE cash prizes. The slot machine runs live — spin immediately after completing!',
            'reward_type'      => 'prize_draw',
            'prize_name'       => 'Cash Prize Draw',
            'reward_points'    => 500,
            'reward_amount'    => 500,
            'prizes'           => [
                ['name' => '🥇 Grand Prize',   'points' => 1000, 'amount' => null],
                ['name' => '🥈 Second Place',  'points' => 500,  'amount' => null],
                ['name' => '🥉 Third Place',   'points' => 250,  'amount' => null],
            ],
            'draw_phase_active' => true,
            'is_active'        => true,
            'status'           => 'active',
            'estimated_time'   => 6,
            'response_cap'     => 200,
            'response_count'   => 0,
            'questions'        => [
                [
                    'id'       => 'q1',
                    'type'     => 'mcq',
                    'text'     => 'How often do you shop at a physical market or supermarket?',
                    'required' => true,
                    'options'  => ['Daily', '2–3 times a week', 'Once a week', 'Once a month', 'Rarely'],
                ],
                [
                    'id'       => 'q2',
                    'type'     => 'mcq',
                    'text'     => 'Which of these takes up the biggest share of your monthly budget?',
                    'required' => true,
                    'options'  => ['Food & groceries', 'Rent / accommodation', 'Transport', 'Education', 'Utilities (electricity, water)'],
                ],
                [
                    'id'       => 'q3',
                    'type'     => 'scale',
                    'text'     => 'How satisfied are you with the cost of living in your area? (1 = very unsatisfied, 5 = very satisfied)',
                    'required' => true,
                    'options'  => ['1', '2', '3', '4', '5'],
                ],
                [
                    'id'       => 'q4',
                    'type'     => 'mcq',
                    'text'     => 'How do you mainly get to work or school?',
                    'required' => true,
                    'options'  => ['Walk', 'Minibus / taxi', 'Personal vehicle', 'Motorcycle / bicycle', 'Work from home'],
                ],
                [
                    'id'       => 'q5',
                    'type'     => 'mcq',
                    'text'     => 'Which local brand do you trust most for everyday groceries?',
                    'required' => true,
                    'options'  => ['Shoprite', 'Pick n Pay', 'Choppies', 'Local market / vendor', 'Other'],
                ],
            ],
        ]);

        // ── Survey 2: Multi-Airtime Prize Draw (3 different amounts, instant spin) ──
        Survey::create([
            'user_id'          => $adminId,
            'title'            => 'Mobile Money & Connectivity Study',
            'description'      => 'Tell us how you use mobile money and data in Zambia. Spin the slot machine after completing and win one of THREE airtime prizes: K50, K25, or K10 — sent directly to your phone!',
            'reward_type'      => 'airtime',
            'prize_name'       => 'Airtime Prize',
            'reward_points'    => 0,
            'reward_amount'    => 50,
            'prizes'           => [
                ['name' => '🏆 Top Prize',    'amount' => 50, 'points' => 500],
                ['name' => '🎖 Second Prize', 'amount' => 25, 'points' => 250],
                ['name' => '🎗 Third Prize',  'amount' => 10, 'points' => 100],
            ],
            'draw_phase_active' => true,
            'is_active'        => true,
            'status'           => 'active',
            'estimated_time'   => 5,
            'response_cap'     => 150,
            'response_count'   => 0,
            'questions'        => [
                [
                    'id'       => 'q1',
                    'type'     => 'mcq',
                    'text'     => 'Which mobile network do you use most often?',
                    'required' => true,
                    'options'  => ['Airtel', 'MTN', 'Zamtel', 'I use multiple networks'],
                ],
                [
                    'id'       => 'q2',
                    'type'     => 'mcq',
                    'text'     => 'Which mobile money service do you use to send or receive money?',
                    'required' => true,
                    'options'  => ['Airtel Money', 'MTN MoMo', 'Zamtel Kwacha', 'Bank transfer only', 'I don\'t use mobile money'],
                ],
                [
                    'id'       => 'q3',
                    'type'     => 'scale',
                    'text'     => 'How reliable is your mobile data connection at home? (1 = very poor, 5 = excellent)',
                    'required' => true,
                    'options'  => ['1', '2', '3', '4', '5'],
                ],
                [
                    'id'       => 'q4',
                    'type'     => 'mcq',
                    'text'     => 'How much do you typically spend on mobile data per month?',
                    'required' => true,
                    'options'  => ['Under K30', 'K30–K60', 'K60–K120', 'Over K120', 'I use Wi-Fi only'],
                ],
                [
                    'id'       => 'q5',
                    'type'     => 'mcq',
                    'text'     => 'What do you use mobile internet for most?',
                    'required' => true,
                    'options'  => ['WhatsApp & social media', 'Work & email', 'Online shopping', 'Entertainment (YouTube, music)', 'Mobile banking'],
                ],
            ],
        ]);

        // ── Survey 3: Points Reward ───────────────────────────────────────────
        Survey::create([
            'user_id'          => $adminId,
            'title'            => 'Zambia Energy & Load Shedding Impact Survey',
            'description'      => 'How is load shedding affecting your daily life and business? Share your experience and earn 200 points instantly upon completion.',
            'reward_type'      => 'points',
            'prize_name'       => null,
            'reward_points'    => 200,
            'reward_amount'    => 20,
            'prizes'           => null,
            'draw_phase_active' => false,
            'is_active'        => true,
            'status'           => 'active',
            'estimated_time'   => 4,
            'response_cap'     => 500,
            'response_count'   => 0,
            'questions'        => [
                [
                    'id'       => 'q1',
                    'type'     => 'mcq',
                    'text'     => 'How many hours of load shedding do you experience on a typical day?',
                    'required' => true,
                    'options'  => ['None — stable power', '1–4 hours', '4–8 hours', '8–12 hours', 'More than 12 hours'],
                ],
                [
                    'id'       => 'q2',
                    'type'     => 'mcq',
                    'text'     => 'How does load shedding most affect you?',
                    'required' => true,
                    'options'  => ['Business / work productivity', 'Food spoilage (fridge off)', 'Children\'s studies', 'Home security (no lighting)', 'All of the above'],
                ],
                [
                    'id'       => 'q3',
                    'type'     => 'mcq',
                    'text'     => 'What backup power solution do you use most?',
                    'required' => true,
                    'options'  => ['Generator', 'Solar panels', 'Inverter / UPS', 'Candles / lanterns', 'Nothing — we manage without'],
                ],
                [
                    'id'       => 'q4',
                    'type'     => 'scale',
                    'text'     => 'How satisfied are you with ZESCO\'s communication about outages? (1 = very unsatisfied, 5 = very satisfied)',
                    'required' => true,
                    'options'  => ['1', '2', '3', '4', '5'],
                ],
                [
                    'id'       => 'q5',
                    'type'     => 'mcq',
                    'text'     => 'Would you pay more on your electricity bill if it guaranteed 24/7 stable power?',
                    'required' => true,
                    'options'  => ['Definitely yes', 'Probably yes', 'Not sure', 'Probably not', 'Definitely not'],
                ],
            ],
        ]);

        // ── Survey 4: Two-Prize Grand Draw (instant spin) ─────────────────────
        Survey::create([
            'user_id'          => $adminId,
            'title'            => 'Youth Finance & Entrepreneurship Study',
            'description'      => 'Are you aged 18–35 and navigating work, savings, or starting a business? Complete this survey and spin the slot machine for a chance to win the Grand Prize (K1,000) or the Runner-Up Prize (K500)!',
            'reward_type'      => 'prize_draw',
            'prize_name'       => 'Grand Prize Draw',
            'reward_points'    => 1000,
            'reward_amount'    => 1000,
            'prizes'           => [
                ['name' => '🏆 Grand Prize — K1,000',    'points' => 1000, 'amount' => null],
                ['name' => '🥈 Runner-Up — K500',        'points' => 500,  'amount' => null],
            ],
            'draw_phase_active' => true,
            'is_active'        => true,
            'status'           => 'active',
            'estimated_time'   => 7,
            'response_cap'     => 300,
            'response_count'   => 0,
            'target_age_band'  => '18-35',
            'questions'        => [
                [
                    'id'       => 'q1',
                    'type'     => 'mcq',
                    'text'     => 'What is your current employment or income situation?',
                    'required' => true,
                    'options'  => ['Formally employed', 'Self-employed / freelancer', 'Running my own business', 'Student', 'Currently unemployed'],
                ],
                [
                    'id'       => 'q2',
                    'type'     => 'mcq',
                    'text'     => 'Do you currently save money regularly?',
                    'required' => true,
                    'options'  => ['Yes, every month', 'Sometimes when I can', 'Rarely — expenses take everything', 'No, I don\'t save'],
                ],
                [
                    'id'       => 'q3',
                    'type'     => 'mcq',
                    'text'     => 'Which is your biggest financial challenge right now?',
                    'required' => true,
                    'options'  => ['Not enough income', 'High cost of living', 'Debt / loans', 'No access to startup capital', 'Irregular / unpredictable income'],
                ],
                [
                    'id'       => 'q4',
                    'type'     => 'scale',
                    'text'     => 'How confident are you in your financial future over the next 3 years? (1 = not at all, 5 = very confident)',
                    'required' => true,
                    'options'  => ['1', '2', '3', '4', '5'],
                ],
                [
                    'id'       => 'q5',
                    'type'     => 'mcq',
                    'text'     => 'If you received K5,000 today, what would you do with it?',
                    'required' => true,
                    'options'  => ['Start or grow a business', 'Pay off debts', 'Save it', 'Invest (shares, property)', 'Cover immediate household needs'],
                ],
            ],
        ]);

        $this->command->info('✓ All existing surveys cleared.');
        $this->command->info('✓ 4 showcase surveys created:');
        $this->command->info('   [1] Zambia Consumer Lifestyle Pulse 2026  — 3-prize draw (instant spin)');
        $this->command->info('   [2] Mobile Money & Connectivity Study      — 3-airtime prize draw (instant spin)');
        $this->command->info('   [3] Zambia Energy & Load Shedding Survey   — 200 points (instant reward)');
        $this->command->info('   [4] Youth Finance & Entrepreneurship Study — 2-prize grand draw (instant spin)');
    }
}
