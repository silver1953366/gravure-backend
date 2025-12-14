<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaterialDimension;
use Illuminate\Http\Request; // 👈 Assurez-vous que Request est importé
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse; 
use Illuminate\Validation\ValidationException; 
use App\Traits\LogsActivity; 
use Illuminate\Support\Facades\Log;

class MaterialDimensionController extends Controller
{
    use LogsActivity; 

    /**
     * Affiche la liste des entrées du catalogue de prix, avec la possibilité de filtrer 
     * par material_id et shape_id (pour le Front-end).
     * Route: GET /api/catalog/dimensions?material_id=X&shape_id=Y
     */
    public function index(Request $request)
    {
        // 1. Lire les paramètres de requête envoyés par Angular
        $materialId = $request->query('material_id'); 
        $shapeId = $request->query('shape_id');

        // 2. Initialiser la requête de base avec les relations nécessaires
        $query = MaterialDimension::with(['material', 'shape', 'category']);

        // 3. Appliquer le filtre sur le material_id s'il est présent
        if ($materialId) {
            $query->where('material_id', $materialId);
            Log::info("Filtrage des dimensions: Material ID = $materialId");
        }
        
        // 4. Appliquer le filtre sur le shape_id s'il est présent
        if ($shapeId) {
            $query->where('shape_id', $shapeId);
            Log::info("Filtrage des dimensions: Shape ID = $shapeId");
        }
        
        // 5. Exécuter la requête
        $dimensions = $query
            ->orderBy('material_id')
            ->orderBy('shape_id')
            ->orderBy('dimension_label')
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
        // 1. VÉRIFICATION DE L'INVENTAIRE
        if (method_exists($materialDimension, 'inventory') && $materialDimension->inventory()->exists()) {
             return response()->json([
                'message' => 'Impossible de supprimer cette entrée. L\'article est toujours référencé dans l\'inventaire.',
                'details' => 'Veuillez supprimer l\'article de l\'inventaire avant de supprimer la dimension du catalogue.'
             ], 409); // 409 Conflict
        }
        
        // 2. VÉRIFICATION DES DEVIS
        if (method_exists($materialDimension, 'quotes') && $materialDimension->quotes()->exists()) {
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