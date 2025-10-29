<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Gère l'inscription d'un nouvel utilisateur.
     */
    public function register(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client' // Attribution du rôle par défaut
        ]);

        // NOUVEAU: Génération du jeton après l'inscription
        $token = $user->createToken('auth-token')->plainTextToken;

        return response([
            'user' => $user,
            'access_token' => $token, // Renvoi immédiat du jeton d'accès
            'message' => 'Inscription réussie et connexion automatique.'
        ], 201);
    }

    /**
     * Gère la connexion de l'utilisateur.
     */
    public function login(Request $request): Response
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis ne correspondent pas à nos enregistrements.'],
            ]);
        }

   // Suppression des anciens jetons pour des raisons de sécurité (optionnel mais recommandé)
        // $user->tokens()->delete(); 

        $token = $user->createToken('auth-token')->plainTextToken;

        return response([
            'user' => $user,
            'access_token' => $token
        ], 200);
    }
    
    /**
     * Gère la déconnexion de l'utilisateur (révoque le jeton actuel).
     */
    public function logout(Request $request): Response
    {
       // Révoque uniquement le jeton actuel pour permettre à l'utilisateur de rester connecté ailleurs
        $request->user()->currentAccessToken()->delete(); 

        // Si l'on souhaite révoquer TOUS les jetons pour cet utilisateur sur tous les appareils :
        // $request->user()->tokens()->delete(); 

        return response(['message' => 'Déconnexion réussie.'], 200);
    }
}
?>
