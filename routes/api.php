<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =======================================================================
// Importation de TOUS les contrôleurs
// =======================================================================
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController; 
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CatalogController; 
use App\Http\Controllers\DiscountController; 
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CatalogController; 
use App\Http\Controllers\MaterialController; 
use App\Http\Controllers\ShapeController; 
use App\Http\Controllers\MaterialDimensionController; 
use App\Http\Controllers\NotificationController; 
use App\Http\Controllers\OrderController; 
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\InventoryController; 
use App\Http\Controllers\UserController; 
use App\Http\Controllers\ReportController; 
use App\Http\Controllers\AttachmentController; 

// =======================================================================
// Importation des Middlewares de Rôles
// =======================================================================
use App\Http\Middleware\IsController; // Vérifie (admin OU controller) pour la lecture (Section 4)
use App\Http\Middleware\AdminMiddleware; // Vérifie (admin SEULEMENT) pour l'écriture (Section 5)

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


// =======================================================================
// 1. ROUTES D'AUTHENTIFICATION (Publiques)
// =======================================================================

Route::post('login', [AuthController::class, 'login']); 
Route::post('register', [AuthController::class, 'register']);


// =======================================================================
// 2. ROUTES PUBLIQUES/CATALOGUE (Consultation pour le Frontend)
// =======================================================================

Route::prefix('catalog')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']); 
    Route::get('/materials', [CatalogController::class, 'getMaterials']);
    Route::get('/shapes', [CatalogController::class, 'getShapes']);
    Route::get('/dimensions', [CatalogController::class, 'getFixedDimensions']);
    Route::post('/quotes/estimate', [QuoteController::class, 'estimate']);
});


// =======================================================================
// 3. LOGIQUE CLIENT / PROTÉGÉE (Requiert 'auth:sanctum')
// Accessible par Client/Controller/Admin (sauf si permissions plus fines dans les contrôleurs)
// =======================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Déconnexion (optionnel)
    Route::post('logout', [AuthController::class, 'logout']); 
    
    // Gestion des Fichiers Joints (Upload / Téléchargement sécurisé)
    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
    
    // Devis 
    Route::apiResource('quotes', QuoteController::class)->only(['index', 'show', 'store','update']);
    
    // Commandes (Route Client)
    Route::post('orders/convert/{quote}', [OrderController::class, 'convertQuoteToOrder']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show']); 
    
    // Favoris et Notifications
    Route::apiResource('favorites', FavoriteController::class)->except(['update']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'update', 'destroy']);
    
    // Profil utilisateur
    Route::get('user', function (Request $request) { return $request->user(); });

});


// =======================================================================
// 4. LECTURE STOCK (CONTROLLER/ADMIN)
// Protégée par IsController (autorise admin ET controller)
// =======================================================================
Route::middleware(['auth:sanctum', IsController::class])->group(function () {
    
    // Routes de lecture seule de l'inventaire
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/{inventory}', [InventoryController::class, 'show']);
    });
});


// =======================================================================
// 5. ROUTES D'ADMINISTRATION (CRUD)
// Protégée par AdminMiddleware (autorise SEULEMENT admin)
// =======================================================================

Route::middleware(['auth:sanctum', AdminMiddleware::class])->prefix('admin')->group(function () {
    
    // Catalogue (CRUD)
    Route::apiResource('materials', MaterialController::class);
    Route::apiResource('shapes', ShapeController::class);
    // Renommage du paramètre pour être plus explicite dans l'URL:
    Route::apiResource('material-dimensions', MaterialDimensionController::class)->parameters([
        'material-dimensions' => 'materialDimension'
    ]);
    Route::apiResource('categories', CategoryController::class); 
    Route::apiResource('discounts', DiscountController::class);
    
    // Gestion des Devis (Admin) - Récupération, Mise à jour, Suppression
    Route::get('quotes', [QuoteController::class, 'index']); 
    Route::put('quotes/{quote}', [QuoteController::class, 'update']);
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']); 
    
    // Gestion des Commandes / Utilisateurs
    // 🛑 CORRECTION D'ÉCRASMENT : Renommage en 'admin-orders'. L'accès se fera via /api/admin/admin-orders
    Route::apiResource('admin-orders', OrderController::class)->except(['store']);
    Route::apiResource('users', UserController::class);


    // Gestion des Notifications (Admin)
    Route::get('notifications/all', [NotificationController::class, 'indexAdmin']);
    Route::apiResource('notifications', NotificationController::class)->except(['index', 'show']); 

    // Journal d'Activité
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/{activity}', [ActivityController::class, 'show']);

    // Gestion de l'Inventaire (CRUD Admin) - ÉCRITURE SEULEMENT
    // Les méthodes index et show sont exclues car gérées par le middleware IsController (Section 4)
    Route::apiResource('inventory', InventoryController::class)->except(['index', 'show']);

    // Rapports et Analyses
    Route::prefix('reports')->group(function () {
        Route::get('revenue', [ReportController::class, 'getRevenueReport']); 
    });
});
