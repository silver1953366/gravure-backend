<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\LogsActivity;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    use LogsActivity;
    
    // ... (index et show non modifiées)
    
    /**
     * Affiche la liste complète de l'inventaire avec les relations nécessaires.
     */
    public function index(Request $request): JsonResponse
    {
        $inventory = Inventory::with(['materialDimension.material', 'materialDimension.shape'])
            ->latest('updated_at')
            ->get();
            
        return response()->json($inventory);
    }

    /**
     * Affiche les détails d'un article d'inventaire.
     */
    public function show(Inventory $inventory): JsonResponse
    {
        return response()->json($inventory->load(['materialDimension.material', 'materialDimension.shape']));
    }

    /**
     * Crée une nouvelle entrée d'inventaire (Admin seulement).
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validation des données
        try {
            $validatedData = $request->validate([
                'material_dimension_id' => 'required|unique:inventories,material_dimension_id|exists:material_dimensions,id',
                'stock_quantity' => 'required|integer|min:0',
                'minimum_threshold' => 'required|integer|min:0',
                'reserved_quantity' => 'sometimes|integer|min:0', 
                'price_per_unit' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            // Interception locale de la validation pour garantir le 422
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        }

        $data = $validatedData;
        
        if (!isset($data['reserved_quantity'])) {
            $data['reserved_quantity'] = 0;
        }
        
        $data['last_restock_at'] = now();

        // 2. Tentative de création avec gestion des erreurs d'insertion (500)
        try {
            $inventory = Inventory::create($data);
        } catch (\Exception $e) {
            Log::error("Erreur de création d'inventaire: " . $e->getMessage(), ['data' => $data]);
            
            return response()->json([
                'message' => 'Erreur lors de l\'insertion en base de données.',
                'error_details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
        
        $this->logActivity('inventory_item_created', ['material_dimension_id' => $inventory->material_dimension_id], $inventory);

        return response()->json($inventory, 201);
    }

    /**
     * Met à jour le stock (Admin seulement).
     */
    public function update(Request $request, Inventory $inventory): JsonResponse
    {
        try {
            // 1. Validation des données. Si elle échoue (ex: stock_quantity < 0), 
            // la ValidationException est lancée et capturée dans le bloc suivant.
            $data = $request->validate([
                'stock_quantity' => 'sometimes|required|integer|min:0', // <-- C'EST ICI QUE LE MIN:0 EST TESTÉ
                'reserved_quantity' => 'sometimes|required|integer|min:0',
                'minimum_threshold' => 'sometimes|required|integer|min:0',
                'price_per_unit' => 'sometimes|required|numeric|min:0',
            ]);

            $oldQuantity = $inventory->stock_quantity;

            // 2. Logique de mise à jour (si validation réussie)
            if (isset($data['stock_quantity']) && (int)$data['stock_quantity'] !== $oldQuantity) {
                $data['last_restock_at'] = now();
            }

            // 3. Mise à jour en Base de Données
            $inventory->update($data);

            // 4. Log
            if (isset($data['stock_quantity']) && (int)$data['stock_quantity'] !== $oldQuantity) {
                $this->logActivity('inventory_stock_updated', [
                    'material_dimension_id' => $inventory->material_dimension_id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $inventory->stock_quantity,
                ], $inventory);
            }

            $inventory->load(['materialDimension.material', 'materialDimension.shape']);

            // 5. Succès
            return response()->json($inventory, 200);

        } catch (ValidationException $e) {
            // 6. GESTION DES ERREURS DE VALIDATION (stock négatif, etc.) -> STATUT 422
            Log::warning("Erreur de validation lors de la mise à jour d'inventaire.", ['inventory_id' => $inventory->id, 'errors' => $e->errors()]);

            return response()->json([
                'message' => 'Les données fournies ne sont pas valides. Veuillez vérifier que les quantités ne sont pas négatives.',
                'errors' => $e->errors(),
            ], 422); // <-- GARANTIT LA RÉPONSE 422

        } catch (\Exception $e) {
            // 7. Gestion des erreurs de Base de Données (Erreur 500)
            Log::error("Erreur inattendue de mise à jour d'inventaire: " . $e->getMessage(), ['inventory_id' => $inventory->id, 'data' => $request->all()]);
            
            return response()->json([
                'message' => 'Erreur inattendue du serveur lors de la mise à jour de l\'inventaire.',
                'error_details' => app()->environment('local') ? $e->getMessage() : null 
            ], 500);
        }
    }

    /**
     * Supprime une entrée d'inventaire (Admin seulement).
     */
    public function destroy(Inventory $inventory): JsonResponse
    {
        $this->logActivity('inventory_item_deleted', ['material_dimension_id' => $inventory->material_dimension_id]);
        $inventory->delete();
        return response()->json(['message' => 'Article d\'inventaire supprimé.']);
    }
}
