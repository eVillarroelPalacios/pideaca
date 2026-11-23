<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reportes del comercio para la app movil (Fase 2, paridad con la web).
 *
 * Espejo movil de GET /api/v1/provider/reports/* de la web (mismo
 * ReportController, mismos JSON que consumen los tabs de Rentabilidad,
 * MRR, Comisiones, Clientes, Campanas, Demanda y Abastecimiento).
 * Autenticacion: token Bearer via auth:sanctum; el propio
 * ReportController exige comercio asociado (403 si no lo hay).
 */
class ProviderReportsController extends Controller
{
    public function __construct(private ReportController $reports) {}

    public function margins(Request $request): JsonResponse
    {
        return $this->reports->getMarginAndRecipeCost($request);
    }

    public function mrr(Request $request): JsonResponse
    {
        return $this->reports->getMRRAndSubscriptions($request);
    }

    public function commission(Request $request): JsonResponse
    {
        return $this->reports->getCommissionSavings($request);
    }

    public function ltv(Request $request): JsonResponse
    {
        return $this->reports->getLTVAndAtRiskCustomers($request);
    }

    public function campaigns(Request $request): JsonResponse
    {
        return $this->reports->getCampaignPerformance($request);
    }

    public function demandHeatmap(Request $request): JsonResponse
    {
        return $this->reports->getDemandHeatmap($request);
    }

    public function stockForecast(Request $request): JsonResponse
    {
        return $this->reports->getSubscriptionStockForecast($request);
    }
}
