<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsController
{
    /**
     * Gère une requête entrante, vérifiant si l'utilisateur est 'admin' ou 'controller'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Vérification de l'authentification
        if (!Auth::check()) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $userRole = Auth::user()->role;
        
        // 2. Vérification des rôles (Admin ou Controller)
        if ($userRole === 'admin' || $userRole === 'controller') {
            return $next($request);
        }

        // 3. Accès refusé
        return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs et contrôleurs peuvent voir l\'inventaire.'], 403);
    }
}