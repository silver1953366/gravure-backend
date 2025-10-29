<?php

namespace App\Http\Controllers\Api; // CHANGEMENT: Utilisation du namespace Api

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User; // Ajout pour cibler les utilisateurs
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * LOGIQUE ADMIN: Affiche TOUTES les notifications du système.
     * Cette méthode doit être protégée par le middleware 'admin'.
     */
    public function indexAdmin(): JsonResponse
    {
        $notifications = Notification::with('user:id,name,email')->latest()->get();
        return response()->json($notifications);
    }
    
    /**
     * Affiche toutes les notifications de l'utilisateur authentifié (Client/Controleur).
     */
    public function index(): JsonResponse
    {
        // La relation `notifications()` récupère les notifications stockées dans la base de données.
        $notifications = Auth::user()->notifications()->latest()->get();
        return response()->json($notifications);
    }

    /**
     * LOGIQUE ADMIN: Crée et envoie une nouvelle notification à un ou plusieurs utilisateurs.
     * Cette méthode doit être protégée par le middleware 'admin'.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|array', // Peut être une liste d'IDs [1, 5, 8]
            'user_id.*' => 'exists:users,id', // Chaque ID doit exister
            'type' => 'required|string|in:info,warning,success,error',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'link' => 'nullable|string|url|max:255',
        ]);

        $users = User::whereIn('id', $data['user_id'])->get();
        $createdNotifications = [];

        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'link' => $data['link'] ?? null,
                'is_read' => false,
            ]);
            $createdNotifications[] = $notification;
            
            // NOTE: Pour un système de notification en temps réel (websockets),
            // la logique d'envoi sera ajoutée ici.
        }

        return response()->json([
            'message' => count($createdNotifications) . ' notification(s) créée(s) avec succès.',
            'notifications' => $createdNotifications
        ], 201);
    }

    /**
     * Affiche une notification spécifique.
     */
    public function show(Notification $notification): JsonResponse
    {
        // La vérification de la propriété est déjà présente
        if ($notification->user_id !== Auth::id() && Auth::user()->role !== 'admin') { // Ajout de la permission Admin
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        return response()->json($notification);
    }

    /**
     * Marque une notification comme lue.
     */
    public function update(Request $request, Notification $notification): JsonResponse
    {
        // La vérification de la propriété est déjà présente
        if ($notification->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // L'Admin peut aussi marquer une notification comme lue (pour le client)
        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }
    
    /**
     * Supprime une notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Seul le propriétaire ou l'Admin peut supprimer
        if ($notification->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $notification->delete();
        
        return response()->json(['message' => 'Notification supprimée avec succès.']);
    }
}