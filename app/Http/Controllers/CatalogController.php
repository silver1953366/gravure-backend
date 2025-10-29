<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\Shape;
use App\Models\MaterialDimension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Retourne la liste des catégories pour la navigation.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::where('is_active', true)->get(['id', 'name', 'description']);
        return response()->json($categories);
    }
    
    /**
     * Retourne la liste de tous les matériaux actifs avec leurs catégories.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMaterials(): JsonResponse
    {
        $materials = Material::with('category:id,name')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug', 'category_id', 'description', 'color']);

        return response()->json($materials);
    }

    /**
     * Retourne la liste de toutes les formes actives.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShapes(): JsonResponse
    {
        $shapes = Shape::where('is_active', true)->get(['id', 'name', 'description', 'base_price_impact']);
        return response()->json($shapes);
    }

    /**
     * Retourne la liste des prix fixes (MaterialDimension) basés sur des filtres.
     * C'est le catalogue de prix standard utilisés pour les devis.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFixedDimensions(Request $request): JsonResponse
    {
        $request->validate([
            'material_id' => 'sometimes|exists:materials,id',
            'shape_id' => 'sometimes|exists:shapes,id',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $query = MaterialDimension::where('is_active', true)
            ->with(['material:id,name', 'shape:id,name', 'category:id,name']);

        if ($request->has('material_id')) {
            $query->where('material_id', $request->input('material_id'));
        }
        
        if ($request->has('shape_id')) {
            $query->where('shape_id', $request->input('shape_id'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $dimensions = $query->orderBy('unit_price_fcfa')->get();

        return response()->json($dimensions);
    }
}