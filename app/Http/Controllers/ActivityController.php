<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    /**
     * Affiche la liste des activités (Admin seulement).
     * Permet le filtrage et le tri.
     */
    public function index(Request $request): JsonResponse
    {
        // La protection par middleware 'admin' est essentielle ici.
        $activities = Activity::with('user:id,name,email');
        
        // Optionnel : Filtrer par utilisateur
        if ($userId = $request->query('user_id')) {
            $activities->where('user_id', $userId);
        }
        
        // Optionnel : Filtrer par type de modèle ou d'action
        if ($action = $request->query('action')) {
            $activities->where('action', $action);
        }

        // Tri par défaut (plus récent en premier)
        $activities->latest(); 

        return response()->json($activities->paginate(50));
    }

    /**
     * Affiche les détails d'une activité.
     */
    public function show(Activity $activity): JsonResponse
    {
        return response()->json($activity->load('user:id,name,email'));
    }
}