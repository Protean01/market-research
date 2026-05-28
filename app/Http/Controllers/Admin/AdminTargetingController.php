<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Profile;
use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTargetingController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');

        $query = Survey::with('client')->orderByDesc('created_at');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $surveys = $query->get([
            'id', 'title', 'status', 'is_active', 'client_id',
            'target_gender', 'target_age_band', 'target_location',
            'target_language', 'target_employment', 'target_income_band',
            'target_traits', 'exclude_traits',
        ]);

        // Aggregate unique trait keys from all profiles for the filter UI
        $profiles = Profile::whereNotNull('enriched_attributes')->get(['enriched_attributes']);
        $availableTraits = [];
        foreach ($profiles as $profile) {
            if ($profile->enriched_attributes) {
                foreach ($profile->enriched_attributes as $key => $val) {
                    if (! in_array($key, $availableTraits)) {
                        $availableTraits[] = $key;
                    }
                }
            }
        }
        sort($availableTraits);

        $clients = Client::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/AdminTargeting', [
            'surveys' => $surveys,
            'available_traits' => $availableTraits,
            'clients' => $clients,
            'selected_client_id' => $clientId,
        ]);
    }

    public function update(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'target_gender' => ['nullable', 'string'],
            'target_age_band' => ['nullable', 'string'],
            'target_location' => ['nullable', 'string'],
            'target_language' => ['nullable', 'string'],
            'target_employment' => ['nullable', 'string'],
            'target_income_band' => ['nullable', 'string'],
            'target_traits' => ['nullable', 'array'],
            'exclude_traits' => ['nullable', 'array'],
        ]);

        $survey->update($validated);

        return redirect()->back()->with('success', 'Targeting settings updated successfully.');
    }

    public function estimate(Request $request)
    {
        $query = Profile::query()
            ->where('is_complete', true)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('target_gender')) {
            $query->where('gender', $request->target_gender);
        }
        if ($request->filled('target_age_band')) {
            $query->where('age_band', $request->target_age_band);
        }
        if ($request->filled('target_location')) {
            $query->where('location', 'like', '%'.$request->target_location.'%');
        }
        if ($request->filled('target_language')) {
            $query->where('language', 'like', '%'.$request->target_language.'%');
        }
        if ($request->filled('target_employment')) {
            $query->where('employment', $request->target_employment);
        }
        if ($request->filled('target_income_band')) {
            $query->where('income_band', $request->target_income_band);
        }

        // Advanced Trait-based estimation
        if ($request->filled('target_traits') && is_array($request->target_traits)) {
            foreach ($request->target_traits as $rule) {
                $key = $rule['key'] ?? null;
                $value = $rule['value'] ?? null;
                $op = $rule['operator'] ?? 'eq';
                if (! $key) {
                    continue;
                }

                if ($op === 'eq') {
                    $query->where("enriched_attributes->$key", $value);
                } elseif ($op === 'neq') {
                    $query->where("enriched_attributes->$key", '!=', $value);
                }
            }
        }

        if ($request->filled('exclude_traits') && is_array($request->exclude_traits)) {
            foreach ($request->exclude_traits as $rule) {
                $key = $rule['key'] ?? null;
                $value = $rule['value'] ?? null;
                $op = $rule['operator'] ?? 'eq';
                if (! $key) {
                    continue;
                }

                if ($op === 'eq') {
                    $query->where(function ($q) use ($key, $value) {
                        $q->whereNull("enriched_attributes->$key")
                            ->orWhere("enriched_attributes->$key", '!=', $value);
                    });
                } elseif ($op === 'neq') {
                    $query->where("enriched_attributes->$key", $value);
                }
            }
        }

        $matchedCount = $query->count();
        $totalCount = Profile::where('is_complete', true)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->count();

        $percentage = $totalCount > 0 ? round(($matchedCount / $totalCount) * 100, 1) : 0;

        return response()->json([
            'matched' => $matchedCount,
            'total' => $totalCount,
            'percentage' => $percentage,
        ]);
    }
}
