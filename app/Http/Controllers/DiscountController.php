<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// NOTE : J'ai déplacé le namespace à la racine 'App\Http\Controllers' 
// si votre fichier n'était pas dans un sous-dossier 'Admin', 
// mais je le remets dans 'Admin' car l'Admin namespace est meilleur pour la sécurité/organisation.
// Si vous n'avez pas de sous-dossier Admin, vous devrez le déplacer.
// Pour la vérification, je vais utiliser le namespace que vous m'avez fourni initialement.

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    /**
     * Liste tous les codes de réduction (y compris inactifs/expirés) pour l'administration.
     */
    public function index()
    {
        // Cette méthode doit être utilisée uniquement par l'Admin pour la gestion interne.
        $discounts = Discount::orderBy('created_at', 'desc')->get();
        return response()->json($discounts);
    }

    /**
     * Crée un nouveau code de réduction.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $discount = Discount::create($data);
        return response()->json($discount, 201);
    }

    /**
     * Affiche un code de réduction spécifique.
     */
    public function show(Discount $discount)
    {
        return response()->json($discount);
    }

    /**
     * Met à jour un code de réduction existant.
     */
    public function update(Request $request, Discount $discount)
    {
        $data = $request->validate($this->rules($discount->id));

        $discount->update($data);
        return response()->json($discount);
    }

    /**
     * Supprime un code de réduction.
     */
    public function destroy(Discount $discount)
    {
        // VÉRIFICATION DE L'INTÉGRITÉ: Empêche la suppression si le code a été utilisé sur des commandes finalisées.
        if (method_exists($discount, 'orders') && $discount->orders()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce code car il a déjà été utilisé sur des commandes.'
            ], 409); // 409 Conflict
        }
        
        $discount->delete();
        return response()->json(null, 204);
    }

    /**
     * Définit les règles de validation pour la création et la mise à jour.
     */
    protected function rules($discountId = null)
    {
        return [
            // Le code doit être unique (sauf pour l'ID actuel en cas de mise à jour)
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('discounts', 'code')->ignore($discountId),
            ],
            // Type : 'percentage' ou 'fixed'
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            // La valeur (ex: 10 pour 10% ou 5000 pour 5000 FCFA)
            'value' => 'required|numeric|min:0.01', 
            
            // Conditions d'application
            'min_purchase_fcfa' => 'nullable|numeric|min:0', // Montant minimum de commande
            'max_usage' => 'nullable|integer|min:1', // Nombre maximum d'utilisations total
            'max_usage_per_user' => 'nullable|integer|min:1', // Nombre maximum d'utilisations par utilisateur
            
            // Validité
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date|after_or_equal:today', // Date d'expiration
        ];
    }
}