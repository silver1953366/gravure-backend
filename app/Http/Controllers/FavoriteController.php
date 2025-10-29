<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Affiche une liste de tous les favoris de l'utilisateur authentifié.
     */
    public function index()
    {
        $favorites = Auth::user()->favorites()->with('quote')->get();
        return response()->json($favorites);
    }

    /**
     * Ajoute un devis aux favoris.
     */
    public function store(Request $request)
    {
        $request->validate([
            'quote_id' => 'required|exists:quotes,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'quote_id' => $request->quote_id,
        ]);

        return response()->json($favorite, 201);
    }

    /**
     * Affiche un favori spécifique.
     */
    public function show(Favorite $favorite)
    {
        if ($favorite->user_id !== Auth::id()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        return response()->json($favorite->load('quote'));
    }

    /**
     * Supprime un favori.
     */
    public function destroy(Favorite $favorite)
    {
        if ($favorite->user_id !== Auth::id()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        $favorite->delete();

        return response()->json(['message' => 'Favori supprimé avec succès']);
    }
}