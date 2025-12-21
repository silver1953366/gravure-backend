<?php

namespace App\Http\Controllers;

use App\Models\Shape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShapeController extends Controller
{
    /**
     * Affiche la liste de toutes les formes.
     */
    public function index()
    {
        // On récupère les formes triées par nom
        $shapes = Shape::orderBy('name')->get();
        return response()->json($shapes);
    }

    /**
     * Crée une nouvelle forme avec gestion d'image et de slug.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:shapes,name',
            'slug'        => 'required|string|max:255|unique:shapes,slug',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes', // On traite la conversion en booléen après
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        // Conversion du statut envoyé par FormData (souvent '1' ou '0') en booléen pur
        $data['is_active'] = $request->is_active === '1' || $request->is_active === true;

        // Gestion de l'upload d'image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Stockage dans : storage/app/public/shapes
            $path = $file->store('shapes', 'public');
            // Génération de l'URL publique
            $data['image_url'] = asset('storage/' . $path);
        }

        $shape = Shape::create($data);

        return response()->json([
            'message' => 'Forme créée avec succès',
            'data' => $shape
        ], 201);
    }

    /**
     * Affiche une forme spécifique.
     */
    public function show(Shape $shape)
    {
        return response()->json($shape);
    }

    /**
     * Met à jour une forme existante.
     */
    public function update(Request $request, Shape $shape)
    {
        // Note: Pour le PUT avec fichiers dans Laravel, assurez-vous d'utiliser 
        // une requête POST avec le champ _method = 'PUT' côté Angular.
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:shapes,name,' . $shape->id,
            'slug'        => 'required|string|max:255|unique:shapes,slug,' . $shape->id,
            'description' => 'nullable|string',
            'is_active'   => 'sometimes',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        $data['is_active'] = $request->is_active === '1' || $request->is_active === true;

        if ($request->hasFile('image')) {
            // Optionnel : Supprimer l'ancienne image du disque si elle existe
            if ($shape->image_url) {
                $oldPath = str_replace(asset('storage/'), '', $shape->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('shapes', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }

        $shape->update($data);

        return response()->json([
            'message' => 'Forme mise à jour avec succès',
            'data' => $shape
        ]);
    }

    /**
     * Supprime une forme et son fichier image associé.
     */
    public function destroy(Shape $shape)
    {
        // Vérification des dépendances (MaterialDimension)
        if ($shape->materialDimensions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette forme car elle est liée à des prix de catalogue.'
            ], 409); 
        }

        // Suppression de l'image sur le disque avant de supprimer l'entrée en base
        if ($shape->image_url) {
            $path = str_replace(asset('storage/'), '', $shape->image_url);
            Storage::disk('public')->delete($path);
        }

        $shape->delete();

        return response()->json(['message' => 'Forme supprimée'], 204);
    }
}