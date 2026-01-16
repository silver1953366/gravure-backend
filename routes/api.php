<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =======================================================================
// Importation de TOUS les contrôleurs
// =======================================================================
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController; 
use App\Http\Controllers\CategoryController; 
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
use App\Http\Controllers\CartController; 
use App\Http\Controllers\CarouselController;

// =======================================================================
// Importation des Middlewares de Rôles
// =======================================================================
use App\Http\Middleware\IsController;
use App\Http\Middleware\AdminMiddleware;

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
// 2. ROUTES PUBLIQUES / CATALOGUE & CARROUSEL
// =======================================================================
// Carrousel public (Front-end : affiche uniquement les slides actives)
Route::get('/carousel', [CarouselController::class, 'index']);

Route::prefix('catalog')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']); 
    Route::get('/materials', [MaterialController::class, 'index']); 
    Route::get('/shapes', [ShapeController::class, 'index']);
    Route::get('/dimensions', [MaterialDimensionController::class, 'index']);
    
    // Route d'estimation utilisée par ClientQuoteService
    Route::post('/quotes/estimate', [QuoteController::class, 'estimate']);
});

// PANIER (Anonyme/Session)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']); 
    // Correction ici : changement de store vers add (si applicable) ou maintien selon votre contrôleur
    Route::post('/items', [CartController::class, 'store']); 
    Route::patch('/items/{cartItem}', [CartController::class, 'update']); 
    Route::delete('/items/{cartItem}', [CartController::class, 'destroy']); 
});


// =======================================================================
// 3. LOGIQUE CLIENT / PROTÉGÉE (Requiert 'auth:sanctum')
// =======================================================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Gestion du Profil Utilisateur ---
    Route::get('user', [UserController::class, 'profile']);
    Route::put('user', [UserController::class, 'updateProfile']);
    Route::post('logout', [AuthController::class, 'logout']); 

    // --- Panier & Devis ---
    Route::post('/cart/convert-to-quote', [CartController::class, 'convertToQuote']); 

    // Gestion des fichiers joints (Images/Conceptions)
    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
    
    // Gestion des DEVIS (Client)
    Route::apiResource('quotes', QuoteController::class)->only(['index', 'show', 'store', 'update']);
    
    // Commandes & Favoris
    Route::post('orders/convert/{quote}', [OrderController::class, 'convertQuoteToOrder']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show']); 
    Route::apiResource('favorites', FavoriteController::class)->except(['update']);

    // Notifications (Client)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
});


// =======================================================================
// 4. LECTURE STOCK (CONTROLLER/OPÉRATEUR)
// =======================================================================
Route::middleware(['auth:sanctum', IsController::class])->group(function () {
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/{inventory}', [InventoryController::class, 'show']);
    });
});


// =======================================================================
// 5. ROUTES D'ADMINISTRATION (Requiert AdminMiddleware)
// =======================================================================
Route::middleware(['auth:sanctum', AdminMiddleware::class])->prefix('admin')->group(function () {
    
    // --- CARROUSSEL (Gestion Admin Totale) ---
    // 'carousel/all' fournit toutes les slides (actives ou non) pour la liste admin
    Route::get('carousel/all', [CarouselController::class, 'indexAdmin']);
    // 'carousel' gère le CRUD (store, update, destroy)
    Route::apiResource('carousel', CarouselController::class)->except(['index']);

    // --- CATALOGUE (CRUD Complet) ---
    Route::apiResource('materials', MaterialController::class);
    Route::apiResource('shapes', ShapeController::class);
    Route::apiResource('material-dimensions', MaterialDimensionController::class)->parameters([
        'material-dimensions' => 'materialDimension'
    ]);
    Route::apiResource('categories', CategoryController::class); 
    Route::apiResource('discounts', DiscountController::class);
    
    // --- GESTION DES DEVIS (ADMIN) ---
    Route::get('quotes', [QuoteController::class, 'index']); 
    Route::put('quotes/{quote}', [QuoteController::class, 'update']);
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']); 
    
    // --- GESTION DES COMMANDES / UTILISATEURS ---
    Route::apiResource('admin-orders', OrderController::class)->except(['store']);
    Route::get('/users/all', [UserController::class, 'getAllClients']);
    Route::apiResource('users', UserController::class);

    // --- GESTION DES NOTIFICATIONS (ADMIN) ---
    Route::prefix('notifications-admin')->group(function () {
        Route::get('/all', [NotificationController::class, 'indexAdmin']);
        Route::post('/send-manual', [NotificationController::class, 'store']);
        Route::apiResource('notifications', NotificationController::class)->only(['update', 'destroy']);
    });

    // --- JOURNAL D'ACTIVITÉ & INVENTAIRE ---
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/{activity}', [ActivityController::class, 'show']);
    Route::apiResource('inventory', InventoryController::class)->except(['index', 'show']);

    // --- RAPPORTS ---
    Route::prefix('reports')->group(function () {
        Route::get('revenue', [ReportController::class, 'getRevenueReport']); 
    });
});