<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Affiche une liste de toutes les catégories.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    /**
     * Crée une nouvelle catégorie.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($request->all());

        return response()->json($category, 201);
    }

    /**
     * Affiche une catégorie spécifique.
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Met à jour une catégorie existante.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($request->all());

        return response()->json($category);
    }

    /**
     * Supprime une catégorie.
     */
     public function destroy(Category $category)
    {
        // VÉRIFICATION DES DÉPENDANCES : 
        // Assurez-vous que la relation 'materials' existe sur votre modèle Category.
        if (method_exists($category, 'materials') && $category->materials()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car des matériaux y sont toujours associés.'
            ], 409); // 409 Conflict est le code standard pour ce type de conflit
        }
        
        // Ajoutez ici d'autres vérifications (ex: $category->shapes()->exists()) si nécessaire

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès.'], 200); // Code 200 ou 204
    }
}
