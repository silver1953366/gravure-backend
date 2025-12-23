<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * CLIENT : Liste des notifications de l'utilisateur connecté.
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->with(['resource']) 
            ->latest()
            ->paginate(15);

        return response()->json($notifications);
    }

    /**
     * ADMIN : Liste globale (monitoring).
     */
    public function indexAdmin(): JsonResponse
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Accès admin requis'], 403);
        }

        $notifications = Notification::with(['user:id,name,email', 'resource'])
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * ADMIN : Envoi manuel d'une notification.
     * C'est ici que l'erreur 500 se produit.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validation des données
        $validated = $request->validate([
            'user_ids'      => 'required|array',
            'user_ids.*'    => 'exists:users,id',
            'type'          => 'required|string|in:info,warning,success,error',
            'title'         => 'required|string|max:255',
            'message'       => 'required|string',
            'link'          => 'nullable|string',
            'resource_id'   => 'nullable|integer',
            'resource_type' => 'nullable|string',
        ]);

        try {
            $createdCount = 0;

            // 2. Utilisation de la transaction pour éviter les données partielles
            DB::transaction(function () use ($validated, &$createdCount) {
                foreach ($validated['user_ids'] as $userId) {
                    Notification::create([
                        'user_id'       => $userId,
                        'type'          => $validated['type'],
                        'title'         => $validated['title'],
                        'message'       => $validated['message'],
                        'link'          => $validated['link'] ?? null,
                        'resource_id'   => $validated['resource_id'] ?? null,
                        'resource_type' => $validated['resource_type'] ?? null,
                        'is_read'       => false,
                    ]);
                    $createdCount++;
                }
            });

            return response()->json([
                'status'  => 'success',
                'message' => "{$createdCount} notification(s) envoyée(s) avec succès."
            ], 201);

        } catch (\Exception $e) {
            // Log de l'erreur dans storage/logs/laravel.log
            Log::error("Échec envoi notification : " . $e->getMessage());

            // 3. Retourne l'erreur précise pour le debug (à désactiver en prod)
            return response()->json([
                'status'  => 'error',
                'message' => 'Erreur interne du serveur lors de l\'insertion.',
                'error'   => $e->getMessage(), // Nous dira si une colonne manque
                'line'    => $e->getLine()
            ], 500);
        }
    }

    /**
     * Marquer comme lue.
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marquer TOUT comme lu.
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Toutes les notifications lues.']);
    }

    /**
     * Supprimer une notification.
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée.']);
    }

    /**
     * Compteur pour Angular.
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}