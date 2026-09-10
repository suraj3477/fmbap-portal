<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display a listing of all registered users.
     */
    public function index(Request $request): Response
    {
        $users = User::select('id', 'name', 'email', 'role', 'state', 'is_approved', 'created_at')
            ->orderBy('is_approved', 'asc') // Pending users first
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    /**
     * Toggle user account approval status.
     */
    public function toggleApprove(User $user)
    {
        // Prevent Super Admin from locking themselves out
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot alter your own approval status.');
        }

        $user->update([
            'is_approved' => !$user->is_approved,
        ]);

        return back()->with('message', 'User approval status updated successfully.');
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:super_admin,board_official,state_official,mojs_official',
        ]);

        // Prevent Super Admin from demoting themselves
        if ($user->id === auth()->id() && $request->role !== 'super_admin') {
            return back()->with('error', 'You cannot demote your own Super Admin role.');
        }

        $user->update([
            'role' => $request->role,
        ]);

        return back()->with('message', 'User role updated successfully.');
    }
}