<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /**
     * Liste des matériaux avec leurs catégories associées.
     */
    public function index()
    {
        try {
            // On récupère les matériaux avec leur catégorie (Eager Loading)
            $materials = Material::with('category')->orderBy('id', 'desc')->get();
            return response()->json($materials);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des matériaux.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrement d'un nouveau matériau.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100|unique:materials,name',
            'slug'               => 'required|string|max:100|unique:materials,slug',
            'category_id'        => 'nullable|exists:categories,id', // Optionnel
            'description'        => 'nullable|string',
            'price_per_sq_meter' => 'nullable|numeric|min:0', // Optionnel
            'thickness_options'  => 'nullable|string',
            'color'              => 'nullable|string|max:50',
            'is_active'          => 'required', 
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // 1. Gestion de l'upload d'image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('materials', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }

        // 2. Conversion du statut (FormData envoie des strings)
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        // 3. Création en base de données
        $material = Material::create($data);

        return response()->json($material->load('category'), 201);
    }

    /**
     * Détails d'un matériau.
     */
    public function show(Material $material)
    {
        return response()->json($material->load('category'));
    }

    /**
     * Mise à jour d'un matériau.
     */
    public function update(Request $request, Material $material)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100|unique:materials,name,' . $material->id,
            'slug'               => 'required|string|max:100|unique:materials,slug,' . $material->id,
            'category_id'        => 'nullable|exists:categories,id',
            'description'        => 'nullable|string',
            'price_per_sq_meter' => 'nullable|numeric|min:0',
            'thickness_options'  => 'nullable|string',
            'color'              => 'nullable|string|max:50',
            'is_active'          => 'required',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // 1. Gestion de la nouvelle image
        if ($request->hasFile('image')) {
            // Nettoyage de l'ancienne image sur le disque
            if ($material->image_url) {
                $oldPath = str_replace(asset('storage/'), '', $material->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('materials', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }

        // 2. Conversion du statut
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $material->update($data);

        return response()->json($material->load('category'));
    }

    /**
     * Suppression d'un matériau et de son fichier image.
     */
    public function destroy(Material $material)
    {
        try {
            // Suppression physique du fichier image
            if ($material->image_url) {
                $path = str_replace(asset('storage/'), '', $material->image_url);
                Storage::disk('public')->delete($path);
            }

            $material->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la suppression.'], 500);
        }
    }
}