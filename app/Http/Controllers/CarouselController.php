<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CarouselController extends Controller
{
    /**
     * Liste pour le Front-end (Public)
     * On ne récupère que les slides actives, triées par ordre.
     */
    public function index(): JsonResponse
    {
        $slides = Carousel::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
            
        return response()->json($slides);
    }

    /**
     * Liste pour l'Administration
     * On récupère tout, même les inactives.
     */
    public function indexAdmin(): JsonResponse
    {
        try {
            $slides = Carousel::orderBy('order', 'asc')->get();
            return response()->json($slides);
        } catch (\Exception $e) {
            Log::error("Erreur indexAdmin Carousel: " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération'], 500);
        }
    }

    /**
     * Ajouter une nouvelle slide
     */
    public function store(Request $request): JsonResponse
    {
        // Validation stricte incluant les nouveaux champs
        $validatedData = $request->validate([
            'title'         => 'required|string|max:255',
            'subtitle'      => 'required|string|max:255',
            'image'         => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
            'link_url'      => 'required|string',
            'category_name' => 'required|string|max:100', // Texte du bouton
            'order'         => 'required|integer|min:1',
            'image_height'  => 'nullable|integer|min:200|max:1000',
        ]);

        try {
            // Utilisation d'une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request, $validatedData) {
                
                // 1. Gestion automatique de l'ordre : décale les slides existantes
                $this->reorderSlides($validatedData['order']);

                // 2. Stockage de l'image
                $path = $request->file('image')->store('carousel', 'public');

                // 3. Création de l'enregistrement
                $slide = Carousel::create([
                    'title'         => $validatedData['title'],
                    'subtitle'      => $validatedData['subtitle'],
                    'image_url'     => $path,
                    'link'          => $validatedData['link_url'],
                    'category_name' => $validatedData['category_name'],
                    'order'         => $validatedData['order'],
                    'height'        => $request->input('image_height', 480),
                    'is_active'     => true,
                ]);

                return response()->json([
                    'message' => 'Slide créée avec succès',
                    'data' => $slide
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Erreur Store Carousel: " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la sauvegarde'], 500);
        }
    }

    /**
     * Mettre à jour une slide
     */
    public function update(Request $request, $id): JsonResponse
    {
        $carousel = Carousel::findOrFail($id);

        $validatedData = $request->validate([
            'title'         => 'nullable|string|max:255',
            'subtitle'      => 'nullable|string|max:255',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'link_url'      => 'nullable|string',
            'category_name' => 'nullable|string|max:100',
            'order'         => 'nullable|integer|min:1',
            'image_height'  => 'nullable|integer',
            'is_active'     => 'nullable' 
        ]);

        try {
            return DB::transaction(function () use ($request, $carousel) {
                
                $dataToUpdate = $request->only(['title', 'subtitle', 'category_name', 'order']);

                // 1. Si l'ordre a changé, on gère le décalage automatique
                if ($request->has('order') && $carousel->order != $request->order) {
                    $this->reorderSlides($request->order, $carousel->id);
                }

                // 2. Gestion de l'image (suppression de l'ancienne si nouvelle)
                if ($request->hasFile('image')) {
                    if ($carousel->image_url) {
                        Storage::disk('public')->delete($carousel->image_url);
                    }
                    $dataToUpdate['image_url'] = $request->file('image')->store('carousel', 'public');
                }

                // 3. Mappings spécifiques
                if ($request->has('link_url')) $dataToUpdate['link'] = $request->link_url;
                if ($request->has('image_height')) $dataToUpdate['height'] = $request->image_height;
                if ($request->has('is_active')) {
                    $dataToUpdate['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                }

                $carousel->update($dataToUpdate);

                return response()->json([
                    'message' => 'Slide mise à jour avec succès',
                    'data' => $carousel
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Erreur Update Carousel: " . $e->getMessage());
            return response()->json(['error' => 'Erreur de mise à jour'], 500);
        }
    }

    /**
     * Supprimer une slide
     */
    public function destroy(Carousel $carousel): JsonResponse
    {
        try {
            // Note: La suppression du fichier image est gérée dans le modèle Carousel (booted)
            $carousel->delete();
            return response()->json(['message' => 'Slide supprimée avec succès'], 200);
        } catch (\Exception $e) {
            Log::error("Erreur Destroy Carousel: " . $e->getMessage());
            return response()->json(['error' => 'Erreur de suppression'], 500);
        }
    }

    /**
     * LOGIQUE PRIVÉE : Gestion automatique de l'ordre
     * Décale toutes les slides dont l'ordre est >= à l'ordre demandé
     */
    private function reorderSlides($requestedOrder, $excludeId = null)
    {
        $query = Carousel::where('order', '>=', $requestedOrder);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // On incrémente l'ordre de toutes les slides impactées
        $query->increment('order');
    }
}