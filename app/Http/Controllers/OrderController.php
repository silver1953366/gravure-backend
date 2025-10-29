<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; 
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

class OrderController extends Controller 
{
    // Ajout du trait AuthorizesRequests pour utiliser $this->authorize()
    use AuthorizesRequests;

    /**
     * Affiche la liste des commandes. Affiche TOUTES pour l'Admin, et UNIQUEMENT les siennes pour le Client.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. AUTORISATION : Assurez-vous que l'utilisateur a le droit de voir n'importe quelle commande
        // La OrderPolicy@before gère les Admin/Controller, et OrderPolicy@viewAny gère le Client.
        $this->authorize('viewAny', Order::class); // <-- Vérifie si l'accès est autorisé (403 si non)

        // Initialisation de la requête avec les relations pour optimiser la performance
        $orders = Order::with('quote.material', 'quote.shape', 'user');

        // 2. FILTRAGE : Si l'utilisateur n'est PAS Admin, on filtre par son ID.
        if ($user->role !== User::ROLE_ADMIN) {
            // Si le rôle n'est PAS admin, on ne doit voir que les commandes de l'utilisateur.
            // Note : le CONTROLLER est géré par la policy@before, donc seul l'ADMIN passe ce filtre ici.
            $orders = $orders->where('user_id', $user->id);
        }
        
        // --- Logique de Tri ---
        $sortBy = $request->query('sort_by', 'recent'); 

        if ($sortBy === 'oldest') {
            $orders = $orders->orderBy('created_at', 'asc'); 
        } else {
            $orders = $orders->orderBy('created_at', 'desc'); 
        }
        // --- Fin Logique de Tri ---
        
        // Récupérer et retourner les commandes
        return response()->json($orders->get());
    }
    
    /**
     * Crée une nouvelle commande à partir d'un devis confirmé (méthode dédiée).
     */
    public function convertQuoteToOrder(Request $request, Quote $quote)
    {
        try {
            // 1. Autorisation : Vérifie si l'utilisateur peut convertir ce devis spécifique.
            $this->authorize('convert', $quote); 
            
            // Validation des données requises pour la commande (Adresse de livraison)
            $request->validate([
                'shipping_address' => 'required|array',
                'shipping_address.street' => 'required|string|max:255',
                'shipping_address.city' => 'required|string|max:100',
                'shipping_address.postal_code' => 'required|string|max:20',
            ]);
            
            // 2. Préparation des données du devis pour la commande
            $orderData = array_merge(
                [
                    'user_id' => $quote->user_id,
                    'quote_id' => $quote->id,
                    // Génération d'une référence propre : Ex: CMD-A3B4C5-123
                    'reference' => 'CMD-' . Str::upper(Str::random(6)) . '-' . $quote->id, 
                    'final_price_fcfa' => $quote->final_price_fcfa,
                    'shipping_address' => $request->input('shipping_address'),
                    'status' => Order::STATUS_PENDING_PAYMENT,
                ],
                // Copie des champs de spécification du produit (Snapshots)
                $quote->only([
                    'material_id',
                    'shape_id',
                    'material_dimension_id',
                    'quantity',
                    'client_details',
                    'details_snapshot',
                ])
            );

            // Début de la transaction pour garantir l'atomicité de la conversion
            DB::beginTransaction();
            
            // 3. Créer la commande
            $order = Order::create($orderData);
            
            // 4. Mettre à jour le devis (lier à la commande et changer le statut)
            $quote->update([
                'order_id' => $order->id,
                'status' => Quote::STATUS_ORDERED,
            ]);
            
            DB::commit();

            // 5. Retourner la commande
            return response()->json([
                'message' => 'Commande créée avec succès. En attente de paiement.',
                'order' => $order->load('quote')
            ], Response::HTTP_CREATED); // 201 Created

        } catch (AuthorizationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Vous n\'êtes pas autorisé à convertir ce devis en commande.', 
                'code' => 'AUTHORIZATION_FAILED',
                'details' => $e->getMessage() // Ajout du message pour le debug
            ], Response::HTTP_FORBIDDEN); // 403
        
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY); // 422

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Échec de la conversion du devis en commande (Erreur Interne).', 
                'debug' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Affiche une commande spécifique.
     */
    public function show(Order $order)
    {
        // La Policy vérifie si l'utilisateur est le propriétaire ou un Admin/Contrôleur.
        $this->authorize('view', $order); 
        
        return response()->json($order->load('quote.material', 'quote.shape'));
    }
    
    /**
     * Met à jour le statut ou l'adresse de livraison d'une commande.
     */
    public function update(Request $request, Order $order)
    {
        try {
            // 1. Autorisation (La Policy gère qui peut modifier quoi)
            $this->authorize('update', $order);

            // 2. Validation
            $validatedData = $request->validate([
                'status' => ['sometimes', 'required', 'string', 'in:' . implode(',', Order::STATUSES)],
                'payment_id' => ['sometimes', 'nullable', 'string', 'max:255'],
                'shipping_address' => 'sometimes|required|array',
                'shipping_address.street' => 'required_with:shipping_address|string|max:255',
                'shipping_address.city' => 'required_with:shipping_address|string|max:100',
                'shipping_address.postal_code' => 'required_with:shipping_address|string|max:20',
            ]);

            // 3. Mise à jour dans une transaction
            DB::beginTransaction();

            $order->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Commande mise à jour avec succès.',
                'order' => $order->load('quote.material', 'quote.shape')
            ]);

        } catch (AuthorizationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Vous n\'êtes pas autorisé à modifier cette commande.',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN); // 403

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY); // 422

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Échec de la mise à jour de la commande (Erreur Interne).',
                'debug' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Annule une commande et rétablit le devis pour une nouvelle tentative éventuelle.
     */
    public function destroy(Order $order)
    {
        try {
            // 1. Autorisation (Seul l'Admin ou le propriétaire si la commande n'est pas en production)
            $this->authorize('delete', $order);

            // Début de la transaction
            DB::beginTransaction();

            // Rétablissez d'abord le devis : 
            // - Déliez l'order_id
            // - Remettez le statut à CALCULATED (ou celui qui permet la reconversion)
            $order->quote()->update([
                'order_id' => null,
                'status' => Quote::STATUS_CALCULATED, 
            ]);

            // 2. Supprimez la commande
            $order->delete();
            
            DB::commit();

            return response()->json([
                'message' => 'Commande annulée et devis rétabli avec succès.',
            ], Response::HTTP_NO_CONTENT); // 204 No Content

        } catch (AuthorizationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Vous n\'êtes pas autorisé à annuler cette commande.',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN); // 403

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Échec de l\'annulation de la commande (Erreur Interne).',
                'debug' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }
}
