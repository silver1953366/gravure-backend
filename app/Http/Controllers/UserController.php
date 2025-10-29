<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs (Admin seulement).
     */
    public function index(): JsonResponse
    {
        // La protection par middleware 'admin' sur la route garantit l'accès.
        $users = User::latest()->get(['id', 'name', 'email', 'role', 'created_at']);
        return response()->json($users);
    }

    /**
     * Crée un nouvel utilisateur (Admin seulement).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in(['admin', 'controller', 'client'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return response()->json($user, 201);
    }

    /**
     * Affiche un utilisateur.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    /**
     * Met à jour un utilisateur (y compris le rôle).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'controller', 'client'])],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);

        return response()->json($user);
    }

    /**
     * Supprime un utilisateur.
     */
    public function destroy(User $user): JsonResponse
    {
        // Empêcher l'admin de supprimer son propre compte s'il est le seul admin
        // (Logique avancée à implémenter si nécessaire)
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}