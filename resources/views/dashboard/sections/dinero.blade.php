<style>
    .dn-layout{display:flex;align-items:stretch;}
    .dn-sidebar{width:250px;flex:0 0 250px;background:#fff;border-right:1px solid #e2e8f0;margin-right:0;}
    .dn-side-head{display:flex;gap:10px;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;}
    .dn-side-head svg{width:22px;height:22px;flex:0 0 22px;}
    .dn-side-title{font-size:14px;font-weight:800;color:#0c2a4d;line-height:1.25;}
    .dn-side-sub{font-size:11.5px;color:#64748b;}
    .dn-nav{display:flex;flex-direction:column;padding:12px 0;}
    .dn-nav .sidebar-link{width:100%;text-align:left;background:transparent;border-top:none;border-right:none;border-bottom:none;cursor:pointer;font-family:inherit;}
    #dn-tabs .sidebar-link{border-left:3px solid transparent;}
    #dn-tabs .sidebar-link.active{background:#FFF3EB;color:#D9531E;border-left-color:#D9531E;font-weight:500;}
    .dn-main{flex:1 1 auto;min-width:0;background:#F1F5F9;padding:20px;}
    .dn-panel{background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,.04);padding:24px;margin-bottom:0;}
    #dn-sidebar-toggle{display:none;}
    @media (max-width:767px){
        .dn-sidebar{position:fixed;top:60px;left:0;height:calc(100vh - 60px);width:250px;flex:none;margin:0;border-right:1px solid #e2e8f0;background:#fff;z-index:250;transform:translateX(-100%);transition:transform .25s ease;overflow-y:auto;padding:0;}
        .dn-sidebar.open{transform:translateX(0);}
        #dn-sidebar-toggle{display:block !important;}
        #dn-sidebar-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:240;}
        .dn-side-head{padding:16px 20px;border-bottom:1px solid #e2e8f0;}
        .dn-nav{flex-direction:column;flex-wrap:nowrap;overflow:visible;padding:12px 0;}
        .dn-nav .sidebar-link{white-space:normal;}
        .dn-main-title{display:none;}
        #dn-rules-table{min-width:640px;}
        #dn-rules-table th,#dn-rules-table td{white-space:nowrap;}
        .dn-btn-full{width:100%;}
    }
</style>
<div class="pcard dn-layout">
    <aside class="dn-sidebar" id="dn-sidebar">
        <div class="dn-side-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
            <div>
                <div class="dn-side-title">Dinero</div>                
            </div>
        </div>
        <nav id="dn-tabs" class="dn-nav">
            <button type="button" data-dntab="comisiones" class="dn-tab sidebar-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg><span>Comisiones</span></button>
            <button type="button" data-dntab="matriz" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6"/><path d="M4 9v10"/><path d="M20 9v10"/><path d="M8 13v3"/><path d="M12 13v3"/><path d="M16 13v3"/><path d="M2 21h20"/></svg><span>Matriz PideAca</span></button>
            <button type="button" data-dntab="liquidaciones" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3l4 4-4 4"/><path d="M21 7H8"/><path d="M7 21l-4-4 4-4"/><path d="M3 17h13"/></svg><span>Liquidaciones</span></button>
            <button type="button" data-dntab="cobros" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01"/><path d="M18 12h.01"/></svg><span>Cobros</span></button>
            <button type="button" data-dntab="efectivo" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M15 9.5c-.7-1-1.7-1.5-3-1.5-1.7 0-3 .9-3 2.5 0 3.5 6 1.5 6 5 0 1.6-1.3 2.5-3 2.5-1.3 0-2.3-.5-3-1.5"/></svg><span>Efectivo</span></button>
            <button type="button" data-dntab="conciliacion" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 14l2 2 4-4"/></svg><span>Conciliación</span></button>
            <button type="button" data-dntab="planes" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg><span>Planes Hogar</span></button>
            <button type="button" data-dntab="facturas" class="dn-tab sidebar-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6"/><path d="M9 11h6"/></svg><span>Facturas</span></button>
        </nav>
    </aside>
    <div id="dn-sidebar-overlay" onclick="dnToggleSidebar(false)" style="display:none;"></div>
    <div class="dn-main">
    <div style="display:flex;align-items:center;gap:8px;">
    <button type="button" id="dn-sidebar-toggle" onclick="dnToggleSidebar()" aria-label="Abrir menú Dinero" style="display:none;background:none;border:none;cursor:pointer;padding:4px;color:#0f172a;flex:0 0 auto;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg></button>
    <h3 class="pcard-title dn-main-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg> Dinero</h3>
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
        <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="dnSaveRule()" class="dn-btn-full" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Guardar regla</button></div>
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
</div>
