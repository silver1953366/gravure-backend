<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    /**
     * Stocke un nouveau fichier joint.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 1. Validation de la requête
        try {
            $request->validate([
                // Limitez la taille (ex: 10 Mo) et les types de fichiers acceptés
                'file' => 'required|file|max:10240|mimes:png,jpg,jpeg,dxf,ai,pdf,svg', 
                'quote_id' => 'required|exists:quotes,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Assurez-vous que l'utilisateur est bien le propriétaire du devis
        // Cette vérification sera finalisée une fois la création du devis codée
        // $quote = Auth::user()->quotes()->findOrFail($request->quote_id);

        // 2. Gestion du fichier
        $file = $request->file('file');
        
        // Stockage du fichier dans le disque privé 'local' (storage/app/attachments)
        // L'utilisation de 'putFile' garantit un nom de fichier unique pour éviter les collisions
        $storedPath = Storage::disk('local')->putFile(
            'attachments', // Sous-répertoire
            $file
        );

        if (!$storedPath) {
            return response()->json(['message' => 'Erreur lors du stockage du fichier.'], 500);
        }

        // 3. Enregistrement des métadonnées en base de données
        $attachment = Attachment::create([
            'quote_id' => $request->quote_id,
            'user_id' => Auth::id(), // L'utilisateur actuellement authentifié est l'uploader
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Fichier téléversé avec succès.',
            'attachment' => $attachment
        ], 201);
    }
    
    /**
     * Récupère et télécharge le fichier de manière sécurisée.
     * @param  \App\Models\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function show(Attachment $attachment)
    {
        // 1. Vérification de la permission (Politique d'accès)
        // Seul l'utilisateur propriétaire du fichier, un contrôleur, ou l'admin peut accéder.
        $user = Auth::user();
        $isOwner = $user->id === $attachment->user_id;
        $isAuthorizedRole = $user->role->name === 'Admin' || $user->role->name === 'Controller';
        
        if (!$isOwner && !$isAuthorizedRole) {
            return response()->json(['message' => 'Accès non autorisé au fichier.'], 403);
        }
        
        // 2. Vérification de l'existence du fichier sur le disque
        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            return response()->json(['message' => 'Fichier non trouvé sur le serveur.'], 404);
        }

        // 3. Retour du fichier pour téléchargement
        // L'utilisation de Storage::download force le téléchargement et cache le chemin réel.
        return Storage::disk('local')->download(
            $attachment->stored_path, 
            $attachment->original_name
        );
    }
}