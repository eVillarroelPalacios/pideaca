{{-- Vista del cliente: elegir plan, controlar entregas, pausar y cambiar sabores. --}}
<section id="dash-{{ $page->url }}" class="dash-section" style="display:none;max-width:1000px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Tus pedidos automáticos y el día que querés recibirlos</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" onclick="openSubNewForm()" title="Suscribirme" aria-label="Suscribirme" style="background:#D24C19;color:#fff;border:none;width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg></button>
            <button type="button" onclick="loadMySubscriptions()" title="Actualizar suscripciones" aria-label="Actualizar suscripciones" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg></button>
        </div>
    </div>

    <div id="mysub-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando tus suscripciones...</p>
    </div>

    <div id="mysub-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="mysub-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar tus suscripciones.</p>
    </div>

    <div id="mysub-content" style="display:none;">
        <div id="mysub-list" style="display:flex;flex-direction:column;gap:14px;"></div>
    </div>

    <div id="mysub-form" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:20px;margin-top:16px;">
        <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0 0 12px;">Nueva suscripción</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:12px;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Comercio</label>
                <select id="mysub-provider" onchange="onMySubProviderChange()" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                    <option value="">Elegí un comercio</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Plan</label>
                <select id="mysub-plan" onchange="onMySubPlanChange()" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                    <option value="">Elegí un plan</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Dirección de entrega</label>
                <select id="mysub-address" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                    <option value="">Elegí una dirección</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Forma de pago</label>
                <select id="mysub-payment" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                    <option value="cash">Efectivo en la entrega</option>
                    <option value="transfer">Transferencia</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Primera entrega</label>
                <input type="date" id="mysub-date" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Día preferido</label>
                <select id="mysub-day" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">
                    <option value="">Sin preferencia</option>
                    <option value="1">Lunes</option>
                    <option value="2">Martes</option>
                    <option value="3">Miércoles</option>
                    <option value="4">Jueves</option>
                    <option value="5">Viernes</option>
                    <option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Horario preferido</label>
                <input type="time" id="mysub-time" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
            </div>
        </div>

        <div id="mysub-plan-detail" style="display:none;background:#f9fafb;border:1px solid #e5e7eb;padding:14px;margin-bottom:12px;font-size:13px;"></div>

        <div id="mysub-flavors" style="display:none;margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:6px;">Distribución de sabores (opcional)</label>
            <div id="mysub-flavors-rows"></div>
            <p id="mysub-flavors-hint" style="font-size:11px;color:#9ca3af;margin:6px 0 0;"></p>
        </div>

        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" onclick="createMySubscription()" title="Confirmar suscripción" aria-label="Confirmar suscripción" style="background:#D24C19;color:#fff;border:none;width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button>
            <button type="button" onclick="closeSubNewForm()" title="Cancelar" aria-label="Cancelar" style="background:#fff;color:#374151;border:1px solid #d1d5db;width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
        </div>
    </div>

    <div id="mysub-items-form" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:20px;margin-top:16px;">
        <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0 0 4px;" id="mysub-items-title">Cambiar sabores</h3>
        <p style="font-size:12px;color:#6b7280;margin:0 0 12px;">Podés cambiar los productos entre sí, pero la cantidad total del plan se mantiene.</p>
        <div id="mysub-items-rows"></div>
        <p id="mysub-items-hint" style="font-size:12px;margin:8px 0 0;color:#6b7280;font-weight:600;"></p>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;">
            <button type="button" onclick="saveMySubItems()" title="Guardar" aria-label="Guardar" style="background:#D24C19;color:#fff;border:none;width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button>
            <button type="button" onclick="closeMySubItems()" title="Cerrar" aria-label="Cerrar" style="background:#fff;color:#374151;border:1px solid #d1d5db;width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
        </div>
    </div>
</section>

<script>
(function () {
    var MYSUB_URL = {
        index: '{{ url("/api/v1/customer/subscriptions") }}',
        store: '{{ url("/api/v1/customer/subscriptions") }}',
        status: '{{ url("/api/v1/customer/subscriptions") }}',
        items: '{{ url("/api/v1/customer/subscriptions") }}',
        addresses: '{{ url("/api/me/addresses") }}',
        providers: '{{ url("/api/providers") }}?only_with_plans=1',
        publicPlans: '{{ url("/api/v1/public/providers") }}'
    };

    var MYSUB_DAYS = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };
    var MYSUB_FREQ = { DAILY: 'cada día', WEEKLY: 'cada semana', BIWEEKLY: 'cada dos semanas', MONTHLY: 'cada mes' };

    var MYSUB_STATUS = {
        ACTIVE: ['Activa', '#047857', '#d1fae5'],
        PAUSED: ['Pausada', '#b45309', '#fef3c7'],
        CANCELLED: ['Cancelada', '#6b7280', '#f3f4f6'],
        PAYMENT_FAILED: ['Pago pendiente', '#b91c1c', '#fee2e2']
    };

    var MYSUB_PAYMENT = {
        PENDING: ['Pendiente', '#b45309', '#fef3c7'],
        PAID: ['Pagado', '#047857', '#d1fae5'],
        FAILED: ['Fallido', '#b91c1c', '#fee2e2'],
        REFUNDED: ['Reembolsado', '#6b7280', '#f3f4f6']
    };

    var mySubState = { subscriptions: [], providers: [], plans: [], addresses: [], plan: null, editingItems: null };

    function mySubBadge(map, key) {
        var s = map[key] || [key || '-', '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    function mySubSetState(state, text) {
        var map = { loading: 'mysub-loading', message: 'mysub-message', content: 'mysub-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('mysub-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function loadMySubscriptions() {
        mySubSetState('loading');

        fdFetchJson(MYSUB_URL.index)
            .then(function (res) {
                if (!res.ok) {
                    mySubSetState('message', (res.data && res.data.message) || 'No se pudieron cargar tus suscripciones.');
                    return;
                }

                mySubState.subscriptions = res.data.subscriptions || [];

                var box = document.getElementById('mysub-list');
                if (!mySubState.subscriptions.length) {
                    box.innerHTML = '<div style="background:#fff;border:1px dashed #d1d5db;padding:40px 24px;text-align:center;">'
                        + '<p style="font-size:14px;color:#6b7280;margin:0 0 12px;">'
                        + 'Todavía no tenés suscripciones. Buscá un comercio y armá tu plan.</p>'
                        + '<button type="button" onclick="openSubNewForm()" title="Elegir plan" aria-label="Elegir plan" style="background:#D24C19;color:#fff;border:none;'
                        + 'width:34px;height:34px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
                        + '</button></div>';
                } else {
                    box.innerHTML = mySubState.subscriptions.map(function (sub) {
                        var plan = sub.plan || {};
                        var items = sub.items || [];
                        var payments = sub.payments || [];
                        var canEdit = sub.status === 'ACTIVE' || sub.status === 'PAUSED';

                        return '<div style="background:#fff;border:1px solid #e5e7eb;border-left:3px solid '
                            + (sub.status === 'ACTIVE' ? '#D24C19' : '#d1d5db') + ';padding:18px;">'
                            + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:6px;">'
                            + '<div>'
                            + '<h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0;">'
                            + escapeHtml(plan.title || 'Plan') + '</h3>'
                            + '<p style="font-size:12px;color:#6b7280;margin:2px 0 0;">'
                            + escapeHtml(sub.provider ? sub.provider.business_name : 'Comercio') + '</p>'
                            + '</div>'
                            + '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">'
                            + mySubBadge(MYSUB_STATUS, sub.status)
                            + fdMoney(plan.charge_amount) + ' '
                            + '<span style="font-size:11px;color:#6b7280;">'
                            + escapeHtml(MYSUB_FREQ[plan.frequency] || plan.frequency || '') + '</span>'
                            + '</div></div>'
                            + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;margin:12px 0;">'
                            + '<div><p style="font-size:11px;color:#9ca3af;margin:0;">Próxima entrega</p>'
                            + '<p style="font-size:13px;color:#0c2a4d;margin:2px 0 0;font-weight:600;">'
                            + escapeHtml(sub.next_delivery_date || '—') + '</p></div>'
                            + '<div><p style="font-size:11px;color:#9ca3af;margin:0;">Día preferido</p>'
                            + '<p style="font-size:13px;color:#0c2a4d;margin:2px 0 0;font-weight:600;">'
                            + escapeHtml(MYSUB_DAYS[sub.preferred_delivery_day] || 'Sin preferencia') + '</p></div>'
                            + '<div><p style="font-size:11px;color:#9ca3af;margin:0;">Horario</p>'
                            + '<p style="font-size:13px;color:#0c2a4d;margin:2px 0 0;font-weight:600;">'
                            + escapeHtml(sub.preferred_delivery_time || 'Sin preferencia') + '</p></div>'
                            + '<div><p style="font-size:11px;color:#9ca3af;margin:0;">Pago</p>'
                            + '<p style="font-size:13px;color:#0c2a4d;margin:2px 0 0;font-weight:600;">'
                            + escapeHtml(sub.payment_method === 'cash' ? 'Efectivo' : (sub.payment_method || '—')) + '</p></div>'
                            + '</div>'
                            + (items.length
                                ? '<div style="background:#f9fafb;border:1px solid #f3f4f6;padding:10px;margin-bottom:12px;">'
                                    + '<p style="font-size:11px;font-weight:600;color:#6b7280;margin:0 0 4px;">Tu distribución</p>'
                                    + '<p style="font-size:12px;color:#374151;margin:0;">'
                                    + items.map(function (i) { return escapeHtml(i.name) + ' x' + i.quantity; }).join(' · ') + '</p>'
                                    + '</div>'
                                : '')
                            + (payments.length
                                ? '<div style="margin-bottom:12px;"><p style="font-size:11px;font-weight:600;color:#6b7280;margin:0 0 6px;">Últimos cobros</p>'
                                    + '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
                                    + payments.map(function (p) {
                                        return fdBadge(fdMoney(p.total) + ' · ' + (MYSUB_PAYMENT[p.status] ? MYSUB_PAYMENT[p.status][0] : p.status),
                                            (MYSUB_PAYMENT[p.status] || [])[1] || '#334155',
                                            (MYSUB_PAYMENT[p.status] || [])[2] || '#f1f5f9')
                                            + '<span style="font-size:11px;color:#9ca3af;">' + escapeHtml(p.period_start || '') + '</span>';
                                    }).join('')
                                    + '</div></div>'
                                : '')
                            + (canEdit
                                ? '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
                                    + (plan.items && plan.items.length > 1
                                        ? '<button type="button" onclick="openMySubItems(' + sub.id + ')" title="Cambiar sabores" aria-label="Cambiar sabores" style="background:#fff;color:#D24C19;border:1px solid #D24C19;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg></button>'
                                        : '')
                                    + (sub.status === 'ACTIVE'
                                        ? '<button type="button" onclick="changeMySubStatus(' + sub.id + ', \'PAUSED\')" title="Pausar" aria-label="Pausar" style="background:#fff;color:#b45309;border:1px solid #fde68a;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg></button>'
                                        : '')
                                    + (sub.status === 'PAUSED'
                                        ? '<button type="button" onclick="changeMySubStatus(' + sub.id + ', \'ACTIVE\')" title="Reanudar" aria-label="Reanudar" style="background:#047857;color:#fff;border:none;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg></button>'
                                        : '')
                                    + '<button type="button" onclick="changeMySubStatus(' + sub.id + ', \'CANCELLED\')" title="Cancelar suscripción" aria-label="Cancelar suscripción" style="background:#fff;color:#b91c1c;border:1px solid #fecaca;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>'
                                    + '</div>'
                                : '')
                            + '</div>';
                    }).join('');
                }

                mySubSetState('content');
            })
            .catch(function () {
                mySubSetState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function changeMySubStatus(id, status) {
        var labels = { PAUSED: 'pausar', ACTIVE: 'reanudar', CANCELLED: 'cancelar' };

        if (!window.confirm('¿Querés ' + labels[status] + ' esta suscripción?')) return;

        fdFetchJson(MYSUB_URL.status + '/' + id + '/status', {
            method: 'PUT',
            body: JSON.stringify({ status: status })
        })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo actualizar la suscripción.', true);
                    return;
                }
                fdToast(res.data.message || 'Suscripción actualizada.', false);
                loadMySubscriptions();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function openMySubItems(id) {
        var sub = mySubState.subscriptions.filter(function (s) { return s.id === id; })[0];
        if (!sub || !sub.plan || !sub.plan.items) return;

        mySubState.editingItems = sub;
        document.getElementById('mysub-items-title').textContent = 'Cambiar sabores · ' + sub.plan.title;

        var current = {};
        (sub.items || []).forEach(function (i) { current[i.product_id] = i.quantity; });

        document.getElementById('mysub-items-rows').innerHTML = sub.plan.items.map(function (item) {
            return '<div style="display:grid;grid-template-columns:2fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;">'
                + '<span style="font-size:13px;color:#374151;">' + escapeHtml(item.name)
                + ' <span style="font-size:11px;color:#9ca3af;">(plan: x' + item.quantity + ')</span></span>'
                + '<input type="number" min="0" class="mysub-item-qty" data-product-id="' + item.product_id + '" '
                + 'value="' + (current[item.product_id] || 0) + '" oninput="updateMySubItemsTotal()" '
                + 'style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />'
                + '<span style="font-size:11px;color:#9ca3af;min-width:80px;" class="mysub-item-line"></span>'
                + '</div>';
        }).join('');

        var box = document.getElementById('mysub-items-form');
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        updateMySubItemsTotal();
    }

    function closeMySubItems() {
        mySubState.editingItems = null;
        var box = document.getElementById('mysub-items-form');
        if (box) box.style.display = 'none';
    }

    function updateMySubItemsTotal() {
        if (!mySubState.editingItems) return;

        var plan = mySubState.editingItems.plan;
        var totalAllowed = 0;
        var totalChosen = 0;

        plan.items.forEach(function (item) {
            totalAllowed += Number(item.quantity);
            var input = document.querySelector('#mysub-items-rows .mysub-item-qty[data-product-id="' + item.product_id + '"]');
            var qty = input ? Math.max(0, Number(input.value || 0)) : 0;
            totalChosen += qty;
            var line = input ? input.parentNode.querySelector('.mysub-item-line') : null;
            if (line) line.textContent = fdMoney(Math.round(qty * Number(item.unit_price || 0) * 100) / 100);
        });

        mySubSetHint('mysub-items-hint', totalAllowed, totalChosen);
    }

    /**
     * Mismo cartel para las dos pantallas de distribucion de sabores: si la
     * suma no cierra con la del plan, el backend la rechaza.
     */
    function mySubSetHint(id, totalAllowed, totalChosen) {
        var hint = document.getElementById(id);
        if (!hint) return;

        var available = totalAllowed - totalChosen;
        var color = available === 0 ? '#047857' : (available > 0 ? '#b45309' : '#b91c1c');

        hint.style.color = color;
        hint.textContent = available === 0
            ? 'Distribución completa (' + totalChosen + ' productos).'
            : (available > 0
                ? 'Te faltan ' + available + ' productos por distribuir.'
                : 'Te pasaste por ' + Math.abs(available) + ' productos.');
    }

    function saveMySubItems() {
        if (!mySubState.editingItems) return;

        var items = [];
        var total = 0;
        var inputs = document.querySelectorAll('#mysub-items-rows .mysub-item-qty');

        for (var i = 0; i < inputs.length; i++) {
            var qty = Math.max(0, Number(inputs[i].value || 0));
            if (qty > 0) {
                items.push({ product_id: Number(inputs[i].getAttribute('data-product-id')), quantity: qty });
                total += qty;
            }
        }

        var allowed = mySubState.editingItems.plan.items.reduce(function (acc, item) {
            return acc + Number(item.quantity);
        }, 0);

        if (!items.length) {
            fdToast('Elegí al menos un producto.', true);
            return;
        }

        if (total !== allowed) {
            fdToast('La suma tiene que ser ' + allowed + ' productos; ahora es ' + total + '.', true);
            return;
        }

        fdFetchJson(MYSUB_URL.items + '/' + mySubState.editingItems.id + '/items', {
            method: 'PUT',
            body: JSON.stringify({ items: items })
        })
            .then(function (res) {
                if (!res.ok) {
                    var errors = res.data && res.data.errors;
                    fdToast(errors ? (Object.values(errors)[0] || [])[0] : 'No se pudo guardar la distribución.', true);
                    return;
                }
                fdToast(res.data.message || 'Distribución actualizada.', false);
                closeMySubItems();
                loadMySubscriptions();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function openSubNewForm(preselectProviderId) {
        var form = document.getElementById('mysub-form');
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        loadMySubAddresses();

        var provider = document.getElementById('mysub-provider');

        function renderProviders(list) {
            if (!list.length) {
                provider.innerHTML = '<option value="">No hay comercios con planes disponibles</option>';
                return;
            }

            provider.innerHTML = '<option value="">Elegí un comercio</option>'
                + list.map(function (p) {
                    return '<option value="' + p.id + '">' + escapeHtml(p.business_name) + '</option>';
                }).join('');

            if (preselectProviderId) {
                provider.value = String(preselectProviderId);
                if (provider.value) onMySubProviderChange();
            }
        }

        if (mySubState.providers.length) {
            renderProviders(mySubState.providers);
        } else {
            provider.innerHTML = '<option value="">Cargando comercios...</option>';
            fdFetchJson(MYSUB_URL.providers)
                .then(function (res) {
                    if (!res.ok) {
                        provider.innerHTML = '<option value="">No se pudieron cargar los comercios</option>';
                        return;
                    }

                    mySubState.providers = res.data.providers || [];
                    renderProviders(mySubState.providers);
                })
                .catch(function () {
                    provider.innerHTML = '<option value="">No se pudo conectar con el servidor</option>';
                });
        }
    }

    function closeSubNewForm() {
        var form = document.getElementById('mysub-form');
        if (form) form.style.display = 'none';
    }

    function loadMySubAddresses() {
        var select = document.getElementById('mysub-address');

        return fdFetchJson(MYSUB_URL.addresses)
            .then(function (res) {
                if (!res.ok) return;

                mySubState.addresses = res.data.addresses || [];
                select.innerHTML = '<option value="">Elegí una dirección</option>'
                    + mySubState.addresses.map(function (a) {
                        return '<option value="' + a.id + '">' + escapeHtml(a.formatted)
                            + (a.is_primary ? ' (principal)' : '') + '</option>';
                    }).join('');
            })
            .catch(function () {});
    }

    function onMySubProviderChange() {
        var providerId = document.getElementById('mysub-provider').value;
        var plan = document.getElementById('mysub-plan');

        mySubState.plans = [];
        mySubState.plan = null;
        document.getElementById('mysub-plan-detail').style.display = 'none';
        document.getElementById('mysub-flavors').style.display = 'none';

        if (!providerId) {
            plan.innerHTML = '<option value="">Elegí un plan</option>';
            return;
        }

        plan.innerHTML = '<option value="">Cargando planes...</option>';

        fdFetchJson(MYSUB_URL.publicPlans + '/' + providerId + '/subscription-plans')
            .then(function (res) {
                if (!res.ok) {
                    plan.innerHTML = '<option value="">No hay planes disponibles</option>';
                    return;
                }

                mySubState.plans = res.data.plans || [];
                plan.innerHTML = '<option value="">Elegí un plan</option>'
                    + mySubState.plans.map(function (p) {
                        var sinCupos = p.capacity !== null && p.capacity !== undefined && p.available_slots <= 0;
                        return '<option value="' + p.id + '"' + (sinCupos ? ' disabled' : '') + '>'
                            + escapeHtml(p.title) + ' — ' + fdMoney(p.charge_amount)
                            + (sinCupos ? ' (sin cupos)' : '') + '</option>';
                    }).join('');
            })
            .catch(function () {
                plan.innerHTML = '<option value="">No se pudo conectar con el servidor</option>';
            });
    }

    function onMySubPlanChange() {
        var planId = Number(document.getElementById('mysub-plan').value);
        var plan = mySubState.plans.filter(function (p) { return p.id === planId; })[0];

        mySubState.plan = plan || null;

        var detail = document.getElementById('mysub-plan-detail');
        var flavors = document.getElementById('mysub-flavors');

        if (!plan) {
            detail.style.display = 'none';
            flavors.style.display = 'none';
            return;
        }

        detail.style.display = 'block';
        detail.innerHTML = '<p style="margin:0 0 6px;color:#0c2a4d;font-weight:600;">'
            + escapeHtml(plan.title) + ' · ' + escapeHtml(MYSUB_FREQ[plan.frequency] || plan.frequency) + '</p>'
            + (plan.description ? '<p style="margin:0 0 6px;color:#6b7280;">' + escapeHtml(plan.description) + '</p>' : '')
            + '<p style="margin:0;color:#374151;">'
            + (plan.items || []).map(function (i) { return escapeHtml(i.name) + ' x' + i.quantity; }).join(' · ')
            + '</p>'
            + (plan.discount_amount
                ? '<p style="margin:6px 0 0;color:#047857;">Descuento aplicado: -' + fdMoney(plan.discount_amount) + '</p>'
                : '');

        if ((plan.items || []).length > 1) {
            flavors.style.display = 'block';
            document.getElementById('mysub-flavors-rows').innerHTML = plan.items.map(function (item) {
                return '<div style="display:grid;grid-template-columns:2fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;">'
                    + '<span style="font-size:13px;color:#374151;">' + escapeHtml(item.name) + '</span>'
                    + '<input type="number" min="0" class="mysub-flavor-qty" data-product-id="' + item.product_id + '" '
                    + 'value="' + item.quantity + '" oninput="updateMySubFlavorTotal()" '
                    + 'style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />'
                    + '<span style="font-size:11px;color:#9ca3af;min-width:80px;" class="mysub-flavor-line"></span>'
                    + '</div>';
            }).join('');
            updateMySubFlavorTotal();
        } else {
            flavors.style.display = 'none';
        }
    }

    function updateMySubFlavorTotal() {
        if (!mySubState.plan) return;

        var allowed = mySubState.plan.items.reduce(function (acc, item) { return acc + Number(item.quantity); }, 0);
        var total = 0;
        var inputs = document.querySelectorAll('#mysub-flavors-rows .mysub-flavor-qty');

        for (var i = 0; i < inputs.length; i++) {
            var qty = Math.max(0, Number(inputs[i].value || 0));
            total += qty;
            var product = mySubState.plan.items.filter(function (p) {
                return Number(p.product_id) === Number(inputs[i].getAttribute('data-product-id'));
            })[0];
            var line = inputs[i].parentNode.querySelector('.mysub-flavor-line');
            if (line && product) line.textContent = fdMoney(Math.round(qty * Number(product.unit_price || 0) * 100) / 100);
        }

        mySubSetHint('mysub-flavors-hint', allowed, total);
    }

    function createMySubscription() {
        var body = {
            subscription_plan_id: Number(document.getElementById('mysub-plan').value),
            delivery_address_id: Number(document.getElementById('mysub-address').value),
            payment_method: document.getElementById('mysub-payment').value,
            next_delivery_date: document.getElementById('mysub-date').value,
            preferred_delivery_day: document.getElementById('mysub-day').value || null,
            preferred_delivery_time: document.getElementById('mysub-time').value || null,
            items: []
        };

        var inputs = document.querySelectorAll('#mysub-flavors-rows .mysub-flavor-qty');
        for (var i = 0; i < inputs.length; i++) {
            var qty = Math.max(0, Number(inputs[i].value || 0));
            if (qty > 0) {
                body.items.push({ product_id: Number(inputs[i].getAttribute('data-product-id')), quantity: qty });
            }
        }

        var missing = [];
        if (!body.subscription_plan_id) missing.push('un plan');
        if (!body.delivery_address_id) missing.push('una dirección');
        if (!body.next_delivery_date) missing.push('la fecha de la primera entrega');

        if (missing.length) {
            fdToast('Completá ' + missing.join(', ') + '.', true);
            return;
        }

        fdFetchJson(MYSUB_URL.store, { method: 'POST', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    var errors = res.data && res.data.errors;
                    fdToast(errors ? (Object.values(errors)[0] || [])[0] : 'No se pudo crear la suscripción.', true);
                    return;
                }
                fdToast(res.data.message || 'Suscripción creada.', false);
                closeSubNewForm();
                loadMySubscriptions();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    window.loadMySubscriptions = loadMySubscriptions;
    window.changeMySubStatus = changeMySubStatus;
    window.openMySubItems = openMySubItems;
    window.closeMySubItems = closeMySubItems;
    window.updateMySubItemsTotal = updateMySubItemsTotal;
    window.saveMySubItems = saveMySubItems;
    window.openSubNewForm = openSubNewForm;
    window.closeSubNewForm = closeSubNewForm;
    window.onMySubProviderChange = onMySubProviderChange;
    window.onMySubPlanChange = onMySubPlanChange;
    window.updateMySubFlavorTotal = updateMySubFlavorTotal;
    window.createMySubscription = createMySubscription;
})();
</script>
