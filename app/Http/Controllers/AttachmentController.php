<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AttachmentController extends Controller
{
    /**
     * Stocke un nouveau fichier joint.
     * Supporte à la fois un devis existant (quote_id) 
     * ou une création en cours (temp_quote_id).
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validation assouplie
        try {
            $request->validate([
                'file' => 'required|file|max:10240|mimes:png,jpg,jpeg,dxf,ai,pdf,svg',
                // On permet l'un ou l'autre
                'quote_id' => 'nullable|integer',
                'temp_quote_id' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $e->errors()
            ], 422);
        }

        // 2. Vérification de sécurité minimale
        if (!$request->quote_id && !$request->temp_quote_id) {
            return response()->json([
                'message' => 'Un identifiant de devis (réel ou temporaire) est requis.'
            ], 422);
        }

        // 3. Gestion du fichier physique
        $file = $request->file('file');
        
        try {
            // Stockage dans 'storage/app/attachments'
            $storedPath = Storage::disk('local')->putFile('attachments', $file);

            if (!$storedPath) {
                throw new \Exception('Le fichier n\'a pas pu être écrit sur le disque.');
            }

            // 4. Enregistrement en Base de Données
            $attachment = Attachment::create([
                'quote_id'      => $request->quote_id,      // Sera null si nouveau devis
                'temp_quote_id' => $request->temp_quote_id, // Le timestamp d'Angular
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $storedPath,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            return response()->json([
                'message' => 'Fichier téléversé avec succès.',
                'attachment' => $attachment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du traitement du fichier.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un fichier joint et son contenu physique.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        // Vérifier si l'utilisateur a le droit (Propriétaire ou Admin)
        if ($attachment->user_id !== Auth::id() && Auth::user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        try {
            // Supprimer le fichier physique
            if (Storage::disk('local')->exists($attachment->stored_path)) {
                Storage::disk('local')->delete($attachment->stored_path);
            }

            // Supprimer l'entrée en base
            $attachment->delete();

            return response()->json(['message' => 'Fichier supprimé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la suppression.'], 500);
        }
    }

    /**
     * Télécharge le fichier de manière sécurisée.
     */
    public function show(Attachment $attachment)
    {
        // Sécurité : Seul le proprio ou le staff peut voir
        $user = Auth::user();
        if ($user->id !== $attachment->user_id && !in_array($user->role->name, ['Admin', 'Controller'])) {
            abort(403, 'Accès non autorisé.');
        }

        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404, 'Fichier physique introuvable.');
        }

        return Storage::disk('local')->download(
            $attachment->stored_path, 
            $attachment->original_name
        );
    }
}