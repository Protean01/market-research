<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $showDeleted = $request->boolean('deleted');

        $query = $showDeleted
            ? User::onlyTrashed()->with(['profile', 'wallet'])
            : User::query()->with(['profile', 'wallet']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/AdminUsers', [
            'users'       => $users,
            'filters'     => $request->only(['search', 'deleted']),
            'showDeleted' => $showDeleted,
        ]);
    }

    public function toggleStatus(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with('success', 'User status updated successfully.');
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,researcher,member',
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User role updated.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->phone_number === config('app.bypass_phone')) {
            return back()->with('error', 'This admin account is protected.');
        }

        // Revoke active sessions/tokens — wallet and profile are preserved for restore
        $user->tokens()->delete();
        $user->notifications()->delete();

        $user->delete(); // soft delete via SoftDeletes trait

        return back()->with('success', 'User soft-deleted. They can be restored from the Deleted Users view.');
    }

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', "User {$user->name} has been restored.");
    }
}
