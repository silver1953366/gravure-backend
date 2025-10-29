<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Material;
use App\Models\Shape;
use App\Models\Discount;
use App\Models\MaterialDimension; 
use App\Models\Attachment; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\LogsActivity;
use Illuminate\Auth\Access\AuthorizationException;

class QuoteController extends Controller
{
    use LogsActivity, AuthorizesRequests;

    // --- Constantes (À déplacer idéalement dans le modèle Quote) ---
    // Assurez-vous que ces constantes existent dans App\Models\Quote (STATUS_SENT, STATUS_DRAFT, ROLE_CLIENT)
    // const STATUS_DRAFT = 'draft'; 
    // const STATUS_SENT = 'sent';
    // const ROLE_CLIENT = 'client';

    /**
     * Génère une référence de devis unique (ex: DEV-2024-000001).
     */
    private function generateReference(): string
    {
        $datePrefix = 'DEV-' . now()->year . '-';
        $lastId = Quote::max('id') ?? 0; 
        $nextNumber = $lastId + 1;
        return $datePrefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * LOGIQUE CLÉ: Calcule le prix total basé sur le prix unitaire et la quantité.
     */
    private function calculateTotalPrice(float $unit_price_fcfa, int $quantity, ?Discount $discount): array
    {
        $base_price = $unit_price_fcfa * $quantity;
        $discount_amount = 0.0;

        // Application de la logique de rabais
        if ($discount && $discount->is_active && $base_price >= $discount->min_order_amount) {
            
            if ($discount->type === 'percentage') {
                $discount_amount = $base_price * ($discount->value / 100);
            } elseif ($discount->type === 'fixed') {
                $discount_amount = $discount->value;
            }

            // Le montant du rabais ne peut pas excéder le prix de base
            $discount_amount = min($discount_amount, $base_price);
        }

        $final_price = $base_price - $discount_amount;
        
        // Arrondi des prix
        $base_price = round($base_price, 2);
        $discount_amount = round($discount_amount, 2);
        $final_price = round($final_price, 2);

        $snapshot = [
            'unit_price_fcfa' => $unit_price_fcfa,
            'quantity' => $quantity,
            'discount_used' => $discount ? $discount->name : 'Aucun',
            'discount_details' => $discount ? $discount->toArray() : null,
        ];

        return [
            'base_price_fcfa' => $base_price,
            'discount_amount_fcfa' => $discount_amount,
            'final_price_fcfa' => $final_price,
            'details_snapshot' => $snapshot,
        ];
    }


    /**
     * Point d'API pour estimer le prix sans enregistrer le devis.
     */
    public function estimate(Request $request)
    {
        try {
            $data = $request->validate([
                'material_dimension_id' => 'required_without:special_price_fcfa|nullable|exists:material_dimensions,id',
                'special_price_fcfa' => 'required_without:material_dimension_id|nullable|numeric|min:0.01',
                'quantity' => 'required|integer|min:1',
                'material_id' => 'required|exists:materials,id',
                'shape_id' => 'required|exists:shapes,id',
                'dimension_label' => 'nullable|string|max:255', 
                'discount_id' => 'nullable|exists:discounts,id',
            ]);

            $unit_price = 0.0;
            $price_source = 'special';
            $material_dimension_id = null;
            $dimension_label = $data['dimension_label'] ?? 'Commande Spéciale';
            $material_id = $data['material_id'];
            $shape_id = $data['shape_id'];
            
            // Détermination du prix unitaire
            if (isset($data['material_dimension_id'])) {
                $md = MaterialDimension::findOrFail($data['material_dimension_id']);
                if ($md->material_id != $material_id || $md->shape_id != $shape_id) {
                    throw new \Exception("Incohérence entre l'ID de dimension/prix (catalogue) et le Matériau/Forme spécifié.");
                }
                $unit_price = $md->unit_price_fcfa;
                $price_source = 'standard';
                $material_dimension_id = $md->id;
                $dimension_label = $md->dimension_label; 
            } elseif (isset($data['special_price_fcfa'])) {
                $unit_price = $data['special_price_fcfa'];
                $price_source = 'special';
            } else {
                throw new \Exception("Une source de prix valide est manquante.");
            }

            $discount = $data['discount_id'] ? Discount::find($data['discount_id']) : null;
            $calculation = $this->calculateTotalPrice($unit_price, $data['quantity'], $discount);
            
            return response()->json([
                'unit_price_fcfa' => $unit_price,
                'quantity' => $data['quantity'],
                'price_source' => $price_source,
                'material_id' => $material_id,
                'shape_id' => $shape_id,
                'material_dimension_id' => $material_dimension_id,
                'material' => Material::find($material_id)->name ?? 'N/A',
                'shape' => Shape::find($shape_id)->name ?? 'N/A',
                'dimension_label' => $dimension_label,
                'cost_details' => $calculation,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur interne lors de l\'estimation du prix.', 'debug' => $e->getMessage()], 500);
        }
    }


    /**
     * Enregistre le devis initial dans la base de données (côté client).
     */
    public function store(Request $request)
    {
        try {
            // Applique QuotePolicy@create
            $this->authorize('create', Quote::class); 

            // 1. Exécuter l'estimation pour la validation et le calcul
            // estimate() gère déjà son propre 422 si les champs produit sont invalides.
            $estimationResponse = $this->estimate($request);
            if ($estimationResponse->getStatusCode() !== 200) { return $estimationResponse; }

            $estimationData = json_decode($estimationResponse->getContent(), true);
            $calculation = $estimationData['cost_details'];

            // 2. Valider les détails du client et les pièces jointes (Deuxième validation)
            $request->validate([
                // NOTE : Utiliser une simple validation lève une ValidationException
                'client_details' => 'required|array',
                'client_details.name' => 'required|string|max:255',
                'client_details.email' => 'required|email|max:255',
                'client_details.phone' => 'nullable|string|max:50',
                'customization_details' => 'nullable|array', 
                'files' => 'nullable|array', 
                'files.*' => 'nullable|integer|exists:attachments,id', 
            ]);
            
            // 3. Création du devis DANS une transaction
            return DB::transaction(function () use ($request, $estimationData, $calculation) {
                
                $quote = Quote::create([
                    'reference' => $this->generateReference(),
                    'user_id' => auth()->id(), 
                    'client_details' => $request->input('client_details'),
                    'material_id' => $estimationData['material_id'],
                    'shape_id' => $estimationData['shape_id'],
                    'material_dimension_id' => $estimationData['material_dimension_id'],
                    'quantity' => $estimationData['quantity'],
                    'unit_price_fcfa' => $estimationData['unit_price_fcfa'],
                    'price_source' => $estimationData['price_source'],
                    'dimension_label' => $estimationData['dimension_label'],
                    'discount_id' => $request->input('discount_id'),
                    'base_price_fcfa' => $calculation['base_price_fcfa'],
                    'discount_amount_fcfa' => $calculation['discount_amount_fcfa'],
                    'final_price_fcfa' => $calculation['final_price_fcfa'],
                    'details_snapshot' => array_merge($calculation['details_snapshot'], [
                        'customization' => $request->input('customization_details') ?? [],
                        'files_references' => $request->input('files') ?? [], 
                    ]),
                    'status' => Quote::STATUS_SENT, 
                ]);
                
                // 4. Lier les pièces jointes
                $file_ids = $request->input('files', []);
                if (!empty($file_ids)) {
                    Attachment::whereIn('id', $file_ids)
                        ->where('user_id', auth()->id()) 
                        ->whereNull('quote_id')
                        ->update(['quote_id' => $quote->id]);
                }

                $this->logActivity('quote_created', [
                    'client' => $quote->client_details['name'] ?? 'Inconnu',
                    'final_price' => $quote->final_price_fcfa,
                ], $quote);

                return response()->json([
                    'message' => 'Demande de devis enregistrée. Un administrateur va la traiter.',
                    'quote' => $quote->load(['material', 'shape', 'materialDimension', 'discount', 'attachments']) 
                ], 201);
            });
        
        } catch (AuthorizationException $e) {
            // Gère l'échec de la Policy (403)
            return response()->json(['error' => $e->getMessage(), 'code' => 'AUTHORIZATION_FAILED'], 403);
        } catch (ValidationException $e) {
            // Gère l'échec de la deuxième validation (422)
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Gère toute autre erreur, y compris les erreurs de la transaction (500)
            return response()->json([
                'error' => 'Échec de l\'enregistrement du devis (Erreur Interne).', 
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour les détails du devis (Admin/Client).
     */
    public function update(Request $request, Quote $quote)
    {
        try {
            // 1. Applique QuotePolicy@update
            $this->authorize('update', $quote); 
            
            // Validation des champs Admin/Statut
            $validationRules = [
                'status' => 'sometimes|required|in:draft,sent,calculated,ordered,rejected,archived', 
                'final_price_fcfa' => 'sometimes|required|numeric|min:0',
                'admin_note' => 'nullable|string',
            ];

            // Vérifie si la modification est faite par le propriétaire sur un devis DRAFT
            $isClientModification = $quote->status === Quote::STATUS_DRAFT && $request->user()->id === $quote->user_id;

            if ($isClientModification) {
                $validationRules = array_merge($validationRules, [
                    'material_dimension_id' => 'sometimes|nullable|exists:material_dimensions,id',
                    'quantity' => 'sometimes|required|integer|min:1',
                    'discount_id' => 'sometimes|nullable|exists:discounts,id',
                    // Inclure ici tous les champs de configuration que le client peut modifier
                ]);
            }

            $data = $request->validate($validationRules);

            // Début de la transaction
            DB::beginTransaction();

            $updateData = $data; 

            // -----------------------------------------------------
            // LOGIQUE CLÉ : RECALCUL 
            // -----------------------------------------------------
            if ($isClientModification && (isset($data['quantity']) || isset($data['material_dimension_id']) || isset($data['discount_id']))) {

                $newQuantity = $data['quantity'] ?? $quote->quantity;
                $newDiscountId = $data['discount_id'] ?? $quote->discount_id;
                $newMaterialDimensionId = $data['material_dimension_id'] ?? $quote->material_dimension_id;

                $md = MaterialDimension::findOrFail($newMaterialDimensionId);
                $discount = $newDiscountId ? Discount::find($newDiscountId) : null;
                
                $calculation = $this->calculateTotalPrice($md->unit_price_fcfa, $newQuantity, $discount);
                
                // Mise à jour des champs de configuration et de prix avec les valeurs recalculées
                $updateData['quantity'] = $newQuantity;
                $updateData['material_dimension_id'] = $newMaterialDimensionId;
                $updateData['discount_id'] = $newDiscountId;
                $updateData['unit_price_fcfa'] = $md->unit_price_fcfa; 
                $updateData['dimension_label'] = $md->dimension_label; 
                $updateData['base_price_fcfa'] = $calculation['base_price_fcfa'];
                $updateData['discount_amount_fcfa'] = $calculation['discount_amount_fcfa'];
                $updateData['final_price_fcfa'] = $calculation['final_price_fcfa'];
                $updateData['details_snapshot'] = array_merge($quote->details_snapshot ?? [], $calculation['details_snapshot']);
                
                if (!isset($data['admin_note'])) {
                    unset($updateData['admin_note']);
                }
            }
            // -----------------------------------------------------

            // Exécution de la mise à jour
            $quote->update($updateData);

            DB::commit();

            // Log de l'activité
            $this->logActivity('quote_updated', [
                'status' => $quote->status,
                'final_price' => $quote->final_price_fcfa,
            ], $quote);

            return response()->json([
                'message' => 'Devis mis à jour avec succès.',
                'quote' => $quote->load(['material', 'shape', 'materialDimension', 'discount', 'attachments'])
            ]);

        } catch (AuthorizationException $e) {
            // Gère spécifiquement l'erreur de Policy (403 Forbidden)
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(), 
                'code' => 'AUTHORIZATION_FAILED'
            ], 403);

        } catch (ValidationException $e) {
            // Gère les erreurs de validation (422 Unprocessable Entity)
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            // Gère toutes les autres erreurs internes (500 Internal Server Error)
            DB::rollBack();
            return response()->json([
                'error' => 'Échec de la mise à jour du devis (Erreur Interne).', 
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des devis. Affiche TOUS pour l'Admin/Controller, et UNIQUEMENT les siens pour le Client.
     */
    public function index(Request $request)
    {
        // 1. Applique QuotePolicy@viewAny.
        $this->authorize('viewAny', Quote::class);

        $quotes = Quote::with(['material', 'shape', 'materialDimension', 'discount', 'user', 'attachments'])
                       ->latest();
        
        // 2. FILTRE PAR PROPRIÉTÉ (pour les clients)
        if ($request->user() && $request->user()->role === Quote::ROLE_CLIENT) {
             $quotes->where('user_id', $request->user()->id);
        }

        // --- Logique de Tri ---
        $sortBy = $request->query('sort_by', 'recent'); 

        if ($sortBy === 'oldest') {
            $quotes->orderBy('created_at', 'asc'); 
        } else {
            $quotes->orderBy('created_at', 'desc'); 
        }
        // --- Fin Logique de Tri ---

        return response()->json($quotes->get());
    }

    /**
     * Affiche les détails d'un devis.
     */
    public function show(Quote $quote, Request $request)
    {
        // 1. Applique QuotePolicy@view
        $this->authorize('view', $quote); 
        
        return response()->json($quote->load(['material', 'shape', 'materialDimension', 'discount', 'user', 'attachments']));
    }

    /**
     * Supprime un devis.
     */
    public function destroy(Quote $quote)
    {
        // 1. Applique QuotePolicy@delete
        $this->authorize('delete', $quote);

        $this->logActivity('quote_deleted', [
            'reference' => $quote->reference,
        ], $quote);

        $quote->delete();
        
        return response()->json(['message' => 'Devis supprimé avec succès.'], 200);
    }
}