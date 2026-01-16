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
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    use LogsActivity, AuthorizesRequests;

    /**
     * LISTE DES DEVIS (Admin & Client)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Quote::with(['material', 'shape', 'materialDimension', 'attachments']);

            if ($request->user()->role !== 'admin') {
                $query->where('user_id', $request->user()->id);
            }

            return response()->json($query->latest()->get());
        } catch (\Exception $e) {
            Log::error("Erreur Index Quote: " . $e->getMessage());
            return response()->json(['error' => 'Impossible de récupérer les devis.'], 500);
        }
    }

    /**
     * DÉTAIL D'UN DEVIS
     */
    public function show(Quote $quote): JsonResponse
    {
        try {
            if (auth()->user()->role !== 'admin' && $quote->user_id !== auth()->id()) {
                return response()->json(['error' => 'Action non autorisée.'], 403);
            }

            $quote->load(['material', 'shape', 'materialDimension', 'attachments', 'user']);
            return response()->json($quote);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Détails introuvables.'], 404);
        }
    }

    /**
     * ESTIMATION : Utilisé par Angular pour obtenir le prix en temps réel.
     */
    public function estimate(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'material_id'           => 'required|numeric|exists:materials,id',
                'shape_id'              => 'required|numeric|exists:shapes,id',
                'material_dimension_id' => 'nullable|numeric|exists:material_dimensions,id',
                'dimension_label'       => 'nullable|string',
                'quantity'              => 'required|integer|min:1',
                'discount_id'           => 'nullable|exists:discounts,id',
            ]);

            $md = null;

            if (!empty($data['material_dimension_id'])) {
                $md = MaterialDimension::find($data['material_dimension_id']);
            } elseif (!empty($data['dimension_label'])) {
                $md = MaterialDimension::where('material_id', $data['material_id'])
                    ->where('shape_id', $data['shape_id'])
                    ->where('dimension_label', $data['dimension_label'])
                    ->first();
            }

            if (!$md) {
                return response()->json([
                    'error' => 'Format non disponible',
                    'message' => 'Aucun prix n\'est configuré pour cette combinaison.'
                ], 404);
            }

            $discount = !empty($data['discount_id']) ? Discount::find($data['discount_id']) : null;
            $calc = $this->calculateTotalPrice($md->unit_price_fcfa, $data['quantity'], $discount);

            return response()->json([
                'unit_price_fcfa'       => $calc['unit_price_fcfa'],
                'quantity'              => $calc['quantity'],
                'price_source'          => 'standard',
                'dimension_label'       => $md->dimension_label,
                'material_dimension_id' => $md->id,
                'cost_details'          => [
                    'base_price_fcfa'      => $calc['base_price_fcfa'],
                    'discount_amount_fcfa' => $calc['discount_amount_fcfa'],
                    'final_price_fcfa'     => $calc['final_price_fcfa'],
                    'details_snapshot'     => [
                        'material_dimension_id' => $md->id,
                        'discount_id'           => $discount?->id
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Calcul impossible.', 'details' => $e->getMessage()], 400);
        }
    }

    /**
     * ENREGISTREMENT DU DEVIS (Gère "Envoyé" et "Brouillon")
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $estimateResponse = $this->estimate($request);
            if ($estimateResponse->getStatusCode() !== 200) return $estimateResponse;
            $est = json_decode($estimateResponse->getContent(), true);

            $request->validate([
                'client_details'         => 'required|array',
                'client_details.name'    => 'required|string',
                'client_details.email'   => 'required|email',
                'customization_details'  => 'nullable|array',
                'file_ids'               => 'nullable|array',
                'file_ids.*'             => 'integer|exists:attachments,id',
                'status'                 => 'nullable|string|in:draft,sent'
            ]);

            return DB::transaction(function () use ($request, $est) {
                $finalStatus = $request->input('status', Quote::STATUS_SENT);

                $quote = Quote::create([
                    'reference'             => $this->generateReference(),
                    'user_id'               => auth()->id(),
                    'client_details'        => $request->client_details,
                    'material_id'           => $request->material_id,
                    'shape_id'              => $request->shape_id,
                    'material_dimension_id' => $est['material_dimension_id'],
                    'quantity'              => $est['quantity'],
                    'unit_price_fcfa'       => $est['unit_price_fcfa'],
                    'price_source'          => 'standard',
                    'dimension_label'       => $est['dimension_label'],
                    'base_price_fcfa'       => $est['cost_details']['base_price_fcfa'],
                    'discount_amount_fcfa'  => $est['cost_details']['discount_amount_fcfa'],
                    'final_price_fcfa'      => $est['cost_details']['final_price_fcfa'],
                    'discount_id'           => $est['cost_details']['details_snapshot']['discount_id'] ?? null,
                    'status'                => $finalStatus,
                    'details_snapshot'      => [
                        'customization' => $request->customization_details,
                        'full_estimate' => $est
                    ],
                ]);

                if ($request->has('file_ids') && is_array($request->file_ids)) {
                    Attachment::whereIn('id', $request->file_ids)
                        ->where('user_id', auth()->id())
                        ->update(['quote_id' => $quote->id]);
                }

                $logType = ($finalStatus === Quote::STATUS_DRAFT) ? 'quote_drafted' : 'quote_created';
                $this->logActivity($logType, ['ref' => $quote->reference], $quote);

                $message = ($finalStatus === Quote::STATUS_DRAFT) 
                    ? 'Brouillon enregistré avec succès.' 
                    : 'Votre demande de devis a été envoyée.';

                return response()->json([
                    'message' => $message, 
                    'quote' => $quote
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Store Quote Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde.', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MISE À JOUR
     */
    public function update(Request $request, Quote $quote): JsonResponse
    {
        try {
            $data = $request->validate([
                'final_price_fcfa' => 'nullable|numeric',
                'status'           => 'nullable|string|in:draft,sent,calculated,ordered,rejected,archived'
            ]);

            $quote->update($data);
            $this->logActivity('quote_updated', ['ref' => $quote->reference, 'status' => $quote->status], $quote);
            return response()->json(['message' => 'Devis mis à jour.', 'quote' => $quote]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * SUPPRESSION
     */
    public function destroy(Quote $quote): JsonResponse
    {
        if ($quote->status === Quote::STATUS_ORDERED) {
            return response()->json(['error' => 'Impossible de supprimer un devis déjà transformé en commande.'], 422);
        }
        $quote->delete();
        return response()->json(['message' => 'Devis supprimé avec succès.']);
    }

    // --- MÉTHODES PRIVÉES ---

    /**
     * Génère une référence unique robuste
     */
    private function generateReference(): string
    {
        $year = now()->year;
        
        // Utilisation de max('id') pour éviter les collisions si des lignes sont supprimées
        $lastId = Quote::max('id') ?? 0;
        $nextNumber = $lastId + 1;
        
        $reference = 'DEV-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Double vérification de sécurité
        while (Quote::where('reference', $reference)->exists()) {
            $nextNumber++;
            $reference = 'DEV-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $reference;
    }

    private function calculateTotalPrice(float $unitPrice, int $quantity, ?Discount $discount): array
    {
        $base = $unitPrice * $quantity;
        $discAmount = 0;

        if ($discount && $discount->is_active && $base >= ($discount->min_order_amount ?? 0)) {
            $discAmount = ($discount->type === 'percentage') 
                ? $base * ($discount->value / 100) 
                : $discount->value;
        }

        return [
            'unit_price_fcfa'      => $unitPrice,
            'quantity'             => $quantity,
            'base_price_fcfa'      => round($base, 2),
            'discount_amount_fcfa' => round($discAmount, 2),
            'final_price_fcfa'     => round($base - $discAmount, 2),
        ];
    }
}