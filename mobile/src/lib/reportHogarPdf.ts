import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { Platform } from 'react-native';

import type { ReportHogarKpis, ReportHogarOrder } from '@/types/api';

/**
 * Datos que necesita el PDF: los mismos que ya carga la pantalla, sin una
 * llamada extra al servidor. Se genera el documento desde el HTML de la vista
 * web para que el archivo se vea igual que "Reportes Hogar" en el navegador.
 */
export type ReportHogarPdfData = {
  isPrestador: boolean;
  kpis: ReportHogarKpis;
  orders: ReportHogarOrder[];
  filters: {
    fecha_desde: string | null;
    fecha_hasta: string | null;
    estado: string | null;
    tipo: string | null;
  };
};

const TYPE_LABELS: Record<string, string> = {
  EMERGENCY: 'Urgencia 24/7',
  SCHEDULED: 'Cita Programada',
  QUOTE: 'Presupuesto',
};

const ESTADO_LABELS: Record<string, string> = {
  REQUESTED: 'Pendiente',
  QUOTED: 'Presupuestado',
  ACCEPTED: 'Confirmado / Cita',
  IN_PROGRESS: 'En camino / Proceso',
  COMPLETED: 'Completado',
  CANCELLED: 'Cancelado',
};

function money(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  const negative = n < 0;
  const abs = Math.abs(Number.isNaN(n) ? 0 : n);
  const fixed = abs.toFixed(2);
  const [intPart, decPart] = fixed.split('.');
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

function dateTime(iso: string | null | undefined): string {
  if (!iso) {
    return '';
  }

  const date = new Date(String(iso).replace(' ', 'T'));

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function displayDate(ymd: string | null): string {
  if (!ymd) {
    return '—';
  }

  const [y, m, d] = ymd.split('-');

  if (!y || !m || !d) {
    return ymd;
  }

  return `${d}/${m}/${y}`;
}

function statusPill(status: string): string {
  let background = '#f1f5f9';
  let foreground = '#475569';

  if (status === 'COMPLETED') {
    background = '#d1fae5';
    foreground = '#065f46';
  } else if (['ACCEPTED', 'IN_PROGRESS', 'QUOTED'].includes(status)) {
    background = '#e0f2fe';
    foreground = '#0369a1';
  } else if (status === 'CANCELLED') {
    background = '#fee2e2';
    foreground = '#991b1b';
  }

  return `<span class="pill" style="background:${background};color:${foreground};">${escapeHtml(status)}</span>`;
}

function kpiCard(label: string, value: string, accent: string): string {
  return `
    <div class="kpi" style="border-left-color:${accent};">
      <span class="kpi-label">${escapeHtml(label)}</span>
      <div class="kpi-value">${value}</div>
    </div>
  `;
}

/**
 * HTML del reporte. Sigue el mismo encabezado, KPIs y tabla de la vista web
 * (resources/views/reports/hogar.blade.php).
 */
export function buildReportHogarHtml(data: ReportHogarPdfData): string {
  const { isPrestador, kpis, orders, filters } = data;

  const kpisHtml = [
    kpiCard(
      isPrestador ? 'Total Facturado' : 'Total Gastado',
      money(kpis.total_economico),
      '#D24C19',
    ),
    kpiCard('Seña en Custodia', money(kpis.senas_custodia), '#f59e0b'),
    kpiCard('Servicios Finalizados', String(kpis.completados), '#10b981'),
    ...(isPrestador
      ? [
          kpiCard('Efectividad Cotizaciones', `${kpis.efectividad_cotizaciones ?? 0}%`, '#3b82f6'),
          kpiCard(
            'Trabajos Pendientes',
            `${kpis.pendientes ?? 0} (${kpis.en_proceso ?? 0} en proceso)`,
            '#6366f1',
          ),
        ]
      : []),
    kpiCard(
      'Reputación / Satisfacción',
      `★ ${kpis.promedio_reputacion ?? 0} <span class="kpi-hint">(${kpis.total_resenas ?? 0} reseñas)</span>`,
      '#ec4899',
    ),
    kpiCard('Cancelaciones', String(kpis.cancelados), '#ef4444'),
  ].join('');

  const rowsHtml = orders.length
    ? orders
        .map((order) => {
          const thirdParty = isPrestador
            ? `<strong>${escapeHtml(order.customer?.name ?? 'Cliente')}</strong>
               <div class="muted">${escapeHtml(order.customer?.phone ?? order.customer?.email ?? '')}</div>`
            : `<strong>${escapeHtml(order.merchant?.business_name ?? 'Prestador')}</strong>
               <div class="muted">${escapeHtml(
                 order.merchant?.phone ?? order.merchant?.contact_name ?? '',
               )}</div>`;

          return `
            <tr>
              <td class="order-id">#${escapeHtml(order.id)}</td>
              <td class="muted nowrap">${escapeHtml(dateTime(order.created_at))}</td>
              <td>${thirdParty}</td>
              <td>
                <span class="type-pill">${escapeHtml(TYPE_LABELS[order.order_type] ?? order.order_type)}</span>
                <div class="subgroup">${escapeHtml(order.sub_group?.description ?? 'Servicio Hogar')}</div>
              </td>
              <td class="detail">${escapeHtml(order.description ?? 'Sin detalle especificado')}</td>
              <td class="amount nowrap">${money(order.total_amount)}</td>
              <td class="deposit nowrap">
                ${money(order.deposit_amount)}
                <div class="muted small">${escapeHtml(order.escrow_status ?? 'NONE')}</div>
              </td>
              <td class="nowrap">${statusPill(order.status)}</td>
            </tr>
          `;
        })
        .join('')
    : `
      <tr>
        <td colspan="8" class="empty">
          No se encontraron registros de servicios para el hogar con los filtros seleccionados.
        </td>
      </tr>
    `;

  return `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reportes Hogar - PideAca</title>
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
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
    }
    h1 { font-size: 18px; font-weight: 700; color: #0c2a4d; margin: 0; }
    .subtitle { font-size: 13px; color: #64748b; margin: 4px 0 0; }
    .filters { font-size: 11px; color: #64748b; margin-top: 8px; }
    .filters strong { color: #475569; }
    .kpis {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .kpi {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-left: 4px solid #D24C19;
      border-radius: 8px;
      padding: 18px;
    }
    .kpi-label {
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
    }
    .kpi-value { font-size: 22px; font-weight: 700; color: #0c2a4d; margin-top: 6px; }
    .kpi-hint { font-size: 12px; color: #64748b; font-weight: normal; }
    .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .panel-title {
      padding: 16px 20px;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
      font-weight: 700;
      font-size: 14px;
      color: #0c2a4d;
    }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; }
    thead tr { background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0; }
    th { padding: 12px 16px; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    td { padding: 12px 16px; vertical-align: top; }
    .order-id { font-weight: 700; color: #0c2a4d; font-family: monospace; }
    .muted { color: #64748b; font-size: 11px; }
    .small { font-size: 10px; }
    .nowrap { white-space: nowrap; }
    .type-pill {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      background: #e0f2fe;
      color: #0369a1;
    }
    .subgroup { font-weight: 600; color: #1e293b; margin-top: 3px; }
    .detail { color: #475569; max-width: 220px; }
    .amount { font-weight: 700; color: #0c2a4d; }
    .deposit { color: #d97706; font-weight: 600; }
    .pill {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 700;
    }
    .empty { padding: 32px; text-align: center; color: #94a3b8; font-style: italic; }
    @page { margin: 16px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div>
        <h1>Reportes Hogar</h1>
        <p class="subtitle">Consolidado de servicios, métricas financieras y estados del Módulo Hogar</p>
        <p class="filters">
          <strong>Desde:</strong> ${escapeHtml(displayDate(filters.fecha_desde))}
          &nbsp;·&nbsp; <strong>Hasta:</strong> ${escapeHtml(displayDate(filters.fecha_hasta))}
          &nbsp;·&nbsp; <strong>Estado:</strong> ${escapeHtml(
            filters.estado ? ESTADO_LABELS[filters.estado] ?? filters.estado : 'Todos los estados',
          )}
          &nbsp;·&nbsp; <strong>Modalidad:</strong> ${escapeHtml(
            filters.tipo ? TYPE_LABELS[filters.tipo] ?? filters.tipo : 'Todas las modalidades',
          )}
        </p>
      </div>
      <div class="muted">Generado ${escapeHtml(dateTime(new Date().toISOString()))}</div>
    </div>

    <div class="kpis">${kpisHtml}</div>

    <div class="panel">
      <div class="panel-title">Detalle de Órdenes y Solicitudes Hogar</div>
      <table>
        <thead>
          <tr>
            <th>N° Orden</th>
            <th>Fecha / Hora</th>
            <th>${isPrestador ? 'Cliente' : 'Prestador'}</th>
            <th>Tipo / Rubro</th>
            <th>Problema / Detalle</th>
            <th>Monto Total</th>
            <th>Seña Custodia</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
      </table>
    </div>
  </div>
</body>
</html>`;
}

/**
 * Genera y comparte el PDF. En web abre el diálogo de impresión del navegador
 * (igual que el botón de la vista web); en Android/iOS crea el archivo en la
 * caché y abre la hoja de compartir.
 */
export async function exportReportHogarPdf(data: ReportHogarPdfData): Promise<void> {
  const html = buildReportHogarHtml(data);

  if (Platform.OS === 'web') {
    printOnWeb(html);
    return;
  }

  const { uri } = await Print.printToFileAsync({ html });

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(uri, {
      UTI: 'com.adobe.pdf',
      mimeType: 'application/pdf',
      dialogTitle: 'Reportes Hogar',
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
    // El diálogo de impresión bloquea en la mayoría de los navegadores; el
    // timeout es un respaldo para liberar el iframe cuando no lo hace.
    setTimeout(cleanup, 1000);
  };

  iframe.srcdoc = html;
  document.body.appendChild(iframe);
}
