<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    // Important : Ce contrôleur doit être protégé par un middleware d'administration
    /*
    public function __construct()
    {
        $this->middleware('can:manage-catalogue'); // Assurez-vous que seul l'administrateur peut accéder
    }
    */

    /**
     * Affiche la liste de tous les matériaux.
     */
public function index()
{
    try {
        // Logique initiale
        $materials = Material::orderBy('name')->get(); 
        
        // Optionnel : Si vous aviez besoin de relations, vous pouvez les ajouter ici:
        // $materials = Material::with(['category', 'materialDimensions'])->orderBy('name')->get();
        
        return response()->json($materials);
        
    } catch (\Exception $e) {
        // En cas d'échec de la requête DB (la cause de votre ancien 502)
        // Nous renvoyons une erreur 500 avec le message de l'exception pour le débogage.
        return response()->json([
            'error' => 'Échec lors de la récupération des matériaux.', 
            'debug' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Crée un nouveau matériau.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:materials,name',
            'description' => 'nullable|string',
        ]);

        $material = Material::create($data);
        return response()->json($material, 201);
    }

    /**
     * Affiche un matériau spécifique.
     */
    public function show(Material $material)
    {
        return response()->json($material);
    }

    /**
     * Met à jour un matériau existant.
     */
    public function update(Request $request, Material $material)
    {
        $data = $request->validate([
            // La règle unique ignore l'ID actuel lors de la vérification
            'name' => 'required|string|max:255|unique:materials,name,' . $material->id,
            'description' => 'nullable|string',
        ]);

        $material->update($data);
        return response()->json($material);
    }

    /**
     * Supprime un matériau.
     * ATTENTION : Une vérification des dépendances (MaterialDimension) est nécessaire
     */
    public function destroy(Material $material)
    {
        if ($material->materialDimensions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce matériau car il est lié à des prix de catalogue existants.'
            ], 409); // 409 Conflict
        }
        
        $material->delete();
        return response()->json(null, 204);
    }

/**
 * Détermine si l'utilisateur peut convertir un devis en commande.
 * Le client peut le faire s'il est propriétaire ET si le statut est finalisé (SENT, CALCULATED).
 */
public function convert(User $user, Quote $quote): Response
{
    // L'Admin est géré par before()

    // 1. Vérification de la propriété
    if ($user->id !== $quote->user_id) {
        return Response::deny('Vous ne pouvez convertir que vos propres devis.');
    }

    // 2. Vérification du statut : Autoriser seulement SENT et CALCULATED
    $canConvert = in_array($quote->status, [
        Quote::STATUS_SENT, 
        Quote::STATUS_CALCULATED
    ]);

    return $canConvert
        ? Response::allow()
        : Response::deny('Le devis n\'est pas dans un statut permettant la conversion en commande (doit être soumis ou calculé).');
}

}
