import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { Platform } from 'react-native';

import type {
  ProviderCampaignsResponse,
  ProviderCommissionResponse,
  ProviderForecastResponse,
  ProviderHeatmapResponse,
  ProviderLtvResponse,
  ProviderMarginsResponse,
  ProviderMrrResponse,
} from '@/types/api';

/**
 * PDF de Reportes Comercio del prestador: mismas 7 vistas que los tabs de
 * la web (Rentabilidad, MRR, Comisiones, Clientes, Campanas, Demanda,
 * Abastecimiento). Se genera con lo ya cargado en pantalla.
 */
export type ProviderComercioPdfData =
  | { tab: 'margins'; data: ProviderMarginsResponse }
  | { tab: 'mrr'; data: ProviderMrrResponse }
  | { tab: 'commission'; data: ProviderCommissionResponse }
  | { tab: 'ltv'; data: ProviderLtvResponse }
  | { tab: 'campaigns'; data: ProviderCampaignsResponse }
  | { tab: 'heatmap'; data: ProviderHeatmapResponse }
  | { tab: 'forecast'; data: ProviderForecastResponse };

const TAB_TITLES: Record<ProviderComercioPdfData['tab'], string> = {
  margins: 'Rentabilidad',
  mrr: 'Suscripciones y MRR',
  commission: 'Comisiones y ahorro',
  ltv: 'Clientes',
  campaigns: 'Campañas WhatsApp',
  heatmap: 'Demanda',
  forecast: 'Abastecimiento',
};

function money(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  const negative = n < 0;
  const abs = Math.abs(Number.isNaN(n) ? 0 : n);
  const [intPart, decPart] = abs.toFixed(2).split('.');
  const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  const out = `$ ${grouped},${decPart ?? '00'}`;

  return negative ? `-${out}` : out;
}

function escapeHtml(value: string | number | null | undefined): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function shortDate(iso: string | null | undefined): string {
  if (!iso) {
    return '—';
  }

  return String(iso).slice(0, 10);
}

function kpiGrid(items: { label: string; value: string; color?: string; hint?: string }[]): string {
  return `
    <div class="kpis">
      ${items
        .map(
          (item) => `
        <div class="kpi">
          <span class="kpi-label">${escapeHtml(item.label)}</span>
          <div class="kpi-value" style="color:${item.color ?? '#0c2a4d'};">${item.value}</div>
          ${item.hint ? `<div class="kpi-hint">${escapeHtml(item.hint)}</div>` : ''}
        </div>
      `,
        )
        .join('')}
    </div>
  `;
}

function table(headers: string[], rows: string[][]): string {
  return `
    <table>
      <thead><tr>${headers.map((h) => `<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>
      <tbody>
        ${rows.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('')}
      </tbody>
    </table>
  `;
}

function bodyHtml(payload: ProviderComercioPdfData): string {
  switch (payload.tab) {
    case 'margins': {
      const s = payload.data.summary;
      return (
        kpiGrid([
          { label: 'Productos', value: String(s.total_products) },
          { label: 'Margen promedio', value: s.average_margin_percent === null ? '—' : `${s.average_margin_percent}%` },
          { label: 'En verde', value: String(s.green), color: '#047857' },
          { label: 'En amarillo', value: String(s.yellow), color: '#b45309' },
          { label: 'En rojo', value: String(s.red), color: '#b91c1d' },
        ]) +
        table(
          ['Producto', 'Precio', 'Costo', 'Margen $', 'Margen %', 'Semáforo', 'Recomendación'],
          payload.data.products.map((p) => [
            `<strong>${escapeHtml(p.name ?? '—')}</strong>${p.has_recipe ? '' : '<br><span class="muted">Sin ficha técnica</span>'}`,
            money(p.sale_price),
            money(p.production_cost),
            p.gross_margin_amount === null ? '—' : money(p.gross_margin_amount),
            p.gross_margin_percent === null ? '—' : `${p.gross_margin_percent}%`,
            escapeHtml(p.traffic_light_label ?? p.traffic_light),
            escapeHtml(p.recommendation ?? '—'),
          ]),
        )
      );
    }
    case 'mrr': {
      const s = payload.data.summary;
      return (
        kpiGrid([
          { label: 'MRR', value: money(s.mrr), color: '#047857' },
          { label: 'Proyección 30 días', value: money(s.projection_30_days) },
          { label: 'Activos', value: String(s.active_subscribers) },
          { label: 'Churn', value: `${s.churn_rate_percent}%` },
          { label: 'Ticket promedio', value: money(s.average_ticket) },
          { label: 'Cobrado en período', value: money(s.collected_in_period) },
        ]) +
        table(
          ['Plan', 'Frecuencia', 'Suscriptores', 'Cobro', 'MRR'],
          payload.data.by_plan.map((p) => [
            escapeHtml(p.title ?? '—'),
            escapeHtml(p.frequency ?? '—'),
            String(p.subscribers),
            money(p.charge_amount),
            money(p.mrr),
          ]),
        )
      );
    }
    case 'commission': {
      const s = payload.data.summary;
      const p = payload.data.period;
      return (
        `<p class="muted">Período: ${escapeHtml(p.start_date)} al ${escapeHtml(p.end_date)}</p>` +
        kpiGrid([
          { label: 'Volumen bruto', value: money(s.gross_volume) },
          { label: 'Comisión PideAca', value: `${money(s.pideaca_commission_amount)} (${s.pideaca_commission_percent}%)` },
          { label: 'Ahorro vs. app tradicional', value: `${money(s.net_savings_vs_min)} – ${money(s.net_savings_vs_max)}`, color: '#047857' },
        ]) +
        `<p class="muted">${escapeHtml(s.savings_note ?? '')}</p>`
      );
    }
    case 'ltv': {
      const s = payload.data.summary;
      const row = (c: ProviderLtvResponse['vip_customers'][number]) => [
        escapeHtml(c.name ?? '—'),
        escapeHtml(c.email ?? '—'),
        String(c.orders_count),
        money(c.ltv),
        shortDate(c.last_order_at),
        c.days_since_last_order === null ? '—' : `${c.days_since_last_order}d`,
      ];
      return (
        kpiGrid([
          { label: 'Clientes', value: String(s.total_customers) },
          { label: 'VIP', value: String(s.vip_count), color: '#047857' },
          { label: 'En riesgo', value: String(s.at_risk_count), color: '#b91c1d' },
        ]) +
        '<h3>VIP por valor de vida</h3>' +
        table(['Cliente', 'Correo', 'Pedidos', 'LTV', 'Última compra', 'Inactivo'], payload.data.vip_customers.map(row)) +
        '<h3>En riesgo</h3>' +
        table(['Cliente', 'Correo', 'Pedidos', 'LTV', 'Última compra', 'Inactivo'], payload.data.at_risk_customers.map(row))
      );
    }
    case 'campaigns': {
      const s = payload.data.summary;
      return (
        kpiGrid([
          { label: 'Enviados', value: String(s.total_sent) },
          { label: 'Convertidos', value: String(s.total_converted), color: '#047857' },
          { label: 'Conversión', value: `${s.overall_conversion_rate_percent}%` },
          { label: 'Ventas atribuidas', value: money(s.attributed_sales_total) },
        ]) +
        table(
          ['Regla', 'Código', 'Enviados', 'Fallidos', 'Convertidos', 'Tasa', 'Ventas'],
          payload.data.campaigns.map((c) => [
            escapeHtml(c.rule_type ?? '—'),
            escapeHtml(c.discount_code ?? '—'),
            String(c.sent),
            String(c.failed),
            String(c.converted),
            `${c.conversion_rate_percent}%`,
            money(c.attributed_sales),
          ]),
        )
      );
    }
    case 'heatmap': {
      const s = payload.data.summary;
      return (
        kpiGrid([
          { label: 'Pedidos', value: String(s.total_orders) },
          { label: 'Express', value: String(s.express_orders) },
          { label: 'Suscripciones', value: String(s.subscription_orders) },
        ]) +
        table(
          ['Día', 'Pedidos', 'Hora pico', 'Pedidos pico'],
          payload.data.heatmap.map((d) => [
            escapeHtml(d.day_name),
            String(d.total_orders),
            `${d.peak_hour}:00`,
            String(d.peak_hour_orders),
          ]),
        )
      );
    }
    case 'forecast': {
      const s = payload.data.summary;
      const p = payload.data.period;
      return (
        `<p class="muted">Período: ${escapeHtml(p.start_date)} al ${escapeHtml(p.end_date)}</p>` +
        kpiGrid([
          { label: 'Suscripciones activas', value: String(s.active_subscriptions) },
          { label: 'Platos', value: String(s.total_products) },
          { label: 'Insumos', value: String(s.total_supplies) },
          { label: 'Costo insumos', value: money(s.estimated_supplies_cost) },
        ]) +
        '<h3>Platos necesarios</h3>' +
        table(
          ['Plato', 'Cantidad', 'Suscripciones'],
          payload.data.products.map((x) => [escapeHtml(x.name ?? '—'), String(x.quantity), String(x.subscriptions)]),
        ) +
        '<h3>Insumos necesarios</h3>' +
        table(
          ['Insumo', 'Unidad', 'Cantidad', 'Costo est.'],
          payload.data.supplies.map((x) => [
            escapeHtml(x.name ?? '—'),
            escapeHtml(x.unit_symbol ?? '—'),
            String(x.quantity_required),
            money(x.estimated_cost),
          ]),
        )
      );
    }
  }
}

export async function exportProviderComercioPdf(
  payload: ProviderComercioPdfData,
  userName?: string | null,
): Promise<void> {
  const title = TAB_TITLES[payload.tab];
  const fecha = new Date().toLocaleDateString('es-AR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });

  const html = `
    <html>
      <head>
        <meta charset="utf-8" />
        <style>
          body { font-family: -apple-system, Arial, sans-serif; color: #0c2a4d; padding: 24px; }
          h1 { font-size: 20px; margin: 6px 0 4px; }
          h3 { font-size: 14px; margin: 18px 0 8px; }
          .brand { font-size: 10px; font-weight: 700; color: #D24C19; letter-spacing: .8px; text-transform: uppercase; margin: 0; }
          .meta { font-size: 11px; color: #6b7280; margin: 0 0 16px; }
          .muted { font-size: 11px; color: #6b7280; }
          .kpis { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
          .kpi { flex: 1 1 30%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; }
          .kpi-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
          .kpi-value { font-size: 16px; font-weight: 800; margin-top: 4px; }
          .kpi-hint { font-size: 10px; color: #6b7280; margin-top: 2px; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 16px; }
          th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; vertical-align: top; }
          th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
        </style>
      </head>
      <body>
        <p class="brand">PideAca · Reportes Comercio</p>
        <h1>${escapeHtml(title)}</h1>
        <p class="meta">${escapeHtml(userName ?? '')} · Generado el ${escapeHtml(fecha)}</p>
        ${bodyHtml(payload)}
      </body>
    </html>
  `;

  const { uri } = await Print.printToFileAsync({ html });

  if (Platform.OS === 'web') {
    const link = document.createElement('a');
    link.href = uri;
    link.download = `reporte-comercio-${payload.tab}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return;
  }

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(uri, {
      mimeType: 'application/pdf',
      dialogTitle: `Reporte: ${title}`,
    });
  }
}
