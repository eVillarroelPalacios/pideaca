{{-- Salud financiera del comercio: insumos, ficha tecnica y margen por producto. --}}
<section id="dash-{{ $page->url }}" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fin-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Costo de producción y margen de cada producto</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" onclick="openSupplyForm()" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600;">+ Insumo</button>
            <button type="button" onclick="loadFinances()" title="Actualizar salud financiera" aria-label="Actualizar salud financiera" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg></button>
        </div>
    </div>

    <div id="fin-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Calculando costos...</p>
    </div>

    <div id="fin-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="fin-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar la salud financiera.</p>
    </div>

    <div id="fin-content" style="display:none;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="fin-badges"></div>

        <div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                        <th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>
                        <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Precio</th>
                        <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Costo</th>
                        <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Margen</th>
                        <th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Estado</th>
                        <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Ficha</th>
                    </tr>
                </thead>
                <tbody id="fin-rows"></tbody>
            </table>
        </div>

        <div id="fin-pager" style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;font-size:12px;color:#6b7280;">
            <span id="fin-pager-label"></span>
            <span style="display:flex;gap:6px;">
                <button type="button" onclick="finGoPage(-1)" style="background:#fff;border:1px solid #d1d5db;padding:6px 10px;border-radius:4px;cursor:pointer;font-size:12px;">Anterior</button>
                <button type="button" onclick="finGoPage(1)" style="background:#fff;border:1px solid #d1d5db;padding:6px 10px;border-radius:4px;cursor:pointer;font-size:12px;">Siguiente</button>
            </span>
        </div>

        <div style="margin-top:28px;">
            <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0 0 4px;">Insumos</h3>
            <p style="font-size:12px;color:#6b7280;margin:0 0 12px;">Lo que necesita tu comercio para producir. El costo de cada insumo se usa para calcular el margen.</p>
            <div id="fin-supplies"></div>
        </div>
    </div>

    <div id="fin-supply-form" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:20px;margin-top:16px;">
        <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0 0 12px;" id="fin-supply-form-title">Nuevo insumo</h3>
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Nombre</label>
                <input type="text" id="fin-supply-name" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Unidad</label>
                <select id="fin-supply-unit" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;"></select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Costo por unidad</label>
                <input type="number" step="0.01" min="0" id="fin-supply-cost" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
            </div>
            <div style="display:flex;gap:6px;">
                <button type="button" onclick="saveSupply()" style="background:#D24C19;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Guardar</button>
                <button type="button" onclick="closeSupplyForm()" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:9px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="fin-recipe" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:20px;margin-top:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
            <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0;" id="fin-recipe-title">Ficha técnica</h3>
            <div style="display:flex;gap:6px;">
                <button type="button" onclick="addRecipeRow()" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:7px 12px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">+ Insumo</button>
                <button type="button" onclick="saveRecipe()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Guardar ficha</button>
                <button type="button" onclick="closeRecipe()" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cerrar</button>
            </div>
        </div>
        <div id="fin-recipe-items"></div>
        <div id="fin-recipe-total" style="margin-top:12px;font-size:13px;font-weight:600;color:#0c2a4d;"></div>
    </div>
</section>

<script>
(function () {
    var FIN_URL = {
        health: '{{ url("/api/v1/provider/financial-health") }}',
        supplies: '{{ url("/api/v1/provider/supplies") }}',
        recipe: '{{ url("/api/v1/provider/products") }}'
    };

    var finState = { page: 1, products: [], supplies: [], units: [], recipeProduct: null, editingSupply: null };

    function finSetState(state, text) {
        var map = { loading: 'fin-loading', message: 'fin-message', content: 'fin-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fin-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function finNum(value) {
        if (value === null || typeof value === 'undefined') return '—';
        var n = Number(value);
        if (isNaN(n)) return escapeHtml(String(value));
        return String(Math.round(n * 100) / 100);
    }

    function finMarginBadge(margin, threshold) {
        if (margin === null || typeof margin === 'undefined') {
            return fdBadge('Sin precio', '#9a3412', '#fff7ed');
        }
        if (margin < threshold) {
            return fdBadge('Margen bajo ' + finNum(margin) + '%', '#b91c1c', '#fee2e2');
        }
        return fdBadge('Margen ' + finNum(margin) + '%', '#047857', '#d1fae5');
    }

    function finAlerts(row) {
        return (row.alerts || []).map(function (a) {
            var color = a.severity === 'warning' ? '#b45309' : '#1d4ed8';
            var bg = a.severity === 'warning' ? '#fef3c7' : '#dbeafe';
            return fdBadge(a.message, color, bg);
        }).join(' ');
    }

    function loadFinances() {
        finSetState('loading');

        fdFetchJson(FIN_URL.health + '?page=' + finState.page)
            .then(function (res) {
                if (!res.ok) {
                    finSetState('message', (res.data && res.data.message) || 'No se pudo cargar la salud financiera.');
                    return;
                }

                var data = res.data;
                finState.products = data.products || [];

                var summary = document.getElementById('fin-summary');
                if (summary) {
                    summary.textContent = 'Costo de producción y margen de cada producto · umbral recomendado '
                        + finNum(data.summary.min_margin_percent) + '%';
                }

                var badges = document.getElementById('fin-badges');
                badges.innerHTML = fdBadge(data.summary.total_products + ' productos', '#0c2a4d', '#eef2f7')
                    + ' ' + fdBadge(data.summary.products_with_alert + ' con alerta',
                        data.summary.products_with_alert ? '#b91c1c' : '#047857',
                        data.summary.products_with_alert ? '#fee2e2' : '#d1fae5');

                var rows = document.getElementById('fin-rows');
                if (!finState.products.length) {
                    rows.innerHTML = '<tr><td colspan="6" style="padding:32px;text-align:center;color:#6b7280;">'
                        + 'Todavía no tenés productos. Cargalos desde Mi Catálogo para calcular el costo y el margen.</td></tr>';
                } else {
                    var threshold = data.summary.min_margin_percent;
                    rows.innerHTML = finState.products.map(function (row) {
                        return '<tr style="border-top:1px solid #f3f4f6;">'
                            + '<td style="padding:10px 12px;">' + escapeHtml(row.name) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">' + fdMoney(row.sale_price) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">'
                            + (row.has_recipe ? fdMoney(row.production_cost) : '<span style="color:#9ca3af;">—</span>') + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">' + finMarginBadge(row.profit_margin, threshold) + '</td>'
                            + '<td style="padding:10px 12px;font-size:11px;">' + (finAlerts(row) || fdBadge('OK', '#047857', '#d1fae5')) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">'
                            + '<button type="button" onclick="openRecipe(' + row.product_id + ')" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px 10px;border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;">'
                            + (row.has_recipe ? 'Editar' : 'Cargar') + '</button></td>'
                            + '</tr>';
                    }).join('');
                }

                var pag = data.pagination || {};
                var label = document.getElementById('fin-pager-label');
                if (label) label.textContent = 'Página ' + (pag.current_page || 1) + ' de ' + (pag.last_page || 1);
                var pager = document.getElementById('fin-pager');
                if (pager) pager.style.display = (pag.last_page > 1) ? 'flex' : 'none';

                finSetState('content');
                loadSupplies();
            })
            .catch(function () {
                finSetState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function finGoPage(step) {
        finState.page = Math.max(1, finState.page + step);
        loadFinances();
    }

    function loadSupplies() {
        return fdFetchJson(FIN_URL.supplies)
            .then(function (res) {
                if (!res.ok) return;

                finState.supplies = res.data.supplies || [];
                finState.units = res.data.units_of_measure || [];

                var box = document.getElementById('fin-supplies');
                if (!box) return;

                if (!finState.supplies.length) {
                    box.innerHTML = '<div style="background:#f9fafb;border:1px dashed #d1d5db;padding:24px;text-align:center;font-size:13px;color:#6b7280;">'
                        + 'Todavía no cargaste insumos. Usá el botón "+ Insumo" para empezar.</div>';
                    return;
                }

                box.innerHTML = '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
                    + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                    + '<thead><tr style="background:#f9fafb;">'
                    + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Insumo</th>'
                    + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Unidad</th>'
                    + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Costo</th>'
                    + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;"></th>'
                    + '</tr></thead><tbody>'
                    + finState.supplies.map(function (s) {
                        return '<tr style="border-top:1px solid #f3f4f6;">'
                            + '<td style="padding:8px 12px;">' + escapeHtml(s.name) + '</td>'
                            + '<td style="padding:8px 12px;color:#6b7280;">'
                            + escapeHtml(s.unit_of_measure ? s.unit_of_measure.name : '—') + '</td>'
                            + '<td style="padding:8px 12px;text-align:right;">' + fdMoney(s.cost_per_unit) + '</td>'
                            + '<td style="padding:8px 12px;text-align:right;">'
                            + '<button type="button" onclick="openSupplyForm(' + s.id + ')" style="background:none;border:none;color:#D24C19;font-size:12px;font-weight:600;cursor:pointer;">Editar</button>'
                            + '</td></tr>';
                    }).join('')
                    + '</tbody></table></div>';
            });
    }

    function openSupplyForm(id) {
        finState.editingSupply = id ? (finState.supplies.filter(function (s) { return s.id === id; })[0] || null) : null;
        var form = document.getElementById('fin-supply-form');
        var title = document.getElementById('fin-supply-form-title');

        if (title) title.textContent = finState.editingSupply ? 'Editar insumo' : 'Nuevo insumo';

        var unit = document.getElementById('fin-supply-unit');
        if (unit) {
            unit.innerHTML = finState.units.map(function (u) {
                return '<option value="' + u.id + '">' + escapeHtml(u.name) + '</option>';
            }).join('');
        }

        var name = document.getElementById('fin-supply-name');
        var cost = document.getElementById('fin-supply-cost');
        if (name) name.value = finState.editingSupply ? finState.editingSupply.name : '';
        if (cost) cost.value = finState.editingSupply ? finState.editingSupply.cost_per_unit : '';
        if (unit && finState.editingSupply) unit.value = finState.editingSupply.unit_of_measure_id;

        if (form) {
            form.style.display = 'block';
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function closeSupplyForm() {
        finState.editingSupply = null;
        var form = document.getElementById('fin-supply-form');
        if (form) form.style.display = 'none';
    }

    function saveSupply() {
        var body = {
            name: document.getElementById('fin-supply-name').value.trim(),
            unit_of_measure_id: Number(document.getElementById('fin-supply-unit').value),
            cost_per_unit: Number(document.getElementById('fin-supply-cost').value)
        };

        if (finState.editingSupply) body.id = finState.editingSupply.id;

        fdFetchJson(FIN_URL.supplies, { method: 'POST', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo guardar el insumo.', true);
                    return;
                }
                fdToast(res.data.message || 'Insumo guardado.', false);
                closeSupplyForm();
                loadFinances();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function openRecipe(productId) {
        var row = finState.products.filter(function (p) { return p.product_id === productId; })[0];
        if (!row) return;

        finState.recipeProduct = row;

        var title = document.getElementById('fin-recipe-title');
        if (title) title.textContent = 'Ficha técnica · ' + row.name;

        var box = document.getElementById('fin-recipe');
        if (box) {
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        var items = document.getElementById('fin-recipe-items');
        items.innerHTML = '<p style="font-size:12px;color:#6b7280;">Cargando ficha...</p>';

        fdFetchJson(FIN_URL.recipe + '/' + productId + '/recipe')
            .then(function (res) {
                if (!res.ok) {
                    items.innerHTML = '<p style="font-size:12px;color:#b91c1c;">'
                        + escapeHtml((res.data && res.data.message) || 'No se pudo cargar la ficha.') + '</p>';
                    return;
                }
                renderRecipeRows(res.data.recipe.items || []);
                updateRecipeTotal();
            })
            .catch(function () {
                items.innerHTML = '<p style="font-size:12px;color:#b91c1c;">No se pudo conectar con el servidor.</p>';
            });
    }

    function renderRecipeRows(items) {
        var box = document.getElementById('fin-recipe-items');

        if (!items.length) {
            box.innerHTML = '<p style="font-size:12px;color:#6b7280;margin:0 0 10px;">'
                + 'Este producto todavía no tiene ficha técnica. Agregá los insumos que necesita para calcular su costo.</p>';
        }

        box.innerHTML += items.map(function (item) {
            return finRecipeRowHtml(item.supply_id, item.quantity_required);
        }).join('');
    }

    function finRecipeRowHtml(supplyId, quantity) {
        var options = finState.supplies.map(function (s) {
            return '<option value="' + s.id + '"' + (s.id === supplyId ? ' selected' : '') + '>'
                + escapeHtml(s.name) + '</option>';
        }).join('');

        return '<div style="display:grid;grid-template-columns:2fr 1fr auto;gap:8px;margin-bottom:8px;">'
            + '<select class="fin-recipe-supply" onchange="updateRecipeTotal()" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">'
            + (options || '<option value="">Cargá insumos primero</option>') + '</select>'
            + '<input type="number" step="0.001" min="0.001" oninput="updateRecipeTotal()" class="fin-recipe-qty" value="' + (quantity || '') + '" '
            + 'style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" placeholder="Cantidad" />'
            + '<button type="button" onclick="this.parentNode.parentNode.remove()" '
            + 'style="background:#fff;color:#b91c1c;border:1px solid #fecaca;padding:8px 12px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Quitar</button>'
            + '</div>';
    }

    function addRecipeRow() {
        if (!finState.supplies.length) {
            fdToast('Primero cargá al menos un insumo.', true);
            return;
        }
        document.getElementById('fin-recipe-items').insertAdjacentHTML('beforeend', finRecipeRowHtml(null, ''));
    }

    function saveRecipe() {
        if (!finState.recipeProduct) return;

        var items = [];
        var selects = document.querySelectorAll('#fin-recipe-items .fin-recipe-supply');
        var quantities = document.querySelectorAll('#fin-recipe-items .fin-recipe-qty');

        for (var i = 0; i < selects.length; i++) {
            if (!selects[i].value) continue;
            items.push({ supply_id: Number(selects[i].value), quantity_required: Number(quantities[i].value) });
        }

        if (!items.length) {
            fdToast('Agregá al menos un insumo a la ficha.', true);
            return;
        }

        fdFetchJson(FIN_URL.recipe + '/' + finState.recipeProduct.product_id + '/recipe', {
            method: 'POST',
            body: JSON.stringify({ items: items })
        })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo guardar la ficha técnica.', true);
                    return;
                }
                fdToast('Ficha técnica guardada. Costo: ' + fdMoney(res.data.production_cost)
                    + ' · Margen: ' + (res.data.profit_margin === null ? '—' : finNum(res.data.profit_margin) + '%'), false);
                loadFinances();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function updateRecipeTotal() {
        var total = document.getElementById('fin-recipe-total');
        if (!total) return;

        var selects = document.querySelectorAll('#fin-recipe-items .fin-recipe-supply');
        var quantities = document.querySelectorAll('#fin-recipe-items .fin-recipe-qty');
        var sum = 0;
        var missing = false;

        for (var i = 0; i < selects.length; i++) {
            var supply = finState.supplies.filter(function (s) { return s.id === Number(selects[i].value); })[0];
            if (!supply) { missing = true; continue; }
            sum += Number(quantities[i].value || 0) * Number(supply.cost_per_unit || 0);
        }

        total.textContent = finState.recipeProduct
            ? 'Costo estimado: ' + fdMoney(Math.round(sum * 100) / 100)
                + (missing ? ' · completá las cantidades' : '')
            : '';
    }

    function closeRecipe() {
        finState.recipeProduct = null;
        var box = document.getElementById('fin-recipe');
        if (box) box.style.display = 'none';
    }

    window.loadFinances = loadFinances;
    window.finGoPage = finGoPage;
    window.openSupplyForm = openSupplyForm;
    window.closeSupplyForm = closeSupplyForm;
    window.saveSupply = saveSupply;
    window.openRecipe = openRecipe;
    window.closeRecipe = closeRecipe;
    window.saveRecipe = saveRecipe;
    window.addRecipeRow = addRecipeRow;
    window.updateRecipeTotal = updateRecipeTotal;
})();
</script>
