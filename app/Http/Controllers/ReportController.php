<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Génère un rapport de revenus basés sur les commandes finalisées.
     * Accessible par Admin seulement.
     * @return JsonResponse
     */
    public function getRevenueReport(Request $request): JsonResponse
    {
        // 1. Filtrage par date (facultatif mais essentiel pour les rapports)
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Order::query();
        
        // Seules les commandes "livrées" ou "payées" complètes sont comptabilisées comme revenu.
        $query->whereIn('status', ['delivered', 'paid']); 

        // 2. Application des filtres de date
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // 3. Agrégation des données
        $totalRevenue = (clone $query)->sum('final_price_fcfa');
        $orderCount = (clone $query)->count();
        $averageOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;
        
        // 4. Analyse par mois (pour un graphique)
        $monthlyRevenue = (clone $query)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("SUM(final_price_fcfa) as revenue")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();


        return response()->json([
            'message' => 'Rapport de revenus généré avec succès.',
            'summary' => [
                'total_revenue_fcfa' => round($totalRevenue, 2),
                'total_completed_orders' => $orderCount,
                'average_order_value_fcfa' => round($averageOrderValue, 2),
            ],
            'monthly_breakdown' => $monthlyRevenue,
        ]);
    }
}