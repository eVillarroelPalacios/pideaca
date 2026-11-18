<div class="pcard">
    <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg> Billeteras por país</h3>
    <p style="font-size:12.5px;color:#64748b;margin:0 0 12px;">Catálogo de tipos de billetera que ven los usuarios al cargar la suya. Lo desactivado no aparece en los combos.</p>
    <div><span class="save-msg" id="wt-msg"></span></div>
    <div style="overflow-x:auto;margin-bottom:16px;"><table style="width:100%;font-size:12.5px;border-collapse:collapse;">
        <thead><tr style="background:#f8fafc;text-align:left;"><th style="padding:8px;border:1px solid #e2e8f0;">País</th><th style="padding:8px;border:1px solid #e2e8f0;">Nombre</th><th style="padding:8px;border:1px solid #e2e8f0;">Código</th><th style="padding:8px;border:1px solid #e2e8f0;">Campos</th><th style="padding:8px;border:1px solid #e2e8f0;">Activa</th><th style="padding:8px;border:1px solid #e2e8f0;">Acción</th></tr></thead>
        <tbody id="wt-body"><tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Cargando…</td></tr></tbody>
    </table></div>
    <div class="profile-field-grid">
        <div><label class="field-label">País</label><select id="wt-country" class="field-input"><option value="AR">Argentina</option><option value="ES">España</option></select></div>
        <div><label class="field-label">Código (único por país)</label><input type="text" id="wt-code" class="field-input" placeholder="ej: Galicia" style="text-transform:lowercase;" /></div>
        <div><label class="field-label">Nombre visible</label><input type="text" id="wt-name" class="field-input" placeholder="ej: Banco Galicia" /></div>
        <div><label class="field-label">Campos (coma: holder,cbu,alias,iban,email,phone,bank)</label><input type="text" id="wt-fields" class="field-input" placeholder="holder,cbu,alias" /></div>
    </div>
    <div style="display:flex;justify-content:flex-end;margin-top:12px;"><button type="button" onclick="wtSave()" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Agregar tipo</button></div>
</div>
