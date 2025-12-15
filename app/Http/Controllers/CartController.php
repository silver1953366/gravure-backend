<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MaterialDimension;
use App\Models\Quote; 
use App\Models\Discount; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Récupère le panier actif de l'utilisateur ou de la session.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.materialDimension.material', 'items.materialDimension.shape', 'discount');

        return response()->json($cart);
    }

    /**
     * Ajoute un article ou met à jour la quantité/options d'un article existant.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'material_dimension_id' => 'required|exists:material_dimensions,id',
                'quantity' => 'required|integer|min:1',
                'engraving_text' => 'nullable|string|max:255', 
                'mounting_option' => 'nullable|string|max:100',
                'custom_options' => 'nullable|json',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        }

        $cart = $this->getOrCreateCart($request);
        $materialDimension = MaterialDimension::findOrFail($data['material_dimension_id']);

        // 1. CALCUL DES PRIX : Inclut la base et le coût de la gravure
        $priceDetails = $this->calculateItemPrices(
            $materialDimension->unit_price_fcfa, 
            $data['quantity'], 
            $data['engraving_text']
        );
        
        $data['fixed_unit_price_fcfa'] = $priceDetails['fixed_unit_price_fcfa'];
        $data['cart_id'] = $cart->id;

        // 2. Tenter de trouver un article existant exactement identique (pour incrémenter la quantité)
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('material_dimension_id', $data['material_dimension_id'])
            ->where('fixed_unit_price_fcfa', $data['fixed_unit_price_fcfa']) 
            ->where('engraving_text', $data['engraving_text'])
            ->where('mounting_option', $data['mounting_option'])
            ->first(); 
        
        if ($cartItem) {
            $cartItem->quantity += $data['quantity'];
            $cartItem->save();
        } else {
            $cartItem = CartItem::create($data);
        }
        
        $cart->load('items.materialDimension.material', 'items.materialDimension.shape');
        return response()->json($cart, 201);
    }
    
    /**
     * Met à jour la quantité d'un article dans le panier.
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->cart_id !== $this->getOrCreateCart($request)->id) {
            return response()->json(['message' => 'Article non trouvé dans le panier actif.'], 404);
        }
        
        $data = $request->validate(['quantity' => 'required|integer|min:1']);
        $cartItem->update($data);
        
        return $this->index($request);
    }

    /**
     * Supprime un article du panier.
     */
    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->cart_id !== $this->getOrCreateCart($request)->id) {
            return response()->json(['message' => 'Article non trouvé dans le panier actif.'], 404);
        }
        
        $cartItem->delete();
        return response()->json(null, 204);
    }
    
    /**
     * Convertit le panier actif en Demande de Devis (Quote). (Protégé par Auth)
     */
    public function convertToQuote(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request); 

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Le panier est vide. Impossible de créer un devis.'], 400);
        }
        
        // La logique de création d'un devis à partir du panier doit être implémentée ici
        $quote = Quote::create([
            'user_id' => $cart->user_id,
            'reference' => 'TEMP-' . time(),
            'status' => 'pending', 
        ]);
        
        // TODO: Créer les QuoteItems si Quote supporte plusieurs articles
        // TODO: Récupérer les détails clients si $cart->user_id est null
        
        $cart->update(['status' => 'ordered']); 
        
        return response()->json([
            'message' => 'Demande de devis créée avec succès.',
            'quote_id' => $quote->id
        ], 201);
    }
    
    // ------------------------------------------------------------------
    // --- FONCTIONS INTERNES D'AIDE : GESTION DU PANIER PERSISTANT ---
    // ------------------------------------------------------------------

    /**
     * Récupère le panier via Auth::id() ou via le session_token, en gérant la fusion.
     */
    private function getOrCreateCart(Request $request): Cart
    {
        $sessionToken = $request->cookie('cart_token') ?? session()->getId();
        
        // Cas 1: Utilisateur Authentifié
        if (Auth::check()) {
            $userId = Auth::id();
            
            $userCart = Cart::firstOrCreate(
                ['user_id' => $userId, 'status' => 'pending'],
                ['session_token' => null]
            );

            $anonCart = Cart::where('session_token', $sessionToken)
                            ->whereNull('user_id')
                            ->where('status', 'pending')
                            ->first();

            // Logique de Fusion
            if ($anonCart && $anonCart->id !== $userCart->id) {
                $anonCart->items()->update(['cart_id' => $userCart->id]);
                $anonCart->delete();
            }
            
            return $userCart;
        } 
        
        // Cas 2: Utilisateur Anonyme
        $cart = Cart::firstOrCreate(
            ['session_token' => $sessionToken, 'status' => 'pending'],
            ['user_id' => null]
        );

        return $cart;
    }

    /**
     * Calcule le coût unitaire final de l'article (Base + Gravure)
     */
    private function calculateItemPrices(float $base_unit_price, int $quantity, ?string $engraving_text): array
    {
        $engraving_cost = $this->calculateEngravingCost($engraving_text);
        $unit_price_ht = $base_unit_price + $engraving_cost;

        return [
            'fixed_unit_price_fcfa' => round($unit_price_ht, 2),
            'engraving_cost_ht_fcfa' => round($engraving_cost, 2),
            'total_base_price_ht_fcfa' => round($unit_price_ht * $quantity, 2),
        ];
    }
    
    /**
     * Logique du coût de la gravure.
     */
    private function calculateEngravingCost(?string $text): float
    {
        if (empty($text)) {
            return 0.00;
        }
        // Logique de prix: 5 FCFA par caractère
        return strlen($text) * 5.00; 
    }
}