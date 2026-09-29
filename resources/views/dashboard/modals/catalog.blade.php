            <div id="fd-product-overlay" style="display:none;position:fixed;inset:0;z-index:320;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                <div style="background:#fff;padding:24px;width:100%;max-width:480px;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                    <h3 id="fd-product-title" style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px;">Nuevo producto</h3>
                    <input type="hidden" id="fd-product-id" />

                    <div style="margin-bottom:12px;">
                        <label class="field-label" for="fd-product-name">Nombre *</label>
                        <input type="text" id="fd-product-name" class="field-input" placeholder="Ej: Empanada de carne" />
                    </div>

                    <div style="display:flex;gap:12px;margin-bottom:12px;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:6px;">
                                <label class="field-label" for="fd-product-category" style="margin-bottom:6px;">Categoría *</label>
                                <button type="button" id="fd-product-newcategory-btn" onclick="fdProdShowNewCategory()" style="background:none;border:none;padding:0;margin-bottom:6px;color:#D24C19;font-size:11px;font-weight:700;cursor:pointer;">+ Nueva</button>
                            </div>
                            <select id="fd-product-category" class="field-input"></select>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <label class="field-label" for="fd-product-price">Precio *</label>
                            <input type="number" step="0.01" min="0" id="fd-product-price" class="field-input" placeholder="0.00" />
                        </div>
                    </div>

                    <div id="fd-product-newcategory-box" style="display:none;margin-bottom:12px;">
                        <label class="field-label" for="fd-product-newcategory-input">Nueva categoría</label>
                        <div style="display:flex;gap:8px;align-items:flex-end;">
                            <div style="flex:1;min-width:0;">
                                <input type="text" id="fd-product-newcategory-input" class="field-input" maxlength="80" placeholder="Ej: Bebidas frías"
                                    onkeydown="if(event.key === 'Enter'){ event.preventDefault(); fdProdCreateCategory(); }" />
                            </div>
                            <button type="button" class="btn-secondary" onclick="fdProdCancelNewCategory()">Cancelar</button>
                            <button type="button" id="fd-product-newcategory-ok" class="btn-primary" onclick="fdProdCreateCategory()">Agregar</button>
                        </div>
                        <span class="save-msg err" id="fd-product-newcategory-error" style="display:none;margin-top:6px;"></span>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label class="field-label" for="fd-product-description">Descripción</label>
                        <textarea id="fd-product-description" class="field-input" rows="2" placeholder="Opcional"></textarea>
                    </div>

                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#374151;margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" id="fd-product-available" checked style="cursor:pointer;" />
                        Producto disponible para pedidos
                    </label>

                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#374151;margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" id="fd-product-track" onchange="fdProdTrackChanged()" style="cursor:pointer;" />
                        Controlar stock (habilitar seguimiento)
                    </label>

                    <div id="fd-product-stock-fields" style="display:none;">
                        <div style="display:flex;gap:12px;margin-bottom:12px;">
                            <div style="flex:1;">
                                <label class="field-label" for="fd-product-initial">Stock inicial</label>
                                <input type="number" step="0.001" id="fd-product-initial" class="field-input" placeholder="0" />
                            </div>
                            <div style="flex:1;">
                                <label class="field-label" for="fd-product-min">Stock mínimo</label>
                                <input type="number" step="0.001" min="0" id="fd-product-min" class="field-input" placeholder="0" />
                            </div>
                        </div>
                        <div style="margin-bottom:12px;">
                            <label class="field-label" for="fd-product-unit">Unidad de medida</label>
                            <select id="fd-product-unit" class="field-input"></select>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;margin-bottom:8px;cursor:pointer;">
                            <input type="checkbox" id="fd-product-negative" style="cursor:pointer;" />
                            Permitir stock negativo
                        </label>
                        <p style="font-size:11px;color:#94a3b8;margin:0;">El stock inicial solo aplica para la creación; después se edita desde Inventario.</p>
                    </div>

                    <div style="margin-top:20px;text-align:right;">
                        <span class="save-msg err" id="fd-product-error" style="display:none;margin-right:8px;"></span>
                        <button type="button" class="btn-secondary" onclick="closeProductModal()" style="margin-right:8px;">Cancelar</button>
                        <button type="button" class="btn-primary" onclick="saveProduct()">Guardar</button>
                    </div>
                </div>
            </div>

            <div id="fd-variant-overlay" style="display:none;position:fixed;inset:0;z-index:320;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                <div style="background:#fff;padding:24px;width:100%;max-width:660px;max-height:86vh;overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 4px;">Variantes</h3>
                    <p id="fd-variant-product" style="font-size:12px;color:#64748b;margin:0 0 8px;"></p>
                    <p style="font-size:12px;color:#64748b;margin:0 0 16px;">
                        Los tamaños o formatos del producto (Individual, Grande, Familiar...).
                        <strong>El precio que paga el cliente es el de la variante elegida</strong>; si no tiene variantes se usa el precio base.
                    </p>

                    <div id="fd-variant-rows"></div>

                    <button type="button" class="btn-secondary" style="margin-top:12px;" onclick="fdVariantAddRow()">+ Agregar variante</button>

                    <div style="margin-top:20px;text-align:right;">
                        <span class="save-msg err" id="fd-variant-error" style="display:none;margin-right:8px;"></span>
                        <button type="button" class="btn-secondary" onclick="closeVariantModal()" style="margin-right:8px;">Cancelar</button>
                        <button type="button" class="btn-primary" onclick="saveVariants()">Guardar</button>
                    </div>
                </div>
            </div>

            <div id="fd-opt-overlay" style="display:none;position:fixed;inset:0;z-index:320;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                <div style="background:#fff;padding:24px;width:100%;max-width:720px;max-height:88vh;overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 4px;">Agregados</h3>
                    <p id="fd-opt-product" style="font-size:12px;color:#64748b;margin:0 0 8px;"></p>
                    <p style="font-size:12px;color:#64748b;margin:0 0 16px;">
                        Grupos de opciones con precio extra (ej.: Muzzarella extra +$900).
                        El <strong>mínimo</strong> y el <strong>máximo</strong> definen cuántas opciones se pueden elegir al pedir.
                    </p>

                    <div id="fd-opt-groups"></div>

                    <button type="button" class="btn-secondary" style="margin-top:4px;" onclick="fdOptAddGroup()">+ Agregar grupo</button>

                    <div style="margin-top:20px;text-align:right;">
                        <span class="save-msg err" id="fd-opt-error" style="display:none;margin-right:8px;"></span>
                        <button type="button" class="btn-secondary" onclick="closeOptionModal()" style="margin-right:8px;">Cancelar</button>
                        <button type="button" class="btn-primary" onclick="saveOptions()">Guardar</button>
                    </div>
                </div>
            </div>