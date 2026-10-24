<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\ServiceRating;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReporteHogarController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $isPrestador = strcasecmp((string) ($user->typeUser?->description ?? ''), 'Prestador') === 0;

        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');
        $estado = $request->query('estado');
        $tipoOrden = $request->query('tipo');

        $query = ServiceOrder::with([
            'customer:id,name,email,phone',
            'merchant.user:id,name,email,phone',
            'subGroup:id,description',
            'ratings',
            'quotes',
        ]);

        if ($isPrestador) {
            $provider = $user->provider;
            if (! $provider) {
                return view('reports.hogar', [
                    'user' => $user,
                    'isPrestador' => true,
                    'kpis' => [],
                    'orders' => collect(),
                    'filters' => $request->all(),
                ]);
            }
            $query->where('merchant_id', $provider->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        if ($fechaDesde) {
            $query->where('created_at', '>=', Carbon::parse($fechaDesde)->startOfDay());
        }
        if ($fechaHasta) {
            $query->where('created_at', '<=', Carbon::parse($fechaHasta)->endOfDay());
        }
        if ($estado) {
            $query->where('status', $estado);
        }
        if ($tipoOrden) {
            $query->where('order_type', $tipoOrden);
        }

        $orders = $query->latest()->get();

        $kpis = [];
        if ($isPrestador) {
            $completedOrders = $orders->where('status', 'COMPLETED');
            $totalFacturado = $completedOrders->sum(fn ($o) => (float) ($o->total_amount ?? 0));
            $totalSenas = $completedOrders->sum(fn ($o) => (float) ($o->deposit_amount ?? 0));
            $pendientes = $orders->whereIn('status', ['REQUESTED', 'PENDING'])->count();
            $enProceso = $orders->whereIn('status', ['ACCEPTED', 'PROVIDER_DISPATCHED', 'IN_PROGRESS', 'QUOTED'])->count();
            $completadosCount = $completedOrders->count();
            $cancelados = $orders->where('status', 'CANCELLED')->count();

            $totalQuotes = $orders->where('order_type', 'QUOTE')->count();
            $acceptedQuotes = $orders->where('order_type', 'QUOTE')->where('status', 'COMPLETED')->count();
            $efectividad = $totalQuotes > 0 ? round(($acceptedQuotes / $totalQuotes) * 100, 1) : 0;

            $ratings = ServiceRating::where('ratee_id', $user->id)->get();
            $promedioEstrellas = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : 0;

            $kpis = [
                'total_economico' => $totalFacturado,
                'senas_custodia' => $totalSenas,
                'pendientes' => $pendientes,
                'en_proceso' => $enProceso,
                'completados' => $completadosCount,
                'cancelados' => $cancelados,
                'efectividad_cotizaciones' => $efectividad,
                'promedio_reputacion' => $promedioEstrellas,
                'total_resenas' => $ratings->count(),
            ];
        } else {
            $completedOrders = $orders->where('status', 'COMPLETED');
            $totalGastado = $completedOrders->sum(fn ($o) => (float) ($o->total_amount ?? 0));
            $senasCustodia = $orders->whereIn('escrow_status', ['HELD', 'DEPOSITED'])->sum(fn ($o) => (float) ($o->deposit_amount ?? 0));
            $completadosCount = $completedOrders->count();
            $cancelados = $orders->where('status', 'CANCELLED')->count();

            $ratings = ServiceRating::where('reviewer_id', $user->id)->get();
            $promedioEstrellas = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : 0;

            $kpis = [
                'total_economico' => $totalGastado,
                'senas_custodia' => $senasCustodia,
                'completados' => $completadosCount,
                'cancelados' => $cancelados,
                'promedio_reputacion' => $promedioEstrellas,
                'total_resenas' => $ratings->count(),
            ];
        }

        return view('reports.hogar', [
            'user' => $user,
            'isPrestador' => $isPrestador,
            'kpis' => $kpis,
            'orders' => $orders,
            'filters' => $request->all(),
        ]);
    }

    public function pdf(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $isPrestador = strcasecmp((string) ($user->typeUser?->description ?? ''), 'Prestador') === 0;

        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');
        $estado = $request->query('estado');
        $tipoOrden = $request->query('tipo');

        $query = ServiceOrder::with([
            'customer:id,name,email,phone',
            'merchant.user:id,name,email,phone',
            'subGroup:id,description',
            'ratings',
            'quotes',
        ]);

        if ($isPrestador) {
            $provider = $user->provider;
            if ($provider) {
                $query->where('merchant_id', $provider->id);
            }
        } else {
            $query->where('customer_id', $user->id);
        }

        if ($fechaDesde) {
            $query->where('created_at', '>=', Carbon::parse($fechaDesde)->startOfDay());
        }
        if ($fechaHasta) {
            $query->where('created_at', '<=', Carbon::parse($fechaHasta)->endOfDay());
        }
        if ($estado) {
            $query->where('status', $estado);
        }
        if ($tipoOrden) {
            $query->where('order_type', $tipoOrden);
        }

        $orders = $query->latest()->get();

        $completedOrders = $orders->where('status', 'COMPLETED');
        $totalMonto = $completedOrders->sum(fn ($o) => (float) ($o->total_amount ?? 0));
        $totalSenas = $isPrestador 
            ? $completedOrders->sum(fn ($o) => (float) ($o->deposit_amount ?? 0))
            : $orders->whereIn('escrow_status', ['HELD', 'DEPOSITED'])->sum(fn ($o) => (float) ($o->deposit_amount ?? 0));
        
        $ratings = ServiceRating::where($isPrestador ? 'ratee_id' : 'reviewer_id', $user->id)->get();
        $satisfaccion = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : 0;

        return view('reports.hogar-pdf', [
            'user' => $user,
            'isPrestador' => $isPrestador,
            'orders' => $orders,
            'totalMonto' => $totalMonto,
            'totalSenas' => $totalSenas,
            'satisfaccion' => $satisfaccion,
            'filters' => $request->all(),
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
        ]);
    }
}
