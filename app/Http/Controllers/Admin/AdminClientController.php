<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class AdminClientController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'description' => 'nullable|string',
        ]);

        Client::create($validated);

        return redirect()->back()->with('success', 'Client created successfully.');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,'.$client->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $client->update($validated);

        return redirect()->back()->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->back()->with('success', 'Client deleted.');
    }

    public function api()
    {
        return response()->json(Client::where('is_active', true)->get(['id', 'name']));
    }
}
