<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shape;
use Illuminate\Http\Request;

class ShapeController extends Controller
{
    /**
     * Affiche la liste de toutes les formes.
     */
    public function index()
    {
        $shapes = Shape::orderBy('name')->get();
        return response()->json($shapes);
    }

    /**
     * Crée une nouvelle forme.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:shapes,name',
            'description' => 'nullable|string',
        ]);

        $shape = Shape::create($data);
        return response()->json($shape, 201);
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
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:shapes,name,' . $shape->id,
            'description' => 'nullable|string',
        ]);

        $shape->update($data);
        return response()->json($shape);
    }

    /**
     * Supprime une forme.
     * ATTENTION : Une vérification des dépendances (MaterialDimension) est nécessaire
     */
    public function destroy(Shape $shape)
    {
        if ($shape->materialDimensions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette forme car elle est liée à des prix de catalogue existants.'
            ], 409); 
        }

        $shape->delete();
        return response()->json(null, 204);
    }
}
