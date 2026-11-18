<div class="pcard">
    <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg> Dinero — backoffice (Fase 1: manual)</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="dn-tabs">
        <button type="button" data-dntab="comisiones" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #D24C19;background:#D24C19;color:#fff;font-size:13px;font-weight:700;cursor:pointer;">Comisiones</button>
        <button type="button" data-dntab="matriz" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Matriz PideAca</button>
        <button type="button" data-dntab="liquidaciones" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Liquidaciones</button>
        <button type="button" data-dntab="cobros" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Cobros</button>
        <button type="button" data-dntab="efectivo" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Efectivo</button>
        <button type="button" data-dntab="conciliacion" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Conciliación</button>
        <button type="button" data-dntab="planes" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Planes Hogar</button>
        <button type="button" data-dntab="facturas" class="dn-tab" style="padding:8px 14px;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Facturas</button>
    </div>
    <div><span class="save-msg" id="dn-msg"></span></div>

    <div class="dn-panel" data-dnpanel="comisiones">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Reglas de comisión</h4>
        <div style="overflow-x:auto;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;" id="dn-rules-table">
            <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">Módulo</th><th style="padding:8px;border:1px solid #e2e8f0;">País</th><th style="padding:8px;border:1px solid #e2e8f0;">Moneda</th><th style="padding:8px;border:1px solid #e2e8f0;">%</th><th style="padding:8px;border:1px solid #e2e8f0;">Mínimo</th><th style="padding:8px;border:1px solid #e2e8f0;">Activa</th><th style="padding:8px;border:1px solid #e2e8f0;">Acción</th></tr></thead>
            <tbody id="dn-rules-body"><tr><td colspan="7" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
        </table></div>
        <div class="profile-field-grid" style="margin-top:12px;">
            <div><label class="field-label">Módulo</label><select id="dn-rule-module" class="field-input"><option value="mercado">Mercado (Módulo A)</option><option value="hogar">Hogar (Módulo B)</option></select></div>
            <div><label class="field-label">País (vacío = todos)</label><input type="text" id="dn-rule-country" class="field-input" placeholder="AR" maxlength="2" /></div>
            <div><label class="field-label">Moneda (vacío = todas)</label><input type="text" id="dn-rule-currency" class="field-input" placeholder="ARS" maxlength="3" /></div>
            <div><label class="field-label">% comisión</label><input type="number" id="dn-rule-percent" class="field-input" min="0" max="100" step="0.01" placeholder="2" /></div>
            <div><label class="field-label">Mínimo por cobro</label><input type="number" id="dn-rule-min" class="field-input" min="0" step="0.01" placeholder="150" /></div>
            <div><label class="field-label">Activa</label><select id="dn-rule-active" class="field-input"><option value="1">Sí</option><option value="0">No</option></select></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnSaveRule()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Guardar regla</button></div>
        <p style="font-size:11.5px;color:#64748b;">Cambiar el % rige para cobros futuros. Lo ya cobrado conserva su foto.</p>
    </div>

    <div class="dn-panel" data-dnpanel="matriz" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Cuenta matriz de PideAca (donde pagan los clientes)</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">ARS - CBU</label><input type="text" id="dn-mx-ar-cbu" class="field-input" inputmode="numeric" /></div>
            <div><label class="field-label">ARS - Alias</label><input type="text" id="dn-mx-ar-alias" class="field-input" /></div>
            <div><label class="field-label">ARS - Titular</label><input type="text" id="dn-mx-ar-holder" class="field-input" /></div>
            <div><label class="field-label">ARS - Banco</label><input type="text" id="dn-mx-ar-bank" class="field-input" /></div>
            <div><label class="field-label">EUR - IBAN</label><input type="text" id="dn-mx-es-iban" class="field-input" /></div>
            <div><label class="field-label">EUR - Titular</label><input type="text" id="dn-mx-es-holder" class="field-input" /></div>
            <div><label class="field-label">EUR - Banco</label><input type="text" id="dn-mx-es-bank" class="field-input" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnSaveMatrix()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Guardar matriz</button></div>
    </div>

    <div class="dn-panel" data-dnpanel="liquidaciones" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Liquidaciones</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">ID usuario (beneficiario)</label><input type="number" id="dn-settle-user" class="field-input" /></div>
            <div><label class="field-label">Moneda</label><input type="text" id="dn-settle-currency" class="field-input" value="ARS" maxlength="3" /></div>
            <div><label class="field-label">IDs de cobros (coma)</label><input type="text" id="dn-settle-charges" class="field-input" placeholder="12,13" /></div>
            <div><label class="field-label">Notas</label><input type="text" id="dn-settle-notes" class="field-input" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnCreateSettlement()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Crear liquidación</button></div>
        <div style="overflow-x:auto;margin-top:12px;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">ID</th><th style="padding:8px;border:1px solid #e2e8f0;">Usuario</th><th style="padding:8px;border:1px solid #e2e8f0;">Bruto</th><th style="padding:8px;border:1px solid #e2e8f0;">Comisión</th><th style="padding:8px;border:1px solid #e2e8f0;">Neto</th><th style="padding:8px;border:1px solid #e2e8f0;">Estado</th><th style="padding:8px;border:1px solid #e2e8f0;">Acción</th></tr></thead>
            <tbody id="dn-settlements-body"><tr><td colspan="7" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
        </table></div>
    </div>

    <div class="dn-panel" data-dnpanel="cobros" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Registrar cobro manual</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">Módulo</label><select id="dn-ch-module" class="field-input"><option value="mercado">Mercado</option><option value="hogar">Hogar</option></select></div>
            <div><label class="field-label">Tipo pedido</label><select id="dn-ch-ordertype" class="field-input"><option value="order">Pedido (order)</option><option value="service_order">Servicio (service_order)</option></select></div>
            <div><label class="field-label">ID pedido</label><input type="number" id="dn-ch-order" class="field-input" min="1" /></div>
            <div><label class="field-label">Monto</label><input type="number" id="dn-ch-amount" class="field-input" min="1" step="0.01" /></div>
            <div><label class="field-label">Medio</label><select id="dn-ch-method" class="field-input"><option value="transferencia">Transferencia</option><option value="efectivo">Efectivo</option></select></div>
            <div><label class="field-label">Referencia</label><input type="text" id="dn-ch-ref" class="field-input" placeholder="Código / comprobante" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnRegisterCharge()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Registrar cobro</button></div>
        <h4 style="font-size:14px;color:#0f172a;margin:18px 0 10px;">Pendientes de verificación</h4>
        <div style="overflow-x:auto;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">ID</th><th style="padding:8px;border:1px solid #e2e8f0;">Pedido</th><th style="padding:8px;border:1px solid #e2e8f0;">Monto</th><th style="padding:8px;border:1px solid #e2e8f0;">Comisión</th><th style="padding:8px;border:1px solid #e2e8f0;">Medio</th><th style="padding:8px;border:1px solid #e2e8f0;">Acción</th></tr></thead>
            <tbody id="dn-charges-body"><tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
        </table></div>
    </div>

    <div class="dn-panel" data-dnpanel="efectivo" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Deudas de efectivo por usuario</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">ID usuario</label><input type="number" id="dn-cash-user" class="field-input" /></div>
            <div><label class="field-label">Moneda</label><input type="text" id="dn-cash-currency" class="field-input" value="ARS" maxlength="3" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
            <button type="button" onclick="dnCheckDebt()" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;">Consultar deuda</button>
        </div>
        <div id="dn-debt-result" style="margin-top:10px;font-size:13px;color:#0f172a;"></div>
    </div>

    <div class="dn-panel" data-dnpanel="conciliacion" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Conciliación (últimos movimientos)</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">Tipo</label><select id="dn-led-type" class="field-input"><option value="">Todos</option><option value="fee">Comisión</option><option value="wallet_debit">Débito efectivo</option><option value="payout">Pago</option><option value="collection">Cobro deuda</option><option value="subscription">Suscripción</option></select></div>
            <div><label class="field-label">Moneda</label><input type="text" id="dn-led-currency" class="field-input" value="ARS" maxlength="3" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnLoadLedger()" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;">Actualizar</button></div>
        <div style="overflow-x:auto;margin-top:12px;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">ID</th><th style="padding:8px;border:1px solid #e2e8f0;">Tipo</th><th style="padding:8px;border:1px solid #e2e8f0;">Usuario</th><th style="padding:8px;border:1px solid #e2e8f0;">Monto</th><th style="padding:8px;border:1px solid #e2e8f0;">Ref</th><th style="padding:8px;border:1px solid #e2e8f0;">Detalle</th></tr></thead>
            <tbody id="dn-ledger-body"><tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
        </table></div>
    </div>

    <div class="dn-panel" data-dnpanel="planes" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Planes Hogar</h4>
        <div id="dn-plans-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;font-size:12.5px;color:#64748b;">Cargando…</div>
        <div class="profile-field-grid">
            <div><label class="field-label">Nombre</label><input type="text" id="dn-plan-name" class="field-input" placeholder="Hogar Mensual" /></div>
            <div><label class="field-label">Moneda</label><input type="text" id="dn-plan-currency" class="field-input" value="ARS" maxlength="3" /></div>
            <div><label class="field-label">Precio</label><input type="number" id="dn-plan-price" class="field-input" min="0" step="0.01" /></div>
            <div><label class="field-label">Días período</label><input type="number" id="dn-plan-days" class="field-input" value="30" min="1" /></div>
            <div><label class="field-label">Días gracia</label><input type="number" id="dn-plan-grace" class="field-input" value="15" min="1" /></div>
            <div><label class="field-label">Activo</label><select id="dn-plan-active" class="field-input"><option value="1">Sí</option><option value="0">No</option></select></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnSavePlan()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Guardar plan</button></div>
        <h4 style="font-size:14px;color:#0f172a;margin:18px 0 10px;">Suscribir prestador</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">ID usuario</label><input type="number" id="dn-sub-user" class="field-input" /></div>
            <div><label class="field-label">ID plan</label><input type="number" id="dn-sub-plan" class="field-input" /></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnSubscribe()" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;">Suscribir</button></div>
    </div>

    <div class="dn-panel" data-dnpanel="facturas" style="display:none;">
        <h4 style="font-size:14px;color:#0f172a;margin:0 0 10px;">Facturas de suscripción</h4>
        <div class="profile-field-grid">
            <div><label class="field-label">Estado</label><select id="dn-inv-status" class="field-input"><option value="">Todas</option><option value="pending">Pendiente</option><option value="overdue">Vencida</option><option value="paid">Pagada</option></select></div>
            <div><label class="field-label">&nbsp;</label><button type="button" onclick="dnLoadInvoices()" style="background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;width:100%;">Filtrar</button></div>
        </div>
        <div style="overflow-x:auto;margin-top:12px;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">ID</th><th style="padding:8px;border:1px solid #e2e8f0;">Usuario</th><th style="padding:8px;border:1px solid #e2e8f0;">Monto</th><th style="padding:8px;border:1px solid #e2e8f0;">Vence</th><th style="padding:8px;border:1px solid #e2e8f0;">Estado</th><th style="padding:8px;border:1px solid #e2e8f0;">Acción</th></tr></thead>
            <tbody id="dn-invoices-body"><tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
        </table></div>
    </div>
</div>
