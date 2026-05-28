<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFraudController extends Controller
{
    public function index(Request $request)
    {
        $events = FraudEvent::with('user')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/AdminFraud', [
            'events' => $events,
        ]);
    }
}
