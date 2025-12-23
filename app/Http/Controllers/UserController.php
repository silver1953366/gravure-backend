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
     * NOTIFICATIONS HELPER : Récupère uniquement les utilisateurs de type 'client'.
     * Cette méthode est appelée par votre service Angular : admin/users/all
     */
    public function getAllClients(): JsonResponse
    {
        // On récupère les clients pour alimenter le menu déroulant du frontend
        $clients = User::where('role', User::ROLE_CLIENT)
            ->select('id', 'name', 'email')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $clients
        ]);
    }

    /**
     * Liste complète des utilisateurs (Gestion Admin).
     */
    public function index(): JsonResponse
    {
        // On récupère tout pour le tableau de gestion des utilisateurs
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
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_CONTROLLER, User::ROLE_CLIENT])],
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
     * Affiche les détails d'un utilisateur spécifique.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    /**
     * Met à jour les informations d'un utilisateur.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 
                'required', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8',
            'role' => [
                'sometimes', 
                'required', 
                'string', 
                Rule::in([User::ROLE_ADMIN, User::ROLE_CONTROLLER, User::ROLE_CLIENT])
            ],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // Ne pas écraser avec du vide si non fourni
        }
        
        $user->update($data);

        return response()->json($user);
    }

    /**
     * Supprime un utilisateur de la base de données.
     */
    public function destroy(User $user): JsonResponse
    {
        // Sécurité : On peut ajouter ici une vérification pour empêcher 
        // la suppression de son propre compte admin.
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}