import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { Platform } from 'react-native';

import type {
  ReportComercioHistoryResponse,
  ReportComercioNutritionResponse,
  ReportComercioSavingsResponse,
  ReportComercioSubscriptionsResponse,
} from '@/types/api';

/**
 * Datos del PDF de Reportes Comercio. Se genera con lo que ya cargó la
 * pantalla, sin una llamada extra, igual que hace la vista web con el
 * contenido del tab activo al presionar "PDF / Imprimir".
 */
export type ReportComercioPdfData =
  | { tab: 'savings'; data: ReportComercioSavingsResponse }
  | { tab: 'mysubs'; data: ReportComercioSubscriptionsResponse }
  | { tab: 'history'; data: ReportComercioHistoryResponse }
  | { tab: 'nutrition'; data: ReportComercioNutritionResponse; month: string };

const TAB_TITLES: Record<ReportComercioPdfData['tab'], string> = {
  savings: 'Mi ahorro',
  mysubs: 'Suscripciones activas',
  history: 'Histórico y hábitos',
  nutrition: 'Resumen nutricional',
};

const FREQUENCY_LABELS: Record<string, string> = {
  DAILY: 'cada día',
  WEEKLY: 'cada semana',
  BIWEEKLY: 'cada dos semanas',
  MONTHLY: 'cada mes',
};

const DAY_LABELS: Record<number, string> = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miércoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sábado',
  7: 'Domingo',
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

function table(headers: string[], rows: string[][], empty: string): string {
  if (rows.length === 0) {
    return `<div class="empty">${escapeHtml(empty)}</div>`;
  }

  return `
    <div class="panel">
      <table>
        <thead>
          <tr>${headers.map((header) => `<th>${escapeHtml(header)}</th>`).join('')}</tr>
        </thead>
        <tbody>
          ${rows
            .map(
              (row) =>
                `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`,
            )
            .join('')}
        </tbody>
      </table>
    </div>
  `;
}

function savingsBody(data: ReportComercioSavingsResponse): string {
  const s = data.summary;

  return `
    ${kpiGrid([
      { label: 'Total ahorrado', value: money(s.total_saved), color: '#047857', hint: 'Vs. comprar por separado' },
      { label: 'Por suscripciones', value: money(s.saved_with_subscriptions) },
      { label: 'Por promociones', value: money(s.saved_with_promotions) },
      { label: 'Ciclos suscriptos pagados', value: String(s.paid_subscription_cycles) },
      { label: 'Pedidos con descuento', value: String(s.orders_with_discount) },
    ])}
    <div class="banner">
      Comprar con suscripción y promociones te dejó <strong>${money(s.total_saved)}</strong>
      frente a los precios Express del mismo carrito.
    </div>
    ${table(
      ['Suscripción', 'Comercio'],
      data.by_subscription.map((sub) => [
        `<strong>${escapeHtml(sub.plan_title ?? 'Plan')}</strong>`,
        escapeHtml(sub.provider ?? '—'),
      ]),
      'Todavía no tenés suscripciones generando ahorro.',
    )}
  `;
}

function subscriptionsBody(data: ReportComercioSubscriptionsResponse): string {
  const s = data.summary;

  const cards = data.subscriptions.length
    ? data.subscriptions
        .map((sub) => {
          const active = sub.status === 'ACTIVE';
          const nextCharge = sub.next_charge
            ? `Cobro ${money(sub.next_charge.total)} el ${escapeHtml(sub.next_charge.period_start ?? '—')}`
            : 'Sin cobro pendiente';
          const products = sub.products.length
            ? sub.products
                .map((product) => `${escapeHtml(product.name ?? '')} x${Number(product.quantity || 0)}`)
                .join(' · ')
            : '';

          return `
            <div class="sub" style="border-left-color:${active ? '#D24C19' : '#f59e0b'};">
              <div class="sub-head">
                <div>
                  <strong class="sub-title">${escapeHtml(sub.plan_title ?? 'Plan')}</strong>
                  <span class="muted"> ${escapeHtml(sub.provider ?? '')}</span>
                </div>
                <div>
                  <span class="pill" style="background:${active ? '#d1fae5' : '#fef3c7'};color:${active ? '#047857' : '#b45309'};">
                    ${escapeHtml(sub.status_label || sub.status)}
                  </span>
                  <strong class="sub-amount">${money(sub.charge_amount)}</strong>
                  <span class="muted"> ${escapeHtml(FREQUENCY_LABELS[sub.frequency ?? ''] ?? '')}</span>
                </div>
              </div>
              <div class="sub-grid">
                <span><span class="muted">Próxima entrega:</span> <strong>${escapeHtml(sub.next_delivery_date ?? '—')}</strong></span>
                <span><span class="muted">Día preferido:</span> ${escapeHtml(
                  DAY_LABELS[sub.preferred_delivery_day ?? 0] ?? 'Sin preferencia',
                )}</span>
                <span><span class="muted">Horario:</span> ${escapeHtml(sub.preferred_delivery_time ?? 'Sin preferencia')}</span>
                <span><span class="muted">Pago:</span> ${escapeHtml(
                  sub.payment_method === 'cash' ? 'Efectivo' : (sub.payment_method ?? '—'),
                )}</span>
                <span><span class="muted">Cobro:</span> ${nextCharge}</span>
              </div>
              ${products ? `<p class="sub-products"><span class="muted">Productos:</span> ${products}</p>` : ''}
            </div>
          `;
        })
        .join('')
    : `<div class="empty">No tenés suscripciones activas o pausadas. Armá un plan desde Comercios.</div>`;

  return `
    ${kpiGrid([
      { label: 'Activas', value: String(s.active_count), color: '#047857' },
      { label: 'Pausadas', value: String(s.paused_count), color: '#b45309' },
      { label: 'Compromiso mensual', value: money(s.monthly_commitment), color: '#D24C19' },
    ])}
    ${cards}
  `;
}

function historyBody(data: ReportComercioHistoryResponse): string {
  const s = data.summary;

  return `
    ${kpiGrid([
      { label: 'Pedidos realizados', value: String(s.total_orders) },
      { label: 'Total gastado', value: money(s.total_spent) },
      { label: 'Tu plato favorito', value: escapeHtml(s.favorite_dish ?? '—'), hint: 'El que más pediste' },
      { label: 'Tu comercio favorito', value: escapeHtml(s.favorite_provider ?? '—') },
    ])}
    <h3>Platos más pedidos <span class="h3-hint">(reordenables en 1 clic)</span></h3>
    ${table(
      ['Plato', 'Veces pedido', 'Unidades', 'Gastado', 'Última vez', ''],
      data.top_products.map((product) => [
        `<strong>${escapeHtml(product.name ?? '—')}</strong>`,
        String(Number(product.times_ordered || 0)),
        String(Number(product.total_quantity || 0)),
        money(product.total_spent),
        escapeHtml(shortDate(product.last_ordered_at)),
        product.can_reorder_1_click
          ? '<span class="pill" style="background:#fff1eb;color:#D24C19;">Reordenar 1-click</span>'
          : '',
      ]),
      'Todavía no tenés pedidos en tu historial.',
    )}
    <h3>Comercios más frecuentes</h3>
    ${table(
      ['Comercio', 'Pedidos', 'Gastado', 'Último pedido'],
      data.top_providers.map((provider) => [
        `<strong>${escapeHtml(provider.business_name ?? '—')}</strong>`,
        String(Number(provider.orders_count || 0)),
        money(provider.total_spent),
        escapeHtml(shortDate(provider.last_order_at)),
      ]),
      'Todavía no tenés comercios frecuentes.',
    )}
  `;
}

function nutritionBody(data: ReportComercioNutritionResponse): string {
  const s = data.summary;

  return `
    ${kpiGrid([
      { label: 'Pedidos entregados', value: String(s.total_delivered_orders) },
      { label: 'Comidas recibidas', value: String(s.total_meals), color: '#D24C19' },
      { label: 'Gastado en el mes', value: money(s.total_spent) },
      { label: 'Categoría principal', value: escapeHtml(s.main_category ?? '—'), color: '#047857' },
    ])}
    ${table(
      ['Categoría', 'Comidas', 'Unidades', 'Gastado', 'Más pedidos'],
      data.categories.map((category) => [
        `<strong>${escapeHtml(category.category_name ?? '—')}</strong>`,
        String(Number(category.meals_count || 0)),
        String(Number(category.total_quantity || 0)),
        money(category.total_spent),
        `<span class="muted">${escapeHtml(
          category.top_products.map((product) => `${product.name} x${product.quantity}`).join(' · ') || '—',
        )}</span>`,
      ]),
      'Todavía no recibiste comidas en el mes seleccionado.',
    )}
  `;
}

/**
 * HTML del reporte. Sigue el encabezado, KPIs, banners y tablas de la vista
 * web (resources/views/dashboard/sections/reportes.blade.php).
 */
export function buildReportComercioHtml(data: ReportComercioPdfData, userName?: string): string {
  const title = TAB_TITLES[data.tab];
  const filters =
    data.tab === 'nutrition' && data.month ? `Mes: ${data.month}` : '';

  const body =
    data.tab === 'savings'
      ? savingsBody(data.data)
      : data.tab === 'mysubs'
        ? subscriptionsBody(data.data)
        : data.tab === 'history'
          ? historyBody(data.data)
          : nutritionBody(data.data);

  return `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reportes Comercio - ${escapeHtml(title)}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 24px;
      background: #f8fafc;
      color: #1e293b;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .wrap { max-width: 1300px; margin: 0 auto; }
    .header { border-bottom: 2px solid #D24C19; padding-bottom: 10px; margin-bottom: 16px; }
    .eyebrow { font-size: 10px; font-weight: 700; color: #D24C19; margin: 0; letter-spacing: .8px; text-transform: uppercase; }
    h1 { font-size: 20px; font-weight: 700; color: #0c2a4d; margin: 6px 0 4px; }
    .meta { font-size: 11px; color: #6b7280; margin: 0; }
    .kpis {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 12px;
      margin-bottom: 16px;
    }
    .kpi { background: #fff; border: 1px solid #e5e7eb; padding: 14px 16px; }
    .kpi-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; }
    .kpi-value { font-size: 17px; font-weight: 700; margin: 6px 0 0; }
    .kpi-hint { font-size: 11px; color: #9ca3af; margin: 4px 0 0; }
    .banner { background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px 14px; font-size: 12px; color: #065f46; margin-bottom: 14px; }
    .panel { background: #fff; border: 1px solid #e5e7eb; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; text-align: left; }
    th { padding: 9px 10px; background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #e5e7eb; }
    td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
    h3 { font-size: 14px; font-weight: 700; color: #0c2a4d; margin: 20px 0 8px; }
    h3:first-of-type { margin-top: 0; }
    .h3-hint { font-size: 11px; font-weight: 500; color: #9ca3af; }
    .empty { background: #fff; border: 1px dashed #d1d5db; padding: 32px 24px; text-align: center; font-size: 13px; color: #6b7280; }
    .muted { color: #6b7280; font-size: 11px; }
    .pill { display: inline-block; padding: 3px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
    .sub { background: #fff; border: 1px solid #e5e7eb; border-left: 3px solid #D24C19; padding: 16px 18px; margin-bottom: 12px; }
    .sub-head { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 8px; }
    .sub-title { font-size: 14px; color: #0c2a4d; }
    .sub-amount { font-size: 13px; font-weight: 700; color: #0c2a4d; margin-left: 6px; }
    .sub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; font-size: 12px; color: #374151; }
    .sub-products { font-size: 12px; color: #475569; margin: 10px 0 0; }
    @page { margin: 16px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <p class="eyebrow">PideAca · Reportes</p>
      <h1>${escapeHtml(title)}</h1>
      <p class="meta">
        ${escapeHtml(userName ?? '')}${userName ? ' · ' : ''}Generado el ${escapeHtml(
          new Date().toLocaleDateString('es-AR', { day: '2-digit', month: 'long', year: 'numeric' }),
        )}${filters ? ` · ${escapeHtml(filters)}` : ''}
      </p>
    </div>
    ${body}
  </div>
</body>
</html>`;
}

/**
 * Genera y comparte el PDF. En web abre el diálogo de impresión del navegador
 * (igual que la vista web); en Android/iOS crea el archivo en la caché y abre
 * la hoja de compartir.
 */
export async function exportReportComercioPdf(
  data: ReportComercioPdfData,
  userName?: string,
): Promise<void> {
  const html = buildReportComercioHtml(data, userName);

  if (Platform.OS === 'web') {
    printOnWeb(html);
    return;
  }

  const { uri } = await Print.printToFileAsync({ html });

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(uri, {
      UTI: 'com.adobe.pdf',
      mimeType: 'application/pdf',
      dialogTitle: 'Reportes Comercio',
    });
    return;
  }

  throw new Error('No se pudo abrir el compartir de archivos en este dispositivo.');
}

function printOnWeb(html: string): void {
  const iframe = document.createElement('iframe');
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = '0';

  const cleanup = () => {
    if (iframe.parentNode) {
      iframe.parentNode.removeChild(iframe);
    }
  };

  iframe.onload = () => {
    const frameWindow = iframe.contentWindow;

    if (!frameWindow) {
      cleanup();
      return;
    }

    frameWindow.focus();
    frameWindow.print();
    setTimeout(cleanup, 1000);
  };

  iframe.srcdoc = html;
  document.body.appendChild(iframe);
}
