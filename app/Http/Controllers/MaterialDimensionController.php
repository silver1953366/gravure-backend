<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaterialDimension;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse; 
use Illuminate\Validation\ValidationException; 
use App\Traits\LogsActivity; 
use Illuminate\Support\Facades\Log; // Ajout pour le logging

class MaterialDimensionController extends Controller
{
    use LogsActivity; 

    /**
     * Affiche la liste des entrées du catalogue de prix, avec les relations.
     */
    public function index()
    {
        $dimensions = MaterialDimension::with(['material', 'shape', 'category'])
                                       ->orderBy('material_id')
                                       ->orderBy('shape_id')
                                       ->get();
        return response()->json($dimensions);
    }

    /**
     * Crée une nouvelle entrée dans le catalogue de prix.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'material_id' => 'required|exists:materials,id',
                'shape_id' => 'required|exists:shapes,id',
                'category_id' => 'required|exists:categories,id', 
                
                'dimension_label' => [
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('material_dimensions')->where(function ($query) use ($request) {
                        return $query->where('material_id', $request->material_id)
                                     ->where('shape_id', $request->shape_id);
                    }),
                ],
                'unit_price_fcfa' => 'required|numeric|min:0.01',
                'is_active' => 'sometimes|boolean',
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $dimension = MaterialDimension::create($data);
            
            $this->logActivity('material_dimension_created', ['id' => $dimension->id], $dimension);

            return response()->json($dimension->load(['material', 'shape', 'category']), 201);

        } catch (\Exception $e) {
            Log::error("Erreur d'insertion MaterialDimension: " . $e->getMessage(), ['data' => $data]);
            
            return response()->json([
                'message' => 'Une erreur interne est survenue lors de l\'enregistrement en base de données.',
                'error_details' => app()->environment('local') ? $e->getMessage() : null 
            ], 500);
        }
    }

    /**
     * Affiche une entrée spécifique du catalogue.
     */
    public function show(MaterialDimension $materialDimension)
    {
        return response()->json($materialDimension->load(['material', 'shape', 'category']));
    }

    /**
     * Met à jour une entrée existante du catalogue.
     */
    public function update(Request $request, MaterialDimension $materialDimension)
    {
        try {
            $data = $request->validate([
                'material_id' => 'sometimes|required|exists:materials,id',
                'shape_id' => 'sometimes|required|exists:shapes,id',
                'category_id' => 'sometimes|required|exists:categories,id',

                'dimension_label' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('material_dimensions')->ignore($materialDimension->id)->where(function ($query) use ($request, $materialDimension) {
                        return $query->where('material_id', $request->material_id ?? $materialDimension->material_id)
                                     ->where('shape_id', $request->shape_id ?? $materialDimension->shape_id);
                    }),
                ],
                'unit_price_fcfa' => 'sometimes|required|numeric|min:0.01', 
                'is_active' => 'sometimes|boolean',
            ]);

            $materialDimension->update($data);
            return response()->json($materialDimension->load(['material', 'shape', 'category']));
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erreur de validation des données.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erreur de mise à jour MaterialDimension: " . $e->getMessage());
            return response()->json(['message' => 'Erreur interne lors de la mise à jour.', 'error_details' => app()->environment('local') ? $e->getMessage() : null], 500);
        }
    }

    /**
     * Supprime une entrée du catalogue de prix.
     */
    public function destroy(MaterialDimension $materialDimension)
    {
        // 1. VÉRIFICATION DE L'INVENTAIRE : Bloque la suppression si l'article est en stock.
        // On suppose que la relation est nommée 'inventory' (ou 'inventories')
        if ($materialDimension->inventory()->exists()) { 
             return response()->json([
                 'message' => 'Impossible de supprimer cette entrée. L\'article est toujours référencé dans l\'inventaire.',
                 'details' => 'Veuillez supprimer l\'article de l\'inventaire avant de supprimer la dimension du catalogue.'
             ], 409); // 409 Conflict pour les conflits de règles métier
        }
        
        // 2. VÉRIFICATION DES DEVIS (Votre logique existante)
        if ($materialDimension->quotes()->exists()) {
             return response()->json([
                 'message' => 'Impossible de supprimer cette entrée car elle est déjà utilisée dans des devis clients.',
                 'details' => 'Les devis clients (historiques) dépendent de cette référence de prix.'
             ], 409);
        }

        // 3. Suppression
        try {
            $this->logActivity('material_dimension_deleted', ['id' => $materialDimension->id]);
            $materialDimension->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error("Erreur de suppression MaterialDimension: " . $e->getMessage());
            return response()->json(['message' => 'Erreur interne lors de la suppression.', 'error_details' => app()->environment('local') ? $e->getMessage() : null], 500);
        }
    }
}
