<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Gestion des utilisateurs - Admin uniquement
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'shop']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::whereIn('name', ['caissiere', 'technicien'])->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|exists:roles,name',
            'shop_id' => 'nullable|exists:shops,id',
            'is_active' => 'boolean',
        ]);

        // Les caissières et techniciens doivent avoir une boutique
        if (in_array($validated['role'], ['caissiere', 'technicien']) && empty($validated['shop_id'])) {
            return back()->withInput()->with('error', 'Les caissières et techniciens doivent être assignés à une boutique.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'shop_id' => $validated['shop_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        ActivityLog::log('create', $user, null, $user->toArray(), "Création utilisateur: {$user->name}");

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'sales', 'repairsCreated', 'repairsAssigned', 'activityLogs' => function ($q) {
            $q->latest()->take(20);
        }]);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        // Empêcher la modification d'un admin par un autre admin
        if ($user->hasRole('admin') && $user->id !== auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas modifier un autre administrateur.');
        }

        $roles = Role::whereIn('name', ['caissiere', 'technicien'])->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        // Empêcher la modification d'un admin par un autre admin
        if ($user->hasRole('admin') && $user->id !== auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas modifier un autre administrateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => 'required|exists:roles,name',
            'shop_id' => 'nullable|exists:shops,id',
            'is_active' => 'boolean',
        ]);

        // Les caissières et techniciens doivent avoir une boutique
        if (in_array($validated['role'], ['caissiere', 'technicien']) && empty($validated['shop_id'])) {
            return back()->withInput()->with('error', 'Les caissières et techniciens doivent être assignés à une boutique.');
        }

        $oldValues = $user->toArray();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'shop_id' => $validated['shop_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Mettre à jour le rôle si ce n'est pas un admin
        if (!$user->hasRole('admin')) {
            $user->syncRoles([$validated['role']]);
        }

        ActivityLog::log('update', $user, $oldValues, $user->toArray(), "Modification utilisateur: {$user->name}");

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        // Empêcher la suppression d'un admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de supprimer un administrateur.');
        }

        // Empêcher la suppression de soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        ActivityLog::log('delete', $user, $user->toArray(), null, "Suppression utilisateur: {$user->name}");

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de désactiver un administrateur.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activé' : 'désactivé';
        ActivityLog::log('update', $user, null, null, "Compte {$status}: {$user->name}");

        return back()->with('success', "Compte utilisateur {$status}.");
    }
}
