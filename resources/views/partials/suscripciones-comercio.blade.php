{{-- Panel del comercio: planes, ingresos recurrentes y cobros por confirmar. --}}
<section id="dash-{{ $page->url }}" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
        <button type="button" onclick="openSubPlanForm()" title="Agregar plan" aria-label="Agregar plan" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </button>
    </div>

    <div id="sub-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando suscripciones...</p>
    </div>

    <div id="sub-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="sub-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar el panel de suscripciones.</p>
    </div>

    <div id="sub-content" style="display:none;">
        <div id="sub-kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;"></div>

        <div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:16px;">
            <button type="button" class="sub-tab active" data-sub-tab="planes" onclick="switchSubTab('planes')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v7.125c0 .621.504 1.125 1.125 1.125h9.75a5.625 5.625 0 0 0 5.625-5.625V4.5M3.75 21V9.75"/></svg>
                Ingresos por plan
            </button>
            <button type="button" class="sub-tab" data-sub-tab="cobros" onclick="switchSubTab('cobros')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                Cobros por confirmar
            </button>
            <button type="button" class="sub-tab" data-sub-tab="historial" onclick="switchSubTab('historial')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Historial por período
            </button>
        </div>

        <div id="sub-pane-planes" class="sub-pane">
            <div id="sub-plans" style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;"></div>
        </div>

        <div id="sub-pane-cobros" class="sub-pane" style="display:none;">
            <div id="sub-charges" style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;"></div>
        </div>

        <div id="sub-pane-historial" class="sub-pane" style="display:none;">
            <div id="sub-history" style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;"></div>
        </div>
    </div>
</section>

<div id="sub-plan-overlay" style="display:none;position:fixed;inset:0;z-index:320;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px;width:100%;max-width:760px;max-height:88vh;overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
        <div id="sub-plan-form">
            <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px;" id="sub-plan-form-title">Nuevo plan</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:12px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Nombre del plan</label>
                    <input type="text" id="sub-plan-title" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Frecuencia</label>
                    <select id="sub-plan-frequency" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                        <option value="DAILY">Diaria</option>
                        <option value="WEEKLY">Semanal</option>
                        <option value="BIWEEKLY">Quincenal</option>
                        <option value="MONTHLY">Mensual</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Precio</label>
                    <input type="number" step="0.01" min="0" id="sub-plan-price" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Descuento %</label>
                    <input type="number" step="0.01" min="0" max="100" id="sub-plan-discount" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" placeholder="0" />
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Cupos (opcional)</label>
                    <input type="number" min="1" id="sub-plan-capacity" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" placeholder="Ilimitado" />
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;cursor:pointer;padding-bottom:8px;">
                        <input type="checkbox" id="sub-plan-active" checked style="width:16px;height:16px;cursor:pointer;" /> Plan visible
                    </label>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Descripción</label>
                <textarea id="sub-plan-description" rows="2" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;">Productos del plan</label>
                    <button type="button" onclick="addSubPlanRow()" title="Agregar producto" aria-label="Agregar producto" style="background:#D24C19;color:#fff;border:none;padding:6px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>
                <div id="sub-plan-items"></div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:20px;">
                <button type="button" onclick="saveSubPlan()" title="Guardar plan" aria-label="Guardar plan" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
                <button type="button" onclick="closeSubPlanForm()" title="Cancelar" aria-label="Cancelar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <span id="sub-plan-estimate" style="font-size:12px;color:#6b7280;"></span>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var SUB_URL = {
        plans: '{{ url("/api/v1/provider/subscription-plans") }}',
        revenue: '{{ url("/api/v1/provider/subscriptions/revenue") }}',
        payments: '{{ url("/api/v1/provider/subscription-payments") }}',
        catalog: '{{ url("/api/providers") }}'
    };

    var subState = { plans: [], products: [], editing: null };

    var SUB_FREQ = { DAILY: 'Diaria', WEEKLY: 'Semanal', BIWEEKLY: 'Quincenal', MONTHLY: 'Mensual' };
    var SUB_PAYMENT = {
        PENDING: ['Pendiente', '#b45309', '#fef3c7'],
        PAID: ['Pagado', '#047857', '#d1fae5'],
        FAILED: ['Fallido', '#b91c1c', '#fee2e2'],
        REFUNDED: ['Reembolsado', '#6b7280', '#f3f4f6']
    };

    function subBadge(status) {
        var s = SUB_PAYMENT[status] || [status, '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    function subSetState(state, text) {
        var map = { loading: 'sub-loading', message: 'sub-message', content: 'sub-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('sub-message-text');
            if (msg) msg.textContent = text;
        }
    }

    var subActiveTab = 'planes';

    function switchSubTab(tab) {
        subActiveTab = tab || 'planes';
        document.querySelectorAll('.sub-tab').forEach(function(btn) {
            var active = btn.getAttribute('data-sub-tab') === subActiveTab;
            btn.classList.toggle('active', active);
            btn.style.color = active ? '#D24C19' : '#64748b';
            btn.style.borderBottomColor = active ? '#D24C19' : 'transparent';
            btn.style.background = active ? 'linear-gradient(180deg,rgba(210,76,25,.06),transparent)' : 'none';
        });
        ['planes', 'cobros', 'historial'].forEach(function(k) {
            var pane = document.getElementById('sub-pane-' + k);
            if (pane) pane.style.display = (k === subActiveTab) ? 'block' : 'none';
        });
    }

    function subKpi(label, value, hint, color) {
        return '<div style="background:#fff;border:1px solid #e5e7eb;border-top:3px solid ' + (color || '#D24C19')
            + ';padding:14px;">'
            + '<p style="font-size:11px;color:#6b7280;margin:0 0 4px;">' + escapeHtml(label) + '</p>'
            + '<p style="font-size:20px;font-weight:700;color:#0c2a4d;margin:0;">' + value + '</p>'
            + (hint ? '<p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">' + escapeHtml(hint) + '</p>' : '')
            + '</div>';
    }

    function loadSubRevenue() {
        subSetState('loading');

        fdFetchJson(SUB_URL.revenue)
            .then(function (res) {
                if (!res.ok) {
                    subSetState('message', (res.data && res.data.message) || 'No se pudo cargar el panel.');
                    return;
                }

                var data = res.data;
                var rec = data.recurring || {};
                var cash = data.cash || {};

                document.getElementById('sub-kpis').innerHTML =
                    subKpi('Ingreso recurrente (MRR)', fdMoney(rec.mrr), rec.mrr_basis, '#D24C19')
                    + subKpi('Activas', String(rec.active_subscriptions || 0), (rec.paused_subscriptions || 0) + ' pausadas', '#0c2a4d')
                    + subKpi('Cobrado 30 días', fdMoney(cash.collected_last_30_days),
                        'Envíos: ' + fdMoney(cash.delivery_fees_last_30_days), '#047857')
                    + subKpi('Por cobrar', fdMoney(cash.pending_amount), (cash.failed_last_30_days || 0) + ' cobros fallidos', '#b45309');

                var plans = rec.by_plan || [];
                document.getElementById('sub-plans').innerHTML = plans.length
                    ? '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
                        + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Plan</th>'
                        + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Frecuencia</th>'
                        + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Precio</th>'
                        + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Cobra</th>'
                        + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Suscriptores</th>'
                        + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Cupos</th>'
                        + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">MRR</th>'
                        + '</tr></thead><tbody>'
                        + plans.map(function (p) {
                            return '<tr style="border-top:1px solid #f3f4f6;">'
                                + '<td style="padding:10px 12px;">' + escapeHtml(p.title || '—') + '</td>'
                                + '<td style="padding:10px 12px;color:#6b7280;">' + escapeHtml(SUB_FREQ[p.frequency] || p.frequency) + '</td>'
                                + '<td style="padding:10px 12px;text-align:right;">' + fdMoney(p.price) + '</td>'
                                + '<td style="padding:10px 12px;text-align:right;">' + fdMoney(p.charge_amount) + '</td>'
                                + '<td style="padding:10px 12px;text-align:right;">' + (p.subscribers || 0) + '</td>'
                                + '<td style="padding:10px 12px;text-align:right;">'
                                + (p.capacity
                                    ? escapeHtml(p.available_slots) + ' / ' + escapeHtml(p.capacity)
                                    : '<span style="color:#9ca3af;">Libre</span>') + '</td>'
                                + '<td style="padding:10px 12px;text-align:right;font-weight:600;">' + fdMoney(p.mrr) + '</td>'
                                + '</tr>';
                        }).join('')
                        + '</tbody></table>'
                    : '<p style="padding:28px;text-align:center;font-size:13px;color:#6b7280;margin:0;">'
                        + 'Todavía no tenés suscriptores activos. Cuando se sumen, el detalle aparece acá.</p>';

                var charges = data.pending_charges || [];
                document.getElementById('sub-charges').innerHTML = charges.length
                    ? '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<thead><tr style="background:#f9fafb;">'
                        + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Cliente</th>'
                        + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Plan</th>'
                        + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Período</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Total</th>'
                        + '<th style="text-align:center;padding:8px 12px;font-weight:600;color:#374151;">Estado</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Acción</th>'
                        + '</tr></thead><tbody>'
                        + charges.map(function (c) {
                            return '<tr style="border-top:1px solid #f3f4f6;">'
                                + '<td style="padding:8px 12px;">'
                                + escapeHtml(c.customer ? c.customer.name : '—')
                                + (c.customer ? '<br><span style="font-size:11px;color:#9ca3af;">' + escapeHtml(c.customer.email) + '</span>' : '')
                                + '</td>'
                                + '<td style="padding:8px 12px;color:#6b7280;">'
                                + escapeHtml(c.subscription && c.subscription.plan ? c.subscription.plan : '—') + '</td>'
                                + '<td style="padding:8px 12px;color:#6b7280;">'
                                + escapeHtml(c.period_start || '—') + ' → ' + escapeHtml(c.period_end || '—') + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;font-weight:600;">' + fdMoney(c.total) + '</td>'
                                + '<td style="padding:8px 12px;text-align:center;">' + subBadge(c.status) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;white-space:nowrap;">'
                                + '<button type="button" onclick="updateSubCharge(' + c.id + ', \'PAID\')" title="Confirmar pago" aria-label="Confirmar pago" style="background:#047857;color:#fff;border:none;padding:6px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button> '
                                + '<button type="button" onclick="updateSubCharge(' + c.id + ', \'FAILED\')" title="Marcar fallido" aria-label="Marcar fallido" style="background:#fff;color:#b91c1c;border:1px solid #fecaca;padding:6px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>'
                                + '</td></tr>';
                        }).join('')
                        + '</tbody></table>'
                    : '<p style="padding:28px;text-align:center;font-size:13px;color:#6b7280;margin:0;">'
                        + 'No hay cobros esperando confirmación. Los ciclos se generan solos cuando llega la fecha de entrega.</p>';

                var history = data.history || [];
                document.getElementById('sub-history').innerHTML = history.length
                    ? '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<thead><tr style="background:#f9fafb;">'
                        + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Período</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Cobrado</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Pendiente</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Fallidos</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Reembolsos</th>'
                        + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Cobros</th>'
                        + '</tr></thead><tbody>'
                        + history.map(function (h) {
                            return '<tr style="border-top:1px solid #f3f4f6;">'
                                + '<td style="padding:8px 12px;">' + escapeHtml(h.period) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;color:#047857;">' + fdMoney(h.collected) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;color:#b45309;">' + fdMoney(h.pending) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;color:#b91c1c;">' + fdMoney(h.failed) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;color:#6b7280;">' + fdMoney(h.refunded) + '</td>'
                                + '<td style="padding:8px 12px;text-align:right;">' + (h.charges || 0) + '</td>'
                                + '</tr>';
                        }).join('')
                        + '</tbody></table>'
                    : '<p style="padding:28px;text-align:center;font-size:13px;color:#6b7280;margin:0;">'
                        + 'Sin cobros registrados en los últimos meses.</p>';

                subSetState('content');
                switchSubTab(subActiveTab);
                loadSubPlans();
            })
            .catch(function () {
                subSetState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function updateSubCharge(paymentId, status) {
        var body = { status: status };

        if (status === 'FAILED') {
            var reason = window.prompt('¿Por qué falló el cobro?', 'El cliente no pudo pagar.');
            if (reason === null) return;
            body.failure_reason = reason;
        }

        fdFetchJson(SUB_URL.payments + '/' + paymentId, { method: 'PUT', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo actualizar el cobro.', true);
                    return;
                }
                fdToast(res.data.message || 'Cobro actualizado.', false);
                loadSubRevenue();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function loadSubPlans() {
        return fdFetchJson(SUB_URL.plans)
            .then(function (res) {
                if (!res.ok) return;

                subState.plans = res.data.plans || [];

                var box = document.getElementById('sub-plan-items');
                if (!box) return;

                if (!subState.products.length) {
                    loadSubProducts();
                }

                if (subState.plans.length) {
                    var list = document.createElement('div');
                    list.style.marginBottom = '10px';
                    list.innerHTML = '<p style="font-size:11px;font-weight:600;color:#6b7280;margin:0 0 6px;">Planes existentes</p>'
                        + subState.plans.map(function (plan) {
                            return '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 10px;'
                                + 'border:1px solid #f3f4f6;margin-bottom:4px;font-size:12px;">'
                                + '<span>' + escapeHtml(plan.title) + ' · ' + escapeHtml(SUB_FREQ[plan.frequency] || plan.frequency)
                                + ' · ' + fdMoney(plan.price) + ' · ' + escapeHtml(plan.charge_amount) + '/ciclo</span>'
                                + '<span style="display:flex;gap:6px;align-items:center;">'
                                + (plan.is_active ? fdBadge('Visible', '#047857', '#d1fae5') : fdBadge('Oculto', '#6b7280', '#f3f4f6'))
                                + '<button type="button" onclick="openSubPlanForm(' + plan.id + ')" title="Editar plan" aria-label="Editar plan" style="background:none;border:none;color:#D24C19;cursor:pointer;display:inline-flex;align-items:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z"/><path d="m15 5 4 4"/></svg></button></span></div>';
                        }).join('');

                    box.parentNode.insertBefore(list, box);
                    var previous = document.getElementById('sub-plan-list');
                    if (previous) previous.remove();
                    list.id = 'sub-plan-list';
                }
            });
    }

    function loadSubProducts() {
        var section = document.getElementById('dash-suscripciones');
        var providerId = section ? section.getAttribute('data-provider-id') : null;
        if (!providerId) return Promise.resolve();

        return fdFetchJson(SUB_URL.catalog + '/' + providerId + '/catalog')
            .then(function (res) {
                if (!res.ok) return;

                var products = [];
                (res.data.categories || []).forEach(function (c) {
                    (c.products || []).forEach(function (p) {
                        products.push({ id: p.id, name: p.name, price: p.price });
                    });
                });

                subState.products = products;
            })
            .catch(function () { subState.products = []; });
    }

    function openSubPlanForm(planId) {
        subState.editing = planId
            ? (subState.plans.filter(function (p) { return p.id === planId; })[0] || null)
            : null;

        var plan = subState.editing;
        var title = document.getElementById('sub-plan-form-title');
        if (title) title.textContent = plan ? 'Editar plan' : 'Nuevo plan';

        document.getElementById('sub-plan-title').value = plan ? plan.title : '';
        document.getElementById('sub-plan-frequency').value = plan ? plan.frequency : 'WEEKLY';
        document.getElementById('sub-plan-price').value = plan ? plan.price : '';
        document.getElementById('sub-plan-discount').value = plan && plan.discount_percentage ? plan.discount_percentage : '';
        document.getElementById('sub-plan-capacity').value = plan && plan.capacity ? plan.capacity : '';
        document.getElementById('sub-plan-description').value = plan && plan.description ? plan.description : '';
        document.getElementById('sub-plan-active').checked = plan ? plan.is_active : true;

        var items = document.getElementById('sub-plan-items');
        items.innerHTML = '';

        var existing = (plan && plan.items) || [];
        if (existing.length) {
            existing.forEach(function (item) { addSubPlanRow(item.product_id, item.quantity); });
        } else {
            addSubPlanRow();
        }

        updateSubPlanEstimate();

        var overlay = document.getElementById('sub-plan-overlay');
        if (overlay) overlay.style.display = 'flex';
    }

    function closeSubPlanForm() {
        subState.editing = null;
        var overlay = document.getElementById('sub-plan-overlay');
        if (overlay) overlay.style.display = 'none';
    }

    function addSubPlanRow(productId, quantity) {
        if (!subState.products.length) {
            fdToast('Cargando el catálogo del comercio...', false);
            loadSubProducts().then(function () {
                if (subState.products.length) addSubPlanRow(productId, quantity);
            });
            return;
        }

        var options = subState.products.map(function (p) {
            return '<option value="' + p.id + '"' + (Number(productId) === Number(p.id) ? ' selected' : '') + '>'
                + escapeHtml(p.name) + ' (' + fdMoney(p.price) + ')</option>';
        }).join('');

        document.getElementById('sub-plan-items').insertAdjacentHTML('beforeend',
            '<div style="display:grid;grid-template-columns:2fr 1fr auto;gap:8px;margin-bottom:8px;">'
            + '<select class="sub-plan-product" onchange="updateSubPlanEstimate()" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">'
            + options + '</select>'
            + '<input type="number" min="1" value="' + (quantity || 1) + '" oninput="updateSubPlanEstimate()" class="sub-plan-qty" '
            + 'style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />'
            + '<button type="button" onclick="this.parentNode.parentNode.remove()" title="Quitar producto" aria-label="Quitar producto" style="background:#fff;color:#b91c1c;'
            + 'border:1px solid #fecaca;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>'
            + '</div>');

        updateSubPlanEstimate();
    }

    function updateSubPlanEstimate() {
        var estimate = document.getElementById('sub-plan-estimate');
        if (!estimate) return;

        var selects = document.querySelectorAll('#sub-plan-items .sub-plan-product');
        var quantities = document.querySelectorAll('#sub-plan-items .sub-plan-qty');
        var total = 0;
        var count = 0;

        for (var i = 0; i < selects.length; i++) {
            var product = subState.products.filter(function (p) { return Number(p.id) === Number(selects[i].value); })[0];
            if (!product) continue;
            var qty = Math.max(1, Number(quantities[i].value || 1));
            total += qty * Number(product.price || 0);
            count++;
        }

        estimate.textContent = count
            ? count + ' producto(s) · suma ' + fdMoney(Math.round(total * 100) / 100)
            : 'Agregá al menos un producto';
    }

    function saveSubPlan() {
        var body = {
            title: document.getElementById('sub-plan-title').value.trim(),
            description: document.getElementById('sub-plan-description').value.trim() || null,
            frequency: document.getElementById('sub-plan-frequency').value,
            price: Number(document.getElementById('sub-plan-price').value),
            discount_percentage: document.getElementById('sub-plan-discount').value === ''
                ? null : Number(document.getElementById('sub-plan-discount').value),
            capacity: document.getElementById('sub-plan-capacity').value === ''
                ? null : Number(document.getElementById('sub-plan-capacity').value),
            is_active: document.getElementById('sub-plan-active').checked,
            items: []
        };

        if (subState.editing) body.id = subState.editing.id;

        var selects = document.querySelectorAll('#sub-plan-items .sub-plan-product');
        var quantities = document.querySelectorAll('#sub-plan-items .sub-plan-qty');

        for (var i = 0; i < selects.length; i++) {
            if (!selects[i].value) continue;
            body.items.push({ product_id: Number(selects[i].value), quantity: Math.max(1, Number(quantities[i].value || 1)) });
        }

        if (!body.title) { fdToast('Escribí el nombre del plan.', true); return; }
        if (!body.items.length) { fdToast('Agregá al menos un producto al plan.', true); return; }

        fdFetchJson(SUB_URL.plans, { method: 'POST', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    var errors = res.data && res.data.errors;
                    fdToast(errors ? (Object.values(errors)[0] || [])[0] : 'No se pudo guardar el plan.', true);
                    return;
                }
                fdToast(res.data.message || 'Plan guardado.', false);
                closeSubPlanForm();
                loadSubRevenue();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    window.loadSubRevenue = loadSubRevenue;
    window.openSubPlanForm = openSubPlanForm;
    window.closeSubPlanForm = closeSubPlanForm;
    window.addSubPlanRow = addSubPlanRow;
    window.updateSubPlanEstimate = updateSubPlanEstimate;
    window.saveSubPlan = saveSubPlan;
    window.updateSubCharge = updateSubCharge;
    window.switchSubTab = switchSubTab;
})();
</script>
