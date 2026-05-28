<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/AdminSettings', [
            'settings' => [
                'reward_daily_cap' => Setting::get('reward_daily_cap', env('REWARD_DAILY_CAP', 0)),
                'reward_weekly_cap' => Setting::get('reward_weekly_cap', env('REWARD_WEEKLY_CAP', 0)),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'reward_daily_cap' => ['required', 'integer', 'min:0'],
            'reward_weekly_cap' => ['required', 'integer', 'min:0'],
        ]);

        Setting::set('reward_daily_cap', (int) $data['reward_daily_cap']);
        Setting::set('reward_weekly_cap', (int) $data['reward_weekly_cap']);

        return back()->with('success', 'Settings updated');
    }
}
