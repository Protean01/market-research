<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\QuestionTemplate;
use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionBankController extends Controller
{
    public function index()
    {
        $templates = QuestionTemplate::with(['user', 'client'])->latest()->get();
        $clients = Client::withCount('questionTemplates')->latest()->get();
        $allSurveys = Survey::withCount('responses')->latest()->get();

        return Inertia::render('admin/QuestionBank', [
            'initialTemplates' => $templates,
            'clients' => $clients,
            'allSurveys' => $allSurveys,
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'client_id' => 'required|exists:clients,id', // Required now
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|string',
            'questions.*.options' => 'nullable|array',
        ]);

        $data = $validated;
        $data['user_id'] = auth()->id();

        QuestionTemplate::create($data);

        return redirect()->back()->with('success', 'Question set added to client library.');
    }

    public function destroyTemplate(QuestionTemplate $template)
    {
        if (! auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->delete();

        return redirect()->back()->with('success', 'Question removed from library.');
    }

    public function api()
    {
        $query = QuestionTemplate::query();

        if (! auth()->user()->isAdmin()) {
            $clientId = auth()->user()->client_id;
            $query->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')->whereNull('user_id') // Global
                    ->orWhere('client_id', $clientId) // Their brand
                    ->orWhere('user_id', auth()->id()); // Their private sets
            });
        }

        return response()->json($query->get());
    }
}
