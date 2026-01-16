<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * PROFIL PERSONNEL : Récupère les infos de l'utilisateur connecté.
     * GET /api/user
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * MISE À JOUR DU PROFIL PERSONNEL (Utilisé par le profil client Angular)
     * PUT /api/user
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:25',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $user->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'user'    => $user
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur updateProfile: " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour.'], 500);
        }
    }

    /**
     * LISTE GLOBALE : Uniquement pour l'admin.
     * GET /api/users
     */
    public function index(): JsonResponse
    {
        $users = User::latest()->get(['id', 'name', 'email', 'role', 'phone', 'address', 'created_at']);
        return response()->json($users);
    }

    /**
     * RÉCUPÉRER TOUS LES CLIENTS (Pour les menus déroulants/filtres)
     * GET /api/admin/users/all-clients
     */
    public function getAllClients(): JsonResponse
    {
        $clients = User::where('role', User::ROLE_CLIENT)
            ->select('id', 'name', 'email')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $clients
        ]);
    }

    /**
     * CRÉATION : Par un administrateur.
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_CONTROLLER, User::ROLE_CLIENT])],
            'phone'    => 'nullable|string',
            'address'  => 'nullable|string'
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'phone'    => $data['phone'] ?? null,
            'address'  => $data['address'] ?? null,
        ]);

        return response()->json($user, 201);
    }

    /**
     * DÉTAILS : Récupérer un utilisateur par son ID.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    /**
     * MISE À JOUR ADMIN : Modifier n'importe quel utilisateur via son ID.
     * PUT /api/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role'     => ['sometimes', Rule::in([User::ROLE_ADMIN, User::ROLE_CONTROLLER, User::ROLE_CLIENT])],
            'phone'    => 'nullable|string',
            'address'  => 'nullable|string'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return response()->json($user);
    }

    /**
     * SUPPRESSION
     */
    public function destroy(User $user): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}