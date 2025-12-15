<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session; // Importation ajoutée pour la session

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

        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Récupération de l'ID de session actuel pour le front-end (utile pour la fusion de panier)
        $sessionToken = Session::getId(); 

        return response([
            'user' => $user,
            'access_token' => $token, 
            'session_token' => $sessionToken,
            'message' => 'Inscription réussie et connexion automatique.'
        ], 201);
    }

    /**
     * Gère la connexion de l'utilisateur et renvoie le token pour la fusion de panier.
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

        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Récupérer l'ID de session ACTUEL. Ceci est crucial pour que le CartController
        // puisse identifier le panier anonyme et le fusionner.
        $sessionToken = Session::getId(); 
        
        return response([
            'user' => $user,
            'access_token' => $token,
            'session_token' => $sessionToken, // IMPORTANT pour la fusion de panier côté client/serveur
        ], 200);
    }
    
    /**
     * Gère la déconnexion de l'utilisateur (révoque le jeton actuel).
     */
    public function logout(Request $request): Response
    {
        // Révoque uniquement le jeton actuel
        $request->user()->currentAccessToken()->delete(); 

        return response(['message' => 'Déconnexion réussie.'], 200);
    }
}