    function toggleMenu() {
        var menu = document.getElementById('mobile-menu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    function closeDropdowns() {
        document.querySelectorAll('.nav-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
    }

    // Delegado para que los dropdowns reconstruidos dinámicamente también abran.
    document.addEventListener('mouseover', function (e) {
        var dd = e.target && e.target.closest ? e.target.closest('nav .nav-dropdown') : null;
        document.querySelectorAll('nav .nav-dropdown-menu').forEach(function (m) {
            if (!dd || m.parentNode !== dd) m.style.display = 'none';
        });
        if (dd) {
            var menu = dd.querySelector('.nav-dropdown-menu');
            if (menu) menu.style.display = 'block';
        }
    });

    function toggleMobileSub(btn) {
        var sub = btn.nextElementSibling;
        var svg = btn.querySelector('svg');
        if (sub.style.display === 'none') {
            sub.style.display = 'block';
            if (svg) { svg.style.transition = 'transform 0.2s ease'; svg.style.transform = 'rotate(180deg)'; }
        } else {
            sub.style.display = 'none';
            if (svg) { svg.style.transition = 'transform 0.2s ease'; svg.style.transform = 'rotate(0deg)'; }
        }
    }

    var fdSectionHistoryReady = false;
    var fdSectionPopNav = false;
    // Listado de comercios: seccion de salida de la tienda (volver y fallback
    // del historial si el navegador avanza a la tienda ya cerrada).
    var FD_SECTION_SHOP_FALLBACK = 'comercios';

    function showDashSection(key) {
        // La pagina "Reportes Repartidor" vive con url '/repartidor/reportes'
        // (ruta directa) pero su seccion SPA es 'dash-reportes-repartidor'.
        // Sin esta normalizacion el getElementById no encuentra nada y el
        // panel queda en blanco o conserva la seccion anterior (p. ej. la de
        // reportes del cliente).
        if (key === '/repartidor/reportes' || key === 'repartidor/reportes') {
            key = 'reportes-repartidor';
        }
        unmountProfileUsuariosPanel();
        var sections = document.querySelectorAll('.dash-section');
        sections.forEach(function (s) { s.style.display = 'none'; });
        var target = document.getElementById('dash-' + key);
        if (target) target.style.display = 'block';

        // Cada seccion deja una entrada en el historial: el boton Atras del
        // navegador recorre el panel del usuario en vez de salir al sitio.
        if (!fdSectionPopNav && (!history.state || history.state.fdSection !== key)) {
            var state = { fdSection: key };
            var navUrl = window.APP_BASE+"";
            try {
                var mid = (typeof umManagedId === 'function') ? umManagedId() : null;
                if (mid) navUrl += '?managed=' + encodeURIComponent(mid);
            } catch (e) {}
            if (fdSectionHistoryReady) {
                history.pushState(state, '', navUrl);
            } else {
                history.replaceState(state, '', navUrl);
                fdSectionHistoryReady = true;
            }
        }

        closeDropdowns();
        var menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
        // Cerrar drawers moviles al cambiar de seccion (p. ej. admin que va a
        // 'usuarios' desde showProfilePanel, o navegacion entre secciones).
        var psb = document.getElementById('profile-sidebar');
        if (psb) psb.classList.remove('open');
        var sov = document.getElementById('sidebar-overlay');
        if (sov) sov.style.display = 'none';
        var usb = document.getElementById('user-sidebar');
        if (usb) usb.classList.remove('open');
        var uov = document.getElementById('user-sidebar-overlay');
        if (uov) uov.style.display = 'none';
        // Entrada directa a Gestión de Usuarios (nav superior u otros accesos):
        // marcar 'Usuarios' aunque no se pase por showProfilePanel.
        if (key === 'usuarios' && typeof setProfileSidebarActive === 'function') setProfileSidebarActive('usuarios');
    }

    window.addEventListener('popstate', function (e) {
        var key = (e.state && e.state.fdSection) || 'perfil';
        fdSectionPopNav = true;
        try {
            if (key === 'tienda') {
                var shop = document.getElementById('fd-shop-overlay');
                if (typeof FD_SHOP !== 'undefined' && FD_SHOP.providerId && shop) {
                    document.querySelectorAll('.dash-section').forEach(function (s) { s.style.display = 'none'; });
                    shop.style.display = 'block';
                    fdStartShopPolling();
                } else {
                    showDashSection(FD_SECTION_SHOP_FALLBACK);
                }
            } else {
                showDashSection(key);
            }
        } finally {
            fdSectionPopNav = false;
        }
    });

    function doLogout() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.APP_BASE+"/logout";
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = window.__CSRF_TOKEN;
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    var allModules = [];
    var deleteModuleId = null;

    function loadModules() {
        document.getElementById('modules-loading').style.display = 'block';
        document.getElementById('modules-empty').style.display = 'none';
        document.getElementById('modules-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/modules", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allModules = data;
            renderModules(data);
        })
        .catch(function() {
            document.getElementById('modules-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar módulos.</p>';
        });
    }

    function renderModules(modules) {
        var tbody = document.getElementById('modules-tbody');
        var loading = document.getElementById('modules-loading');
        var empty = document.getElementById('modules-empty');
        var tableWrap = document.getElementById('modules-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (modules.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        modules.forEach(function(m, i) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', m.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(m.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editModule(' + m.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openModuleDelete(' + m.id + ', \'' + escapeHtml(m.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterModules() {
        var q = document.getElementById('module-search').value.toLowerCase();
        var filtered = allModules.filter(function(m) {
            return m.description.toLowerCase().indexOf(q) !== -1;
        });
        renderModules(filtered);
    }

    function openModuleModal(id, description, icon) {
        document.getElementById('module-id').value = id || '';
        document.getElementById('module-description').value = description || '';
        document.getElementById('module-icon').value = icon || '';
        document.getElementById('module-description-error').style.display = 'none';
        document.getElementById('module-modal-title').textContent = id ? 'Editar Módulo' : 'Nuevo Módulo';
        document.getElementById('module-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('module-modal-overlay').style.display = 'flex';
        document.getElementById('module-description').focus();
    }

    function closeModuleModal() {
        document.getElementById('module-modal-overlay').style.display = 'none';
    }

    function editModule(id) {
        var m = allModules.find(function(mod) { return mod.id === id; });
        if (m) openModuleModal(m.id, m.description, m.icon);
    }

    function submitModule(e) {
        e.preventDefault();
        var id = document.getElementById('module-id').value;
        var desc = document.getElementById('module-description').value.trim();
        var iconVal = document.getElementById('module-icon').value.trim();
        var errorEl = document.getElementById('module-description-error');
        var submitBtn = document.getElementById('module-submit-btn');

        errorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/modules"+"/" + id : window.APP_BASE+"/modules";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, icon: iconVal })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeModuleModal();
                loadModules();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openModuleDelete(id, name) {
        deleteModuleId = id;
        document.getElementById('module-delete-name').textContent = name;
        document.getElementById('module-delete-warning').style.display = 'none';
        document.getElementById('module-delete-overlay').style.display = 'flex';
    }

    function closeModuleDelete() {
        document.getElementById('module-delete-overlay').style.display = 'none';
        deleteModuleId = null;
    }

    function confirmDeleteModule() {
        if (!deleteModuleId) return;
        var btn = document.getElementById('module-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/modules"+"/" + deleteModuleId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeModuleDelete();
                loadModules();
            } else {
                var warn = document.getElementById('module-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('module-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allGroups = [];
    var allGroupStatusOptions = [];
    var deleteGroupId = null;

    function loadGroups() {
        document.getElementById('groups-loading').style.display = 'block';
        document.getElementById('groups-empty').style.display = 'none';
        document.getElementById('groups-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/groups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allGroups = data;
            renderGroups(data);
        })
        .catch(function() {
            document.getElementById('groups-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar grupos.</p>';
        });
    }

    function renderGroups(groups) {
        var tbody = document.getElementById('groups-tbody');
        var loading = document.getElementById('groups-loading');
        var empty = document.getElementById('groups-empty');
        var tableWrap = document.getElementById('groups-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (groups.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        groups.forEach(function(g, i) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', g.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(g.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editGroup(' + g.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openGroupDelete(' + g.id + ', \'' + escapeHtml(g.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterGroups() {
        var q = document.getElementById('group-search').value.toLowerCase();
        var filtered = allGroups.filter(function(g) {
            return g.description.toLowerCase().indexOf(q) !== -1;
        });
        renderGroups(filtered);
    }

    function openGroupModal(id, description, icon, statusId) {
        document.getElementById('group-id').value = id || '';
        document.getElementById('group-description').value = description || '';
        document.getElementById('group-icon').value = icon || '';
        document.getElementById('group-description-error').style.display = 'none';
        document.getElementById('group-status-error').style.display = 'none';
        document.getElementById('group-modal-title').textContent = id ? 'Editar Grupo' : 'Nuevo Grupo';
        document.getElementById('group-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('group-modal-overlay').style.display = 'flex';
        loadGroupStatusOptions(statusId);
        document.getElementById('group-description').focus();
    }

    function loadGroupStatusOptions(selectedId) {
        var sel = document.getElementById('group-status-select');
        sel.innerHTML = '<option value="">Cargando estados...</option>';
        sel.disabled = true;

        fetch(window.APP_BASE+"/group-statuses", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(list) {
            fillGroupStatusSelect(Array.isArray(list) ? list : [], selectedId);
        })
        .catch(function() {
            sel.innerHTML = '<option value="">No se pudieron cargar los estados</option>';
            sel.disabled = false;
        });
    }

    function fillGroupStatusSelect(statuses, selectedId) {
        var sel = document.getElementById('group-status-select');
        allGroupStatusOptions = statuses;
        sel.innerHTML = '';

        if (statuses.length === 0) {
            sel.innerHTML = '<option value="">Sin estados disponibles</option>';
            sel.disabled = false;
            return;
        }

        statuses.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.description;
            sel.appendChild(opt);
        });
        sel.disabled = false;

        var wanted = selectedId ? String(selectedId) : '';
        var match = wanted && statuses.some(function(s) { return String(s.id) === wanted; });
        if (match) {
            sel.value = wanted;
            return;
        }
        // Grupo nuevo (o estado eliminado): se preselecciona "Activo".
        var activo = statuses.find(function(s) { return s.description === 'Activo'; });
        sel.value = activo ? activo.id : statuses[0].id;
    }

    function closeGroupModal() {
        document.getElementById('group-modal-overlay').style.display = 'none';
    }

    function editGroup(id) {
        var g = allGroups.find(function(grp) { return grp.id === id; });
        if (g) openGroupModal(g.id, g.description, g.icon, g.group_status_id);
    }

    function submitGroup(e) {
        e.preventDefault();
        var id = document.getElementById('group-id').value;
        var desc = document.getElementById('group-description').value.trim();
        var iconVal = document.getElementById('group-icon').value.trim();
        var statusSel = document.getElementById('group-status-select');
        var errorEl = document.getElementById('group-description-error');
        var statusErrorEl = document.getElementById('group-status-error');
        var submitBtn = document.getElementById('group-submit-btn');

        errorEl.style.display = 'none';
        statusErrorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        var payload = { description: desc, icon: iconVal };
        if (statusSel.value) {
            payload.group_status_id = parseInt(statusSel.value, 10);
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/groups"+"/" + id : window.APP_BASE+"/groups";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                if (data.errors.group_status_id) {
                    statusErrorEl.textContent = data.errors.group_status_id[0];
                    statusErrorEl.style.display = 'block';
                }
                if (data.errors.description) {
                    errorEl.textContent = data.errors.description[0];
                    errorEl.style.display = 'block';
                } else if (!data.errors.group_status_id) {
                    errorEl.textContent = 'Error de validación.';
                    errorEl.style.display = 'block';
                }
                return;
            }

            if (data.success) {
                closeGroupModal();
                loadGroups();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openGroupDelete(id, name) {
        deleteGroupId = id;
        document.getElementById('group-delete-name').textContent = name;
        document.getElementById('group-delete-warning').style.display = 'none';
        document.getElementById('group-delete-overlay').style.display = 'flex';
    }

    function closeGroupDelete() {
        document.getElementById('group-delete-overlay').style.display = 'none';
        deleteGroupId = null;
    }

    function confirmDeleteGroup() {
        if (!deleteGroupId) return;
        var btn = document.getElementById('group-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/groups"+"/" + deleteGroupId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeGroupDelete();
                loadGroups();
            } else {
                var warn = document.getElementById('group-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('group-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allSubGroups = [];
    var allSubGroupGroups = [];
    var deleteSubGroupId = null;
    var subgroupCurrentPage = 1;
    var subgroupPerPage = 10;

    function loadSubGroupGroups(callback) {
        fetch(window.APP_BASE+"/groups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allSubGroupGroups = data;
            var select = document.getElementById('subgroup-group-id');
            select.innerHTML = '<option value="">-- Seleccionar grupo --</option>';
            data.forEach(function(g) {
                var opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.description;
                select.appendChild(opt);
            });
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    function loadSubGroupFilter() {
        var select = document.getElementById('subgroup-group-filter');
        if (!select) return Promise.resolve();

        var current = select.value;
        select.innerHTML = '<option value="">Todos los grupos</option>';

        return fetch(window.APP_BASE+"/groups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            (Array.isArray(data) ? data : []).forEach(function(g) {
                var opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.description;
                select.appendChild(opt);
            });
            select.value = current;
        })
        .catch(function() {});
    }

    function loadSubGroups() {
        subgroupCurrentPage = 1;
        document.getElementById('subgroups-loading').style.display = 'block';
        document.getElementById('subgroups-empty').style.display = 'none';
        document.getElementById('subgroups-table-wrap').style.display = 'none';

        // Se esperan el combo de grupos y los sub grupos: si no, la grilla se
        // pintaba sin filtrar mientras el combo ya tenia un grupo elegido.
        Promise.all([
            loadSubGroupFilter(),
            fetch(window.APP_BASE+"/subgroups", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); })
        ])
        .then(function(results) {
            allSubGroups = Array.isArray(results[1]) ? results[1] : [];
            allSubGroups._filtered = allSubGroups;
            applySubGroupFilters(false);
        })
        .catch(function() {
            document.getElementById('subgroups-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar sub grupos.</p>';
        });
    }

    function renderSubGroups(subgroups) {
        var tbody = document.getElementById('subgroups-tbody');
        var loading = document.getElementById('subgroups-loading');
        var empty = document.getElementById('subgroups-empty');
        var tableWrap = document.getElementById('subgroups-table-wrap');
        var pagination = document.getElementById('subgroup-pagination');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (subgroups.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            pagination.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        var totalPages = Math.ceil(subgroups.length / subgroupPerPage);
        if (subgroupCurrentPage > totalPages) subgroupCurrentPage = totalPages;
        if (subgroupCurrentPage < 1) subgroupCurrentPage = 1;

        var start = (subgroupCurrentPage - 1) * subgroupPerPage;
        var end = start + subgroupPerPage;
        var pageItems = subgroups.slice(start, end);

        pageItems.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', s.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editSubGroup(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openSubGroupDelete(' + s.id + ', \'' + escapeHtml(s.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });

        if (totalPages <= 1) { pagination.style.display = 'none'; return; }
        pagination.style.display = 'flex';

        var dots = document.getElementById('subgroup-page-dots');
        dots.innerHTML = '';
        for (var p = 1; p <= totalPages; p++) {
            var d = document.createElement('span');
            d.textContent = p;
            d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (p === subgroupCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
            d.onmouseenter = function () { if (parseInt(this.textContent) !== subgroupCurrentPage) this.style.color = '#374151'; };
            d.onmouseleave = function () { if (parseInt(this.textContent) !== subgroupCurrentPage) this.style.color = '#9ca3af'; };
            d.onclick = function () { subgroupCurrentPage = parseInt(this.textContent); renderSubGroups(allSubGroups._filtered || allSubGroups); };
            dots.appendChild(d);
        }
        document.getElementById('subgroup-prev-page').style.opacity = subgroupCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('subgroup-prev-page').style.pointerEvents = subgroupCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('subgroup-next-page').style.opacity = subgroupCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('subgroup-next-page').style.pointerEvents = subgroupCurrentPage === totalPages ? 'none' : 'auto';
    }

    function subGroupPage(dir) {
        var data = allSubGroups._filtered || allSubGroups;
        var totalPages = Math.ceil(data.length / subgroupPerPage);
        if (dir === 1 && subgroupCurrentPage > 1) { subgroupCurrentPage--; renderSubGroups(data); }
        if (dir === 2 && subgroupCurrentPage < totalPages) { subgroupCurrentPage++; renderSubGroups(data); }
    }

    // Aplica el texto del buscador y el grupo elegido en el combo. Es la unica
    // via de pintado: asi la grilla respeta los filtros al cargar y al refrescar.
    function applySubGroupFilters(resetPage) {
        var search = document.getElementById('subgroup-search');
        var combo = document.getElementById('subgroup-group-filter');
        if (resetPage) subgroupCurrentPage = 1;

        var q = ((search && search.value) || '').toLowerCase();
        var groupId = combo ? String(combo.value) : '';
        var list = Array.isArray(allSubGroups) ? allSubGroups : [];

        var filtered = list.filter(function(s) {
            var text = (s.description || '').toLowerCase();
            var matchText = !q || text.indexOf(q) !== -1;
            var matchGroup = !groupId || String(s.group_id) === groupId;
            return matchText && matchGroup;
        });

        allSubGroups._filtered = filtered;
        renderSubGroups(filtered);
    }

    function filterSubGroups() {
        applySubGroupFilters(true);
    }

    function openSubGroupModal(id, description, groupId) {
        document.getElementById('subgroup-id').value = id || '';
        document.getElementById('subgroup-description').value = description || '';
        document.getElementById('subgroup-description-error').style.display = 'none';
        document.getElementById('subgroup-group-error').style.display = 'none';
        document.getElementById('subgroup-modal-title').textContent = id ? 'Editar Sub Grupo' : 'Nuevo Sub Grupo';
        document.getElementById('subgroup-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';

        var open = function() {
            if (groupId) {
                document.getElementById('subgroup-group-id').value = groupId;
            } else {
                document.getElementById('subgroup-group-id').value = '';
            }
            document.getElementById('subgroup-modal-overlay').style.display = 'flex';
            document.getElementById('subgroup-description').focus();
        };

        if (allSubGroupGroups.length === 0) {
            loadSubGroupGroups(open);
        } else {
            open();
        }
    }

    function closeSubGroupModal() {
        document.getElementById('subgroup-modal-overlay').style.display = 'none';
    }

    function editSubGroup(id) {
        var s = allSubGroups.find(function(sub) { return sub.id === id; });
        if (s) openSubGroupModal(s.id, s.description, s.group_id);
    }

    function submitSubGroup(e) {
        e.preventDefault();
        var id = document.getElementById('subgroup-id').value;
        var desc = document.getElementById('subgroup-description').value.trim();
        var groupId = document.getElementById('subgroup-group-id').value;
        var descError = document.getElementById('subgroup-description-error');
        var groupError = document.getElementById('subgroup-group-error');
        var submitBtn = document.getElementById('subgroup-submit-btn');

        descError.style.display = 'none';
        groupError.style.display = 'none';

        if (!desc) {
            descError.textContent = 'La descripción es obligatoria.';
            descError.style.display = 'block';
            return;
        }

        if (!groupId) {
            groupError.textContent = 'Debe seleccionar un grupo.';
            groupError.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/subgroups"+"/" + id : window.APP_BASE+"/subgroups";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, group_id: groupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                if (data.errors.description) {
                    descError.textContent = data.errors.description[0];
                    descError.style.display = 'block';
                }
                if (data.errors.group_id) {
                    groupError.textContent = data.errors.group_id[0];
                    groupError.style.display = 'block';
                }
                return;
            }

            if (data.success) {
                closeSubGroupModal();
                loadSubGroups();
            } else {
                descError.textContent = data.message || 'Error al guardar.';
                descError.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            descError.textContent = 'Error de conexión.';
            descError.style.display = 'block';
        });
    }

    function openSubGroupDelete(id, name) {
        deleteSubGroupId = id;
        document.getElementById('subgroup-delete-name').textContent = name;
        document.getElementById('subgroup-delete-warning').style.display = 'none';
        document.getElementById('subgroup-delete-overlay').style.display = 'flex';
    }

    function closeSubGroupDelete() {
        document.getElementById('subgroup-delete-overlay').style.display = 'none';
        deleteSubGroupId = null;
    }

    function confirmDeleteSubGroup() {
        if (!deleteSubGroupId) return;
        var btn = document.getElementById('subgroup-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/subgroups"+"/" + deleteSubGroupId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeSubGroupDelete();
                loadSubGroups();
            } else {
                var warn = document.getElementById('subgroup-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('subgroup-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ============ FAST DELIVERY: CATÁLOGO Y PEDIDOS DEL PRESTADOR ============

    var FD_ORDER_STATUS = {
        'pending': ['Pendiente', '#b45309', '#fef3c7'],
        'confirmed': ['Confirmado', '#1d4ed8', '#dbeafe'],
        'in_preparation': ['En preparación', '#6d28d9', '#ede9fe'],
        'on_the_way': ['En camino', '#0369a1', '#e0f2fe'],
        'delivered': ['Entregado', '#047857', '#d1fae5'],
        'cancelled': ['Cancelado', '#b91c1c', '#fee2e2']
    };

    var FD_PAYMENT_STATUS = {
        'pending': ['Pago pendiente', '#b45309', '#fef3c7'],
        'processing': ['Pago en proceso', '#1d4ed8', '#dbeafe'],
        'paid': ['Pagado', '#047857', '#d1fae5'],
        'failed': ['Pago fallido', '#b91c1c', '#fee2e2'],
        'refunded': ['Reembolsado', '#6b7280', '#f1f5f9']
    };

    function fdProviderId(sectionId) {
        var el = document.getElementById(sectionId);
        var id = el ? parseInt(el.getAttribute('data-provider-id'), 10) : NaN;
        return isNaN(id) ? null : id;
    }


    function fdDate(value) {
        if (!value) return '';
        var d = new Date(String(value).replace(' ', 'T'));
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function fdBadge(text, color, bg) {
        return '<span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 9px;border-radius:9999px;color:' +
            color + ';background:' + bg + ';white-space:nowrap;">' + escapeHtml(text) + '</span>';
    }

    function fdStatusBadge(status) {
        var s = FD_ORDER_STATUS[status] || [status || '-', '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    function fdPaymentBadge(payments) {
        var p = (payments && payments[0]) || null;
        if (!p) return '';
        var s = FD_PAYMENT_STATUS[p.status] || [p.status || '-', '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    // Siguiente paso del flujo que el comercio puede marcar en cada pedido.
    var FD_ORDER_FLOW = {
        'pending': ['confirmed', 'Confirmar pedido'],
        'confirmed': ['in_preparation', 'Marcar en preparación'],
        'in_preparation': ['on_the_way', 'Marcar en camino'],
        'on_the_way': ['delivered', 'Marcar entregado']
    };

    var FD_ORDER_CANCELLABLE = ['pending', 'confirmed', 'in_preparation'];

    // Icono de cada accion del flujo (botones solo-icono, el texto vive en title/aria-label).
    var FD_ORDER_ACTION_ICONS = {
        'confirmed': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'in_preparation': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.6a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/><path d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/></svg>',
        'on_the_way': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'delivered': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>',
        'cancelled': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>'
    };

    // Acciones de la cabecera del pedido: siguiente paso del flujo, cancelar,
    // imprimir y contraer/expandir. El texto va en el botón (no solo icono).
    function fdOrderActions(o) {
        var html = '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">';
        var flow = FD_ORDER_FLOW[o.status];

        if (flow) {
            var actionLabel = flow[1];
            if (o.delivery_type === 'takeaway') {
                if (o.status === 'in_preparation') actionLabel = 'Marcar listo para retirar';
                if (o.status === 'on_the_way') actionLabel = 'Marcar retirado';
            }
            html += '<button type="button" data-order-action class="fd-btn fd-btn-primary" title="' + escapeHtml(actionLabel) + '" aria-label="' + escapeHtml(actionLabel) + '" onclick="fdUpdateOrderStatus(' + Number(o.id) + ', \'' + flow[0] + '\', this)">'
                + FD_ORDER_ACTION_ICONS[flow[0]]
                + '<span>' + escapeHtml(actionLabel) + '</span></button>';
        }

        if (FD_ORDER_CANCELLABLE.indexOf(o.status) !== -1) {
            html += '<button type="button" data-order-action class="fd-btn fd-btn-danger" title="Cancelar pedido" aria-label="Cancelar pedido" onclick="fdUpdateOrderStatus(' + Number(o.id) + ', \'cancelled\', this)">'
                + FD_ORDER_ACTION_ICONS['cancelled']
                + '<span>Cancelar</span></button>';
        }

        // Liberacion manual al pool general: solo para pedidos sin repartidor
        // de un comercio con despacho cerrado y que aun no fueron liberados.
        if (!o.driver_id && !o.released_to_pool_at && fdTrustedState.enabled
            && o.delivery_type !== 'takeaway'
            && o.status !== 'delivered' && o.status !== 'cancelled') {
            html += '<button type="button" class="fd-btn" title="Enviar al pool general" aria-label="Enviar al pool general" onclick="fdTrustedSendToPool(' + Number(o.id) + ', this)">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>'
                + '<span>Enviar al pool</span></button>';
        }

        html += '<button type="button" class="fd-icon-btn" onclick="fdPrintOrder(' + Number(o.id) + ')" title="Imprimir pedido" aria-label="Imprimir pedido">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>'
            + '</button>';

        var isCollapsed = !!fdOrdersCollapsed[o.id];
        html += '<button type="button" class="fd-icon-btn" data-order-toggle onclick="fdOrdersToggle(' + Number(o.id) + ')" '
            + 'title="' + (isCollapsed ? 'Expandir pedido' : 'Contraer pedido') + '" aria-label="' + (isCollapsed ? 'Expandir pedido' : 'Contraer pedido') + '" '
            + 'style="' + (isCollapsed ? 'transform:rotate(180deg);' : '') + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>'
            + '</button>';

        return html + '</div>';
    }

    // Contrae o expande el detalle de un pedido y recuerda la preferencia
    // para que el sondeo de 8 s no la pierda al repintar la lista.
    function fdOrdersToggle(orderId) {
        var collapsed = !fdOrdersCollapsed[orderId];
        fdOrdersCollapsed[orderId] = collapsed;

        var card = document.querySelector('.fd-orders-card[data-order-id="' + Number(orderId) + '"]');
        if (!card) return;

        var body = card.querySelector('[data-order-body]');
        if (body) body.style.display = collapsed ? 'none' : 'block';

        var btn = card.querySelector('[data-order-toggle]');
        if (btn) {
            btn.style.transform = collapsed ? 'rotate(180deg)' : '';
            var label = collapsed ? 'Expandir pedido' : 'Contraer pedido';
            btn.title = label;
            btn.setAttribute('aria-label', label);
        }
    }

    function fdCopyOrderNumber(orderId) {
        var order = fdOrderByIndex(orderId);
        if (!order) return;

        var done = function () { fdToast('Número de pedido copiado.', false); };
        var text = order.order_number || '';

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { fdCopyFallback(text, done); });
        } else {
            fdCopyFallback(text, done);
        }
    }

    function fdCopyFallback(text, done) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', 'readonly');
        ta.style.cssText = 'position:fixed;left:-9999px;top:0;';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            done();
        } catch (e) {
            fdToast('No se pudo copiar el número.', true);
        }
        document.body.removeChild(ta);
    }

    // Imprime el pedido en un iframe oculto (evita el bloqueo de ventanas
    // emergentes) con una hoja simple tipo ticket.
    function fdPrintOrder(orderId) {
        var order = fdOrderByIndex(orderId);
        if (!order) return;

        var frame = document.createElement('iframe');
        frame.setAttribute('title', 'Impresión de pedido');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        document.body.appendChild(frame);

        var doc = frame.contentWindow.document;
        doc.open();
        doc.write(fdPrintOrderHtml(order));
        doc.close();

        var cleaned = false;
        var cleanup = function () {
            if (cleaned) return;
            cleaned = true;
            if (frame.parentNode) frame.parentNode.removeChild(frame);
        };

        frame.contentWindow.onafterprint = cleanup;
        frame.contentWindow.focus();
        frame.contentWindow.print();
        // Red de seguridad por navegadores que no disparan onafterprint.
        setTimeout(cleanup, 600000);
    }

    function fdPrintOrderHtml(o) {
        var client = o.user || {};
        var address = o.address;
        var driver = o.driver || null;
        var biz = '';
        var bizEl = document.getElementById('fd-orders-bizname');
        if (bizEl) biz = bizEl.textContent || '';

        var rows = (o.items || []).map(function (it) {
            return '<tr><td class="qty">' + Number(it.quantity) + '&times;</td>'
                + '<td>' + escapeHtml(it.product_name)
                + (it.variant_name ? ' <span class="muted">(' + escapeHtml(it.variant_name) + ')</span>' : '')
                + '</td><td class="num">' + fdMoney(it.total_price) + '</td></tr>';
        }).join('');

        return '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            + '<title>Pedido ' + escapeHtml(o.order_number) + '</title>'
            + '<style>'
            + 'body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:24px;font-size:13px;}'
            + 'h1{font-size:16px;margin:0 0 2px;}'
            + '.muted{color:#666;font-size:12px;}'
            + '.head{border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:12px;}'
            + 'table{width:100%;border-collapse:collapse;margin-top:10px;}'
            + 'td{padding:5px 0;border-bottom:1px dotted #ddd;vertical-align:top;}'
            + 'td.qty{width:36px;font-weight:bold;}'
            + 'td.num{text-align:right;white-space:nowrap;}'
            + '.totals{margin-top:12px;}'
            + '.totals div{display:flex;justify-content:space-between;padding:2px 0;}'
            + '.total{font-weight:bold;font-size:15px;border-top:1px solid #333;margin-top:6px;padding-top:6px;}'
            + '.sec{margin-top:14px;}'
            + '.sec b{display:block;margin-bottom:3px;}'
            + '</style></head><body>'
            + '<div class="head"><h1>' + escapeHtml(biz || 'PideAca') + '</h1>'
            + '<div class="muted">Pedido ' + escapeHtml(o.order_number) + ' &middot; ' + fdDateTime(o.created_at) + '</div>'
            + '<div class="muted">Estado: ' + escapeHtml((FD_ORDER_STATUS[o.status] || [o.status || '-'])[0]) + '</div></div>'
            + '<div class="sec"><b>Cliente</b>' + escapeHtml(client.name || 'Cliente')
            + (client.email ? '<div class="muted">' + escapeHtml(client.email) + '</div>' : '')
            + (client.phone ? '<div class="muted">' + escapeHtml(client.phone) + '</div>' : '')
            + '</div>'
            + (address
                ? '<div class="sec"><b>Entrega</b>' + escapeHtml(address.street) + ' ' + escapeHtml(address.number || '')
                    + (address.floor_apartment ? ', ' + escapeHtml(address.floor_apartment) : '')
                    + (address.postal_code ? ' &middot; CP ' + escapeHtml(address.postal_code) : '')
                    + (address.notes ? '<div class="muted">' + escapeHtml(address.notes) + '</div>' : '')
                    + '</div>'
                : '')
            + (driver ? '<div class="sec"><b>Repartidor</b>' + escapeHtml(driver.name || '') + '</div>' : '')
            + '<table>' + rows + '</table>'
            + '<div class="totals">'
            + '<div><span>Subtotal</span><span>' + fdMoney(o.subtotal) + '</span></div>'
            + '<div><span>Envío</span><span>' + fdMoney(o.delivery_fee) + '</span></div>'
            + '<div><span>Descuento</span><span>-' + fdMoney(o.discount) + '</span></div>'
            + '<div class="total"><span>Total</span><span>' + fdMoney(o.total) + '</span></div>'
            + '</div>'
            + (o.notes ? '<div class="sec"><b>Nota</b>' + escapeHtml(o.notes) + '</div>' : '')
            + '</body></html>';
    }

    function fdUpdateOrderStatus(orderId, status, btn) {
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.title = 'Guardando...';
            btn.setAttribute('aria-label', 'Guardando...');
        }

        fdFetchJson(window.APP_BASE+"/api/orders"+"/" + orderId + '/status', {
            method: 'PATCH',
            body: JSON.stringify({ status: status })
        })
        .then(function (res) {
            if (!res.ok) {
                fdToast((res.data && res.data.message) || 'No se pudo actualizar el pedido.', true);
                loadProviderOrders();
                return;
            }

            var card = document.querySelector('[data-order-id="' + orderId + '"]');
            if (card && res.data.order) {
                // Se repinta solo esa tarjeta en su posición y se actualiza el
                // listado en memoria (el repintado siguiente usa estos datos).
                var pos = 0;
                for (var i = 0; i < fdOrdersAll.length; i++) {
                    if (Number(fdOrdersAll[i].id) === Number(orderId)) {
                        pos = i + 1;
                        fdOrdersAll[i] = res.data.order;
                        break;
                    }
                }
                card.outerHTML = fdRenderOrder(res.data.order, pos);
                fdOrdersLastHtml = '';
            }

            // Refresca contadores (KPI/chips) y listado en silencio, sin spinner.
            loadProviderOrders(true);

            fdToast('Pedido actualizado: ' + (FD_ORDER_STATUS[res.data.status] || [res.data.status])[0] + '.', false);
        })
        .catch(function () {
            fdToast('No se pudo conectar con el servidor.', true);
            loadProviderOrders();
        });
    }

    function fdSetCatalogState(state, text) {
        var map = { loading: 'fd-catalog-loading', message: 'fd-catalog-message', content: 'fd-catalog-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });

        var pag = document.getElementById('fd-catalog-pagination');
        if (pag && state !== 'content') { pag.style.display = 'none'; pag.innerHTML = ''; }

        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-catalog-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function fdSetOrdersState(state, text, title, actionHtml) {
        var map = { loading: 'fd-orders-loading', message: 'fd-orders-message', content: 'fd-orders-list' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-orders-message-text');
            if (msg) msg.textContent = text;
            var head = document.getElementById('fd-orders-message-title');
            if (head) head.textContent = typeof title === 'string' && title ? title : 'No se encontraron pedidos';
        }
        // El bloque de accion (p. ej. "Restablecer Filtros") solo vive en el estado vacio.
        var action = document.getElementById('fd-orders-message-action');
        if (action) action.innerHTML = (state === 'message' && actionHtml) ? actionHtml : '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fdFetchJson(url, options) {
        options = options || {};
        var meta = document.querySelector('meta[name="csrf-token"]');
        var headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': meta ? meta.content : ''
        };
        Object.keys(options.headers || {}).forEach(function (k) { headers[k] = options.headers[k]; });
        if (options.body && !headers['Content-Type']) headers['Content-Type'] = 'application/json';

        return fetch(url, Object.assign({}, options, { headers: headers })).then(function (r) {
            return r.text().then(function (text) {
                var data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    data = { message: text || 'Respuesta inválida del servidor.' };
                }
                return { ok: r.ok, data: data };
            });
        }).catch(function (err) {
            return { ok: false, data: { message: 'No se pudo conectar con el servidor.' } };
        });
    }

    // --- Catálogo ---

    function loadProviderCatalog() {
        var providerId = fdProviderId('dash-mi-catalogo');
        if (!providerId) {
            fdSetCatalogState('message', 'Todavía no tenés un comercio configurado.\nCreá tu comercio desde Tu Perfil para cargar el catálogo.');
            return;
        }

        fdSetCatalogState('loading');

        fdFetchJson(window.APP_BASE+"/api/providers"+"/" + providerId + '/catalog')
            .then(function (res) {
                if (!res.ok) {
                    fdSetCatalogState('message', (res.data && res.data.message) || 'No se pudo cargar el catálogo.');
                    return;
                }

                var subtitle = document.getElementById('fd-catalog-subtitle');
                if (subtitle && res.data.provider) {
                    subtitle.textContent = res.data.provider.business_name +
                        (res.data.provider.is_active === false ? ' · comercio pausado' : '');
                }

                var categories = res.data.categories || [];

                if (!categories.length) {
                    fdSetCatalogState('message', 'Tu catálogo está vacío.\nCreá categorías y productos para que los clientes puedan pedir.');
                    return;
                }

                fdPrevData = { categories: categories, provider: res.data.provider };
                fdCatPage = 1;

                document.getElementById('fd-catalog-content').innerHTML =
                    fdRenderCatalog(categories, res.data.provider);

                fdSetCatalogState('content');
                fdCatPaintPagination();
            })
            .catch(function () {
                fdSetCatalogState('message', 'No se pudo conectar con el servidor.');
            });
    }

    // --- Sidebar de Mi catálogo: catálogo, productos, categorías y unidades ---

    var fdCatActive = 'catalogo';

    var FD_CAT_PANELS = {
        catalogo:  { loading: 'fd-catalog-loading', message: 'fd-catalog-message', content: 'fd-catalog-content', text: 'fd-catalog-message-text' },
        productos: { loading: 'fd-prod-loading',    message: 'fd-prod-message',    content: 'fd-prod-content',    text: 'fd-prod-message-text' },
        categorias:{ loading: 'fd-cats-loading',    message: 'fd-cats-message',    content: 'fd-cats-content',    text: 'fd-cats-message-text' },
        unidades:  { loading: 'fd-units-loading',   message: 'fd-units-message',   content: 'fd-units-content',   text: 'fd-units-message-text' }
    };

    var FD_CAT_LABELS = {
        catalogo: 'Mi catálogo',
        productos: 'Productos',
        categorias: 'Categorías',
        unidades: 'Uds. de medida'
    };

    function fdPanelState(ids, state, text) {
        ['loading', 'message', 'content'].forEach(function (k) {
            var el = document.getElementById(ids[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById(ids.text);
            if (msg) msg.textContent = text;
        }
    }

    function fdCatTab(key) {
        if (!FD_CAT_PANELS[key]) return;
        fdCatActive = key;

        document.querySelectorAll('[data-cat-panel]').forEach(function (a) {
            a.classList.toggle('active', a.getAttribute('data-cat-panel') === key);
        });

        document.querySelectorAll('.fd-cat-panel').forEach(function (p) {
            p.classList.toggle('active', p.id === 'fd-cat-panel-' + key);
        });

        var bc = document.getElementById('fd-cat-breadcrumb');
        if (bc) bc.textContent = FD_CAT_LABELS[key] || key;
        fdCatCloseSidebar();

        fdCatLoad(key);
    }

    function fdCatToggleSidebar() {
        var sb = document.getElementById('fd-cat-sidebar');
        var ov = document.getElementById('fd-cat-overlay');
        if (!sb) return;
        var open = sb.classList.toggle('open');
        if (ov) ov.classList.toggle('open', open);
    }

    function fdCatCloseSidebar() {
        var sb = document.getElementById('fd-cat-sidebar');
        var ov = document.getElementById('fd-cat-overlay');
        if (sb) sb.classList.remove('open');
        if (ov) ov.classList.remove('open');
    }

    function fdCatLoad(key) {
        if (key === 'catalogo') loadProviderCatalog();
        else if (key === 'productos') loadProductsPanel();
        else if (key === 'categorias') loadCategoriesPanel();
        else if (key === 'unidades') loadUnitsPanel();
    }

    // Se ejecuta cada vez que se entra a la sección desde el menú.
    function fdCatShow() {
        fdCatTab(fdCatActive);
    }

    // Recarga el panel a la vista y deja la caché de productos fresca.
    function fdCatReload() {
        fdProdData = null;
        fdCatLoad(fdCatActive);
    }

    function fdSeq(tareas) {
        return tareas.reduce(function (p, t) {
            return p.then(function () { return t(); });
        }, Promise.resolve());
    }

    function fdCheck(res, mensaje) {
        if (!res.ok) {
            throw new Error((res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || mensaje);
        }
        return res;
    }

    // --- Panel: productos (con variantes y agregados) ---

    function loadProductsPanel() {
        fdPanelState(FD_CAT_PANELS.productos, 'loading');

        fdFetchJson(window.APP_BASE+"/api/v1/provider/products")
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar los productos.');
                fdProdData = res.data;
                document.getElementById('fd-prod-content').innerHTML = fdRenderProducts(res.data);
                fdPanelState(FD_CAT_PANELS.productos, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.productos, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    var fdProdCatFilter = '';

    var FD_ICON_EDIT = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
    var FD_ICON_VARIANTS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>';
    var FD_ICON_ADDONS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>';
    var FD_ICON_PLUS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>';
    var FD_ICON_SAVE = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>';
    var FD_ICON_UP = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
    var FD_ICON_DOWN = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
    var FD_ICON_TRASH = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
    var FD_ICON_BOOK = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>';
    var FD_ICON_USER_PLUS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>';
    var FD_ICON_STAR = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>';
    var FD_ICON_STAR_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>';

    function fdProdFilterChange(value) {
        fdProdCatFilter = value || '';
        if (!fdProdData) return;
        var content = document.getElementById('fd-prod-content');
        if (content) content.innerHTML = fdRenderProducts(fdProdData);
    }

    function fdRenderProducts(data) {
        var all = data.products || [];
        var cats = data.categories || [];

        var selected = String(fdProdCatFilter || '');
        if (selected && !cats.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdProdCatFilter = '';
        }

        var products = selected
            ? all.filter(function (p) { return String(p.category_id) === selected; })
            : all;

        if (!all.length) {
            return '<div style="background:#fff;border:1px solid #e5e7eb;padding:36px 20px;text-align:center;">'
                + '<p style="font-size:14px;color:#6b7280;margin:0 0 14px;">Todavía no cargaste productos.</p>'
                + '<button type="button" class="btn-primary" onclick="openProductModal(null)">+ Agregar producto</button>'
                + '</div>';
        }

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-prod-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-prod-cat-filter" onchange="fdProdFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + cats.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">'
            + fdBadge(products.length + ' productos', '#0c2a4d', '#eef2f7')
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + combo
            + '</div>';

        if (!products.length) {
            return html + '<div style="background:#fff;border:1px solid #e5e7eb;padding:32px 20px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">No hay productos en esta categoría.</p>'
                + '</div>';
        }

        html += '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Precio</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Variantes</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Agregados</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Stock</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Acciones</th>'
            + '</tr></thead><tbody>';

        products.forEach(function (p, i) {
            var variantes = p.variants || [];
            var grupos = p.option_groups || [];
            var opciones = 0;
            grupos.forEach(function (g) { opciones += (g.options || []).length; });

            var vtxt = variantes.length
                ? variantes.map(function (v) { return escapeHtml(v.name); }).join(', ')
                : '<span style="color:#94a3b8;">ninguna</span>';

            var gtxt = grupos.length
                ? fdBadge(grupos.length + ' grupo(s) · ' + opciones + ' opción(es)', '#9a3412', '#fff7ed')
                : '<span style="color:#94a3b8;">ninguno</span>';

            var stock = p.track_stock
                ? fdBadge(fdInvNum(p.current_stock) + (p.unit_of_measure ? ' ' + p.unit_of_measure.symbol : ''), '#047857', '#d1fae5')
                : fdBadge('S/S', '#64748b', '#f1f5f9');

            var id = escapeHtml(String(p.id));

            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;min-width:170px;">'
                + escapeHtml(p.name)
                + (p.is_available === false ? ' ' + fdBadge('No disponible', '#b91c1c', '#fee2e2') : '')
                + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#D24C19;font-weight:700;">' + fdMoney(p.price) + '</td>'
                + '<td style="padding:10px 12px;color:#475569;min-width:150px;">'
                + (variantes.length ? fdBadge(String(variantes.length), '#0c2a4d', '#eef2f7') + ' ' : '') + vtxt
                + '</td>'
                + '<td style="padding:10px 12px;">' + gtxt + '</td>'
                + '<td style="padding:10px 12px;">' + stock + '</td>'
                + '<td style="padding:10px 12px;white-space:nowrap;">'
                + '<div class="fd-cat-actions">'
                + '<button type="button" class="btn-secondary btn-icon" title="Editar" aria-label="Editar" onclick="openProductModal(' + id + ')">' + FD_ICON_EDIT + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Variantes" aria-label="Variantes" onclick="openVariantModal(' + id + ')">' + FD_ICON_VARIANTS + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Agregados" aria-label="Agregados" onclick="openOptionModal(' + id + ')">' + FD_ICON_ADDONS + '</button>'
                + '</div></td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    // --- Panel: categorías ---

    function loadCategoriesPanel() {
        fdPanelState(FD_CAT_PANELS.categorias, 'loading');

        fdFetchJson(window.APP_BASE+"/api/v1/provider/products")
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar las categorías.');
                fdProdData = res.data;
                document.getElementById('fd-cats-content').innerHTML = fdRenderCategories(res.data);
                fdPanelState(FD_CAT_PANELS.categorias, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.categorias, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderCategories(data) {
        var cats = data.categories || [];

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">'
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + '</div>'
            + '<div style="background:#fff;border:1px solid #e5e7eb;padding:14px;margin-bottom:14px;">'
            + '<label class="field-label" for="fd-cat-new-name">Nueva categoría</label>'
            + '<div style="display:flex;gap:8px;align-items:flex-start;">'
            + '<div style="flex:1;min-width:0;"><input type="text" id="fd-cat-new-name" class="field-input" maxlength="80" placeholder="Ej: Bebidas frías"></div>'
            + '<button type="button" class="btn-primary btn-icon" title="Agregar" aria-label="Agregar" onclick="fdCatCreate()">' + FD_ICON_PLUS + '</button>'
            + '</div>'
            + '<span class="save-msg err" id="fd-cat-new-error" style="display:none;margin-top:6px;"></span>'
            + '</div>';

        html += '<div style="background:#fff;border:1px solid #e5e7eb;">';

        if (!cats.length) {
            html += '<div style="padding:24px 16px;font-size:13px;color:#94a3b8;text-align:center;">Todavía no tenés categorías: creá la primera arriba.</div>';
        }

        cats.forEach(function (c) {
            html += '<div class="fd-cat-row" data-cid="' + escapeHtml(String(c.id)) + '">'
                + '<div style="flex:1;min-width:0;"><input type="text" class="field-input fd-cat-name" maxlength="80" value="' + escapeHtml(c.name) + '" style="padding:7px 10px;font-size:13px;"></div>'
                + fdBadge(c.products_count + ' producto(s)', c.products_count ? '#0c2a4d' : '#64748b', c.products_count ? '#eef2f7' : '#f1f5f9')
                + (c.is_active ? '' : fdBadge('Oculta', '#9a3412', '#fff7ed'))
                + '<div class="fd-cat-actions">'
                + '<button type="button" class="btn-secondary btn-icon" title="Guardar" aria-label="Guardar" onclick="fdCatRename(this)">' + FD_ICON_SAVE + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Subir" aria-label="Subir" onclick="fdCatMove(this, -1)">' + FD_ICON_UP + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Bajar" aria-label="Bajar" onclick="fdCatMove(this, 1)">' + FD_ICON_DOWN + '</button>'
                + '<button type="button" class="btn-danger btn-icon" title="Eliminar" aria-label="Eliminar" onclick="fdCatRemove(this)">' + FD_ICON_TRASH + '</button>'
                + '</div>'
                + '</div>';
        });

        html += '</div>';

        return html;
    }

    function fdCatCreate() {
        var input = document.getElementById('fd-cat-new-name');
        var error = document.getElementById('fd-cat-new-error');
        var nombre = input.value.trim();

        if (nombre.length < 2) {
            error.textContent = 'Escribí el nombre de la categoría.';
            error.style.display = 'inline-block';
            return;
        }

        error.style.display = 'none';

        fdFetchJson(window.APP_BASE+"/api/v1/provider/categories", {
            method: 'POST',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo crear la categoría.');
                fdToast('Categoría creada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline-block';
            });
    }

    function fdCatRename(boton) {
        var fila = boton.closest('.fd-cat-row');
        var nombre = fila.querySelector('.fd-cat-name').value.trim();

        if (nombre.length < 2) {
            fdToast('El nombre de la categoría debe tener al menos 2 caracteres.', true);
            return;
        }

        fdFetchJson(window.APP_BASE+"/api/v1/provider/categories"+"/" + encodeURIComponent(fila.getAttribute('data-cid')), {
            method: 'PUT',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo renombrar la categoría.');
                fdToast('Categoría actualizada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    function fdCatRemove(boton) {
        var fila = boton.closest('.fd-cat-row');
        var nombre = fila.querySelector('.fd-cat-name').value;

        if (!window.confirm('¿Eliminar la categoría "' + nombre + '"?')) return;

        fdFetchJson(window.APP_BASE+"/api/v1/provider/categories"+"/" + encodeURIComponent(fila.getAttribute('data-cid')), {
            method: 'DELETE'
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo eliminar la categoría.');
                fdToast('Categoría eliminada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    function fdCatMove(boton, dir) {
        var filas = Array.prototype.slice.call(document.querySelectorAll('#fd-cats-content .fd-cat-row'));
        var fila = boton.closest('.fd-cat-row');
        var i = filas.indexOf(fila);
        var j = i + dir;

        if (i < 0 || j < 0 || j >= filas.length) return;

        var tmp = filas[i];
        filas[i] = filas[j];
        filas[j] = tmp;

        var tareas = filas.map(function (f, indice) {
            var catId = f.getAttribute('data-cid');
            return function () {
                return fdFetchJson(window.APP_BASE+"/api/v1/provider/categories"+"/" + encodeURIComponent(catId), {
                    method: 'PUT',
                    body: JSON.stringify({ sort_order: (indice + 1) * 10 })
                }).then(function (res) { fdCheck(res, 'No se pudo reordenar.'); });
            };
        });

        fdSeq(tareas)
            .then(function () {
                fdToast('Orden actualizado.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    // --- Panel: unidades de medida (consulta) ---

    function loadUnitsPanel() {
        fdPanelState(FD_CAT_PANELS.unidades, 'loading');

        fdFetchJson(window.APP_BASE+"/api/v1/provider/units")
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar las unidades.');
                document.getElementById('fd-units-content').innerHTML = fdRenderUnits(res.data);
                fdPanelState(FD_CAT_PANELS.unidades, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.unidades, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderUnits(data) {
        var units = data.units || [];

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">'
            + fdBadge(units.length + ' unidades', '#0c2a4d', '#eef2f7')
            + fdBadge((data.products_with_unit || 0) + ' producto(s) con unidad', (data.products_with_unit || 0) ? '#047857' : '#64748b', (data.products_with_unit || 0) ? '#d1fae5' : '#f1f5f9')
            + '</div>'
            + '<div style="background:#fffbeb;border:1px solid #fde68a;padding:12px 16px;margin-bottom:14px;font-size:13px;color:#92400e;">'
            + 'Las unidades de medida son de toda la plataforma: se usan para controlar el stock de los productos. '
            + 'Si necesitás una que no aparece, escribinos y la agregamos.'
            + '</div>'
            + '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Unidad</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Símbolo</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Equivalencia</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Cantidades</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Tus productos</th>'
            + '</tr></thead><tbody>';

        if (!units.length) {
            html += '<tr><td colspan="5" style="padding:24px 16px;color:#94a3b8;text-align:center;">No hay unidades de medida cargadas.</td></tr>';
        }

        units.forEach(function (u, i) {
            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;">' + escapeHtml(u.name) + '</td>'
                + '<td style="padding:10px 12px;color:#6b7280;">' + escapeHtml(u.symbol) + '</td>'
                + '<td style="padding:10px 12px;color:#475569;">1 ' + escapeHtml(u.name) + ' = ' + fdInvNum(u.base_conversion_factor) + ' und</td>'
                + '<td style="padding:10px 12px;">' + (u.is_integer_only ? fdBadge('Solo enteras', '#9a3412', '#fff7ed') : fdBadge('Admite decimales', '#047857', '#d1fae5')) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:' + (u.products_count ? '#0f172a' : '#94a3b8') + ';font-weight:600;">' + u.products_count + '</td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    // --- Productos (alta y edición) ---

    var fdProdData = null;

    function fdProdLoad(callback) {
        if (fdProdData) {
            callback();
            return;
        }

        fdFetchJson(window.APP_BASE+"/api/v1/provider/products")
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudieron cargar los datos del catálogo.', true);
                    return;
                }
                fdProdData = res.data;
                callback();
            })
            .catch(function () {
                fdToast('No se pudo conectar con el servidor.', true);
            });
    }

    function fdProdOptions() {
        var out = { products: [], categories: [], units: [] };

        (fdProdData.products || []).forEach(function (p) { out.products[p.id] = p; });
        (fdProdData.categories || []).forEach(function (c) { out.categories.push(c); });
        (fdProdData.unit_of_measures || []).forEach(function (u) { out.units.push(u); });

        return out;
    }

    function fdProdCategories(selected) {
        var opts = fdProdOptions().categories;
        var html = '<option value="">Sin categoría</option>';

        opts.forEach(function (c) {
            html += '<option value="' + escapeHtml(String(c.id)) + '"'
                + (String(c.id) === String(selected) ? ' selected' : '') + '>'
                + escapeHtml(c.name) + '</option>';
        });

        return html;
    }

    function fdProdUnits(selected) {
        var opts = fdProdOptions().units;
        var html = '<option value="">Sin unidad</option>';

        opts.forEach(function (u) {
            html += '<option value="' + escapeHtml(String(u.id)) + '"'
                + (String(u.id) === String(selected) ? ' selected' : '') + '>'
                + escapeHtml(u.name) + ' (' + escapeHtml(u.symbol) + ')</option>';
        });

        return html;
    }

    function fdProdTrackChanged() {
        var fields = document.getElementById('fd-product-stock-fields');
        if (fields) fields.style.display = document.getElementById('fd-product-track').checked ? 'block' : 'none';
    }

    function fdProdNewCategoryError(mensaje) {
        var error = document.getElementById('fd-product-newcategory-error');
        error.textContent = mensaje;
        error.style.display = mensaje ? 'inline-block' : 'none';
    }

    function fdProdShowNewCategory() {
        document.getElementById('fd-product-newcategory-box').style.display = 'block';
        document.getElementById('fd-product-newcategory-btn').style.visibility = 'hidden';
        document.getElementById('fd-product-newcategory-input').value = '';
        fdProdNewCategoryError('');
        document.getElementById('fd-product-newcategory-input').focus();
    }

    function fdProdCancelNewCategory() {
        document.getElementById('fd-product-newcategory-box').style.display = 'none';
        document.getElementById('fd-product-newcategory-btn').style.visibility = 'visible';
        document.getElementById('fd-product-newcategory-input').value = '';
        fdProdNewCategoryError('');
    }

    // Crea la categoria y, si viene un callback, deja la recién creada seleccionada
    // en el select para que "Guardar" la use directamente.
    function fdProdCreateCategory(alTerminar) {
        var input = document.getElementById('fd-product-newcategory-input');
        var boton = document.getElementById('fd-product-newcategory-ok');
        var nombre = input.value.trim();

        if (nombre.length < 2) {
            fdProdNewCategoryError('Escribí el nombre de la categoría.');
            return;
        }

        if (boton.disabled) return;

        fdProdNewCategoryError('');
        boton.disabled = true;
        boton.textContent = 'Agregando...';

        fdFetchJson(window.APP_BASE+"/api/v1/provider/categories", {
            method: 'POST',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                boton.disabled = false;
                boton.textContent = 'Agregar';

                if (!res.ok) {
                    fdProdNewCategoryError((res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo crear la categoría.');
                    return;
                }

                var cat = res.data.category;
                if (fdProdData) {
                    fdProdData.categories = fdProdData.categories || [];
                    fdProdData.categories.push(cat);
                }

                document.getElementById('fd-product-category').innerHTML = fdProdCategories(cat.id);
                fdProdCancelNewCategory();

                if (typeof alTerminar === 'function') {
                    alTerminar(cat);
                } else {
                    fdToast('Categoría "' + cat.name + '" creada.', false);
                }
            })
            .catch(function () {
                boton.disabled = false;
                boton.textContent = 'Agregar';
                fdProdNewCategoryError('No se pudo conectar con el servidor.');
            });
    }

    function openProductModal(productId) {
        fdProdLoad(function () {
            var editing = productId !== null && typeof productId !== 'undefined';
            var p = editing ? fdProdOptions().products[productId] : null;

            document.getElementById('fd-product-id').value = editing ? productId : '';
            document.getElementById('fd-product-title').textContent = editing ? 'Editar producto' : 'Nuevo producto';
            document.getElementById('fd-product-name').value = p ? p.name : '';
            document.getElementById('fd-product-category').innerHTML = fdProdCategories(p ? p.category_id : '');
            document.getElementById('fd-product-price').value = p ? p.price : '';
            document.getElementById('fd-product-available').checked = !p || p.is_available !== false;
            document.getElementById('fd-product-track').checked = !!p && !!p.track_stock;
            document.getElementById('fd-product-unit').innerHTML = fdProdUnits(p ? p.unit_of_measure_id : '');
            document.getElementById('fd-product-initial').value = '';
            document.getElementById('fd-product-min').value = p && p.min_stock_alert !== null ? p.min_stock_alert : '';
            document.getElementById('fd-product-negative').checked = !!p && p.inventory ? !!p.inventory.allow_negative_stock : false;

            var error = document.getElementById('fd-product-error');
            error.style.display = 'none';
            error.textContent = '';

            fdProdCancelNewCategory();
            fdProdTrackChanged();
            document.getElementById('fd-product-overlay').style.display = 'flex';
        });
    }

    function closeProductModal() {
        document.getElementById('fd-product-overlay').style.display = 'none';
    }

    function saveProduct() {
        var id = document.getElementById('fd-product-id').value;
        var error = document.getElementById('fd-product-error');
        var track = document.getElementById('fd-product-track').checked;

        var nombre = document.getElementById('fd-product-name').value.trim();
        var categoria = document.getElementById('fd-product-category').value;
        var precio = document.getElementById('fd-product-price').value;

        // Si quedó una categoría tipeada y sin dar de alta, primero se crea (queda
        // seleccionada y se limpia el input) y recién después se guarda el producto.
        if (document.getElementById('fd-product-newcategory-input').value.trim()) {
            fdProdCreateCategory(function () { saveProduct(); });
            return;
        }

        if (!nombre || !categoria || precio === '') {
            error.textContent = 'Completá nombre, categoría y precio.';
            error.style.display = 'inline';
            return;
        }

        var cuerpo = {
            name: nombre,
            category_id: Number(categoria),
            price: Number(precio),
            is_available: document.getElementById('fd-product-available').checked,
            track_stock: track
        };

        if (track) {
            var inicial = document.getElementById('fd-product-initial').value;
            var minimo = document.getElementById('fd-product-min').value;
            var unidad = document.getElementById('fd-product-unit').value;

            if (id === '') {
                cuerpo.initial_stock = inicial === '' ? 0 : Number(inicial);
            } else {
                cuerpo.initial_stock = 0;
            }

            cuerpo.min_stock_alert = minimo === '' ? null : Number(minimo);
            cuerpo.unit_of_measure_id = unidad === '' ? null : Number(unidad);
            cuerpo.allow_negative_stock = document.getElementById('fd-product-negative').checked;
        }

        error.style.display = 'none';

        var method = id ? 'PUT' : 'POST';
        var url = window.APP_BASE+"/api/v1/provider/products" + (id ? '/' + encodeURIComponent(id) : '');

        fdFetchJson(url, { method: method, body: JSON.stringify(cuerpo) })
            .then(function (res) {
                if (!res.ok) {
                    var mensaje = (res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo guardar el producto.';
                    error.textContent = mensaje;
                    error.style.display = 'inline';
                    return;
                }
                closeProductModal();
                fdToast(id ? 'Producto actualizado.' : 'Producto creado.', false);
                fdCatReload();
            })
            .catch(function () {
                error.textContent = 'No se pudo conectar con el servidor.';
                error.style.display = 'inline';
            });
    }

    // --- Variantes (Individual / Grande / Familiar) ---

    var fdVariantProducto = null;
    var fdVariantOriginales = [];

    function fdUrlVariants(productId) {
        return window.APP_BASE+"/api/v1/provider/products"+"/" + encodeURIComponent(productId) + '/variants';
    }

    function fdUrlVariant(id) {
        return window.APP_BASE+"/api/v1/provider/variants"+"/" + encodeURIComponent(id);
    }

    function fdVariantRow(v) {
        v = v || {};
        var vid = (v.id !== undefined && v.id !== null) ? String(v.id) : '';
        var precio = (v.price !== undefined && v.price !== null && v.price !== '') ? Number(v.price) : '';

        return '<div class="fd-variant-row" data-vid="' + vid + '" style="display:flex;gap:8px;align-items:flex-end;padding:8px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:150px;"><label class="field-label">Nombre</label>'
            + '<input type="text" class="field-input fd-v-name" maxlength="60" value="' + escapeHtml(v.name || '') + '" placeholder="Ej: Individual" /></div>'
            + '<div style="flex:1;min-width:110px;"><label class="field-label">Precio</label>'
            + '<input type="number" step="0.01" min="0" class="field-input fd-v-price" value="' + precio + '" placeholder="0.00" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-v-avail"' + (v.is_available === false ? '' : ' checked') + ' /> Disponible</label>'
            + '<button type="button" class="icon-btn del" title="Quitar variante" onclick="fdVariantRemove(this)" style="margin-bottom:2px;">&#10005;</button>'
            + '</div>';
    }

    function fdVariantRemove(boton) {
        var fila = boton.closest('.fd-variant-row');
        if (fila && fila.parentNode) fila.parentNode.removeChild(fila);
    }

    function fdVariantAddRow() {
        document.getElementById('fd-variant-rows').insertAdjacentHTML('beforeend', fdVariantRow(null));
    }

    function openVariantModal(productId) {
        fdProdLoad(function () {
            var producto = fdProdOptions().products[productId];
            if (!producto) return;

            fdVariantProducto = producto;
            fdVariantOriginales = (producto.variants || []).map(function (v) { return String(v.id); });

            document.getElementById('fd-variant-product').textContent =
                producto.name + ' · precio base ' + fdMoney(producto.price);
            document.getElementById('fd-variant-rows').innerHTML =
                (producto.variants || []).map(function (v) { return fdVariantRow(v); }).join('');

            var error = document.getElementById('fd-variant-error');
            error.textContent = '';
            error.style.display = 'none';

            document.getElementById('fd-variant-overlay').style.display = 'flex';
        });
    }

    function closeVariantModal() {
        document.getElementById('fd-variant-overlay').style.display = 'none';
    }

    function saveVariants() {
        if (!fdVariantProducto) return;

        var error = document.getElementById('fd-variant-error');
        error.style.display = 'none';

        var filas = Array.prototype.slice.call(document.querySelectorAll('#fd-variant-rows .fd-variant-row'));
        var datos = [];

        for (var i = 0; i < filas.length; i++) {
            var f = filas[i];
            var nombre = f.querySelector('.fd-v-name').value.trim();
            var precio = f.querySelector('.fd-v-price').value;

            if (!nombre || precio === '') {
                error.textContent = 'Completá nombre y precio en cada variante.';
                error.style.display = 'inline';
                return;
            }

            datos.push({
                id: f.getAttribute('data-vid'),
                body: JSON.stringify({
                    name: nombre,
                    price: Number(precio),
                    is_available: f.querySelector('.fd-v-avail').checked
                })
            });
        }

        var vivos = datos.filter(function (d) { return d.id; }).map(function (d) { return d.id; });
        var tareas = [];

        fdVariantOriginales.forEach(function (id) {
            if (vivos.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariant(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar la variante.'); });
                });
            }
        });

        datos.forEach(function (d) {
            if (d.id) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariant(d.id), { method: 'PUT', body: d.body })
                        .then(function (res) { fdCheck(res, 'No se pudo guardar la variante.'); });
                });
            } else {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariants(fdVariantProducto.id), { method: 'POST', body: d.body })
                        .then(function (res) { fdCheck(res, 'No se pudo crear la variante.'); });
                });
            }
        });

        fdSeq(tareas)
            .then(function () {
                closeVariantModal();
                fdToast('Variantes guardadas.', false);
                fdCatReload();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline';
            });
    }

    // --- Agregados (grupos de opciones y opciones) ---

    var fdOptProducto = null;
    var fdOptGruposOriginales = [];
    var fdOptOpcionesOriginales = [];

    function fdUrlGroups(productId) {
        return window.APP_BASE+"/api/v1/provider/products"+"/" + encodeURIComponent(productId) + '/option-groups';
    }

    function fdUrlGroup(id) {
        return window.APP_BASE+"/api/v1/provider/option-groups"+"/" + encodeURIComponent(id);
    }

    function fdUrlOptions(groupId) {
        return window.APP_BASE+"/api/v1/provider/option-groups"+"/" + encodeURIComponent(groupId) + '/options';
    }

    function fdUrlOption(id) {
        return window.APP_BASE+"/api/v1/provider/options"+"/" + encodeURIComponent(id);
    }

    function fdOptOptionRow(o) {
        o = o || {};
        var oid = (o.id !== undefined && o.id !== null) ? String(o.id) : '';
        var extra = (o.extra_price !== undefined && o.extra_price !== null && o.extra_price !== '') ? Number(o.extra_price) : 0;

        return '<div class="fd-opt-option" data-oid="' + oid + '" style="display:flex;gap:8px;align-items:flex-end;padding:8px 0;border-top:1px solid #f1f5f9;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:150px;"><label class="field-label">Opción</label>'
            + '<input type="text" class="field-input fd-o-name" maxlength="60" value="' + escapeHtml(o.name || '') + '" placeholder="Ej: Muzzarella extra" /></div>'
            + '<div style="flex:1;min-width:110px;"><label class="field-label">Precio extra</label>'
            + '<input type="number" step="0.01" min="0" class="field-input fd-o-price" value="' + extra + '" placeholder="0.00" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-o-avail"' + (o.is_available === false ? '' : ' checked') + ' /> Disponible</label>'
            + '<button type="button" class="icon-btn del" title="Quitar opción" onclick="fdOptRemoveOption(this)" style="margin-bottom:2px;">&#10005;</button>'
            + '</div>';
    }

    function fdOptGroupCard(g) {
        g = g || {};
        var gid = (g.id !== undefined && g.id !== null) ? String(g.id) : '';
        var min = (g.min_choices !== undefined && g.min_choices !== null) ? g.min_choices : 0;
        var max = (g.max_choices !== undefined && g.max_choices !== null) ? g.max_choices : 3;

        return '<div class="fd-opt-group" data-gid="' + gid + '" style="border:1px solid #e5e7eb;padding:12px 14px;margin-bottom:12px;background:#fff;">'
            + '<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:160px;"><label class="field-label">Grupo</label>'
            + '<input type="text" class="field-input fd-g-name" maxlength="60" value="' + escapeHtml(g.name || '') + '" placeholder="Ej: Agregados" /></div>'
            + '<div style="width:86px;"><label class="field-label">Mín.</label>'
            + '<input type="number" min="0" max="20" class="field-input fd-g-min" value="' + min + '" /></div>'
            + '<div style="width:86px;"><label class="field-label">Máx.</label>'
            + '<input type="number" min="1" max="20" class="field-input fd-g-max" value="' + max + '" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-g-req"' + (g.is_required ? ' checked' : '') + ' /> Obligatorio</label>'
            + '<button type="button" class="btn-danger" onclick="fdOptRemoveGroup(this)">Quitar grupo</button>'
            + '</div>'
            + '<div class="fd-opt-options" style="margin-top:6px;">'
            + (g.options || []).map(function (o) { return fdOptOptionRow(o); }).join('')
            + '</div>'
            + '<button type="button" onclick="fdOptAddOption(this)" title="Agregar opción" aria-label="Agregar opción" style="margin-top:8px;background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>'
            + '</button>'
            + '</div>';
    }

    function fdOptAddGroup() {
        document.getElementById('fd-opt-groups').insertAdjacentHTML('beforeend', fdOptGroupCard(null));
    }

    function fdOptAddOption(boton) {
        boton.closest('.fd-opt-group').querySelector('.fd-opt-options')
            .insertAdjacentHTML('beforeend', fdOptOptionRow(null));
    }

    function fdOptRemoveOption(boton) {
        var fila = boton.closest('.fd-opt-option');
        if (fila && fila.parentNode) fila.parentNode.removeChild(fila);
    }

    function fdOptRemoveGroup(boton) {
        var card = boton.closest('.fd-opt-group');
        if (card && card.parentNode) card.parentNode.removeChild(card);
    }

    function openOptionModal(productId) {
        fdProdLoad(function () {
            var producto = fdProdOptions().products[productId];
            if (!producto) return;

            fdOptProducto = producto;
            fdOptGruposOriginales = [];
            fdOptOpcionesOriginales = [];

            (producto.option_groups || []).forEach(function (g) {
                fdOptGruposOriginales.push(String(g.id));
                (g.options || []).forEach(function (o) { fdOptOpcionesOriginales.push(String(o.id)); });
            });

            document.getElementById('fd-opt-groups').innerHTML =
                (producto.option_groups || []).map(function (g) { return fdOptGroupCard(g); }).join('');

            var error = document.getElementById('fd-opt-error');
            error.textContent = '';
            error.style.display = 'none';

            document.getElementById('fd-opt-overlay').style.display = 'flex';
        });
    }

    function closeOptionModal() {
        document.getElementById('fd-opt-overlay').style.display = 'none';
    }

    function saveOptions() {
        if (!fdOptProducto) return;

        var error = document.getElementById('fd-opt-error');
        error.style.display = 'none';

        var cards = Array.prototype.slice.call(document.querySelectorAll('#fd-opt-groups .fd-opt-group'));
        var grupos = [];

        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            var nombreGrupo = card.querySelector('.fd-g-name').value.trim();
            var min = Number(card.querySelector('.fd-g-min').value || 0);
            var max = Number(card.querySelector('.fd-g-max').value || 0);
            var opciones = Array.prototype.slice.call(card.querySelectorAll('.fd-opt-option'));
            var datosOpciones = [];

            if (!nombreGrupo) {
                error.textContent = 'Cada grupo necesita un nombre.';
                error.style.display = 'inline';
                return;
            }

            if (max < 1) {
                error.textContent = 'En "' + nombreGrupo + '" el máximo de elecciones debe ser al menos 1.';
                error.style.display = 'inline';
                return;
            }

            if (min > max) {
                error.textContent = 'En "' + nombreGrupo + '" el mínimo no puede superar al máximo.';
                error.style.display = 'inline';
                return;
            }

            for (var j = 0; j < opciones.length; j++) {
                var fila = opciones[j];
                var nombreOp = fila.querySelector('.fd-o-name').value.trim();
                var extra = fila.querySelector('.fd-o-price').value;

                if (!nombreOp || extra === '') {
                    error.textContent = 'Completá nombre y precio extra en cada opción.';
                    error.style.display = 'inline';
                    return;
                }

                datosOpciones.push({
                    id: fila.getAttribute('data-oid'),
                    body: JSON.stringify({
                        name: nombreOp,
                        extra_price: Number(extra),
                        is_available: fila.querySelector('.fd-o-avail').checked
                    })
                });
            }

            grupos.push({
                el: card,
                gid: card.getAttribute('data-gid'),
                body: JSON.stringify({
                    name: nombreGrupo,
                    min_choices: min,
                    max_choices: max,
                    is_required: card.querySelector('.fd-g-req').checked
                }),
                opciones: datosOpciones
            });
        }

        var gruposVivos = grupos.filter(function (g) { return g.gid; }).map(function (g) { return g.gid; });
        var opcionesVivas = [];

        grupos.forEach(function (g) {
            g.opciones.forEach(function (o) { if (o.id) opcionesVivas.push(o.id); });
        });

        var tareas = [];

        // Bajas: primero las opciones sueltas, después los grupos (cascada).
        fdOptOpcionesOriginales.forEach(function (id) {
            if (opcionesVivas.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlOption(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar la opción.'); });
                });
            }
        });

        fdOptGruposOriginales.forEach(function (id) {
            if (gruposVivos.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlGroup(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar el grupo.'); });
                });
            }
        });

        // Altas y ediciones: cada grupo se guarda antes que sus opciones, así
        // una variante nueva ya tiene su id al cargar las opciones.
        grupos.forEach(function (g) {
            tareas.push(function () {
                if (g.gid) {
                    return fdFetchJson(fdUrlGroup(g.gid), { method: 'PUT', body: g.body })
                        .then(function (res) { fdCheck(res, 'No se pudo guardar el grupo.'); });
                }

                return fdFetchJson(fdUrlGroups(fdOptProducto.id), { method: 'POST', body: g.body })
                    .then(function (res) {
                        fdCheck(res, 'No se pudo crear el grupo.');
                        g.gid = String(res.data.group.id);
                        g.el.setAttribute('data-gid', g.gid);
                    });
            });

            g.opciones.forEach(function (o) {
                tareas.push(function () {
                    if (!g.gid) {
                        throw new Error('No se pudo guardar la opción porque falta el grupo.');
                    }

                    if (o.id) {
                        return fdFetchJson(fdUrlOption(o.id), { method: 'PUT', body: o.body })
                            .then(function (res) { fdCheck(res, 'No se pudo guardar la opción.'); });
                    }

                    return fdFetchJson(fdUrlOptions(g.gid), { method: 'POST', body: o.body })
                        .then(function (res) { fdCheck(res, 'No se pudo crear la opción.'); });
                });
            });
        });

        fdSeq(tareas)
            .then(function () {
                closeOptionModal();
                fdToast('Agregados guardados.', false);
                fdCatReload();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline';
            });
    }

    // --- Inventario ---

    function fdSetInventoryState(state, text) {
        var map = { loading: 'fd-inventory-loading', message: 'fd-inventory-message', content: 'fd-inventory-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-inventory-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function loadInventory() {
        fdSetInventoryState('loading');

        fdFetchJson(window.APP_BASE+"/api/v1/provider/inventory")
            .then(function (res) {
                if (!res.ok) {
                    fdSetInventoryState('message', (res.data && res.data.message) || 'No se pudo cargar el inventario.');
                    return;
                }

                var provider = res.data.provider || {};
                var enabled = !!provider.has_inventory_control;
                var toggle = document.getElementById('fd-inventory-enabled');
                if (toggle) toggle.checked = enabled;

                var summary = document.getElementById('fd-inventory-summary');
                if (summary) {
                    summary.textContent = (provider.business_name || 'Tu comercio')
                        + (enabled ? ' · inventario activo' : ' · inventario desactivado')
                        + (res.data.low_stock_count ? ' · ' + res.data.low_stock_count + ' con stock bajo' : '');
                }

                var products = res.data.products || [];
                fdInventoryData = res.data;
                if (!products.length) {
                    fdSetInventoryState('message', 'Todavía no hay productos en tu catálogo.\nCargá productos desde Mi Catálogo para empezar a controlar el stock.');
                    return;
                }

                document.getElementById('fd-inventory-content').innerHTML = fdRenderInventory(res.data);
                fdSetInventoryState('content');
            })
            .catch(function () {
                fdSetInventoryState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function toggleInventoryControl() {
        var toggle = document.getElementById('fd-inventory-enabled');
        if (!toggle) return;

        fdFetchJson(window.APP_BASE+"/api/v1/provider/inventory/settings", {
            method: 'PUT',
            body: JSON.stringify({ has_inventory_control: toggle.checked })
        })
        .then(function (res) {
            if (!res.ok) {
                toggle.checked = !toggle.checked;
                fdToast('No se pudo actualizar la configuración de inventario.', true);
                return;
            }
            fdToast('Configuración de inventario actualizada.', false);
            loadInventory();
        })
        .catch(function () {
            toggle.checked = !toggle.checked;
            fdToast('No se pudo conectar con el servidor.', true);
        });
    }

    function fdInvNum(value) {
        if (value === null || typeof value === 'undefined') return '—';
        var n = Number(value);
        if (isNaN(n)) return escapeHtml(String(value));
        return String(Math.round(n * 1000) / 1000);
    }

    var fdInventoryData = null;
    var fdInvCatFilter = '';

    function fdInvUnitOptions(selectedId) {
        var units = (fdInventoryData && fdInventoryData.unit_of_measures) || [];
        var html = '<option value="">Sin unidad</option>';

        units.forEach(function (u) {
            html += '<option value="' + escapeHtml(String(u.id)) + '"'
                + (String(u.id) === String(selectedId) ? ' selected' : '')
                + (u.is_integer_only ? ' data-integer="1"' : '') + '>'
                + escapeHtml(u.name) + ' (' + escapeHtml(u.symbol) + ')</option>';
        });

        return html;
    }

    function fdInvProduct(productId) {
        var products = (fdInventoryData && fdInventoryData.products) || [];

        for (var i = 0; i < products.length; i++) {
            if (String(products[i].product_id) === String(productId)) return products[i];
        }

        return null;
    }

    function fdInvStockTrackChanged() {
        var fields = document.getElementById('fd-inventory-stock-fields');
        if (fields) fields.style.display = document.getElementById('fd-inventory-stock-track').checked ? 'block' : 'none';
    }

    // Interruptor rapido de la fila: activa o desactiva el seguimiento sin
    // entrar al modal, conservando la cantidad y el minimo ya cargados.
    function fdInvToggleTrack(productId, checkbox) {
        var producto = fdInvProduct(productId);
        if (!producto) return;

        var cuerpo = { track_stock: checkbox.checked };

        if (checkbox.checked) {
            cuerpo.current_stock = Number(producto.current_stock) || 0;
            if (producto.min_stock_alert !== null && typeof producto.min_stock_alert !== 'undefined') {
                cuerpo.min_stock_alert = producto.min_stock_alert;
            }
            if (producto.unit_of_measure) cuerpo.unit_of_measure_id = producto.unit_of_measure.id;
        }

        checkbox.disabled = true;

        fdFetchJson(window.APP_BASE+"/api/v1/provider/inventory/products"+"/" + encodeURIComponent(productId), {
            method: 'PUT',
            body: JSON.stringify(cuerpo)
        })
            .then(function (res) {
                checkbox.disabled = false;
                if (!res.ok) {
                    checkbox.checked = !checkbox.checked;
                    fdToast((res.data && res.data.message) || 'No se pudo cambiar el seguimiento del stock.', true);
                    return;
                }
                fdToast(checkbox.checked ? 'Seguimiento de stock activado.' : 'Seguimiento de stock desactivado.', false);
                loadInventory();
            })
            .catch(function () {
                checkbox.disabled = false;
                checkbox.checked = !checkbox.checked;
                fdToast('No se pudo conectar con el servidor.', true);
            });
    }

    function openInventoryStockModal(productId) {
        var producto = fdInvProduct(productId);
        if (!producto) return;

        document.getElementById('fd-inventory-stock-id').value = producto.product_id;
        document.getElementById('fd-inventory-stock-track').checked = !!producto.track_stock;
        document.getElementById('fd-inventory-stock-current').value = fdInvNum(producto.current_stock) === '—' ? '' : fdInvNum(producto.current_stock);
        document.getElementById('fd-inventory-stock-min').value = (producto.min_stock_alert === null || typeof producto.min_stock_alert === 'undefined') ? '' : fdInvNum(producto.min_stock_alert);
        document.getElementById('fd-inventory-stock-unit').innerHTML = fdInvUnitOptions(producto.unit_of_measure ? producto.unit_of_measure.id : '');
        document.getElementById('fd-inventory-stock-negative').checked = !!producto.allow_negative_stock;
        document.getElementById('fd-inventory-stock-notes').value = '';

        var error = document.getElementById('fd-inventory-stock-error');
        error.style.display = 'none';
        error.textContent = '';

        fdInvStockTrackChanged();
        document.getElementById('fd-inventory-stock-overlay').style.display = 'flex';
    }

    function closeInventoryStockModal() {
        document.getElementById('fd-inventory-stock-overlay').style.display = 'none';
    }

    function saveInventoryStock() {
        var productId = document.getElementById('fd-inventory-stock-id').value;
        var track = document.getElementById('fd-inventory-stock-track').checked;
        var error = document.getElementById('fd-inventory-stock-error');

        var cuerpo = { track_stock: track };

        if (track) {
            var actual = document.getElementById('fd-inventory-stock-current').value;
            var minimo = document.getElementById('fd-inventory-stock-min').value;
            var unidad = document.getElementById('fd-inventory-stock-unit').value;

            if (actual === '') {
                error.textContent = 'Indicá el stock actual.';
                error.style.display = 'inline';
                return;
            }

            cuerpo.current_stock = Number(actual);
            cuerpo.allow_negative_stock = document.getElementById('fd-inventory-stock-negative').checked;
            cuerpo.min_stock_alert = minimo === '' ? null : Number(minimo);
            cuerpo.unit_of_measure_id = unidad === '' ? null : Number(unidad);

            var notas = document.getElementById('fd-inventory-stock-notes').value;
            if (notas) cuerpo.notes = notas;
        }

        error.style.display = 'none';

        fdFetchJson(window.APP_BASE+"/api/v1/provider/inventory/products"+"/" + encodeURIComponent(productId), {
            method: 'PUT',
            body: JSON.stringify(cuerpo)
        })
            .then(function (res) {
                if (!res.ok) {
                    var mensaje = (res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo guardar el stock.';
                    error.textContent = mensaje;
                    error.style.display = 'inline';
                    return;
                }
                closeInventoryStockModal();
                fdToast('Stock actualizado.', false);
                loadInventory();
            })
            .catch(function () {
                error.textContent = 'No se pudo conectar con el servidor.';
                error.style.display = 'inline';
            });
    }

    function fdInvFilterChange(value) {
        fdInvCatFilter = value || '';
        if (!fdInventoryData) return;
        var content = document.getElementById('fd-inventory-content');
        if (content) content.innerHTML = fdRenderInventory(fdInventoryData);
    }

    function fdRenderInventory(data) {
        var all = data.products || [];
        var cats = data.categories || [];
        var enabled = !!(data.provider && data.provider.has_inventory_control);

        var selected = String(fdInvCatFilter || '');
        if (selected && !cats.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdInvCatFilter = '';
        }

        var products = selected
            ? all.filter(function (p) { return String(p.category_id) === selected; })
            : all;

        var lowCount = products.filter(function (p) { return p.low_stock; }).length;

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-inv-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-inv-cat-filter" onchange="fdInvFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + cats.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">'
            + fdBadge(products.length + ' productos', '#0c2a4d', '#eef2f7')
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + fdBadge(lowCount + ' con stock bajo', lowCount ? '#b91c1c' : '#047857', lowCount ? '#fee2e2' : '#d1fae5')
            + fdBadge(enabled ? 'Control activo' : 'Control desactivado', enabled ? '#047857' : '#9a3412', enabled ? '#d1fae5' : '#fff7ed')
            + combo
            + '</div>';

        if (!enabled) {
            html += '<div style="background:#fffbeb;border:1px solid #fde68a;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400e;">'
                + 'El control de inventario está desactivado: las ventas no descuentan stock y los ajustes no se aplican. '
                + 'Activalo con el interruptor de arriba.'
                + '</div>';
        }

        if (!products.length) {
            return html + '<div style="background:#fff;border:1px solid #e5e7eb;padding:32px 20px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">No hay productos en esta categoría.</p>'
                + '</div>';
        }

        html += '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Unidad</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Stock actual</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Reservado</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Disponible</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Mínimo</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Estado</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Acciones</th>'
            + '</tr></thead><tbody>';

        products.forEach(function (p, i) {
            var unit = p.unit_of_measure
                ? escapeHtml(p.unit_of_measure.name) + ' (' + escapeHtml(p.unit_of_measure.symbol) + ')'
                : '—';

            var status;
            if (!p.track_stock) status = fdBadge('S/S', '#64748b', '#f1f5f9');
            else if (p.low_stock) status = fdBadge('Stock bajo', '#b91c1c', '#fee2e2');
            else if (Number(p.available_stock) <= 0) status = fdBadge('Sin stock', '#b91c1c', '#fee2e2');
            else status = fdBadge('OK', '#047857', '#d1fae5');

            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;">' + escapeHtml(p.name) + '</td>'
                + '<td style="padding:10px 12px;color:#6b7280;">' + unit + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#0f172a;font-weight:600;">' + fdInvNum(p.current_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#6b7280;">' + fdInvNum(p.reserved_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#0f172a;">' + fdInvNum(p.available_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#6b7280;">'
                + (typeof p.min_stock_alert !== 'undefined' && p.min_stock_alert !== null ? fdInvNum(p.min_stock_alert) : '—')
                + '</td>'
                + '<td style="padding:10px 12px;">' + status + '</td>'
                + '<td style="padding:10px 12px;white-space:nowrap;text-align:left;">' + fdInvRowActions(p) + '</td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    function fdInvRowActions(p) {
        var id = escapeHtml(String(p.product_id));
        var toggle = '<label style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#475569;cursor:pointer;white-space:nowrap;" title="Cambiar seguimiento de stock">'
            + '<input type="checkbox" ' + (p.track_stock ? 'checked' : '')
            + ' onchange="fdInvToggleTrack(\'' + id + '\', this)" style="cursor:pointer;">'
            + '<span>' + (p.track_stock ? 'C/S' : 'S/S') + '</span>'
            + '</label>';
        var edit = '<button type="button" title="Editar" aria-label="Editar" onclick="openInventoryStockModal(\'' + id + '\')" style="margin-left:10px;background:#fff;color:#D24C19;border:1px solid #D24C19;padding:5px 7px;border-radius:3px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">' + FD_ICON_EDIT + '</button>';

        return toggle + edit;
    }

    var fdPrevCatFilter = '';
    var fdPrevData = null;
    var fdCatPage = 1;
    var FD_CAT_PER_PAGE = 10;
    var fdCatTotalPages = 1;

    function fdPrevFilterChange(value) {
        fdPrevCatFilter = value || '';
        fdCatPage = 1;
        if (!fdPrevData) return;
        var content = document.getElementById('fd-catalog-content');
        if (content) content.innerHTML = fdRenderCatalog(fdPrevData.categories, fdPrevData.provider);
        fdCatPaintPagination();
    }

    // Aplana los productos del catálogo (respetando el orden de sus categorías)
    // para paginarlos de a 10 y reagrupar por categoría solo la página visible.
    function fdCatPageSlice(categories, selected) {
        var shown = selected
            ? categories.filter(function (c) { return String(c.id) === selected; })
            : categories;

        var flat = [];
        shown.forEach(function (c) {
            (c.products || []).forEach(function (p) { flat.push({ cat: c, product: p }); });
        });

        fdCatTotalPages = Math.max(1, Math.ceil(flat.length / FD_CAT_PER_PAGE));
        if (fdCatPage > fdCatTotalPages) fdCatPage = fdCatTotalPages;
        if (fdCatPage < 1) fdCatPage = 1;

        var start = (fdCatPage - 1) * FD_CAT_PER_PAGE;
        var end = start + FD_CAT_PER_PAGE;

        var pageCategories = [];
        var cursor = 0;
        shown.forEach(function (c) {
            var products = c.products || [];
            var inPage = [];

            products.forEach(function (p) {
                if (cursor >= start && cursor < end) inPage.push(p);
                cursor++;
            });

            if (inPage.length) {
                pageCategories.push({ cat: c, products: inPage });
            } else if (!products.length && fdCatPage === 1) {
                // Las categorías vacías solo se muestran en la primera página.
                pageCategories.push({ cat: c, products: [] });
            }
        });

        return { shown: shown, flatCount: flat.length, pageCategories: pageCategories };
    }

    function fdCatPaintPagination() {
        var box = document.getElementById('fd-catalog-pagination');
        if (!box) return;

        if (fdCatTotalPages <= 1) {
            box.style.display = 'none';
            box.innerHTML = '';
            return;
        }

        var base = 'background:#fff;color:#374151;border:1px solid #d1d5db;padding:6px 10px;border-radius:4px;'
            + 'display:inline-flex;align-items:center;justify-content:center;line-height:0;';
        var prev = fdCatPage > 1;
        var next = fdCatPage < fdCatTotalPages;

        box.innerHTML = '<button type="button" onclick="fdCatGoPage(-1)" title="Página anterior" aria-label="Página anterior" '
            + (prev ? '' : 'disabled ') + 'style="' + base + (prev ? 'cursor:pointer;' : 'opacity:.5;') + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>'
            + '<span style="font-size:12px;color:#6b7280;">Página ' + fdCatPage + ' de ' + fdCatTotalPages + '</span>'
            + '<button type="button" onclick="fdCatGoPage(1)" title="Página siguiente" aria-label="Página siguiente" '
            + (next ? '' : 'disabled ') + 'style="' + base + (next ? 'cursor:pointer;' : 'opacity:.5;') + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>';
        box.style.display = 'flex';
    }

    function fdCatGoPage(step) {
        var next = Math.min(Math.max(1, fdCatPage + step), fdCatTotalPages);
        if (next === fdCatPage) return;

        fdCatPage = next;
        if (fdPrevData) {
            var content = document.getElementById('fd-catalog-content');
            if (content) content.innerHTML = fdRenderCatalog(fdPrevData.categories, fdPrevData.provider);
        }
        fdCatPaintPagination();
    }

    function fdRenderCatalog(categories, provider) {
        var selected = String(fdPrevCatFilter || '');
        if (selected && !categories.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdPrevCatFilter = '';
        }

        var page = fdCatPageSlice(categories, selected);
        var shown = page.shown;
        var pageCategories = page.pageCategories;

        var shownProducts = page.flatCount;

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-prev-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-prev-cat-filter" onchange="fdPrevFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + categories.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">'
            + fdBadge(shown.length + (shown.length === 1 ? ' categoría' : ' categorías'), '#0c2a4d', '#eef2f7')
            + fdBadge(shownProducts + (shownProducts === 1 ? ' producto' : ' productos'), '#0c2a4d', '#eef2f7')
            + (provider && provider.zone ? fdBadge(provider.zone, '#9a3412', '#fff7ed') : '')
            + (provider && provider.is_active === false ? fdBadge('Comercio pausado', '#b91c1c', '#fee2e2') : '')
            + combo
            + '</div>';

        pageCategories.forEach(function (entry) {
            var cat = entry.cat;
            var products = entry.products;

            var stripTitle = selected ? 'Filtrando: ' + escapeHtml(cat.name) : escapeHtml(cat.name);
            var stripCount = products.length + (products.length === 1 ? ' producto' : ' productos');

            html += '<div style="background:#fff;border:1px solid #e5e7eb;margin-bottom:16px;overflow:hidden;">'
                + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 16px;background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
                + '<span style="font-size:13px;font-weight:700;color:#fff;">' + stripTitle + '</span>'
                + '<span style="font-size:11px;color:rgba(255,255,255,.75);">' + stripCount + '</span>'
                + '</div>';

            if (!products.length) {
                html += '<div style="padding:20px 16px;font-size:13px;color:#94a3b8;text-align:center;">Esta categoría todavía no tiene productos.</div>';
            } else {
                products.forEach(function (p) { html += fdRenderProduct(p); });
            }

            html += '</div>';
        });

        return html;
    }

    function fdRenderProduct(p) {
        var available = p.is_available !== false;
        var html = '<div style="padding:14px 16px;border-top:1px solid #f1f5f9;">'
            + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">'
            + '<div style="min-width:0;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span style="font-size:14px;font-weight:600;color:#0f172a;">' + escapeHtml(p.name) + '</span>'
            + (available ? fdBadge('Disponible', '#047857', '#d1fae5') : fdBadge('No disponible', '#b91c1c', '#fee2e2'))
            + '</div>';

        if (p.description) {
            html += '<p style="font-size:12px;color:#64748b;margin:4px 0 0;">' + escapeHtml(p.description) + '</p>';
        }

        if (p.track_stock) {
            var stock = p.inventory ? Number(p.inventory.current_stock || 0) : 0;
            var minimo = (typeof p.min_stock_alert !== 'undefined' && p.min_stock_alert !== null) ? Number(p.min_stock_alert) : null;
            var bajo = minimo !== null && stock < minimo;

            html += '<p style="font-size:11px;color:' + (bajo ? '#b91c1c' : '#475569') + ';margin:4px 0 0;">'
                + (bajo ? fdBadge('Stock bajo', '#b91c1c', '#fee2e2') : fdBadge('Con stock', '#047857', '#d1fae5'))
                + ' <span style="margin-left:4px;">' + fdInvNum(stock) + ' disponible'
                + (minimo !== null ? ' · mínimo ' + fdInvNum(minimo) : '')
                + '</span></p>';
        }

        html += '</div>'
            + '<div style="display:flex;align-items:center;gap:10px;white-space:nowrap;">'
            + '<button type="button" title="Editar" aria-label="Editar" onclick="openProductModal(' + escapeHtml(String(p.id)) + ')" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px 8px;border-radius:3px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">' + FD_ICON_EDIT + '</button>'
            + '<div style="font-size:14px;font-weight:700;color:#D24C19;">' + fdMoney(p.price) + '</div>'
            + '</div>'
            + '</div>';

        var variants = p.variants || [];
        if (variants.length) {
            html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">';
            variants.forEach(function (v) {
                var ok = v.is_available !== false;
                html += '<span style="font-size:11px;padding:4px 9px;border:1px solid ' + (ok ? '#fdba74' : '#e2e8f0')
                    + ';border-radius:4px;background:' + (ok ? '#fff7ed' : '#f8fafc')
                    + ';color:' + (ok ? '#9a3412' : '#94a3b8') + ';">'
                    + escapeHtml(v.name) + ' · ' + fdMoney(v.price) + (ok ? '' : ' · sin stock') + '</span>';
            });
            html += '</div>';
        }

        var groups = p.option_groups || [];
        groups.forEach(function (g) {
            var opts = g.options || [];
            var rules = [];
            if (g.min_choices) rules.push('mín. ' + g.min_choices);
            if (g.max_choices) rules.push('máx. ' + g.max_choices);
            if (g.is_required) rules.push('obligatorio');

            html += '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e5e7eb;">'
                + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;">'
                + '<span style="font-weight:600;color:#0f172a;">' + escapeHtml(g.name) + '</span>'
                + (rules.length ? '<span style="color:#94a3b8;">' + escapeHtml(rules.join(' · ')) + '</span>' : '')
                + '</div>';

            if (opts.length) {
                html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">';
                opts.forEach(function (o) {
                    var ok = o.is_available !== false;
                    html += '<span style="font-size:11px;padding:4px 9px;border:1px solid #e2e8f0;border-radius:4px;background:'
                        + (ok ? '#fff' : '#f8fafc') + ';color:' + (ok ? '#334155' : '#94a3b8') + ';">'
                        + escapeHtml(o.name)
                        + (Number(o.extra_price) > 0 ? ' <b style="color:#D24C19;">+' + fdMoney(o.extra_price) + '</b>' : '')
                        + (ok ? '' : ' · sin stock')
                        + '</span>';
                });
                html += '</div>';
            } else {
                html += '<div style="font-size:11px;color:#94a3b8;margin-top:4px;">Sin opciones cargadas.</div>';
            }

            html += '</div>';
        });

        return html + '</div>';
    }

    // --- Pedidos ---

    function fdClock() {
        return new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function fdToast(text, isError) {
        var el = document.createElement('div');
        el.textContent = text;
        el.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:9999;max-width:340px;padding:11px 16px;'
            + 'border-radius:4px;font-size:13px;font-weight:600;color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.18);'
            + 'background:' + (isError ? '#b91c1c' : '#047857') + ';';
        document.body.appendChild(el);
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 4000);
    }

    // Ultimo HTML pintado por lista: evita repintar (y parpadear) cuando la
    // actualizacion automatica trae los mismos datos.
    var fdOrdersLastHtml = '';
    var fdMyOrdersLastHtml = '';

    // Despacho cerrado: estado del toggle y lista de repartidores de confianza
    // del comercio. No hay fallback por tiempo; loadTrustedDrivers solo se
    // consulta al abrir la seccion y despues de cada accion del comercio.
    var fdTrustedState = { enabled: false, drivers: [], candidates: [], loaded: false };
    var fdTrustedLoading = false;
    var fdTrustedSearch = '';

    // Mis Pedidos: pestaña activa (en curso / historial) y pedido completos.
    var fdMyOrdersActive = 'today';
    var fdMyOrdersAll = [];
    // Historial paginado: 3 pedidos por página (en curso se muestra completo).
    var fdMyOrdersHistoryPage = 1;
    var FD_MYORDERS_PER_PAGE = 3;
    // Búsqueda por local, código o plato (client-side sobre lo ya cargado).
    var fdMyOrdersSearch = '';
    var fdMyOrdersSearchTimer = null;
    var fdMyOrdersUpdatedAt = 0;
    // Productos de un pedido a repetir: se cargan al carrito al abrir la tienda.
    var fdShopRepeatQueue = null;

    // Pedidos del comercio: pestaña activa (pedidos de hoy / historial), el
    // listado de la página actual, la paginación del servidor y el rango de
    // fechas. Los contadores de las pestañas vienen del servidor.
    var fdOrdersActive = 'today';
    var fdOrdersAll = [];
    var fdOrdersCounts = { today: 0, history: 0, pending: 0, in_preparation: 0 };
    var fdOrdersPage = 1;
    var fdOrdersPagination = null;
    var FD_ORDERS_PER_PAGE = 10;
    // Pedidos contraídos manualmente: se respetan en cada repintado (el
    // sondeo de 8 s vuelve a renderizar la lista entera).
    var fdOrdersCollapsed = {};
    var fdOrdersSearchTimer = null;

    function loadProviderOrders(silent) {
        var providerId = fdProviderId('dash-pedidos');
        if (!providerId) {
            if (!silent) fdSetOrdersState('message', 'Todavía no tenés un comercio configurado.\nCreá tu comercio desde Tu Perfil para recibir pedidos.', 'Aún no hay un comercio activo', '');
            return;
        }

        var filterEl = document.getElementById('fd-orders-filter');
        var savedFilter = filterEl ? filterEl.value : '';
        var searchEl = document.getElementById('fd-orders-search');
        var savedSearch = searchEl ? searchEl.value.trim() : '';

        if (!silent) {
            fdOrdersPage = 1;
            fdOrdersLastHtml = '';
            fdSetOrdersState('loading');

            var pagBox = document.getElementById('fd-orders-pagination');
            if (pagBox) { pagBox.style.display = 'none'; pagBox.innerHTML = ''; }
        }

        var params = ['tab=' + encodeURIComponent(fdOrdersActive)];
        if (savedFilter) params.push('status=' + encodeURIComponent(savedFilter));
        if (savedSearch) params.push('search=' + encodeURIComponent(savedSearch));
        if (fdOrdersActive === 'history' && fdOrdersRangeActive()) {
            params.push('date_from=' + encodeURIComponent(fdOrdersDateFrom()));
            params.push('date_to=' + encodeURIComponent(fdOrdersDateTo()));
        }
        params.push('page=' + fdOrdersPage);
        params.push('per_page=' + FD_ORDERS_PER_PAGE);

        var url = window.APP_BASE+"/api/providers"+"/" + providerId + '/orders?' + params.join('&');

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok) {
                    if (silent) return;
                    fdSetOrdersState('message', (res.data && res.data.message) || 'No se pudieron cargar los pedidos.', 'No pudimos cargar los pedidos', '');
                    return;
                }

                var filter = document.getElementById('fd-orders-filter');
                if (filter && filter.options.length <= 1) {
                    (res.data.statuses || []).forEach(function (status) {
                        var opt = document.createElement('option');
                        opt.value = status;
                        opt.textContent = FD_ORDER_STATUS[status] ? FD_ORDER_STATUS[status][0] : status;
                        filter.appendChild(opt);
                    });
                    filter.value = savedFilter;
                }

                var orders = res.data.orders || [];
                var provider = res.data.provider || {};

                // Mantiene sincronizado el estado del despacho cerrado. La
                // lista de integrantes se carga una vez (o tras cada accion)
                // para no repintar el selector en cada sondeo de 8 s.
                fdTrustedState.enabled = !!provider.trusted_drivers_enabled;
                if (fdTrustedState.loaded) {
                    fdTrustedPaint(provider);
                } else if (!fdTrustedLoading) {
                    loadTrustedDrivers();
                }

                fdOrdersAll = orders;
                fdOrdersCounts = Object.assign({ today: 0, history: 0, pending: 0, in_preparation: 0 }, res.data.counts || {});
                fdOrdersPagination = res.data.pagination || null;

                // Encabezado en vivo: comercio, hora de sincronización, chips y KPI.
                fdOrdersPaintTabs();
                var biz = document.getElementById('fd-orders-bizname');
                if (biz && provider.business_name) biz.textContent = provider.business_name;
                var sync = document.getElementById('fd-orders-summary');
                if (sync) sync.textContent = 'Sincronizado a las ' + fdClock();

                if (!orders.length) {
                    fdOrdersLastHtml = '';
                    fdOrdersPaintPagination();
                    fdSetOrdersState('message', fdOrdersEmptyText(), fdOrdersEmptyTitle(), fdOrdersResetAction());
                    return;
                }

                fdOrdersRender(silent);
            })
            .catch(function () {
                if (silent) return;
                fdSetOrdersState('message', 'No se pudo conectar con el servidor.\nRevisá tu conexión y volvé a intentar.', 'Sin conexión', '');
            });
    }

    function fdOrdersDateFrom() {
        var el = document.getElementById('fd-orders-date-from');
        return el ? el.value : '';
    }

    function fdOrdersDateTo() {
        var el = document.getElementById('fd-orders-date-to');
        return el ? el.value : '';
    }

    function fdOrdersRangeActive() {
        return !!(fdOrdersDateFrom() || fdOrdersDateTo());
    }

    // Cambia el rango de fechas: el servidor filtra y la pagina vuelve a 1.
    function fdOrdersRangeChange() {
        fdOrdersLastHtml = '';
        loadProviderOrders();
    }

    function fdOrdersClearRange() {
        var fromEl = document.getElementById('fd-orders-date-from');
        var toEl = document.getElementById('fd-orders-date-to');
        if (fromEl) fromEl.value = '';
        if (toEl) toEl.value = '';
        fdOrdersLastHtml = '';
        loadProviderOrders();
    }

    // Hay filtros puestos (estado, texto o rango): habilita el boton de reseteo.
    function fdOrdersFiltersActive() {
        var filterEl = document.getElementById('fd-orders-filter');
        var searchEl = document.getElementById('fd-orders-search');
        return !!((filterEl && filterEl.value) || (searchEl && searchEl.value.trim()) || fdOrdersRangeActive());
    }

    function fdOrdersEmptyText() {
        var filterEl = document.getElementById('fd-orders-filter');
        var searchEl = document.getElementById('fd-orders-search');
        var search = searchEl ? searchEl.value.trim() : '';

        if (fdOrdersActive === 'history' && fdOrdersRangeActive()) return 'No hay pedidos en ese rango de fechas.\nProbá con otro rango o restablecé los filtros.';
        if (search) return 'No hay pedidos que coincidan con "' + search + '".\nRevisá el número de pedido o el nombre del cliente.';
        if (filterEl && filterEl.value) return 'No hay pedidos con ese estado en esta sección.';
        if (!Number(fdOrdersCounts.today) && !Number(fdOrdersCounts.history)) {
            return 'Todavía no recibiste pedidos.\nCuando un cliente coloque su pedido va a aparecer acá.';
        }
        if (fdOrdersActive === 'today') {
            return 'No tenés pedidos hoy.\nPasá por la pestaña de historial para ver los anteriores.';
        }
        return 'Todavía no hay pedidos en el historial.';
    }

    function fdOrdersEmptyTitle() {
        if (fdOrdersFiltersActive()) return 'No se encontraron pedidos';
        if (!Number(fdOrdersCounts.today) && !Number(fdOrdersCounts.history)) return 'Todavía no recibiste pedidos';
        return fdOrdersActive === 'today' ? 'Sin pedidos para hoy' : 'El historial está vacío';
    }

    // Botón del estado vacío: solo aparece si hay algo que limpiar.
    function fdOrdersResetAction() {
        if (!fdOrdersFiltersActive()) return '';
        return '<button type="button" class="fd-btn fd-btn-primary" onclick="fdOrdersResetFilters()">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>'
            + 'Restablecer Filtros</button>';
    }

    function fdOrdersResetFilters() {
        var filterEl = document.getElementById('fd-orders-filter');
        if (filterEl) filterEl.value = '';
        var searchEl = document.getElementById('fd-orders-search');
        if (searchEl) searchEl.value = '';
        var fromEl = document.getElementById('fd-orders-date-from');
        if (fromEl) fromEl.value = '';
        var toEl = document.getElementById('fd-orders-date-to');
        if (toEl) toEl.value = '';

        fdOrdersPage = 1;
        fdOrdersLastHtml = '';
        loadProviderOrders();
    }

    // Búsqueda con espera: cada tecla no dispara un request al servidor.
    function fdOrdersSearchDebounce() {
        if (fdOrdersSearchTimer) clearTimeout(fdOrdersSearchTimer);
        fdOrdersSearchTimer = setTimeout(function () {
            fdOrdersPage = 1;
            fdOrdersLastHtml = '';
            loadProviderOrders();
        }, 350);
    }

    // ===== Repartidores de confianza (despacho cerrado, sin fallback) =====

    function fdTrustedApi(path) {
        return window.APP_BASE+"/api/v1/provider/trusted-drivers" + (path || '');
    }

    function fdTrustedMessage(text, isError) {
        var targets = ['fd-trusted-msg', 'fd-td-msg'];
        var any = false;

        if (fdTrustedMessage.timer) {
            clearTimeout(fdTrustedMessage.timer);
            fdTrustedMessage.timer = null;
        }

        targets.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            any = true;

            if (!text) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }

            el.textContent = text;
            el.style.display = 'block';
            el.style.color = isError ? '#991b1b' : '#166534';
            el.style.background = isError ? '#fef2f2' : '#f0fdf4';
            el.style.border = '1px solid ' + (isError ? '#fecaca' : '#bbf7d0');
        });

        if (!any || !text) return;

        // Los errores quedan visibles hasta la proxima accion para que no
        // pasen desapercibidos, por ejemplo al intentar quitar al ultimo
        // repartidor con la modalidad activa.
        if (isError) return;

        fdTrustedMessage.timer = setTimeout(function () {
            targets.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }, 5000);
    }

    function loadTrustedDrivers() {
        if (fdTrustedLoading) return;

        fdTrustedLoading = true;

        if (!fdTrustedState.loaded) {
            var box = document.getElementById('fd-trusted-list');
            if (box) box.textContent = 'Cargando repartidores...';

            var panelList = document.getElementById('fd-td-list');
            if (panelList) panelList.innerHTML = '<tr><td colspan="5" class="fd-td-empty">Cargando lista de confianza...</td></tr>';
        }

        fdFetchJson(fdTrustedApi())
            .then(function (res) {
                fdTrustedLoading = false;
                if (!res.ok) {
                    fdTrustedMessage((res.data && res.data.message) || 'No se pudo cargar la lista de confianza.', true);
                    return;
                }

                fdTrustedState.loaded = true;
                fdTrustedState.enabled = !!(res.data.provider && res.data.provider.trusted_drivers_enabled);
                fdTrustedState.drivers = res.data.drivers || [];
                fdTrustedState.candidates = res.data.candidates || [];
                fdTrustedPaint();
            })
            .catch(function () {
                fdTrustedLoading = false;
                fdTrustedMessage('No se pudo conectar con el servidor.', true);
            });
    }

    function fdTrustedPaint(provider) {
        if (provider && typeof provider.trusted_drivers_enabled !== 'undefined') {
            fdTrustedState.enabled = !!provider.trusted_drivers_enabled;
        }

        var enabled = !!fdTrustedState.enabled;
        var drivers = fdTrustedState.drivers || [];
        var candidates = fdTrustedState.candidates || [];

        var panelToggle = document.getElementById('fd-td-toggle');
        if (panelToggle) panelToggle.checked = enabled;

        var toggle = document.getElementById('fd-trusted-toggle');
        if (toggle) toggle.checked = enabled;

        var dot = document.getElementById('fd-trusted-dot');
        if (dot) dot.style.background = enabled ? '#D24C19' : '#94a3b8';

        var status = document.getElementById('fd-trusted-status');
        if (status) {
            status.textContent = enabled ? 'Solo repartidores de confianza' : 'Pool general';
            status.style.color = enabled ? '#D24C19' : '#0c2a4d';
        }

        var count = document.getElementById('fd-trusted-count');
        if (count) count.textContent = drivers.length + (drivers.length === 1 ? ' integrante' : ' integrantes');

        var listCount = document.getElementById('fd-trusted-list-count');
        if (listCount) listCount.textContent = drivers.length ? 'lista activa' : 'sin integrantes';

        var list = document.getElementById('fd-trusted-list');
        if (list) {
            if (!drivers.length) {
                list.innerHTML = '<div class="fd-trusted-empty">Todavía no agregaste repartidores. Sumá al menos uno para poder activar la modalidad.</div>';
            } else {
                list.innerHTML = drivers.map(function (d) {
                    var name = d.name || 'Repartidor';
                    var meta = [d.vehicle_label, d.phone].filter(Boolean).join(' · ');
                    return '<div class="fd-trusted-row">'
                        + '<div class="fd-trusted-avatar">' + escapeHtml(fdInitials(name)) + '</div>'
                        + '<div style="min-width:0;flex:1;">'
                        + '<div style="font-size:13px;font-weight:700;color:#0f172a;">' + escapeHtml(name) + '</div>'
                        + (meta ? '<div style="font-size:11.5px;color:#64748b;margin-top:1px;">' + escapeHtml(meta) + '</div>' : '')
                        + '</div>'
                        + '<button type="button" class="fd-icon-btn" title="Quitar de la lista" aria-label="Quitar a ' + escapeHtml(name) + ' de la lista" onclick="fdTrustedRemove(' + Number(d.id) + ', this)">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
                        + '</button>'
                        + '</div>';
                }).join('');
            }
        }

        var select = document.getElementById('fd-trusted-candidate');
        if (select) {
            var current = select.value;
            select.innerHTML = '<option value="">' + (candidates.length ? 'Seleccioná un repartidor...' : 'No hay repartidores disponibles') + '</option>'
                + candidates.map(function (c) {
                    var label = (c.name || 'Repartidor') + (c.vehicle_label ? ' · ' + c.vehicle_label : '');
                    return '<option value="' + Number(c.id) + '">' + escapeHtml(label) + '</option>';
                }).join('');
            select.value = candidates.some(function (c) { return String(c.id) === String(current); }) ? current : '';
        }

        var addBtn = document.getElementById('fd-trusted-add');
        if (addBtn) addBtn.disabled = !candidates.length;

        fdTrustedPaintPanel();
    }

    // ===== Panel "Repartidores de confianza" del menú lateral =====
    // Pinta el estado, los KPIs, la tabla de integrantes y los resultados del
    // buscador. Comparte el mismo fdTrustedState que la tarjeta de Pedidos.

    function fdTrustedPaintPanel() {
        var panel = document.getElementById('fd-td-panel');
        if (!panel) return;

        var enabled = !!fdTrustedState.enabled;
        var drivers = fdTrustedState.drivers || [];
        var active = drivers.filter(function (d) { return d.status === 'active'; }).length;

        var toggle = document.getElementById('fd-td-toggle');
        if (toggle) toggle.checked = enabled;

        var dot = document.getElementById('fd-td-dot');
        if (dot) dot.style.background = enabled ? '#D24C19' : '#94a3b8';

        var mode = document.getElementById('fd-td-mode');
        if (mode) mode.textContent = enabled ? 'Solo repartidores de confianza' : 'Pool general';

        var kpiMode = document.getElementById('fd-td-kpi-mode');
        if (kpiMode) {
            kpiMode.textContent = enabled ? 'Cerrado' : 'Pool general';
            kpiMode.style.color = enabled ? '#D24C19' : '#0c2a4d';
        }

        var count = document.getElementById('fd-td-count');
        if (count) count.textContent = drivers.length + (drivers.length === 1 ? ' integrante' : ' integrantes');

        var kpiCount = document.getElementById('fd-td-kpi-count');
        if (kpiCount) kpiCount.textContent = drivers.length;

        var kpiActive = document.getElementById('fd-td-kpi-active');
        if (kpiActive) kpiActive.textContent = active;

        var listCount = document.getElementById('fd-td-list-count');
        if (listCount) listCount.textContent = drivers.length
            ? (active + (active === 1 ? ' activo' : ' activos') + ' de ' + drivers.length)
            : 'Sin integrantes';

        var list = document.getElementById('fd-td-list');
        if (list) {
            if (!drivers.length) {
                list.innerHTML = '<tr><td colspan="5" class="fd-td-empty">Todavía no agregaste repartidores. Buscá uno arriba y sumalo para poder activar la modalidad.</td></tr>';
            } else {
                list.innerHTML = drivers.map(function (d) {
                    var name = d.name || 'Repartidor';
                    var meta = [d.email, d.phone ? 'Tel. ' + d.phone : '', 'ID ' + d.user_id].filter(Boolean).join(' · ');
                    var isActive = d.status === 'active';
                    var plate = d.vehicle_plate
                        ? '<span class="fd-td-plate">' + escapeHtml(String(d.vehicle_plate).toUpperCase()) + '</span>'
                        : '<span style="color:#94a3b8;">—</span>';

                    return '<tr>'
                        + '<td><div class="fd-td-user">'
                        + '<div class="fd-td-avatar">' + escapeHtml(fdInitials(name)) + '</div>'
                        + '<div style="min-width:0;"><div class="fd-td-name">' + escapeHtml(name) + '</div>'
                        + '<div class="fd-td-meta">' + escapeHtml(meta) + '</div></div>'
                        + '</div></td>'
                        + '<td>' + escapeHtml(d.vehicle_label || 'Sin definir') + '</td>'
                        + '<td>' + plate + '</td>'
                        + '<td><span class="fd-td-badge ' + (isActive ? 'on' : 'off') + '">'
                        + '<span class="fd-td-dot" style="background:' + (isActive ? '#10b981' : '#94a3b8') + ';"></span>'
                        + (isActive ? 'Activo' : 'Inactivo') + '</span></td>'
                        + '<td style="text-align:right;">'
                        + '<button type="button" class="fd-td-btn fd-td-btn-ghost" title="Quitar de la lista" aria-label="Quitar a ' + escapeHtml(name) + ' de la lista" onclick="fdTrustedRemove(' + Number(d.id) + ', this)">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
                        + '</button></td>'
                        + '</tr>';
                }).join('');
            }
        }

        fdTrustedPaintResults();
    }

    // Resultados del buscador: filtra por nombre, correo, teléfono o ID.
    function fdTrustedPaintResults() {
        var box = document.getElementById('fd-td-results');
        if (!box) return;

        var drivers = fdTrustedState.drivers || [];
        var candidates = fdTrustedState.candidates || [];
        var query = String(fdTrustedSearch || '').trim().toLowerCase();

        var filtered = !query ? candidates : candidates.filter(function (c) {
            return String(c.name || '').toLowerCase().indexOf(query) !== -1
                || String(c.email || '').toLowerCase().indexOf(query) !== -1
                || String(c.phone || '').toLowerCase().indexOf(query) !== -1
                || String(c.id) === query
                || String(c.id).indexOf(query) === 0;
        });

        var resultsCount = document.getElementById('fd-td-results-count');
        if (resultsCount) {
            resultsCount.textContent = filtered.length + (filtered.length === 1 ? ' disponible' : ' disponibles');
        }

        var html = '';

        filtered.forEach(function (c) {
            var name = c.name || 'Repartidor';
            var meta = [c.vehicle_label || 'Sin vehículo', c.vehicle_plate ? 'Patente ' + String(c.vehicle_plate).toUpperCase() : '', 'ID ' + c.id].filter(Boolean).join(' · ');

            html += '<div class="fd-td-result">'
                + '<div class="fd-td-avatar">' + escapeHtml(fdInitials(name)) + '</div>'
                + '<div class="fd-td-result-main">'
                + '<div class="fd-td-name">' + escapeHtml(name) + '</div>'
                + '<div class="fd-td-meta">' + escapeHtml(meta) + '</div>'
                + '</div>'
                + '<button type="button" class="fd-td-btn fd-td-btn-primary" onclick="fdTrustedAdd(' + Number(c.id) + ', this)">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>'
                + 'Agregar</button>'
                + '</div>';
        });

        // Búsqueda exacta por ID de un usuario que no está en la lista de
        // candidatos (por ejemplo, porque el listado aún no lo incluye).
        var numeric = /^\d+$/.test(query);
        if (numeric && filtered.length === 0) {
            var inList = drivers.some(function (d) { return String(d.user_id) === query; });

            if (inList) {
                html += '<div class="fd-td-empty">El usuario #' + escapeHtml(query) + ' ya está en tu lista de confianza.</div>';
            } else {
                html += '<div class="fd-td-result">'
                    + '<div class="fd-td-avatar" style="background:#fff7ed;color:#b45309;">#</div>'
                    + '<div class="fd-td-result-main">'
                    + '<div class="fd-td-name">Agregar por ID</div>'
                    + '<div class="fd-td-meta">Usuario #' + escapeHtml(query) + ' · se valida que tenga rol Repartidor</div>'
                    + '</div>'
                    + '<button type="button" class="fd-td-btn fd-td-btn-primary" onclick="fdTrustedAdd(' + Number(query) + ', this)">Agregar</button>'
                    + '</div>';
            }
        } else if (!filtered.length && !numeric) {
            html += '<div class="fd-td-empty">' + (query
                ? 'No encontramos repartidores que coincidan con “' + escapeHtml(fdTrustedSearch) + '”.'
                : 'No hay repartidores disponibles para agregar. Todos los del sistema ya están en tu lista.')
                + '</div>';
        }

        box.innerHTML = html;
    }

    function fdTrustedSearchInput(value) {
        fdTrustedSearch = value || '';
        fdTrustedPaintResults();
    }

    function fdTrustedToggle(input) {
        if (!input) return;

        var enabled = !!input.checked;
        input.disabled = true;

        fdFetchJson(fdTrustedApi('/settings'), {
            method: 'PUT',
            body: JSON.stringify({ enabled: enabled })
        })
            .then(function (res) {
                input.disabled = false;
                if (!res.ok) {
                    input.checked = !enabled;
                    fdTrustedMessage((res.data && res.data.message) || 'No se pudo actualizar la modalidad.', true);
                    return;
                }

                fdTrustedState.enabled = !!(res.data.provider && res.data.provider.trusted_drivers_enabled);
                input.checked = fdTrustedState.enabled;
                fdTrustedPaint();
                fdTrustedMessage(res.data.message || 'Modalidad actualizada.', false);
                loadProviderOrders(true);
            })
            .catch(function () {
                input.disabled = false;
                input.checked = !enabled;
                fdTrustedMessage('No se pudo conectar con el servidor.', true);
            });
    }

    function fdTrustedAdd(userId, btn) {
        var select = document.getElementById('fd-trusted-candidate');
        var id = (userId !== undefined && userId !== null && userId !== '')
            ? Number(userId)
            : Number(select ? select.value : 0);

        if (!id || isNaN(id)) {
            fdTrustedMessage('Seleccioná un repartidor para agregar.', true);
            return;
        }

        var addBtn = document.getElementById('fd-trusted-add');
        if (addBtn) addBtn.disabled = true;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

        fdFetchJson(fdTrustedApi(), {
            method: 'POST',
            body: JSON.stringify({ user_id: id })
        })
            .then(function (res) {
                if (!res.ok) {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    fdTrustedMessage((res.data && res.data.message) || 'No se pudo agregar el repartidor.', true);
                    return;
                }

                fdTrustedState.drivers = res.data.drivers || [];
                fdTrustedState.candidates = res.data.candidates || [];
                fdTrustedSearch = '';
                var search = document.getElementById('fd-td-search');
                if (search) search.value = '';
                fdTrustedPaint();
                fdTrustedMessage(res.data.message || 'Repartidor agregado.', false);
            })
            .catch(function () {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                fdTrustedMessage('No se pudo conectar con el servidor.', true);
            })
            .then(function () {
                if (addBtn) addBtn.disabled = !(fdTrustedState.candidates || []).length;
            });
    }

    async function fdTrustedRemove(driverId, btn) {
        var drivers = fdTrustedState.drivers || [];
        var driver = drivers.filter(function (d) { return Number(d.id) === Number(driverId); })[0];
        var name = driver && driver.name ? driver.name : 'este repartidor';

        // Si la modalidad esta activa y este es el ultimo integrante activo,
        // el backend desactiva la modalidad junto con la baja para no dejar
        // pedidos cerrados sin nadie que pueda verlos.
        var activeCount = drivers.filter(function (d) { return d.status === 'active'; }).length;
        var isLastActive = !!driver && driver.status === 'active' && activeCount <= 1;
        var mustDisable = !!fdTrustedState.enabled && isLastActive;

        var question = '¿Quitar a ' + name + ' de tus repartidores de confianza?';

        if (mustDisable) {
            question += '\n\nEs el último repartidor y la modalidad está activa. '
                + 'Al quitarlo también se desactivará la modalidad y los pedidos volverán al pool general.';
        }

        if (!window.confirm(question)) return;

        var restoreButton = function () {
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '';
            }
        };

        var showError = function (message) {
            restoreButton();
            fdTrustedMessage(message, true);
            fdToast(message, true);
            loadTrustedDrivers();
        };

        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.55';
        }

        fdTrustedMessage('', false);

        try {
            var res = await fdFetchJson(
                fdTrustedApi('/' + Number(driverId)) + (mustDisable ? '?disable_mode=1' : ''),
                { method: 'DELETE' }
            );

            // Estado local desactualizado: el servidor confirma que es el
            // ultimo integrante activo. Se ofrece desactivar y reintentar.
            if (!res.ok && res.data && res.data.code === 'last_active_driver') {
                var retry = window.confirm(
                    (res.data.message || 'No se puede quitar al último repartidor con la modalidad activa.')
                    + '\n\n¿Desactivar la modalidad y quitar a ' + name + '? Los pedidos volverán al pool general.'
                );

                if (!retry) {
                    showError(res.data.message || 'No se pudo quitar el repartidor.');
                    return;
                }

                res = await fdFetchJson(
                    fdTrustedApi('/' + Number(driverId)) + '?disable_mode=1',
                    { method: 'DELETE' }
                );
            }

            if (!res.ok) {
                showError((res.data && res.data.message) || 'No se pudo quitar el repartidor.');
                return;
            }

            var data = res.data || {};
            fdTrustedState.drivers = data.drivers || [];
            fdTrustedState.candidates = data.candidates || [];

            if (data.provider && typeof data.provider.trusted_drivers_enabled !== 'undefined') {
                fdTrustedState.enabled = !!data.provider.trusted_drivers_enabled;
            } else if (mustDisable) {
                fdTrustedState.enabled = false;
            }

            fdTrustedPaint();
            fdTrustedMessage(data.message || 'Repartidor quitado.', false);
            loadProviderOrders(true);
        } catch (e) {
            console.error('Error al quitar repartidor de confianza:', e);
            showError('No se pudo quitar el repartidor. Revisá tu conexión e intentá nuevamente.');
        }
    }

    // Accion manual del comercio: la unica forma de liberar un pedido cerrado.
    // No existe ningun timer ni proceso en segundo plano que lo haga solo.
    function fdTrustedSendToPool(orderId, btn) {
        if (!window.confirm('¿Enviar este pedido al pool general? Cualquier repartidor podrá verlo y tomarlo.')) return;

        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

        fdFetchJson(window.APP_BASE+"/api/v1/provider/orders"+"/" + Number(orderId) + '/send-to-pool', { method: 'POST' })
            .then(function (res) {
                if (!res.ok) {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    fdToast((res.data && res.data.message) || 'No se pudo enviar el pedido al pool general.', true);
                    return;
                }

                fdToast(res.data.message || 'Pedido enviado al pool general.', false);
                // El pedido fresco no trae relaciones: se recarga la lista
                // para pintar la tarjeta completa con el estado de liberacion.
                loadProviderOrders(true);
            })
            .catch(function () {
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                fdToast('No se pudo conectar con el servidor.', true);
            });
    }

    // KPI (Hoy / Historial / Pendientes) y chips de carga del centro de despacho.
    function fdOrdersPaintIndicators() {
        var counts = fdOrdersCounts || {};
        var kpis = { today: 'fd-orders-kpi-today', history: 'fd-orders-kpi-history', pending: 'fd-orders-kpi-pending' };
        Object.keys(kpis).forEach(function (key) {
            var el = document.getElementById(kpis[key]);
            if (el) el.textContent = Number(counts[key] || 0);
        });

        var chipPending = document.getElementById('fd-orders-chip-pending');
        if (chipPending) chipPending.textContent = Number(counts.pending || 0) + ' pendientes';
        var chipKitchen = document.getElementById('fd-orders-chip-kitchen');
        if (chipKitchen) chipKitchen.textContent = Number(counts.in_preparation || 0) + ' en cocina';
    }

    // El punto del selector de estados toma el color del estado elegido.
    function fdOrdersPaintStatusDot() {
        var dot = document.getElementById('fd-orders-status-dot');
        var sel = document.getElementById('fd-orders-filter');
        if (!dot || !sel) return;
        var s = FD_ORDER_STATUS[sel.value];
        dot.style.background = s ? s[1] : '#94a3b8';
    }

    function fdOrdersPaintTabs() {
        ['today', 'history'].forEach(function (key) {
            var count = Number((fdOrdersCounts || {})[key] || 0);

            var badge = document.getElementById('fd-orders-count-' + key);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count ? 'inline-flex' : 'none';
            }

            var btn = document.getElementById('fd-orders-tab-' + key);
            if (btn) btn.classList.toggle('active', fdOrdersActive === key);
        });

        fdOrdersPaintIndicators();
        fdOrdersPaintStatusDot();
    }

    // El rango de fechas vive solo en el historial: "del día" siempre es hoy.
    function fdOrdersPaintRange() {
        var box = document.getElementById('fd-orders-range');
        if (box) box.style.display = fdOrdersActive === 'history' ? 'flex' : 'none';
    }

    // La pestaña se manda al servidor: hoy e historial se filtran en la query.
    function fdOrdersTab(tab) {
        fdOrdersActive = tab === 'history' ? 'history' : 'today';
        fdOrdersPaintRange();
        loadProviderOrders();
    }

    function fdOrdersGoPage(step) {
        var pag = fdOrdersPagination;
        if (!pag) return;

        var next = Math.min(Math.max(1, fdOrdersPage + step), pag.last_page);
        if (next === fdOrdersPage) return;

        fdOrdersPage = next;
        fdOrdersLastHtml = '';
        loadProviderOrders(true);
    }

    function fdOrdersPaintPagination() {
        var box = document.getElementById('fd-orders-pagination');
        if (!box) return;

        var pag = fdOrdersPagination;
        if (!pag || pag.last_page <= 1 || !fdOrdersAll.length) {
            box.style.display = 'none';
            box.innerHTML = '';
            return;
        }

        var base = 'background:#fff;color:#374151;border:1px solid #d1d5db;padding:6px 10px;border-radius:4px;'
            + 'display:inline-flex;align-items:center;justify-content:center;line-height:0;';
        var prev = pag.current_page > 1;
        var next = pag.current_page < pag.last_page;

        box.innerHTML = '<button type="button" onclick="fdOrdersGoPage(-1)" title="Página anterior" aria-label="Página anterior" '
            + (prev ? '' : 'disabled ') + 'style="' + base + (prev ? 'cursor:pointer;' : 'opacity:.5;') + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>'
            + '<span style="font-size:12px;color:#6b7280;">Página ' + pag.current_page + ' de ' + pag.last_page + '</span>'
            + '<button type="button" onclick="fdOrdersGoPage(1)" title="Página siguiente" aria-label="Página siguiente" '
            + (next ? '' : 'disabled ') + 'style="' + base + (next ? 'cursor:pointer;' : 'opacity:.5;') + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>';
        box.style.display = 'flex';
    }

    function fdOrdersRender(silent) {
        fdOrdersPaintTabs();

        var list = fdOrdersAll;

        if (!list.length) {
            fdOrdersLastHtml = '';
            fdOrdersPaintPagination();
            fdSetOrdersState('message', fdOrdersEmptyText(), fdOrdersEmptyTitle(), fdOrdersResetAction());
            return;
        }

        var html = list.map(function (o, i) { return fdRenderOrder(o, i + 1); }).join('');
        var listEl = document.getElementById('fd-orders-list');

        if (silent && html === fdOrdersLastHtml && listEl.style.display === 'block') return;

        fdOrdersLastHtml = html;
        listEl.innerHTML = html;
        fdSetOrdersState('content');
        fdOrdersPaintPagination();
    }

    // ============ RENDER DE CADA PEDIDO (TARJETA) ============

    var FD_MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    function fdParseDate(value) {
        if (!value) return null;
        var d = new Date(String(value).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    // "27 Oct 2026, 21:40 hs"
    function fdDateTime(value) {
        var d = fdParseDate(value);
        if (!d) return '';
        var hh = ('0' + d.getHours()).slice(-2);
        var mm = ('0' + d.getMinutes()).slice(-2);
        return d.getDate() + ' ' + FD_MONTHS[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hh + ':' + mm + ' hs';
    }

    // "hace 18 min" / "hace 3 h" / "hace 2 días": se recalcula en cada repintado.
    function fdTimeAgo(value) {
        var d = fdParseDate(value);
        if (!d) return '';
        var diff = Date.now() - d.getTime();
        if (diff < 0) return 'recién';
        var mins = Math.floor(diff / 60000);
        if (mins < 1) return 'recién';
        if (mins < 60) return 'hace ' + mins + ' min';
        var hours = Math.floor(mins / 60);
        if (hours < 24) return 'hace ' + hours + ' h';
        var days = Math.floor(hours / 24);
        return 'hace ' + days + (days === 1 ? ' día' : ' días');
    }

    function fdInitials(name) {
        var parts = String(name || '').trim().split(/\s+/);
        var ini = (parts[0] || '').charAt(0) + (parts[1] || '').charAt(0);
        return (ini || '?').toUpperCase();
    }

    // Posición del pedido dentro de la página actual: "#01", "#02"...
    function fdNumberBadge(index) {
        var n = Number(index) || 0;
        return n > 0 ? (n < 10 ? '0' + n : String(n)) : '--';
    }

    function fdOrderByIndex(orderId) {
        for (var i = 0; i < fdOrdersAll.length; i++) {
            if (Number(fdOrdersAll[i].id) === Number(orderId)) return fdOrdersAll[i];
        }
        return null;
    }

    function fdOrderPosition(orderId) {
        for (var i = 0; i < fdOrdersAll.length; i++) {
            if (Number(fdOrdersAll[i].id) === Number(orderId)) return i + 1;
        }
        return 0;
    }

    // Link de WhatsApp normalizado (54 + dígitos, sin espacios ni símbolos).
    function fdWaLink(phone, text) {
        var digits = String(phone || '').replace(/\D/g, '');
        if (!digits) return '';
        if (digits.length <= 10) digits = '54' + digits.replace(/^0+/, '');
        return 'https://wa.me/' + digits + '?text=' + encodeURIComponent(text || '');
    }

    var FD_DELIVERY_STATUS = {
        'pending': ['Esperando reparto', '#b45309', '#fef3c7'],
        'assigned': ['Repartidor asignado', '#1d4ed8', '#dbeafe'],
        'picked_up': ['Retiró el pedido', '#0369a1', '#e0f2fe'],
        'delivered': ['Entregado', '#047857', '#d1fae5']
    };

    // Avance del pedido para la barra de tres pasos de la tarjeta.
    function fdOrderStep(status) {
        if (status === 'delivered') return 3;
        if (status === 'on_the_way') return 2;
        if (status === 'confirmed' || status === 'in_preparation') return 1;
        return 0;
    }

    function fdOrderStepperHtml(o) {
        if (o.status === 'cancelled') {
            return '<div style="margin-top:12px;font-size:12px;font-weight:600;color:#b91c1c;">Pedido cancelado: el reparto no continúa.</div>';
        }

        var labels = o.delivery_type === 'takeaway'
            ? ['Confirmado', 'En preparación', 'Listo para retirar']
            : ['Confirmado', 'En camino a local', 'A entrega'];
        var step = fdOrderStep(o.status);
        var html = '<div class="fd-stepbar">';

        labels.forEach(function (label, i) {
            var cls = i < step ? 'done' : (i === step ? 'now' : '');
            var fill = i < step ? '100%' : '0%';
            html += '<div>'
                + '<div class="fd-step-label ' + cls + '">' + (i + 1) + '. ' + escapeHtml(label) + '</div>'
                + '<div class="fd-step-track"><div class="fd-step-fill" style="width:' + fill + '"></div></div>'
                + '</div>';
        });

        return html + '</div>';
    }

    function fdPaymentMethodLabel(method) {
        if (!method) return '';
        if (FD_PAYMENT_METHOD_LABELS[method]) return FD_PAYMENT_METHOD_LABELS[method];
        return String(method).replace(/[_-]+/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    // Texto del medio de pago de la tarjeta. El estado (pagado / pendiente)
    // vive en el badge al lado, así que acá solo va la forma de pago.
    function fdPaymentLine(o) {
        var p = (o.payments && o.payments[0]) || null;
        var method = (p && p.provider) || o.payment_method || '';
        var label = fdPaymentMethodLabel(method);

        if (p && p.status === 'paid') return label ? 'Pagado online mediante ' + label : 'Pago confirmado';
        if (label) return label;
        return 'Sin datos de pago';
    }

    function fdRenderOrder(o, index) {
        var client = o.user || {};
        var address = o.address;
        var driver = o.driver || null;
        var isTakeaway = o.delivery_type === 'takeaway';
        var collapsed = !!fdOrdersCollapsed[o.id];
        var idx = Number(index) || fdOrderPosition(o.id);

        var html = '<div class="fd-orders-card fd-order" data-order-id="' + Number(o.id) + '">';

        // Cabecera: índice, número copiable, estado, acciones y fecha.
        html += '<div class="fd-order-head">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0;">'
            + '<span class="fd-idx">#' + fdNumberBadge(idx) + '</span>'
            + '<button type="button" class="fd-icon-btn" onclick="fdCopyOrderNumber(' + Number(o.id) + ')" title="Copiar número de pedido" aria-label="Copiar número de pedido" style="width:26px;height:26px;border:none;background:transparent;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>'
            + '</button>'
            + '<span class="fd-onum">' + escapeHtml(o.order_number) + '</span>'
            + fdStatusBadge(o.status)
            + '</div>'
            + '<div style="flex:1;"></div>'
            + fdOrderActions(o)
            + '<div style="flex-basis:100%;display:flex;align-items:center;gap:6px;font-size:11.5px;color:#94a3b8;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
            + '<span>' + fdDateTime(o.created_at) + '</span>'
            + '<span style="color:#cbd5e1;">&middot;</span>'
            + '<span>' + fdTimeAgo(o.created_at) + '</span>'
            + '</div>'
            + '</div>';

        html += '<div class="fd-body" data-order-body' + (collapsed ? ' style="display:none;"' : '') + '>';

        // ---------- Cliente ----------
        html += '<div class="fd-block">'
            + '<div class="fd-block-title"><span>Datos del cliente</span></div>'
            + '<div style="display:flex;gap:12px;align-items:flex-start;">'
            + '<div class="fd-avatar">' + escapeHtml(fdInitials(client.name)) + '</div>'
            + '<div style="min-width:0;">'
            + '<div class="fd-info-name">' + escapeHtml(client.name || 'Cliente') + '</div>'
            + (client.email ? '<div class="fd-info-meta">' + escapeHtml(client.email) + '</div>' : '')
            + (client.phone
                ? '<div style="margin-top:5px;"><a class="fd-info-link" href="tel:' + escapeHtml(String(client.phone).replace(/[^\d+]/g, '')) + '">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                    + ' ' + escapeHtml(client.phone) + '</a></div>'
                : '')
            + '</div>'
            + '</div>';

        // Dirección de entrega + referencia del timbre. En retiro se indica
        // que el cliente pasa por el local y no hay dirección que enviar.
        if (isTakeaway) {
            html += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;gap:9px;align-items:flex-start;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px;"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v8h14v-8"/><path d="M9 21v-5h6v5"/></svg>'
                + '<div style="min-width:0;">'
                + '<div style="font-size:13px;font-weight:700;color:#334155;">Retiro en el local</div>'
                + '<div style="font-size:12px;color:#64748b;margin-top:2px;">El cliente retira el pedido en el comercio. No requiere dirección de entrega.</div>'
                + '</div></div>';
        } else {
            html += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;gap:9px;align-items:flex-start;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px;"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'
                + '<div style="min-width:0;">';

            if (address) {
                html += '<div style="font-size:13px;color:#334155;">'
                    + '<b>' + escapeHtml(address.street) + ' ' + escapeHtml(address.number || '') + '</b>'
                    + (address.floor_apartment ? ' &middot; ' + escapeHtml(address.floor_apartment) : '')
                    + (address.postal_code ? ' &middot; CP ' + escapeHtml(address.postal_code) : '')
                    + '</div>';
                if (address.notes) {
                    html += '<div style="margin-top:7px;"><span class="fd-note-chip">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>'
                        + escapeHtml(address.notes) + '</span></div>';
                }
            } else {
                html += '<div style="font-size:12.5px;color:#94a3b8;">Sin dirección registrada</div>';
            }

            html += '</div></div>';
        }

        if (o.notes) {
            html += '<div style="margin-top:7px;font-size:12px;color:#9a3412;"><b>Nota del pedido:</b> ' + escapeHtml(o.notes) + '</div>';
        }

        html += '</div>';

        // ---------- Repartidor ----------
        if (isTakeaway) {
            html += '<div class="fd-block">'
                + '<div class="fd-block-title"><span>Entrega</span>'
                + fdBadge('Retiro en local', '#9a3412', '#ffedd5')
                + '</div>'
                + '<div style="display:flex;gap:10px;align-items:flex-start;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:2px;"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v8h14v-8"/><path d="M9 21v-5h6v5"/></svg>'
                + '<div><div style="font-size:13px;font-weight:600;color:#334155;">Pedido para preparar en el comercio</div>'
                + '<div style="font-size:12px;color:#94a3b8;margin-top:2px;">No se envía a la cola de repartidores. El cliente lo retira cuando esté listo.</div>'
                + '</div>'
                + '</div>'
                + fdOrderStepperHtml(o) + '</div>';
        } else {
            var dStatus = FD_DELIVERY_STATUS[o.delivery_status] || FD_DELIVERY_STATUS['pending'];
            html += '<div class="fd-block">'
                + '<div class="fd-block-title"><span>Repartidor asignado</span>'
                + (driver ? fdBadge(dStatus[0], dStatus[1], dStatus[2]) : fdBadge('Sin asignar', '#64748b', '#f1f5f9'))
                + '</div>';

            if (driver) {
                html += '<div style="display:flex;gap:12px;align-items:flex-start;">'
                    + '<div class="fd-avatar fd-avatar-blue">' + escapeHtml(fdInitials(driver.name)) + '</div>'
                    + '<div style="min-width:0;">'
                    + '<div class="fd-info-name">' + escapeHtml(driver.name || 'Repartidor') + '</div>'
                    + (driver.email ? '<div class="fd-info-meta">' + escapeHtml(driver.email) + '</div>' : '')
                    + (driver.phone ? '<div class="fd-info-meta">' + escapeHtml(driver.phone) + '</div>' : '')
                    + '</div>'
                    + '</div>';
            } else {
                var noDriverHelp = 'Los repartidores toman el pedido desde su panel cuando está listo para retirar.';
                if (o.released_to_pool_at) {
                    noDriverHelp = 'Enviado al pool general el ' + fdDateTime(o.released_to_pool_at) + '. Cualquier repartidor puede tomarlo.';
                } else if (fdTrustedState.enabled) {
                    noDriverHelp = 'Solo visible para tus repartidores de confianza. No se libera automáticamente: usá "Enviar al pool" para abrirlo a todos.';
                }

                html += '<div style="display:flex;gap:10px;align-items:flex-start;">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:2px;"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>'
                    + '<div><div style="font-size:13px;font-weight:600;color:#334155;">Todavía no hay repartidor asignado</div>'
                    + '<div style="font-size:12px;color:#94a3b8;margin-top:2px;">' + escapeHtml(noDriverHelp) + '</div>'
                    + '</div>'
                    + '</div>';
            }

            html += fdOrderStepperHtml(o) + '</div>';
        }

        // ---------- Productos y totales ----------
        html += '<div class="fd-block">'
            + '<div class="fd-items-head"><span>Detalle de productos</span><span>Subtotal</span></div>'
            + (o.items || []).map(fdRenderOrderItem).join('')
            + '<div style="padding-top:12px;">'
            + fdTotalRow('Subtotal', fdMoney(o.subtotal), false)
            + (isTakeaway
                ? fdTotalRow('Retiro en el local', 'Sin costo', false)
                : fdTotalRow('Costo de envío', fdMoney(o.delivery_fee), false))
            + fdTotalRow('Descuento cupón', '-' + fdMoney(o.discount), false)
            + '<div class="fd-total-final"><span>Total a cobrar</span><span>' + fdMoney(o.total) + '</span></div>'
            + '</div>'
            + '</div>';

        // ---------- Calificación (solo pedidos entregados) ----------
        if (o.status === 'delivered') {
            if (o.provider_rated && o.provider_rating) {
                var r = o.provider_rating;
                var stars = '';
                for (var i = 1; i <= 5; i++) {
                    stars += '<span style="color:' + (i <= r.stars ? '#f59e0b' : '#d1d5db') + ';">&#9733;</span>';
                }
                html += '<div class="fd-block" style="background:#f8fafc;">'
                    + '<div class="fd-block-title" style="margin-bottom:8px;"><span>Tu calificación al cliente</span></div>'
                    + '<div style="font-size:18px;line-height:1;margin-bottom:5px;">' + stars + '</div>'
                    + (r.comment ? '<div style="font-size:12px;color:#475569;font-style:italic;">"' + escapeHtml(r.comment) + '"</div>' : '')
                    + '</div>';
            } else {
                html += '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:2px;">'
                    + '<button type="button" class="fd-btn fd-btn-primary" onclick="fdOrderOpenRate(' + Number(o.id) + ')">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                    + 'Calificar cliente</button>'
                    + '</div>'
                    + fdOrderRatePanel(o);
            }
        }

        // ---------- Pago y contacto rápido ----------
        var waText = 'Hola ' + (client.name || '') + ', te contactamos por tu pedido ' + o.order_number + '.';
        var waLink = client.phone ? fdWaLink(client.phone, waText) : '';
        var driverTel = (driver && driver.phone) ? 'tel:' + String(driver.phone).replace(/[^\d+]/g, '') : '';

        html += '<div class="fd-card-foot">'
            + '<span class="fd-pay">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>'
            + '<span>' + escapeHtml(fdPaymentLine(o)) + '</span>'
            + fdPaymentBadge(o.payments)
            + '</span>'
            + '<div style="display:flex;gap:8px;flex-wrap:wrap;">'
            + (waLink
                ? '<a class="fd-btn fd-btn-whatsapp" href="' + waLink + '" target="_blank" rel="noopener noreferrer">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>'
                    + 'WhatsApp Cliente</a>'
                : '')
            + (driverTel
                ? '<a class="fd-btn" href="' + driverTel + '">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                    + 'Contactar repartidor</a>'
                : '')
            + '</div>'
            + '</div>';

        return html + '</div></div>';
    }

    function fdRenderOrderItem(it) {
        var options = it.options || [];
        var optionsHtml = '';

        if (options.length) {
            optionsHtml = '<div style="font-size:11px;color:#94a3b8;margin-top:3px;">'
                + options.map(function (op) {
                    return escapeHtml(op.option_name) +
                        (Number(op.extra_price) > 0 ? ' +' + fdMoney(op.extra_price) : '');
                }).join(' &middot; ')
                + '</div>';
        }

        return '<div class="fd-item">'
            + '<span class="fd-qty">' + Number(it.quantity) + '&times;</span>'
            + '<div style="min-width:0;">'
            + '<div style="font-size:13.5px;font-weight:600;color:#0f172a;">' + escapeHtml(it.product_name)
            + (it.variant_name ? ' <span style="font-weight:500;color:#94a3b8;">(' + escapeHtml(it.variant_name) + ')</span>' : '')
            + '</div>'
            + '<div style="font-size:11.5px;color:#94a3b8;margin-top:2px;">' + fdMoney(it.unit_price) + ' c/u</div>'
            + optionsHtml
            + '</div>'
            + '<span class="fd-item-total">' + fdMoney(it.total_price) + '</span>'
            + '</div>';
    }

    function fdTotalRow(label, value, strong) {
        return '<div style="display:flex;justify-content:space-between;color:#64748b;' +
            (strong ? 'font-weight:700;color:#0c2a4d;' : '') + '">'
            + '<span>' + escapeHtml(label) + '</span><span>' + value + '</span></div>';
    }

    // ============ FAST DELIVERY: COMERCIOS Y MIS PEDIDOS DEL CLIENTE ============

    var FD_SHOP = { providerId: null, providerName: '', catalog: null, cart: [], addresses: null, settings: {}, busy: false, categoryFilter: '', deliveryType: 'delivery', addressId: '', pickupAddress: '' };
    var FD_PAYMENT_METHOD_LABELS = { 'efectivo': 'Efectivo', 'transfer': 'Transferencia', 'transferencia': 'Transferencia', 'transferencia_bancaria': 'Transferencia bancaria', 'mercado_pago': 'Mercado Pago', 'mercadopago': 'Mercado Pago' };
    var fdShopsTimer = null;
    var fdShopsTabActive = 'favorites';
    var fdShopsCounts = { favorites: 0, all: 0 };
    var fdShopsInitialized = false;

    function fdSetShopsState(state, text, actionHtml) {
        var map = { loading: 'fd-shops-loading', message: 'fd-shops-message', content: 'fd-shops-grid' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? (k === 'content' ? 'grid' : 'block') : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-shops-message-text');
            if (msg) msg.textContent = text;
        }
        var action = document.getElementById('fd-shops-message-action');
        if (action) action.innerHTML = actionHtml || '';
    }

    function fdPaintShopsTabs() {
        var favBtn = document.getElementById('fd-shops-tab-favorites');
        var allBtn = document.getElementById('fd-shops-tab-all');
        var isFav = fdShopsTabActive === 'favorites';

        var tabBase = 'padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;';
        if (favBtn) favBtn.style.cssText = isFav
            ? 'background:#fff1eb;color:#D24C19;border:1px solid #D24C19;' + tabBase
            : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' + tabBase;
        if (allBtn) allBtn.style.cssText = !isFav
            ? 'background:#fff1eb;color:#D24C19;border:1px solid #D24C19;' + tabBase
            : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' + tabBase;

        [['favorites', 'fd-shops-count-favorites'], ['all', 'fd-shops-count-all']].forEach(function (pair) {
            var el = document.getElementById(pair[1]);
            if (!el) return;
            var count = fdShopsCounts[pair[0]];
            el.textContent = count;
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });
    }

    function fdShopsTab(tab) {
        if (tab !== 'favorites' && tab !== 'all') return;
        if (fdShopsTabActive === tab) return;
        fdShopsTabActive = tab;
        fdPaintShopsTabs();
        loadComercios();
    }

    // Estado vacio de Favoritos: solo el boton con icono, sin descripcion.
    function fdShopsFavoritesEmptyHtml() {
        return '<button type="button" onclick="fdShopsTab(\'all\')" title="Ver todos los comercios" aria-label="Ver todos los comercios" '
            + 'style="background:#D24C19;color:#fff;border:none;padding:8px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>'
            + '</button>';
    }

    function fdSetMyOrdersState(state, text, actionHtml) {
        var map = { loading: 'fd-myorders-loading', message: 'fd-myorders-message', content: 'fd-myorders-list' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-myorders-message-text');
            if (msg) msg.textContent = text;
        }
        var action = document.getElementById('fd-myorders-message-action');
        if (action) action.innerHTML = actionHtml || '';
    }

    function fdShopsSearchDebounce() {
        if (fdShopsTimer) clearTimeout(fdShopsTimer);
        fdShopsTimer = setTimeout(loadComercios, 300);
    }

    // ---- Direccion principal del perfil: fallback del filtro de distancia ----
    var fdProfileAddressState = { done: false, promise: null, address: null };

    function fdProfileAddressLoad() {
        if (fdProfileAddressState.done) return Promise.resolve(fdProfileAddressState.address);
        if (!fdProfileAddressState.promise) {
            fdProfileAddressState.promise = fetch(window.APP_BASE+"/profile/address", { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    fdProfileAddressState.address = (data && data.address) || null;
                    fdProfileAddressState.done = true;
                    window.fdProfileAddress = fdProfileAddressState.address;
                    return fdProfileAddressState.address;
                })
                .catch(function () {
                    fdProfileAddressState.address = null;
                    fdProfileAddressState.done = true;
                    window.fdProfileAddress = null;
                    return null;
                });
        }
        return fdProfileAddressState.promise;
    }

    function fdProfileAddressText(address) {
        if (!address) return '';
        if (address.formatted) return String(address.formatted).trim();
        var parts = [
            ((address.street || '') + ' ' + (address.number || '')).trim(),
            address.floor_apartment || '',
            [address.postal_code, (address.province && address.province.name) || ''].filter(Boolean).join(' '),
            (address.region && address.region.name) || '',
            (address.country && address.country.name) || ''
        ].filter(Boolean);
        return parts.join(', ');
    }

    function fdProfileAddressCoords(address) {
        if (!address) return null;
        var lat = parseFloat(address.latitude);
        var lng = parseFloat(address.longitude);
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat: lat, lng: lng };
    }

    // Origen del filtro: lo que escribio el usuario o, si esta vacio, la
    // direccion principal del perfil (texto + coordenadas almacenadas).
    function fdFilterResolveOrigin(typed, callback) {
        var text = String(typed || '').trim();
        if (text) {
            callback({ location: text, coords: null });
            return;
        }
        fdProfileAddressLoad().then(function (address) {
            callback({
                location: fdProfileAddressText(address),
                coords: fdProfileAddressCoords(address)
            });
        });
    }

    function fdShopsHaversine(lat1, lng1, lat2, lng2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function fdFilterTokens(text) {
        var stop = { av:1, avenida:1, calle:1, depto:1, piso:1, torre:1, argentina:1, buenos:1, aires:1, ciudad:1, autonoma:1, caba:1, zona:1, norte:1, sur:1, este:1, oeste:1, y:1, de:1, del:1, la:1, el:1, los:1, las:1, para:1, por:1, con:1 };
        var out = [];
        String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').split(/[^a-z0-9]+/).forEach(function (token) {
            if (token.length >= 4 && !stop[token] && out.indexOf(token) === -1) out.push(token);
        });
        return out;
    }

    function fdFilterZoneMatch(tokens, zoneText) {
        var zone = String(zoneText || '').toLowerCase();
        if (zone.normalize) zone = zone.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        for (var i = 0; i < tokens.length; i++) {
            if (zone.indexOf(tokens[i]) !== -1) return true;
        }
        return false;
    }

    function fdShopsOriginPoint(filter) {
        var coords = filter.coords;
        if (coords && typeof coords.lat === 'number' && typeof coords.lng === 'number') {
            return { lat: Number(coords.lat), lng: Number(coords.lng) };
        }
        var text = String(filter.location || '').trim();
        var seed = 0;
        var value = text.toLowerCase();
        for (var i = 0; i < value.length; i++) seed = (seed * 31 + value.charCodeAt(i)) % 100000;
        return {
            lat: -34.6037 + ((seed % 200) - 100) / 1000,
            lng: -58.3816 + ((Math.floor(seed / 200) % 200) - 100) / 1000
        };
    }

    function fdProviderRealPoint(p) {
        var lat = parseFloat(p && (p.lat !== undefined && p.lat !== null ? p.lat : p.latitude));
        var lng = parseFloat(p && (p.lng !== undefined && p.lng !== null ? p.lng : p.longitude));
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat: lat, lng: lng };
    }

    function fdProviderMockKm(p) {
        var id = Number(p && p.id ? p.id : 0);
        return ((id * 13) % 50) + 1;
    }

    function aplicarFiltroComercios(providers) {
        var list = providers || [];
        var filter = (typeof fdShopsFilter !== 'undefined' && fdShopsFilter) ? fdShopsFilter : { mode: 'all' };
        var radius = Number(filter.distanceKm || 0);

        if (filter.mode !== 'distance' || !(radius > 0)) return list;

        var origin = fdShopsOriginPoint(filter);
        var tokens = fdFilterTokens(filter.location);

        var filtered = list.filter(function (p) {
            var zoneText = String((p && p.zone) || '') + ' ' + String((p && p.rubro) || '') + ' ' + String((p && p.business_name) || '');
            if (tokens.length && fdFilterZoneMatch(tokens, zoneText)) return true;

            var point = fdProviderRealPoint(p);
            if (point) return fdShopsHaversine(point.lat, point.lng, origin.lat, origin.lng) <= radius;

            return fdProviderMockKm(p) <= radius;
        });

        if (typeof fdShopsCounts !== 'undefined' && fdShopsCounts) {
            if (typeof fdShopsTabActive !== 'undefined' && fdShopsTabActive === 'favorites') {
                fdShopsCounts.favorites = filtered.length;
            } else {
                fdShopsCounts.all = filtered.length;
            }
        }

        return filtered;
    }

    // ---- Filtro de comercios por ubicación / distancia ----
    // Por defecto se busca en todas partes (sin límite de distancia).
    var fdShopsFilter = { mode: 'all', distanceKm: 10, location: '', coords: null };

    function fdShopsFilterOpen() {
        var modal = document.getElementById('fd-shops-filter-modal');
        if (!modal) return;

        var byDistance = document.getElementById('fd-shops-filter-mode-distance');
        var all = document.getElementById('fd-shops-filter-mode-all');
        var range = document.getElementById('fd-shops-filter-range');
        var location = document.getElementById('fd-shops-filter-location');

        if (byDistance) byDistance.checked = fdShopsFilter.mode !== 'all';
        if (all) all.checked = fdShopsFilter.mode === 'all';
        if (range) range.value = fdShopsFilter.distanceKm;
        if (location) location.value = fdShopsFilter.location;

        fdShopsFilterDistanceLabel();
        fdShopsFilterMode();
        fdProfileAddressLoad().then(function (address) {
            var text = fdProfileAddressText(address);
            var loc = document.getElementById('fd-shops-filter-location');
            if (loc) loc.placeholder = text ? 'Por defecto: ' + text : 'Ej. Av. Siempre Viva 123';
        });
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function fdShopsFilterClose() {
        var modal = document.getElementById('fd-shops-filter-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function fdShopsFilterMode() {
        var all = document.getElementById('fd-shops-filter-mode-all');
        var range = document.getElementById('fd-shops-filter-range');
        var wrap = document.getElementById('fd-shops-filter-slider-wrap');
        var unlimited = all && all.checked;

        if (range) range.disabled = unlimited;
        if (wrap) wrap.style.opacity = unlimited ? '.45' : '1';
    }

    function fdShopsFilterDistanceLabel() {
        var range = document.getElementById('fd-shops-filter-range');
        var label = document.getElementById('fd-shops-filter-distance-label');
        if (!range || !label) return;

        var value = Number(range.value);
        label.textContent = value >= 50 ? '50+ km' : value + ' km';
    }

    function fdShopsFilterLocate() {
        var status = document.getElementById('fd-shops-filter-geo-status');
        var input = document.getElementById('fd-shops-filter-location');

        if (!navigator.geolocation) {
            if (status) status.textContent = 'Tu navegador no soporta geolocalización.';
            return;
        }

        if (status) status.textContent = 'Obteniendo ubicación…';

        navigator.geolocation.getCurrentPosition(function (pos) {
            fdShopsFilter.geoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            fdShopsFilter.coords = fdShopsFilter.geoCoords;
            if (input && !input.value.trim()) {
                input.value = pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
            }
            if (status) status.textContent = 'Ubicación detectada. Se usará como referencia.';
        }, function () {
            if (status) status.textContent = 'No se pudo obtener la ubicación. Escribila manualmente.';
        }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 });
    }

    function fdShopsFilterApply() {
        var all = document.getElementById('fd-shops-filter-mode-all');
        var range = document.getElementById('fd-shops-filter-range');
        var location = document.getElementById('fd-shops-filter-location');

        fdShopsFilter.mode = (all && all.checked) ? 'all' : 'distance';
        fdShopsFilter.distanceKm = range ? Number(range.value) : 10;

        fdFilterResolveOrigin(location ? location.value : '', function (origin) {
            fdShopsFilter.location = origin.location;
            fdShopsFilter.coords = fdShopsFilter.geoCoords || origin.coords;

            // Emite la selección para que la lista de comercios se filtre.
            document.dispatchEvent(new CustomEvent('fd:shops:filter', { detail: Object.assign({}, fdShopsFilter) }));

            var badge = document.getElementById('fd-shops-filter-badge');
            if (badge) {
                var active = fdShopsFilter.mode !== 'all' || fdShopsFilter.location !== '';
                badge.style.display = active ? 'inline-flex' : 'none';
            }

            fdShopsFilterClose();
            loadComercios();
        });
    }

    document.addEventListener('keydown', function (event) {
        var modal = document.getElementById('fd-shops-filter-modal');
        if (event.key === 'Escape' && modal && modal.style.display === 'flex') {
            fdShopsFilterClose();
        }
    });

    document.addEventListener('click', function (event) {
        var modal = document.getElementById('fd-shops-filter-modal');
        if (modal && event.target === modal) {
            fdShopsFilterClose();
        }
    });

    function loadComercios(silent) {
        var input = document.getElementById('fd-shops-search');
        var search = input ? input.value.trim() : '';
        var section = document.getElementById('dash-comercios');
        var moduleId = section ? parseInt(section.getAttribute('data-module-id'), 10) : NaN;
        var params = [];
        var firstLoad = !fdShopsInitialized;
        // En la primera carga pedimos todos (sin filtro) para conocer el
        // conteo de favoritos y decidir el tab por defecto: favoritos si hay,
        // si no, todos.
        var isFavorites = !firstLoad && fdShopsTabActive === 'favorites';

        // Solo comercios de la categoria que corresponde al modulo abierto
        if (moduleId > 0) params.push('module_id=' + moduleId);
        if (search) params.push('search=' + encodeURIComponent(search));
        if (isFavorites) params.push('favorite=1');

        // Filtro de ubicación/distancia: se emite junto con la búsqueda.
        if (fdShopsFilter.mode === 'distance') {
            params.push('max_distance_km=' + encodeURIComponent(fdShopsFilter.distanceKm));

            if (fdShopsFilter.coords) {
                params.push('lat=' + encodeURIComponent(fdShopsFilter.coords.lat));
                params.push('lng=' + encodeURIComponent(fdShopsFilter.coords.lng));
            }
        }

        if (fdShopsFilter.location) {
            params.push('location=' + encodeURIComponent(fdShopsFilter.location));
        }

        if (!silent) fdSetShopsState('loading');

        fdFetchJson(window.APP_BASE+"/api/providers" + (params.length ? '?' + params.join('&') : ''))
            .then(function (res) {
                if (!res.ok) {
                    if (silent) return;
                    fdSetShopsState('message', (res.data && res.data.message) || 'No se pudieron cargar los comercios.');
                    return;
                }

                var providers = res.data.providers || [];
                FD_SHOP.settings = res.data.settings || {};
                fdShopsCounts = {
                    favorites: Number(res.data.favorites_count || 0),
                    all: Number(res.data.total_count || 0),
                };

                providers = aplicarFiltroComercios(providers);

                if (firstLoad) {
                    fdShopsInitialized = true;
                    fdShopsTabActive = fdShopsCounts.favorites > 0 ? 'favorites' : 'all';
                    // Habia favoritos: este request era de "todos", volvemos a
                    // cargar ya con el tab de favoritos activo.
                    if (fdShopsTabActive === 'favorites') {
                        loadComercios(silent);
                        return;
                    }
                }

                fdPaintShopsTabs();

                var summary = document.getElementById('fd-shops-summary');
                if (summary) {
                    if (fdShopsTabActive === 'favorites') {
                        summary.textContent = fdShopsCounts.favorites
                            ? fdShopsCounts.favorites + ' comercios favoritos'
                            : 'Tus comercios concurrentes';
                    } else {
                        summary.textContent = providers.length
                            ? providers.length + ' comercios disponibles'
                            : 'Comercios con envío a tu domicilio';
                    }
                }

                if (!providers.length) {
                    if (fdShopsTabActive === 'favorites') {
                        fdSetShopsState('message', '', fdShopsFavoritesEmptyHtml());
                    } else if (fdShopsFilter.mode === 'distance' && !search) {
                        fdSetShopsState('message', 'No hay comercios dentro del radio seleccionado.');
                    } else {
                        fdSetShopsState('message', search
                            ? 'No encontramos comercios con ese nombre.'
                            : 'Todavía no hay comercios publicados.\nPronto vas a poder pedir desde acá.');
                    }
                    return;
                }

                var html = providers.map(fdRenderProviderCard).join('');
                var grid = document.getElementById('fd-shops-grid');
                if (silent && grid && grid.style.display === 'grid' && grid.innerHTML === html) return;
                if (grid) grid.innerHTML = html;
                fdSetShopsState('content');
            })
            .catch(function () {
                if (silent) return;
                fdSetShopsState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderProviderCard(p) {
        var initial = escapeHtml((p.business_name || '?').replace(/^\s+/, '').charAt(0).toUpperCase());
        var meta = p.rubro || p.zone || 'Comercio';

        var html = '<div data-fd-provider="' + Number(p.id) + '" style="background:#fff;border:1px solid #e5e7eb;padding:16px;display:flex;flex-direction:column;gap:10px;">';

        html += '<div class="fd-shop-head">';
        if (p.publicidad_image) {
            html += '<img class="fd-shop-circle fd-shop-photo" src="' + escapeHtml(p.publicidad_image) + '" alt="Publicidad de ' + escapeHtml(p.business_name || '') + '" />';
        } else {
            html += '<div class="fd-shop-circle fd-shop-initials">' + initial + '</div>';
        }
        html += '</div>';

        html += '<div style="display:flex;gap:12px;align-items:center;">'
            + '<div style="width:44px;height:44px;flex:none;border-radius:50%;background:#fff1eb;color:#D24C19;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:18px;">' + initial + '</div>'
            + '<div style="min-width:0;">'
            + '<div style="font-size:14px;font-weight:700;color:#0c2a4d;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(p.business_name) + '</div>'
            + '<div style="font-size:11px;color:#94a3b8;">' + escapeHtml(meta) + (p.zone && p.rubro ? ' · ' + escapeHtml(p.zone) : '') + '</div>'
            + '</div>'
            + (p.rating ? '<div style="margin-left:auto;font-size:12px;font-weight:700;color:#b45309;white-space:nowrap;">★ ' + escapeHtml(Number(p.rating).toFixed(1)) + '</div>' : '')
            + '</div>';

        if (p.promo) {
            html += '<div style="font-size:11px;color:#047857;background:#ecfdf5;padding:6px 8px;">' + escapeHtml(p.promo) + '</div>';
        }

        html += '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto;flex-wrap:wrap;">'
            + '<span style="font-size:11px;color:#6b7280;">' + Number(p.products_count || 0) + ' productos · ' + Number(p.categories_count || 0) + ' categorías</span>'
            + '<span style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">'
            + '<button type="button" class="btn-icon" onclick="fdToggleFavorite(' + Number(p.id) + ', this)" '
            + 'title="' + (p.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos') + '" '
            + 'aria-label="' + (p.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos') + '" '
            + 'style="background:#fff;color:' + (p.is_favorite ? '#D24C19' : '#94a3b8') + ';border:1px solid ' + (p.is_favorite ? '#D24C19' : '#d1d5db') + ';border-radius:4px;cursor:pointer;">'
            + (p.is_favorite ? FD_ICON_STAR_FILLED : FD_ICON_STAR) + '</button>'
            + (Number(p.active_subscription_plans_count || 0) > 0
                ? '<button type="button" class="btn-icon" onclick="fdSubscribeToProvider(' + Number(p.id) + ',&quot;' + escapeHtml(p.business_name || '').replace(/"/g, '&quot;') + '&quot;)" title="Suscribirse a un plan de este comercio" aria-label="Suscribirse" style="background:#fff;color:#D24C19;border:1px solid #D24C19;border-radius:4px;cursor:pointer;">' + FD_ICON_USER_PLUS + '</button>'
                : '')
            + '<button type="button" class="btn-icon" onclick="openFdShop(' + Number(p.id) + ')" title="Ver catálogo" aria-label="Ver catálogo" style="background:#D24C19;color:#fff;border:none;border-radius:4px;cursor:pointer;">' + FD_ICON_BOOK + '</button>'
            + '</span>'
            + '</div>'
            + '</div>';

        return html;
    }

    function fdSubscribeToProvider(providerId, providerName) {
        var section = document.getElementById('dash-mis-suscripciones');
        if (!section) {
            fdToast('No tenés habilitada la sección Mis Suscripciones.', true);
            return;
        }

        showDashSection('mis-suscripciones');

        if (typeof openSubNewForm === 'function') {
            // Al cerrar o guardar el modal hay que volver a Comercios.
            window.__fdSubReturnTo = 'comercios';
            openSubNewForm(Number(providerId), providerName || '');
        }
    }

    function fdToggleFavorite(providerId, btn) {
        if (btn) btn.disabled = true;

        fdFetchJson(window.APP_BASE+"/api/providers"+"/" + Number(providerId) + '/favorite', { method: 'POST' })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo actualizar el favorito.', true);
                    return;
                }

                var isFav = !!(res.data && res.data.is_favorite);
                fdToast(isFav ? 'Comercio agregado a tus favoritos ★' : 'Comercio quitado de tus favoritos');

                // Feedback inmediato de la estrella.
                if (btn) {
                    var label = isFav ? 'Quitar de favoritos' : 'Agregar a favoritos';
                    btn.title = label;
                    if (btn.setAttribute) btn.setAttribute('aria-label', label);
                    btn.style = btn.style || {};
                    btn.style.color = isFav ? '#D24C19' : '#94a3b8';
                    btn.style.borderColor = isFav ? '#D24C19' : '#d1d5db';
                    btn.innerHTML = isFav ? FD_ICON_STAR_FILLED : FD_ICON_STAR;
                }

                // Refresca contadores (según el módulo), la lista y el estado
                // vacío desde el GET, que sí filtra por la categoría del módulo.
                loadComercios(true);
            })
            .catch(function () {
                fdToast('No se pudo conectar con el servidor.', true);
            })
            .then(function () {
                if (btn) btn.disabled = false;
            });
    }

    function openFdShop(providerId) {
        FD_SHOP.providerId = Number(providerId);
        FD_SHOP.providerName = '';
        FD_SHOP.catalog = null;
        FD_SHOP.cart = [];
        FD_SHOP.categoryFilter = '';
        FD_SHOP.deliveryType = 'delivery';
        FD_SHOP.addressId = '';
        FD_SHOP.pickupAddress = '';
        fdShopFillCategoryFilter([]);

        var overlay = document.getElementById('fd-shop-overlay');
        if (overlay) {
            // La tienda ya no es un modal: reemplaza la seccion actual, como
            // cualquier otra seccion del dashboard.
            document.querySelectorAll('.dash-section').forEach(function (s) { s.style.display = 'none'; });
            overlay.style.display = 'block';
            window.scrollTo(0, 0);

            if (fdSectionHistoryReady) {
                history.pushState({ fdSection: 'tienda' }, '', window.APP_BASE+"");
            }
        }

        document.getElementById('fd-shop-title').textContent = 'Cargando...';
        document.getElementById('fd-shop-subtitle').textContent = '';
        document.getElementById('fd-shop-body').innerHTML =
            '<p style="font-size:13px;color:#6b7280;text-align:center;padding:32px 0;margin:0;">Cargando catálogo...</p>';
        document.getElementById('fd-shop-cart').innerHTML = '';
        fdShopRenderDelivery();
        fdShopPaintBadge();

        fdLoadShopAddresses();

        fdFetchJson(window.APP_BASE+"/api/providers"+"/" + FD_SHOP.providerId + '/catalog?only_available=1')
            .then(function (res) {
                if (!res.ok) {
                    document.getElementById('fd-shop-body').innerHTML =
                        '<p style="font-size:13px;color:#b91c1c;text-align:center;padding:32px 0;margin:0;">'
                        + escapeHtml((res.data && res.data.message) || 'No se pudo cargar el catálogo.') + '</p>';
                    return;
                }

                FD_SHOP.catalog = res.data;
                fdShopFillCategoryFilter(res.data.categories || []);

                var provider = res.data.provider || {};
                FD_SHOP.providerName = provider.business_name || '';
                FD_SHOP.pickupAddress = provider.address || '';
                if (provider.delivery_fee !== undefined && provider.delivery_fee !== null) {
                    FD_SHOP.settings.delivery_fee = provider.delivery_fee;
                }

                document.getElementById('fd-shop-title').textContent = provider.business_name || 'Comercio';

                var hours = provider.hours;
                var hoursText = Array.isArray(hours) ? hours.join(' · ') : (typeof hours === 'string' ? hours : '');
                document.getElementById('fd-shop-subtitle').textContent = [provider.zone, hoursText].filter(Boolean).join(' · ');

                document.getElementById('fd-shop-body').innerHTML =
                    fdRenderShopCatalog(res.data.categories || []);
                fdShopApplyRepeat();
                fdRenderShopCart();
                fdStartShopPolling();
            })
            .catch(function () {
                document.getElementById('fd-shop-body').innerHTML =
                    '<p style="font-size:13px;color:#b91c1c;text-align:center;padding:32px 0;margin:0;">No se pudo conectar con el servidor.</p>';
            });
    }

    function closeFdShop() {
        var overlay = document.getElementById('fd-shop-overlay');
        if (overlay) overlay.style.display = 'none';
        FD_SHOP.providerId = null;
        FD_SHOP.catalog = null;
        FD_SHOP.cart = [];
        fdShopRepeatQueue = null;
        fdShopRenderDelivery();
        fdShopPaintBadge();
        fdStopShopPolling();
    }

    // Al abrir la tienda desde "Repetir este pedido", vuelca la cola de
    // productos al carrito. Lo que ya no exista o este sin stock se omite y
    // se avisa, para no romper la carga del catalogo.
    function fdShopApplyRepeat() {
        var queue = fdShopRepeatQueue;
        fdShopRepeatQueue = null;
        if (!queue || !queue.length || !FD_SHOP.catalog) return;

        var byId = {};
        (FD_SHOP.catalog.categories || []).forEach(function (c) {
            (c.products || []).forEach(function (p) { byId[Number(p.id)] = p; });
        });

        var added = 0;
        var skipped = 0;

        queue.forEach(function (line) {
            var p = byId[Number(line.product_id)];
            if (!p || p.is_out_of_stock) { skipped++; return; }

            var variantId = null;
            var variantName = '';
            var unitPrice = Number(p.price || 0);
            (p.variants || []).forEach(function (v) {
                if (line.variant_id && Number(v.id) === Number(line.variant_id)) {
                    variantId = Number(v.id);
                    variantName = v.name;
                    unitPrice = Number(v.price);
                }
            });

            FD_SHOP.cart.push({
                product_id: Number(p.id),
                variant_id: variantId,
                quantity: Math.max(1, parseInt(line.quantity, 10) || 1),
                product_name: p.name,
                variant_name: variantName,
                unit_price: unitPrice,
                options: [],
                options_extra: 0,
                option_labels: []
            });
            added++;
        });

        if (added) {
            fdToast(added + (added === 1 ? ' producto agregado' : ' productos agregados') + ' al carrito'
                + (skipped ? '. ' + skipped + ' ya no están disponibles.' : '.'), false);
        } else {
            fdToast('Los productos de ese pedido ya no están disponibles.', true);
        }
    }

    // Mientras la tienda esta abierta, el catalogo se refresca solo para que
    // los cambios del prestador (precios, stock, productos) se vean sin F5.
    var fdShopPollTimer = null;

    function fdStopShopPolling() {
        if (fdShopPollTimer) clearInterval(fdShopPollTimer);
        fdShopPollTimer = null;
    }

    function fdStartShopPolling() {
        fdStopShopPolling();
        if (!FD_SHOP.providerId) return;
        fdShopPollTimer = setInterval(function () {
            if (document.hidden) return;
            var overlay = document.getElementById('fd-shop-overlay');
            if (!overlay || overlay.style.display === 'none' || !FD_SHOP.providerId) {
                fdStopShopPolling();
                return;
            }
            fdShopRefreshSilent();
        }, 8000);
    }

    function fdShopRefreshSilent() {
        if (!FD_SHOP.providerId || FD_SHOP.busy) return;

        fdFetchJson(window.APP_BASE+"/api/providers"+"/" + FD_SHOP.providerId + '/catalog?only_available=1')
            .then(function (res) {
                if (!res.ok || !FD_SHOP.providerId) return;

                FD_SHOP.catalog = res.data;
                FD_SHOP.settings = res.data.settings || FD_SHOP.settings;

                var provider = res.data.provider || {};
                FD_SHOP.providerName = provider.business_name || '';
                FD_SHOP.pickupAddress = provider.address || '';
                if (provider.delivery_fee !== undefined && provider.delivery_fee !== null) {
                    FD_SHOP.settings.delivery_fee = provider.delivery_fee;
                }

                var title = document.getElementById('fd-shop-title');
                if (title) title.textContent = provider.business_name || 'Comercio';

                var hours = provider.hours;
                var hoursText = Array.isArray(hours) ? hours.join(' · ') : (typeof hours === 'string' ? hours : '');
                var subtitle = document.getElementById('fd-shop-subtitle');
                if (subtitle) subtitle.textContent = [provider.zone, hoursText].filter(Boolean).join(' · ');

                var sel = document.getElementById('fd-shop-category');
                var prevFilter = sel ? String(sel.value) : '';
                fdShopFillCategoryFilter(res.data.categories || []);
                if (sel) {
                    var options = sel.options;
                    var restored = false;
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].value === prevFilter) { sel.value = prevFilter; restored = true; break; }
                    }
                    if (!restored) sel.value = '';
                }
                FD_SHOP.categoryFilter = sel ? String(sel.value) : '';

                // Filtra el carrito: saca lineas de productos que ya no existen o estan agotados.
                var available = {};
                (res.data.categories || []).forEach(function (c) {
                    (c.products || []).forEach(function (p) { available[p.id] = p; });
                });
                var before = FD_SHOP.cart.length;
                FD_SHOP.cart = FD_SHOP.cart.filter(function (line) {
                    var p = available[line.product_id];
                    return p && !p.is_out_of_stock;
                });
                if (FD_SHOP.cart.length !== before) {
                    fdShopShowMsg('Algunos productos del carrito ya no estan disponibles y se quitaron.', true);
                }

                var categories = res.data.categories || [];
                if (FD_SHOP.categoryFilter !== '') {
                    var index = Number(FD_SHOP.categoryFilter);
                    categories = !isNaN(index) && res.data.categories && res.data.categories[index]
                        ? [res.data.categories[index]]
                        : [];
                }

                var body = document.getElementById('fd-shop-body');
                if (body) body.innerHTML = fdRenderShopCatalog(categories);
                fdRenderShopCart();
            })
            .catch(function () {});
    }

    // Volver desde la tienda: si la abrimos empujando el estado 'tienda',
    // volvemos con el historial para no dejar entradas repetidas.
    function fdShopBack() {
        closeFdShop();
        if (fdSectionHistoryReady && history.state && history.state.fdSection === 'tienda') {
            history.back();
        } else {
            showDashSection(FD_SECTION_SHOP_FALLBACK);
        }
    }

    // Navegacion desde la tienda a otra seccion del panel (ej. Mis Pedidos):
    // reemplaza la entrada 'tienda' para que Atras siga funcionando bien.
    function fdShopGo(key) {
        closeFdShop();
        if (fdSectionHistoryReady && history.state && history.state.fdSection === 'tienda') {
            history.replaceState({ fdSection: key }, '', window.APP_BASE+"");
            fdSectionPopNav = true;
            try {
                showDashSection(key);
            } finally {
                fdSectionPopNav = false;
            }
        } else {
            showDashSection(key);
        }
    }

    // Combo de categorias del comercio: filtra el catalogo de la tienda.
    function fdShopFillCategoryFilter(categories) {
        var sel = document.getElementById('fd-shop-category');
        if (!sel) return;

        sel.innerHTML = '<option value="">Todas las categorías</option>'
            + categories.map(function (c, i) {
                return '<option value="' + i + '">' + escapeHtml(c.name || '') + '</option>';
            }).join('');
        sel.value = '';
    }

    function fdShopCategoryFilter() {
        var sel = document.getElementById('fd-shop-category');
        var body = document.getElementById('fd-shop-body');
        if (!body || !FD_SHOP.catalog) return;

        var value = sel ? String(sel.value) : '';
        FD_SHOP.categoryFilter = value;

        var categories = FD_SHOP.catalog.categories || [];
        var visible = categories;

        if (value !== '') {
            var index = Number(value);
            visible = !isNaN(index) && categories[index] ? [categories[index]] : [];
        }

        body.innerHTML = fdRenderShopCatalog(visible);
        body.scrollTop = 0;
    }

    function fdRenderShopCatalog(categories) {
        if (!categories.length) {
            return '<p style="font-size:13px;color:#6b7280;text-align:center;padding:32px 0;margin:0;">'
                + 'Este comercio todavía no tiene productos disponibles.</p>';
        }

        return categories.map(function (c) {
            return '<div style="margin-bottom:18px;">'
                + '<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#9a3412;margin-bottom:8px;">'
                + escapeHtml(c.name) + '</div>'
                + (c.products || []).map(fdRenderShopProduct).join('')
                + '</div>';
        }).join('');
    }

    function fdRenderShopProduct(p) {
        var variants = p.variants || [];
        var groups = p.option_groups || [];
        var maxQty = Number(FD_SHOP.settings.max_quantity_per_item || 20);

        var initial = escapeHtml(String(p.name || '?').charAt(0).toUpperCase());
        var photo = '<div style="width:88px;height:88px;flex:none;border-radius:6px;overflow:hidden;background:#eef2f6;display:flex;align-items:center;justify-content:center;position:relative;">'
            + '<span style="font-size:26px;font-weight:800;color:#94a3b8;">' + initial + '</span>'
            + (p.image_path
                ? '<img src="window.APP_BASE+"/images"/' + escapeHtml(p.image_path) + '" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" onerror="this.remove();" />'
                : '')
            + '</div>';

        var html = '<div id="fd-p-' + p.id + '" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:12px 14px;margin-bottom:10px;">'
            + '<div style="display:flex;gap:12px;align-items:flex-start;">'
            + photo
            + '<div style="flex:1;min-width:0;">'
            + '<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">'
            + '<div style="min-width:0;">'
            + '<div style="font-size:14px;font-weight:700;color:#0f172a;">' + escapeHtml(p.name) + '</div>'
            + (p.description ? '<div style="font-size:12px;color:#64748b;margin-top:2px;">' + escapeHtml(p.description) + '</div>' : '')
            + '</div>'
            + '<div style="font-size:14px;font-weight:700;color:#0f172a;white-space:nowrap;">'
            + fdMoney(variants.length ? variants[0].price : p.price) + '</div>'
            + '</div>';

        if (variants.length > 1) {
            html += '<div style="margin-top:8px;">'
                + '<select id="fd-variant-' + p.id + '" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
                + variants.map(function (v) {
                    return '<option value="' + v.id + '">' + escapeHtml(v.name) + ' — ' + fdMoney(v.price) + '</option>';
                }).join('')
                + '</select></div>';
        } else if (variants.length === 1) {
            html += '<div style="margin-top:6px;font-size:11px;color:#94a3b8;">' + escapeHtml(variants[0].name) + '</div>'
                + '<input type="hidden" id="fd-variant-' + p.id + '" value="' + variants[0].id + '" />';
        }

        groups.forEach(function (g) {
            var opts = g.options || [];
            if (!opts.length) return;

            var min = Number(g.min_choices || 0);
            var max = Number(g.max_choices || 0);
            var hint = (min > 0 ? 'obligatorio' : 'opcional') + (max > 0 ? ', hasta ' + max : '');

            html += '<div style="margin-top:10px;font-size:12px;color:#334155;">'
                + '<div style="font-weight:700;color:#0f172a;">' + escapeHtml(g.name) + ' <span style="color:#94a3b8;font-weight:500;">(' + hint + ')</span></div>'
                + '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">'
                + opts.map(function (o) {
                    return '<label style="display:flex;align-items:center;gap:6px;font-size:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:5px 8px;cursor:pointer;">'
                        + '<input type="checkbox" class="fd-opt" data-product="' + p.id + '" data-group="' + g.id + '" data-extra="' + Number(o.extra_price || 0) + '" value="' + o.id + '" style="accent-color:#D24C19;cursor:pointer;" />'
                        + escapeHtml(o.name)
                        + (Number(o.extra_price) > 0 ? ' <span style="margin-left:4px;color:#D24C19;font-weight:700;">+' + fdMoney(o.extra_price) + '</span>' : '')
                        + '</label>';
                }).join('')
                + '</div></div>';
        });

        var outOfStock = !!p.is_out_of_stock;
        var qtyControls = '<div style="display:flex;align-items:center;gap:6px;'
            + (outOfStock ? 'opacity:.5;' : '') + '">'
            + '<button type="button" onclick="fdShopQty(' + p.id + ', -1)" title="Quitar una unidad" aria-label="Quitar una unidad" style="width:30px;height:30px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:15px;line-height:1;color:#374151;">−</button>'
            + '<input type="number" id="fd-qty-' + p.id + '" value="1" min="1" readonly style="width:48px;text-align:center;padding:5px 0;border:1px solid #e5e7eb;border-radius:4px;font-size:13px;background:#fff;" />'
            + '<button type="button" onclick="fdShopQty(' + p.id + ', 1)" title="Agregar una unidad" aria-label="Agregar una unidad" style="width:30px;height:30px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:15px;line-height:1;color:#374151;">+</button>'
            + '</div>';

        var addButton = outOfStock
            ? '<span style="font-size:12px;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;padding:6px 10px;border-radius:4px;">Sin stock</span>'
            : '<button type="button" class="btn-icon" onclick="fdShopAdd(' + p.id + ')" title="Agregar al carrito" aria-label="Agregar al carrito" style="background:#D24C19;color:#fff;border:none;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/><line x1="12" x2="12" y1="10" y2="16"/><line x1="9" x2="15" y1="13" y2="13"/></svg>'
            + '</button>';

        html += '</div></div>'
            + '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap;">'
            + qtyControls
            + addButton
            + '</div>'
            + '<div id="fd-p-msg-' + p.id + '" style="display:none;font-size:11px;color:#b91c1c;margin-top:6px;"></div>'
            + '</div>';

        return html;
    }

    function fdShopQty(productId, delta) {
        var el = document.getElementById('fd-qty-' + productId);
        if (!el) return;
        var max = Number(FD_SHOP.settings.max_quantity_per_item || 20);
        var value = (parseInt(el.value, 10) || 1) + delta;
        el.value = Math.min(Math.max(value, 1), Math.max(max, 1));
    }

    function fdShopFindProduct(productId) {
        var categories = (FD_SHOP.catalog && FD_SHOP.catalog.categories) || [];
        for (var i = 0; i < categories.length; i++) {
            var products = categories[i].products || [];
            for (var j = 0; j < products.length; j++) {
                if (Number(products[j].id) === Number(productId)) return products[j];
            }
        }
        return null;
    }

    function fdShopAdd(productId) {
        var p = fdShopFindProduct(productId);
        if (!p) return;

        var msgEl = document.getElementById('fd-p-msg-' + productId);
        var showMsg = function (text) {
            if (msgEl) { msgEl.textContent = text; msgEl.style.display = 'block'; }
        };
        var hideMsg = function () { if (msgEl) msgEl.style.display = 'none'; };

        if (p.is_out_of_stock) {
            showMsg('Sin stock en este momento.');
            return;
        }

        var qtyEl = document.getElementById('fd-qty-' + productId);
        var quantity = Math.max(1, parseInt(qtyEl ? qtyEl.value : '1', 10) || 1);
        var maxQty = Number(FD_SHOP.settings.max_quantity_per_item || 20);

        if (quantity > maxQty) {
            showMsg('Máximo ' + maxQty + ' unidades por producto.');
            return;
        }

        var variants = p.variants || [];
        var variantId = null;
        var variantName = '';
        var unitPrice = Number(p.price || 0);

        var variantEl = document.getElementById('fd-variant-' + productId);
        if (variantEl) {
            var selectedVariantId = parseInt(variantEl.value, 10);
            variants.forEach(function (v) {
                if (Number(v.id) === selectedVariantId) {
                    variantId = Number(v.id);
                    variantName = v.name;
                    unitPrice = Number(v.price);
                }
            });
        }

        var boxes = document.querySelectorAll('#fd-p-' + productId + ' input.fd-opt:checked');
        var byGroup = {};
        var options = [];
        var optionLabels = [];
        var optionsExtra = 0;

        Array.prototype.forEach.call(boxes, function (box) {
            var groupId = box.getAttribute('data-group');
            if (!byGroup[groupId]) byGroup[groupId] = [];
            byGroup[groupId].push(box);
            options.push(Number(box.value));
        });

        var invalid = (p.option_groups || []).some(function (g) {
            var selected = byGroup[g.id] || [];
            var min = Number(g.min_choices || 0);
            var max = Number(g.max_choices || 0);

            if (min > 0 && selected.length < min) {
                showMsg('Elegí al menos ' + min + ' opción de "' + g.name + '".');
                return true;
            }
            if (max > 0 && selected.length > max) {
                showMsg('Podés elegir hasta ' + max + ' opciones de "' + g.name + '".');
                return true;
            }
            return false;
        });

        if (invalid) return;

        Array.prototype.forEach.call(boxes, function (box) {
            var extra = Number(box.getAttribute('data-extra') || 0);
            optionsExtra += extra;
            optionLabels.push(box.parentNode.textContent.trim());
        });

        var maxItems = Number(FD_SHOP.settings.max_items_per_order || 30);
        var sortedOptions = options.slice().sort();
        var sameLine = -1;

        FD_SHOP.cart.forEach(function (line, i) {
            if (sameLine !== -1) return;
            if (Number(line.product_id) === Number(p.id)
                && line.variant_id === variantId
                && (line.options || []).slice().sort().join(',') === sortedOptions.join(',')) {
                sameLine = i;
            }
        });

        if (sameLine !== -1) {
            var merged = FD_SHOP.cart[sameLine];
            if (Number(merged.quantity) + quantity > maxQty) {
                showMsg('Máximo ' + maxQty + ' unidades por producto.');
                return;
            }
            merged.quantity = Number(merged.quantity) + quantity;
        } else {
            if (FD_SHOP.cart.length >= maxItems) {
                showMsg('Podés llevar hasta ' + maxItems + ' productos distintos por pedido.');
                return;
            }

            FD_SHOP.cart.push({
                product_id: Number(p.id),
                variant_id: variantId,
                quantity: quantity,
                product_name: p.name,
                variant_name: variantName,
                unit_price: unitPrice,
                options: options,
                options_extra: optionsExtra,
                option_labels: optionLabels
            });
        }

        if (qtyEl) qtyEl.value = 1;
        hideMsg();
        fdRenderShopCart();
        fdToast('Agregado al carrito');
    }

    function fdShopRemove(index) {
        FD_SHOP.cart.splice(Number(index), 1);
        fdRenderShopCart();
    }

    function fdShopLineQty(index, delta) {
        var line = FD_SHOP.cart[Number(index)];
        if (!line) return;

        var max = Number(FD_SHOP.settings.max_quantity_per_item || 20);
        var next = Number(line.quantity) + Number(delta);

        if (next < 1) {
            fdShopRemove(index);
            return;
        }

        line.quantity = Math.min(next, Math.max(max, 1));
        fdRenderShopCart();
    }

    function fdShopPaintBadge() {
        var badge = document.getElementById('fd-shop-cart-badge');
        var confirmBtn = document.getElementById('fd-shop-submit');
        if (!badge) return;

        var units = 0;
        var subtotal = 0;
        FD_SHOP.cart.forEach(function (line) {
            units += Number(line.quantity);
            subtotal += (Number(line.unit_price) + Number(line.options_extra)) * Number(line.quantity);
        });

        if (!units) {
            badge.style.display = 'none';
            badge.innerHTML = '';
            if (confirmBtn) {
                confirmBtn.style.display = 'none';
                confirmBtn.disabled = false;
                confirmBtn.title = 'Confirmar compra';
            }
            return;
        }

        badge.style.display = 'inline-flex';
        badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
            + '<span style="background:#D24C19;color:#fff;border-radius:9999px;min-width:17px;height:17px;padding:0 5px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">' + units + '</span>'
            + '<b>' + fdMoney(subtotal) + '</b>';

        if (confirmBtn) confirmBtn.style.display = 'inline-flex';
    }

    function fdLoadShopAddresses() {
        FD_SHOP.addresses = null;
        fdFetchJson(window.APP_BASE+"/api/me/addresses")
            .then(function (res) {
                FD_SHOP.addresses = (res.ok && res.data.addresses) || [];
                fdRenderShopCart();
            })
            .catch(function () {
                FD_SHOP.addresses = [];
                fdRenderShopCart();
            });
    }

    function fdRenderShopCart() {
        var el = document.getElementById('fd-shop-cart');
        if (!el) return;

        var prevAddress = document.getElementById('fd-shop-address');
        var prevNotes = document.getElementById('fd-shop-notes');
        var prevPayment = document.getElementById('fd-shop-payment');
        var keepNotes = prevNotes ? prevNotes.value : '';
        var keepPayment = prevPayment ? prevPayment.value : '';

        if (prevAddress && prevAddress.value) FD_SHOP.addressId = String(prevAddress.value);

        if (!FD_SHOP.cart.length) {
            el.innerHTML = '<div style="font-size:12px;color:#6b7280;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#94a3b8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
                + 'Tu carrito está vacío. Agregá productos para continuar.</div>';
            fdShopRenderDelivery();
            fdShopPaintBadge();
            return;
        }

        var subtotal = 0;
        var lines = FD_SHOP.cart.map(function (line, index) {
            var lineTotal = (Number(line.unit_price) + Number(line.options_extra)) * Number(line.quantity);
            subtotal += lineTotal;

            return '<div style="display:flex;justify-content:space-between;gap:10px;font-size:13px;padding:7px 0;border-bottom:1px dashed #e5e7eb;">'
                + '<div style="min-width:0;">'
                + '<b>' + Number(line.quantity) + '×</b> ' + escapeHtml(line.product_name)
                + (line.variant_name ? ' <span style="color:#94a3b8;">(' + escapeHtml(line.variant_name) + ')</span>' : '')
                + (line.option_labels && line.option_labels.length
                    ? '<div style="font-size:11px;color:#6b7280;">' + line.option_labels.map(escapeHtml).join(', ') + '</div>'
                    : '')
                + '</div>'
                + '<div style="display:flex;align-items:center;gap:8px;white-space:nowrap;flex:none;">'
                + '<span style="display:flex;align-items:center;gap:4px;">'
                + '<button type="button" onclick="fdShopLineQty(' + index + ', -1)" title="Quitar una unidad" aria-label="Quitar una unidad" style="width:24px;height:24px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:13px;line-height:1;color:#374151;">−</button>'
                + '<span style="min-width:18px;text-align:center;font-weight:600;">' + Number(line.quantity) + '</span>'
                + '<button type="button" onclick="fdShopLineQty(' + index + ', 1)" title="Agregar una unidad" aria-label="Agregar una unidad" style="width:24px;height:24px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:13px;line-height:1;color:#374151;">+</button>'
                + '</span>'
                + '<span style="font-weight:600;">' + fdMoney(lineTotal) + '</span>'
                + '<button type="button" class="btn-icon" onclick="fdShopRemove(' + index + ')" title="Quitar del carrito" aria-label="Quitar del carrito" style="background:none;color:#dc2626;border:none;border-radius:4px;cursor:pointer;padding:2px;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>'
                + '</button>'
                + '</div></div>';
        }).join('');

        var isTakeaway = FD_SHOP.deliveryType === 'takeaway';
        var fee = isTakeaway ? 0 : Number(FD_SHOP.settings.delivery_fee || 0);
        var total = subtotal + fee;

        var paymentOptions = Object.keys(FD_PAYMENT_METHOD_LABELS).map(function (key) {
            if (key === 'mercado_pago') return '';
            return '<option value="' + key + '"' + (key === keepPayment ? ' selected' : '') + '>'
                + escapeHtml(FD_PAYMENT_METHOD_LABELS[key]) + '</option>';
        }).join('');

        var units = 0;
        FD_SHOP.cart.forEach(function (line) { units += Number(line.quantity); });

        el.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">'
            + '<span style="font-size:12px;font-weight:800;color:#0c2a4d;text-transform:uppercase;letter-spacing:.4px;">Tu carrito</span>'
            + '<span style="font-size:11px;color:#6b7280;">' + units + (units === 1 ? ' unidad' : ' unidades') + ' · ' + FD_SHOP.cart.length + (FD_SHOP.cart.length === 1 ? ' producto' : ' productos') + '</span>'
            + '</div>'
            + lines
            + '<div style="border-top:1px solid #e5e7eb;margin-top:8px;padding-top:8px;font-size:13px;">'
            + fdTotalRow('Subtotal', fdMoney(subtotal), false)
            + (isTakeaway
                ? fdTotalRow('Retiro en el local', 'Sin costo', false)
                : fdTotalRow('Envío', fdMoney(fee), false))
            + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#0c2a4d;margin-top:6px;">'
            + '<span>Total estimado</span><span>' + fdMoney(total) + '</span></div>'
            + '</div>'
            + '<div style="margin-top:10px;">'
            + '<label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Forma de pago</label>'
            + '<select id="fd-shop-payment" onchange="fdShopPaymentChanged()" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
            + paymentOptions
            + '</select>'
            + '<div id="fd-shop-transfer-info" style="display:none;margin-top:8px;font-size:12px;line-height:1.6;color:#0c2a4d;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:9px 12px;"></div></div>'
            + '<div style="margin-top:10px;">'
            + '<label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Nota para el comercio</label>'
            + '<textarea id="fd-shop-notes" rows="2" maxlength="1000" placeholder="Ej: sin cebolla, timbre roto, etc." style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;resize:vertical;">'
            + escapeHtml(keepNotes) + '</textarea></div>';

        var submitBtn = document.getElementById('fd-shop-submit');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.title = 'Confirmar compra · ' + fdMoney(total);
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'
                + '<span>Confirmar compra · ' + fdMoney(total) + '</span>';
        }

        if (keepNotes) {
            var notes = document.getElementById('fd-shop-notes');
            if (notes) notes.value = keepNotes;
        }

        fdShopRenderDelivery();
        fdShopPaintBadge();
    }

    function fdShopSetDeliveryType(type) {
        if (type !== 'delivery' && type !== 'takeaway') return;
        if (FD_SHOP.deliveryType === type) return;

        var addressSel = document.getElementById('fd-shop-address');
        if (addressSel && addressSel.value) FD_SHOP.addressId = String(addressSel.value);

        FD_SHOP.deliveryType = type;
        fdRenderShopCart();
    }

    // Seccion "Metodo de entrega" que vive arriba del boton de confirmar.
    // Envio: costo + direccion del cliente. Retiro: sin costo + direccion del
    // comercio. La opcion activa se resalta con el naranja primario.
    function fdShopRenderDelivery() {
        var el = document.getElementById('fd-shop-delivery');
        if (!el) return;

        if (!FD_SHOP.cart.length) {
            el.style.display = 'none';
            el.innerHTML = '';
            return;
        }

        el.style.display = 'block';

        var isDelivery = FD_SHOP.deliveryType !== 'takeaway';
        var fee = Number(FD_SHOP.settings.delivery_fee || 0);
        var addresses = FD_SHOP.addresses;
        var activeStyle = 'background:#fff7f2;border:2px solid #D24C19;box-shadow:0 4px 14px rgba(210,76,25,.14);';
        var idleStyle = 'background:#fff;border:2px solid #e5e7eb;';
        var cardBase = 'display:flex;align-items:flex-start;gap:10px;width:100%;padding:11px 12px;border-radius:8px;cursor:pointer;font-family:inherit;text-align:left;transition:border-color .15s,box-shadow .15s;';

        var truckIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px;"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm-3 11.5V14l-3-3 4-3 2 3h2"/></svg>';
        var storeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px;"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v8h14v-8"/><path d="M9 21v-5h6v5"/></svg>';
        var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-left:auto;"><circle cx="12" cy="12" r="10" fill="#D24C19" stroke="none"/><polyline points="16.5 9 10.8 14.7 7.5 11.4" stroke="#fff"/></svg>';
        var dotIcon = '<span style="width:16px;height:16px;border-radius:50%;border:2px solid #cbd5e1;flex:none;margin-left:auto;"></span>';

        var html = '<div style="font-size:11px;font-weight:800;color:#0c2a4d;text-transform:uppercase;letter-spacing:.4px;margin-bottom:7px;">Método de entrega</div>'
            + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:9px;">'
            + '<button type="button" onclick="fdShopSetDeliveryType(\'delivery\')" aria-pressed="' + (isDelivery ? 'true' : 'false') + '" title="Envío con repartidor" style="' + cardBase + (isDelivery ? activeStyle : idleStyle) + 'color:' + (isDelivery ? '#9a3412' : '#334155') + ';">'
            + truckIcon
            + '<span style="min-width:0;display:block;">'
            + '<span style="display:block;font-size:12.5px;font-weight:800;color:#0f172a;">Envío con repartidor</span>'
            + '<span style="display:block;font-size:11px;color:#64748b;margin-top:1px;">Costo de envío ' + fdMoney(fee) + '</span>'
            + '</span>'
            + (isDelivery ? checkIcon : dotIcon)
            + '</button>'
            + '<button type="button" onclick="fdShopSetDeliveryType(\'takeaway\')" aria-pressed="' + (!isDelivery ? 'true' : 'false') + '" title="Retiro en el local" style="' + cardBase + (!isDelivery ? activeStyle : idleStyle) + 'color:' + (!isDelivery ? '#9a3412' : '#334155') + ';">'
            + storeIcon
            + '<span style="min-width:0;display:block;">'
            + '<span style="display:block;font-size:12.5px;font-weight:800;color:#0f172a;">Retiro en el local</span>'
            + '<span style="display:block;font-size:11px;color:#64748b;margin-top:1px;">Sin costo adicional</span>'
            + '</span>'
            + (!isDelivery ? checkIcon : dotIcon)
            + '</button>'
            + '</div>';

        if (isDelivery) {
            if (addresses === null) {
                html += '<div style="font-size:12px;color:#6b7280;margin-top:9px;">Cargando direcciones...</div>';
            } else if (!addresses.length) {
                html += '<div style="font-size:12px;color:#b45309;margin-top:9px;">'
                    + 'No tenés direcciones cargadas. Cargá una desde <b>Tu Perfil</b> para poder pedir con envío.</div>';
            } else {
                var selectedAddress = FD_SHOP.addressId ? String(FD_SHOP.addressId) : '';
                html += '<div style="margin-top:9px;">'
                    + '<label for="fd-shop-address" style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Dirección de entrega *</label>'
                    + '<select id="fd-shop-address" onchange="fdShopRememberAddress(this.value)" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
                    + addresses.map(function (a) {
                        return '<option value="' + a.id + '"' + (String(a.id) === selectedAddress ? ' selected' : '') + '>'
                            + escapeHtml(a.formatted || (a.street + ' ' + (a.number || '')))
                            + (a.is_primary ? ' · principal' : '')
                            + '</option>';
                    }).join('')
                    + '</select></div>';
            }
        } else {
            html += '<div style="margin-top:9px;padding:9px 11px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#475569;display:flex;gap:8px;align-items:flex-start;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px;"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'
                + '<span><b>Retirás en:</b> ' + escapeHtml(FD_SHOP.pickupAddress || 'Consultá la dirección del local con el comercio.') + '</span>'
                + '</div>';
        }

        el.innerHTML = html;
    }

    function fdShopRememberAddress(value) {
        FD_SHOP.addressId = value ? String(value) : '';
    }

    function fdShopShowMsg(text, isError) {
        var msg = document.getElementById('fd-shop-msg');
        if (!msg) return;
        msg.textContent = text;
        msg.style.color = isError ? '#b91c1c' : '#047857';
        msg.style.background = isError ? '#fef2f2' : '#ecfdf5';
        msg.style.borderColor = isError ? '#fecaca' : '#a7f3d0';
        msg.style.display = 'block';
        if (typeof msg.scrollIntoView === 'function') {
            try { msg.scrollIntoView({ block: 'nearest' }); } catch (e) {}
        }
    }

    var FD_MATRIX_CACHE = null;

    function fdShopMatrix(done) {
        if (FD_MATRIX_CACHE) { done(FD_MATRIX_CACHE); return; }
        fetch(window.APP_BASE + "/billing/matrix", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            FD_MATRIX_CACHE = (data && data.success) ? data : null;
            done(FD_MATRIX_CACHE);
        })
        .catch(function () { done(null); });
    }

    function fdShopPaymentChanged() {
        var sel = document.getElementById('fd-shop-payment');
        var box = document.getElementById('fd-shop-transfer-info');
        if (!sel || !box) return;
        if (sel.value !== 'transferencia' && sel.value !== 'transferencia_bancaria') {
            box.style.display = 'none';
            box.innerHTML = '';
            return;
        }
        box.style.display = 'block';
        box.innerHTML = 'Cargando datos para transferir…';
        fdShopMatrix(function (res) {
            var m = (res && res.data) || {};
            var cbu = m.matrix_AR_CBU || m['matrix_AR_CBU'];
            var alias = m.matrix_AR_ALIAS || m['matrix_AR_ALIAS'];
            var holder = m.matrix_AR_HOLDER || m['matrix_AR_HOLDER'];
            if (!cbu && !alias) {
                box.innerHTML = 'Elegí transferencia y te mostraremos los datos al confirmar el pedido.';
                return;
            }
            box.innerHTML = 'Transferí a <b>' + escapeHtml(holder || 'PideAca')
                + '</b> · CBU <b>' + escapeHtml(cbu || '—') + '</b>'
                + (alias ? ' · Alias <b>' + escapeHtml(alias) + '</b>' : '')
                + '.<br>Usá como referencia el <b>número de pedido</b> que te mostramos al confirmar.';
        });
    }

    function fdShopSubmit() {
        if (FD_SHOP.busy) return;

        if (!FD_SHOP.cart.length) {
            fdShopShowMsg('Agregá al menos un producto antes de confirmar.', true);
            return;
        }

        var isTakeaway = FD_SHOP.deliveryType === 'takeaway';
        var addressId = null;

        if (!isTakeaway) {
            var addressSel = document.getElementById('fd-shop-address');
            addressId = addressSel ? parseInt(addressSel.value, 10) : NaN;

            if (isNaN(addressId)) {
                fdShopShowMsg('Seleccioná la dirección de entrega.', true);
                return;
            }

            FD_SHOP.addressId = String(addressId);
        }

        var notesEl = document.getElementById('fd-shop-notes');
        var paymentEl = document.getElementById('fd-shop-payment');
        var submitBtn = document.getElementById('fd-shop-submit');

        var payload = {
            provider_id: FD_SHOP.providerId,
            delivery_type: FD_SHOP.deliveryType,
            address_id: addressId,
            payment_method: paymentEl ? paymentEl.value : 'efectivo',
            notes: notesEl ? notesEl.value.trim() : '',
            items: FD_SHOP.cart.map(function (line) {
                return {
                    product_id: line.product_id,
                    variant_id: line.variant_id,
                    quantity: line.quantity,
                    options: line.options
                };
            })
        };

        FD_SHOP.busy = true;
        FD_SHOP.lastPaymentMethod = payload.payment_method;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.title = 'Enviando...';
            submitBtn.style.opacity = '0.65';
        }

        fetch(window.APP_BASE+"/api/orders", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            FD_SHOP.busy = false;

            if (!res.ok) {
                var message = (res.data && res.data.message) || 'No se pudo registrar el pedido.';
                if (res.data && res.data.errors) {
                    var firstKey = Object.keys(res.data.errors)[0];
                    message = res.data.errors[firstKey][0];
                }
                // Repinta el carrito: conserva dirección/nota/pago y devuelve
                // el botón con el total por si el usuario reintenta.
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
                fdRenderShopCart();
                fdShopShowMsg(message, true);
                return;
            }

            var orderNumber = (res.data.order && res.data.order.order_number) || '';
            var paidByTransfer = FD_SHOP.lastPaymentMethod === 'transferencia' || FD_SHOP.lastPaymentMethod === 'transferencia_bancaria';
            var paidWithMP = FD_SHOP.lastPaymentMethod === 'mercadopago' || FD_SHOP.lastPaymentMethod === 'mercado_pago';

            FD_SHOP.cart = [];
            fdShopRenderDelivery();
            fdShopPaintBadge();
            document.getElementById('fd-shop-cart').innerHTML =
                '<div style="text-align:center;padding:6px 0;">'
                + '<div style="font-size:14px;font-weight:700;color:#047857;margin-bottom:6px;">Pedido registrado</div>'
                + '<div style="font-size:12px;color:#64748b;">'
                + (orderNumber ? 'Número <b>' + escapeHtml(orderNumber) + '</b>. ' : '')
                + 'El comercio va a confirmar tu pedido y lo vas a ver en Mis Pedidos.</div>'
                + (paidByTransfer ? '<div id="fd-order-transfer-box" style="margin-top:10px;font-size:12px;line-height:1.6;color:#0c2a4d;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:9px 12px;text-align:left;">Cargando datos para transferir…</div>' : '')
                + '<button type="button" onclick="fdShopGo(\'mis-pedidos\');" title="Ver mis pedidos" aria-label="Ver mis pedidos" style="margin-top:10px;background:#0c2a4d;color:#fff;border:none;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>'
                + '</button>'
                + '</div>';

            var cartEl = document.getElementById('fd-shop-cart');
            if (cartEl) cartEl.scrollTop = 0;

            if (paidWithMP) {
                var mpOrderId = res.data.order && res.data.order.id;
                fdShopStartMercadoPago(mpOrderId);
                return;
            }

            if (paidByTransfer) {
                fdShopMatrix(function (res) {
                    var box = document.getElementById('fd-order-transfer-box');
                    if (!box) return;
                    var m = (res && res.data) || {};
                    var cbu = m.matrix_AR_CBU || m['matrix_AR_CBU'];
                    var alias = m.matrix_AR_ALIAS || m['matrix_AR_ALIAS'];
                    var holder = m.matrix_AR_HOLDER || m['matrix_AR_HOLDER'];
                    if (!cbu && !alias) {
                        box.innerHTML = 'Te avisaremos por este medio los datos para transferir.';
                        return;
                    }
                    box.innerHTML = 'Transferí a <b>' + escapeHtml(holder || 'PideAca')
                        + '</b> · CBU <b>' + escapeHtml(cbu || '—') + '</b>'
                        + (alias ? ' · Alias <b>' + escapeHtml(alias) + '</b>' : '')
                        + '.<br>Usá como referencia el <b>número de pedido ' + escapeHtml(orderNumber || '') + '</b>. Tu pedido queda en preparación al conciliarse el pago.';
                });
            }

            loadMyOrders();
        })
        .catch(function () {
            FD_SHOP.busy = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
            fdRenderShopCart();
            fdShopShowMsg('No se pudo conectar con el servidor.', true);
        });
    }

    function fdMyOrdersSearchDebounce() {
        if (fdMyOrdersSearchTimer) clearTimeout(fdMyOrdersSearchTimer);
        fdMyOrdersSearchTimer = setTimeout(function () {
            var input = document.getElementById('fd-myorders-search');
            fdMyOrdersSearch = input ? input.value.trim() : '';
            fdMyOrdersHistoryPage = 1;
            fdMyOrdersLastHtml = '';
            fdMyOrdersRender(false);
        }, 300);
    }

    function loadMyOrders(silent) {
        var filterEl = document.getElementById('fd-myorders-filter');
        var savedFilter = filterEl ? filterEl.value : '';

        if (!silent) {
            // Carga manual o cambio de filtro: vuelve a la primera pagina.
            fdMyOrdersHistoryPage = 1;
            fdSetMyOrdersState('loading');
        }

        var url = window.APP_BASE+"/api/orders/mine";
        if (savedFilter) url += '?status=' + encodeURIComponent(savedFilter);

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok) {
                    if (silent) return;
                    fdSetMyOrdersState('message', (res.data && res.data.message) || 'No se pudieron cargar tus pedidos.');
                    return;
                }

                var filter = document.getElementById('fd-myorders-filter');
                if (filter && filter.options.length <= 1) {
                    (res.data.statuses || []).forEach(function (status) {
                        var opt = document.createElement('option');
                        opt.value = status;
                        opt.textContent = FD_ORDER_STATUS[status] ? FD_ORDER_STATUS[status][0] : status;
                        filter.appendChild(opt);
                    });
                    filter.value = savedFilter;
                }

                var statusDot = document.getElementById('fd-myorders-status-dot');
                if (statusDot) {
                    var sel = FD_ORDER_STATUS[savedFilter];
                    statusDot.style.background = sel ? sel[1] : '#94a3b8';
                }

                var orders = res.data.orders || [];
                fdMyOrdersAll = orders;
                fdMyOrdersUpdatedAt = Date.now();
                var groups = fdMyOrdersSplit();

                var summary = document.getElementById('fd-myorders-summary');
                if (summary) {
                    summary.textContent = orders.length
                        ? groups.today.length + ' en curso · ' + groups.history.length + ' en historial · Actualizado ' + fdTimeAgo(new Date().toISOString())
                        : 'Tus pedidos y su estado';
                }

                var live = document.getElementById('fd-myorders-live');
                var liveText = document.getElementById('fd-myorders-live-text');
                if (live) live.style.display = groups.today.length ? 'inline-flex' : 'none';
                if (liveText) liveText.textContent = groups.today.length + (groups.today.length === 1 ? ' activo ahora' : ' activos ahora');

                if (!orders.length) {
                    fdMyOrdersLastHtml = '';
                    fdMyOrdersPaintTabs(groups);
                    fdSetMyOrdersState('message', savedFilter
                        ? 'No hay pedidos con ese estado.'
                        : 'Todavía no hiciste pedidos.\nEntrá a Comercios y armá tu primer pedido.',
                        '<button type="button" class="fd-btn fd-btn-outline" onclick="fdShopGo(\'comercios\);">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
                        + 'Ir a Comercios</button>');
                    return;
                }

                fdMyOrdersRender(silent);
            })
            .catch(function (err) {
                console.error('Error cargando mis pedidos:', err);
                if (silent) return;
                fdSetMyOrdersState('message', 'Error de conexión: ' + (err && err.message ? err.message : String(err)));
            });
    }

    // "En curso" = todo lo que aún no terminó (activos); el resto va al historial.
    var FD_MYORDERS_ACTIVE = ['pending', 'confirmed', 'in_preparation', 'on_the_way'];

    function fdMyOrdersSplit() {
        var groups = { today: [], history: [] };
        fdMyOrdersAll.forEach(function (o) {
            var idx = FD_MYORDERS_ACTIVE.indexOf(o.status) !== -1 ? 'today' : 'history';
            groups[idx].push(o);
        });
        return groups;
    }

    // Busqueda por local, numero de pedido o plato (client-side sobre lo cargado).
    function fdMyOrdersMatchSearch(o, text) {
        if (!text) return true;
        var q = text.toLowerCase();
        if (String(o.order_number || '').toLowerCase().indexOf(q) !== -1) return true;
        if (String((o.provider && o.provider.business_name) || '').toLowerCase().indexOf(q) !== -1) return true;
        return (o.items || []).some(function (it) {
            return String(it.product_name || '').toLowerCase().indexOf(q) !== -1
                || String(it.variant_name || '').toLowerCase().indexOf(q) !== -1;
        });
    }

    function fdMyOrdersPaintTabs(groups) {
        var keys = ['today', 'history'];
        keys.forEach(function (key) {
            var count = groups[key].length;

            var badge = document.getElementById('fd-myorders-count-' + key);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count ? 'inline-flex' : 'none';
            }

            var btn = document.getElementById('fd-myorders-tab-' + key);
            if (btn) {
                if (fdMyOrdersActive === key) btn.classList.add('active');
                else btn.classList.remove('active');
            }
        });

        var range = document.getElementById('fd-myorders-range');
        if (range) range.style.display = fdMyOrdersActive === 'history' ? 'flex' : 'none';
    }

    function fdMyOrdersResetFilters() {
        var input = document.getElementById('fd-myorders-search');
        if (input) input.value = '';
        fdMyOrdersSearch = '';

        var filterEl = document.getElementById('fd-myorders-filter');
        if (filterEl) filterEl.value = '';

        var from = document.getElementById('fd-myorders-date-from');
        var to = document.getElementById('fd-myorders-date-to');
        if (from) from.value = '';
        if (to) to.value = '';

        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        loadMyOrders();
    }

    function fdMyOrdersClearRange() {
        var from = document.getElementById('fd-myorders-date-from');
        var to = document.getElementById('fd-myorders-date-to');
        if (from) from.value = '';
        if (to) to.value = '';
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersRangeChange() {
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersTab(tab) {
        fdMyOrdersActive = tab === 'history' ? 'history' : 'today';
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersGoPage(page) {
        fdMyOrdersHistoryPage = Number(page) || 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);

        var listEl = document.getElementById('fd-myorders-list');
        if (listEl && typeof listEl.scrollIntoView === 'function') {
            try { listEl.scrollIntoView({ block: 'start' }); } catch (e) {}
        }
    }

    function fdMyOrdersPaintPagination(totalPages) {
        var pag = document.getElementById('fd-myorders-pagination');
        if (!pag) return;

        if (fdMyOrdersActive !== 'history' || totalPages <= 1) {
            pag.style.display = 'none';
            pag.innerHTML = '';
            return;
        }

        var page = fdMyOrdersHistoryPage;
        var arrowLeft = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>';
        var arrowRight = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';

        var dots = '';
        for (var p = 1; p <= totalPages; p++) {
            dots += '<button type="button" onclick="fdMyOrdersGoPage(' + p + ')" title="Página ' + p + '" aria-label="Página ' + p + '" class="fd-btn'
                + (p === page ? ' fd-btn-outline' : ' fd-btn-ghost') + '" style="min-width:30px;padding:7px 9px;">' + p + '</button>';
        }

        var prevDisabled = page <= 1;
        var nextDisabled = page >= totalPages;

        pag.style.display = 'flex';
        pag.innerHTML = '<button type="button" onclick="fdMyOrdersGoPage(' + (page - 1) + ')" title="Página anterior" aria-label="Página anterior" class="fd-btn fd-btn-ghost" style="opacity:'
            + (prevDisabled ? '.4;' : '1;') + 'pointer-events:' + (prevDisabled ? 'none;' : 'auto;') + '">' + arrowLeft + '</button>'
            + dots
            + '<button type="button" onclick="fdMyOrdersGoPage(' + (page + 1) + ')" title="Página siguiente" aria-label="Página siguiente" class="fd-btn fd-btn-ghost" style="opacity:'
            + (nextDisabled ? '.4;' : '1;') + 'pointer-events:' + (nextDisabled ? 'none;' : 'auto;') + '">' + arrowRight + '</button>';
    }

    function fdMyOrdersRender(silent) {
        var groups = fdMyOrdersSplit();
        fdMyOrdersPaintTabs(groups);

        var list = fdMyOrdersActive === 'today' ? groups.today : groups.history;
        var rangeActive = false;
        var search = fdMyOrdersSearch;

        if (search) {
            list = list.filter(function (o) { return fdMyOrdersMatchSearch(o, search); });
        }

        if (fdMyOrdersActive === 'history') {
            var fromEl = document.getElementById('fd-myorders-date-from');
            var toEl = document.getElementById('fd-myorders-date-to');
            var from = fromEl ? fromEl.value : '';
            var to = toEl ? toEl.value : '';

            if (from || to) {
                rangeActive = true;
                list = list.filter(function (o) {
                    var day = String((o && o.created_at) || '').slice(0, 10);
                    if (!day) return false;
                    return (!from || day >= from) && (!to || day <= to);
                });
            }
        }

        if (!list.length) {
            fdMyOrdersLastHtml = '';
            fdMyOrdersPaintPagination(0);

            var filterEl = document.getElementById('fd-myorders-filter');
            var text;
            if (search) {
                text = 'No hay pedidos que coincidan con "' + search + '".\nRevisá el nombre del local o de un plato.';
            } else if (rangeActive) {
                text = 'No hay pedidos en ese rango de fechas.';
            } else if (filterEl && filterEl.value) {
                text = 'No hay pedidos con ese estado en esta sección.';
            } else if (!fdMyOrdersAll.length) {
                text = 'Todavía no hiciste pedidos.\nEntrá a Comercios y armá tu primer pedido.';
            } else if (fdMyOrdersActive === 'today') {
                text = 'No tenés pedidos en curso.\nCuando hagas uno lo vas a ver acá hasta que se entregue.';
            } else {
                text = 'Todavía no hay pedidos en el historial.';
            }

            fdSetMyOrdersState('message', text);
            fdTrackSync([]);
            return;
        }

        // Historial: se muestran 3 pedidos por pagina.
        var totalPages = 1;
        var pageList = list;

        if (fdMyOrdersActive === 'history') {
            totalPages = Math.max(1, Math.ceil(list.length / FD_MYORDERS_PER_PAGE));
            if (fdMyOrdersHistoryPage > totalPages) fdMyOrdersHistoryPage = totalPages;
            if (fdMyOrdersHistoryPage < 1) fdMyOrdersHistoryPage = 1;
            pageList = list.slice(
                (fdMyOrdersHistoryPage - 1) * FD_MYORDERS_PER_PAGE,
                fdMyOrdersHistoryPage * FD_MYORDERS_PER_PAGE
            );
        }

        var html = pageList.map(function (o) {
            return fdMyOrdersActive === 'history' ? fdRenderMyOrderHistory(o) : fdRenderMyOrderActive(o);
        }).join('');
        var listEl = document.getElementById('fd-myorders-list');

        if (silent && html === fdMyOrdersLastHtml && listEl.style.display === 'block') return;

        fdMyOrdersLastHtml = html;
        listEl.innerHTML = html;
        fdSetMyOrdersState('content');
        fdMyOrdersPaintPagination(totalPages);
        fdTrackSync(fdMyOrdersActive === 'history' ? [] : pageList);
    }

    var FD_MY_STEPS = [
        ['pending', 'Recibido'],
        ['confirmed', 'Confirmado'],
        ['in_preparation', 'En preparación'],
        ['on_the_way', 'En camino'],
        ['delivered', 'Entregado']
    ];

    var FD_MY_STEP_ICONS = [
        // Recibido: cliente
        '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path d="M4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>',
        // Confirmado: palomita
        '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        // En preparacion: fuego de cocina
        '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.6a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/><path d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/></svg>',
        // En camino: moto de reparto (si hay repartidor se pinta su avatar)
        '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm-3 11.5V14l-3-3 4-3 2 3h2"/></svg>',
        // Entregado: casa
        '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>'
    ];

    // Subtexto informativo de cada paso del circuito.
    var FD_MY_STEPS_TAKEAWAY = [
        ['pending', 'Recibido'],
        ['confirmed', 'Confirmado'],
        ['in_preparation', 'En preparación'],
        ['on_the_way', 'Listo para retirar'],
        ['delivered', 'Retirado']
    ];

    function fdMyStepSub(o, key) {
        if (key === 'pending') return 'Esperando confirmación';
        if (key === 'confirmed') return 'Va a cocina';
        if (key === 'in_preparation') return 'En cocina';
        if (key === 'on_the_way') {
            if (o.delivery_type === 'takeaway') return 'Te avisamos';
            if (o.driver) return 'Con ' + String(o.driver.name || 'repartidor').split(' ')[0];
            return 'Próximamente';
        }
        if (key === 'delivered') return o.delivery_type === 'takeaway' ? 'Retirado' : 'Destino';
        return '';
    }

    // Circuito de seguimiento con el repartidor visible en el paso "En camino".
    function fdMyTrackHtml(o) {
        if (o.status === 'cancelled') {
            return '<div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:12px 16px;font-size:13px;color:#b91c1c;font-weight:700;">Pedido cancelado</div>';
        }

        var isTakeaway = o.delivery_type === 'takeaway';
        var steps = isTakeaway ? FD_MY_STEPS_TAKEAWAY : FD_MY_STEPS;

        var current = -1;
        steps.forEach(function (step, i) { if (step[0] === o.status) current = i; });
        if (current < 0) return '';

        var html = '<div data-track="' + o.status + '" class="fd-steps">';

        steps.forEach(function (step, i) {
            var done = i < current;
            var active = i === current;
            var cls = 'fd-step' + (done ? ' done' : '') + (active ? ' now' : '');

            // El paso "En camino" muestra la cara del repartidor si esta asignado.
            var icon = FD_MY_STEP_ICONS[i];
            if (isTakeaway && step[0] === 'on_the_way') {
                icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v8h14v-8"/><path d="M9 21v-5h6v5"/></svg>';
            } else if (step[0] === 'on_the_way' && o.driver) {
                icon = '<span style="font-size:12px;font-weight:800;">' + escapeHtml(fdInitials(o.driver.name)) + '</span>';
            }

            html += '<div class="' + cls + '">'
                + '<div class="fd-step-now">' + (active ? 'Ahora' : '') + '</div>'
                + '<div class="fd-step-ico">' + icon + '</div>'
                + '<div class="fd-step-label">' + escapeHtml(step[1]) + '</div>'
                + '<div class="fd-step-sub">' + escapeHtml(fdMyStepSub(o, step[0])) + '</div>'
                + '</div>';

            if (i < steps.length - 1) {
                html += '<div class="fd-step-line' + (i < current ? ' done' : '') + '"></div>';
            }
        });

        return html + '</div>';
    }

    // Titulo grande del bloque "estado en tiempo real", por estado.
    function fdMyOrdersLiveTitle(o) {
        if (o.status === 'pending') return 'El comercio está revisando tu pedido';
        if (o.status === 'confirmed') return '¡Pedido confirmado! Preparan todo';
        if (o.delivery_type === 'takeaway') {
            if (o.status === 'in_preparation') return 'Tu pedido se está preparando para retirar';
            if (o.status === 'on_the_way') return '¡Tu pedido está listo para retirar!';
            if (o.status === 'delivered') return 'Pedido retirado';
        }
        if (o.status === 'in_preparation') return 'Tu pedido se encuentra en el horno';
        if (o.status === 'on_the_way') {
            return o.driver
                ? String(o.driver.name || 'Tu repartidor').split(' ')[0] + ' va en camino con tu pedido'
                : 'Tu pedido va en camino hacia tu domicilio';
        }
        if (o.status === 'delivered') return 'Pedido entregado';
        if (o.status === 'cancelled') return 'Pedido cancelado';
        return 'Seguí tu pedido';
    }

    // ETA estimada segun el estado: sirve al cliente una hora aproximada de
    // entrega mientras el pedido se mueve por el circuito.
    function fdMyOrdersEta(o) {
        if (FD_MYORDERS_ACTIVE.indexOf(o.status) === -1) return '';
        var mins = { pending: 60, confirmed: 50, in_preparation: 35, on_the_way: 20 }[o.status] || 40;
        var created = fdParseDate(o.created_at);
        if (!created) return '';
        var arrive = new Date(created.getTime() + mins * 60000);
        var diff = arrive.getTime() - Date.now();
        var isTakeaway = o.delivery_type === 'takeaway';
        if (diff <= 0) return isTakeaway ? 'Listo en breve' : 'Llega en breve';
        var hh = arrive.getHours();
        var mm = arrive.getMinutes();
        return (isTakeaway ? 'Listo ≈ ' : 'Llega ≈ ') + (hh < 10 ? '0' : '') + hh + ':' + (mm < 10 ? '0' : '') + mm + ' hs';
    }

    // Tarjeta del pedido en curso: cabecera del local, estado en vivo con el
    // circuito (repartidor incluido), direccion, pago y productos.
    function fdRenderMyOrderActive(o) {
        var shop = o.provider || {};
        var address = o.address;
        var driver = o.driver;
        var isTakeaway = o.delivery_type === 'takeaway';
        var pay = (o.payments && o.payments[0]) || null;
        var eta = fdMyOrdersEta(o);

        var html = '<div class="fd-order fd-myorders-card" data-order-id="' + Number(o.id) + '">';

        // Cabecera: local + estado + ayuda
        html += '<div class="fd-hist-head">'
            + '<div class="fd-shop-ico">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>'
            + '</div>'
            + '<div style="min-width:0;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span class="fd-shop-name">' + escapeHtml(shop.business_name || 'Comercio') + '</span>'
            + fdStatusBadge(o.status)
            + '</div>'
            + '<div style="display:flex;align-items:center;gap:7px;margin-top:3px;flex-wrap:wrap;">'
            + '<span class="fd-onum">' + escapeHtml(o.order_number) + '</span>'
            + '<button type="button" class="fd-icon-btn" style="width:22px;height:22px;border:none;background:transparent;" onclick="fdMyOrderCopyNumber(' + o.id + ')" title="Copiar número de pedido" aria-label="Copiar número de pedido">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>'
            + '</button>'
            + '<span style="color:#e2e8f0;">·</span>'
            + '<span style="font-size:11.5px;color:#94a3b8;">' + fdDate(o.created_at) + '</span>'
            + '</div>'
            + '</div>'
            + (o.provider && o.provider.user && o.provider.user.phone
                ? '<a class="fd-btn" href="' + fdWaLink(o.provider.user.phone, 'Hola ' + (shop.business_name || '') + ', te contacto por mi pedido ' + o.order_number + '.') + '" target="_blank" rel="noopener" style="margin-left:auto;">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                    + 'Ayuda / Contacto</a>'
                : '')
            + '</div>';

        // Estado en vivo: titulo + ETA
        html += '<div class="fd-live">'
            + '<div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">'
            + '<div style="flex:1;min-width:220px;">'
            + '<div class="fd-eyebrow" style="color:var(--fd-orange);">Estado en tiempo real</div>'
            + '<div class="fd-live-title">' + escapeHtml(fdMyOrdersLiveTitle(o)) + '</div>'
            + '</div>'
            + (eta
                ? '<div class="fd-eta" data-fd-eta="' + Number(o.id) + '"><div class="fd-eyebrow">' + (isTakeaway ? 'Tiempo estimado de preparación' : 'Tiempo estimado de entrega') + '</div>'
                    + '<b><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
                    + escapeHtml(eta) + '</b></div>'
                : '')
            + '</div>';

        // Circuito con los cinco pasos y el repartidor a la vista
        html += fdMyTrackHtml(o);

        // Ficha del repartidor asignado, con contacto directo
        if (driver) {
            html += '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;padding:10px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;">'
                + '<span style="width:34px;height:34px;border-radius:50%;background:#0c2a4d;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex:none;">'
                + escapeHtml(fdInitials(driver.name)) + '</span>'
                + '<div style="min-width:0;">'
                + '<div class="fd-driver-name">' + escapeHtml(driver.name || 'Repartidor') + '</div>'
                + '<div style="font-size:11.5px;color:#64748b;">Repartidor asignado a tu pedido</div>'
                + '</div>'
                + (driver.phone
                    ? '<div style="display:flex;gap:8px;margin-left:auto;flex-wrap:wrap;">'
                        + '<a class="fd-btn" href="tel:' + escapeHtml(String(driver.phone).replace(/[^\d+]/g, '')) + '" title="Llamar al repartidor">'
                            + '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                            + 'Llamar</a>'
                        + '<a class="fd-btn fd-btn-outline" href="' + fdWaLink(driver.phone, 'Hola ' + (driver.name || '') + ', ¿dónde está mi pedido ' + o.order_number + '?') + '" target="_blank" rel="noopener">'
                            + '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'
                            + 'WhatsApp</a>'
                        + '</div>'
                    : '')
                + '</div>';
        }

        html += '</div>';

        // Cuerpo: direccion, pago, productos y totales
        html += '<div style="padding:14px 16px;">';

        var addrHtml = '<div class="fd-block">'
            + '<div style="display:flex;gap:11px;align-items:flex-start;">'
            + '<span class="fd-block-ico">'
            + (isTakeaway
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 13v8h14v-8"/><path d="M9 21v-5h6v5"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>')
            + '</span>'
            + '<div style="min-width:0;">'
            + '<div class="fd-eyebrow">' + (isTakeaway ? 'Retiro en el local' : 'Dirección de entrega') + '</div>'
            + (isTakeaway
                ? '<div style="font-size:14px;font-weight:700;color:#0f172a;margin-top:3px;">'
                    + escapeHtml(shop.business_name || 'Comercio')
                    + '</div>'
                    + '<div style="font-size:12px;color:#64748b;margin-top:2px;">'
                    + escapeHtml(o.pickup_address || 'Coordiná la dirección del local con el comercio.')
                    + '</div>'
                : (address
                    ? '<div style="font-size:14px;font-weight:700;color:#0f172a;margin-top:3px;">'
                        + escapeHtml(address.street) + ' ' + escapeHtml(address.number || '')
                        + (address.floor_apartment ? ', ' + escapeHtml(address.floor_apartment) : '')
                        + '</div>'
                        + '<div style="font-size:12px;color:#64748b;margin-top:2px;">'
                        + (address.postal_code ? 'CP ' + escapeHtml(address.postal_code) : 'Sin código postal')
                        + '</div>'
                        + (address.notes ? '<div style="margin-top:7px;"><span class="fd-note-chip">'
                            + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>'
                            + escapeHtml(address.notes) + '</span></div>'
                        : '')
                    : '<div style="font-size:13px;color:#94a3b8;margin-top:3px;">Sin dirección registrada</div>'))
            + '</div>'
            + '</div>'
            + '</div>';

        var payHtml = '<div class="fd-block">'
            + '<div style="display:flex;gap:11px;align-items:flex-start;flex-wrap:wrap;">'
            + '<span class="fd-block-ico fd-block-ico-green">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>'
            + '</span>'
            + '<div style="min-width:0;">'
            + '<div class="fd-eyebrow">Forma de pago</div>'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:3px;">'
            + '<span style="font-size:14px;font-weight:700;color:#0f172a;">'
            + escapeHtml(FD_PAYMENT_METHOD_LABELS[o.payment_method] || o.payment_method || 'A acordar') + '</span>'
            + fdPaymentBadge(o.payments)
            + '</div>'
            + (o.payment_method === 'efectivo'
                ? '<div style="font-size:12px;color:#64748b;margin-top:2px;">Prepará el importe exacto si podés.</div>'
                : '')
            + '</div>'
            + '</div>'
            + '</div>';

        var showTrack = !isTakeaway && o.status === 'on_the_way' && !!driver;
        var trackData = showTrack ? fdTrackCoords(o) : null;
        if (showTrack) {
            html += '<div class="fd-track-grid">'
                + '<div class="fd-track-left">' + addrHtml + payHtml + '</div>'
                + '<div class="fd-track-right">'
                + (trackData
                    ? '<div class="fd-track-map" id="fd-track-map-' + Number(o.id) + '" data-order="' + Number(o.id) + '"></div>'
                    : '<div class="fd-track-empty">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'
                        + '<b>Mapa no disponible todavía</b>'
                        + '<span>Faltan coordenadas de ubicación para trazar la ruta en vivo.</span>'
                        + '</div>')
                + '</div>'
                + '</div>';
        } else {
            html += addrHtml + payHtml;
        }

        if (o.notes) {
            html += '<div style="margin-top:10px;font-size:12.5px;color:#9a3412;"><b>Nota del pedido:</b> ' + escapeHtml(o.notes) + '</div>';
        }

        html += '<div style="border-top:1px solid #f1f5f9;margin-top:10px;padding-top:4px;">'
            + (o.items || []).map(fdRenderOrderItem).join('')
            + '</div>'
            + '<div style="border-top:1px solid #e5e7eb;margin-top:10px;padding-top:10px;font-size:13px;">'
            + fdTotalRow('Subtotal', fdMoney(o.subtotal), false)
            + (isTakeaway
                ? fdTotalRow('Retiro en el local', 'Sin costo', false)
                : fdTotalRow('Envío', fdMoney(o.delivery_fee), false))
            + fdTotalRow('Descuento', '-' + fdMoney(o.discount), false)
            + '<div class="fd-total-final">'
            + '<span>Total</span><span>' + fdMoney(o.total) + '</span></div>'
            + '</div>'
            + '</div>'
            + '</div>';

        return html;
    }

    // ---------- Seguimiento en vivo con mapa (estilo DiDi) ----------
    var FD_TRACK_URL = window.APP_BASE+"/api/orders";
    var FD_TRACK_MS = 5000;
    var fdTrackMaps = {};

    function fdTrackNum(v) {
        var n = parseFloat(v);
        return isFinite(n) ? n : null;
    }

    // Origen (comercio) y destino (cliente) si hay coordenadas para trazar.
    // Nota: la relacion llega serializada como primary_address (snake_case).
    function fdTrackCoords(o) {
        if (!o || o.status !== 'on_the_way' || !o.driver || o.delivery_type === 'takeaway') return null;
        var usr = (o.provider && o.provider.user) || null;
        var pa = usr ? (usr.primary_address || usr.primaryAddress) : null;
        var ad = o.address;
        var oLat = pa ? fdTrackNum(pa.latitude) : null;
        var oLng = pa ? fdTrackNum(pa.longitude) : null;
        var dLat = ad ? fdTrackNum(ad.latitude) : null;
        var dLng = ad ? fdTrackNum(ad.longitude) : null;
        if (oLat === null || oLng === null || dLat === null || dLng === null) return null;
        return { origin: [oLat, oLng], dest: [dLat, dLng] };
    }

    function fdTrackFirstName(name) {
        return String(name || 'Tu repartidor').split(' ')[0];
    }

    function fdTrackClockSvg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    }

    function fdTrackHomeSvg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>';
    }

    function fdTrackInit(orderId, o) {
        var el = document.getElementById('fd-track-map-' + orderId);
        var c = fdTrackCoords(o);
        if (!el || !c) return;
        if (typeof L === 'undefined') {
            el.innerHTML = '';
            var msg = document.createElement('div');
            msg.className = 'fd-track-empty';
            msg.style.height = '100%';
            msg.textContent = 'No se pudo cargar el mapa (sin conexión a la librería).';
            el.appendChild(msg);
            return;
        }
        var shopName = (o.provider && o.provider.business_name) || 'Comercio';
        var drvName = (o.driver && o.driver.name) || '';
        var map = L.map(el, { scrollWheelZoom: false }).setView(c.origin, 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        var originIcon = L.divIcon({
            className: 'fd-track-divicon',
            html: '<div class="fd-pin fd-pin-origin">' + escapeHtml(fdInitials(shopName)) + '</div>',
            iconSize: [38, 38], iconAnchor: [19, 19]
        });
        var destIcon = L.divIcon({
            className: 'fd-track-divicon',
            html: '<div class="fd-pin fd-pin-dest">' + fdTrackHomeSvg() + '</div>',
            iconSize: [38, 38], iconAnchor: [19, 19]
        });
        var driverIcon = L.divIcon({
            className: 'fd-track-divicon fd-track-driver',
            html: '<div class="fd-pin fd-pin-driver">🛵</div><div class="fd-drv-label"><b>' + escapeHtml(fdTrackFirstName(drvName)) + '</b></div>',
            iconSize: [44, 44], iconAnchor: [22, 22]
        });
        L.marker(c.origin, { icon: originIcon, title: shopName }).addTo(map).bindPopup(escapeHtml(shopName));
        L.marker(c.dest, { icon: destIcon, title: 'Tu domicilio' }).addTo(map).bindPopup('Tu domicilio');
        var driverMk = L.marker(c.origin, { icon: driverIcon, title: drvName }).addTo(map);
        var route = L.polyline([c.origin, c.origin, c.dest], {
            color: '#ea580c', weight: 5, opacity: 0.9, lineJoin: 'round'
        }).addTo(map);
        map.fitBounds(L.latLngBounds([c.origin, c.dest]).pad(0.3));
        fdTrackMaps[orderId] = {
            map: map, driver: driverMk, route: route,
            origin: c.origin, dest: c.dest, timer: null
        };
        fdTrackTick(orderId);
        fdTrackMaps[orderId].timer = setInterval(function () { fdTrackTick(orderId); }, FD_TRACK_MS);
    }

    function fdTrackTick(orderId) {
        var st = fdTrackMaps[orderId];
        if (!st || !st.map) return;
        if (document.hidden) return;
        fdFetchJson(FD_TRACK_URL + '/' + orderId + '/driver-location').then(function (res) {
            var d = res && res.data;
            if (!res.ok || !d || !d.success) return;
            if (d.status !== 'on_the_way') {
                fdTrackStop(orderId);
                if (typeof loadMyOrders === 'function') loadMyOrders(true);
                return;
            }
            if (!d.map_available || !d.driver_location) return;
            var pos = [parseFloat(d.driver_location.latitude), parseFloat(d.driver_location.longitude)];
            st.driver.setLatLng(pos);
            st.route.setLatLngs([st.origin, pos, st.dest]);
            var first = d.driver ? fdTrackFirstName(d.driver.name) : 'Tu repartidor';
            var mins = (d.eta_minutos !== null && d.eta_minutos !== undefined) ? d.eta_minutos : null;
            var iconEl = st.driver.getElement ? st.driver.getElement() : null;
            if (iconEl) {
                var b = iconEl.querySelector('.fd-drv-label b');
                if (b) b.textContent = mins !== null ? (first + ' - ' + mins + ' min') : first;
            }
            if (mins !== null) {
                var etaB = document.querySelector('.fd-myorders-card[data-order-id="' + orderId + '"] [data-fd-eta] b');
                if (etaB) etaB.innerHTML = fdTrackClockSvg() + 'Llega en ' + mins + ' min';
            }
        });
    }

    function fdTrackStop(orderId) {
        var st = fdTrackMaps[orderId];
        if (!st) return;
        if (st.timer) clearInterval(st.timer);
        try { if (st.map) st.map.remove(); } catch (e) {}
        delete fdTrackMaps[orderId];
    }

    // Crea los mapas de los pedidos en camino y limpia los que ya no estan.
    function fdTrackSync(list) {
        var ids = {};
        (list || []).forEach(function (o) { if (fdTrackCoords(o)) ids[Number(o.id)] = o; });
        Object.keys(fdTrackMaps).forEach(function (id) {
            if (!ids[id]) { fdTrackStop(Number(id)); return; }
            if (!document.getElementById('fd-track-map-' + id)) fdTrackStop(Number(id));
        });
        Object.keys(ids).forEach(function (id) {
            if (!fdTrackMaps[Number(id)]) fdTrackInit(Number(id), ids[id]);
        });
    }

    // Tarjeta compacta del historial: local, estado, importe, resumen de
    // productos, calificacion y boton para repetir el pedido.
    function fdRenderMyOrderHistory(o) {
        var shop = o.provider || {};
        var items = o.items || [];
        var resume = items.slice(0, 3).map(function (it) {
            return Number(it.quantity) + '× ' + it.product_name + (it.variant_name ? ' (' + it.variant_name + ')' : '');
        }).join(' + ') + (items.length > 3 ? ' +' + (items.length - 3) + ' más' : '');

        var html = '<div class="fd-order fd-myorders-card" data-order-id="' + Number(o.id) + '">';

        html += '<div class="fd-hist-head">'
            + '<div class="fd-shop-ico" style="width:38px;height:38px;border-radius:9px;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>'
            + '</div>'
            + '<div style="min-width:0;">'
            + '<div class="fd-shop-name" style="font-size:14px;">' + escapeHtml(shop.business_name || 'Comercio') + '</div>'
            + '<div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap;">'
            + '<span style="font-size:11.5px;color:#94a3b8;">' + fdDate(o.created_at) + '</span>'
            + '<span style="color:#e2e8f0;">·</span>'
            + '<span class="fd-onum">' + escapeHtml(o.order_number) + '</span>'
            + '<button type="button" class="fd-icon-btn" style="width:22px;height:22px;border:none;background:transparent;" onclick="fdMyOrderCopyNumber(' + o.id + ')" title="Copiar número de pedido" aria-label="Copiar número de pedido">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>'
            + '</button>'
            + '</div>'
            + '</div>'
            + '<div style="margin-left:auto;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
            + fdStatusBadge(o.status)
            + (o.status === 'delivered' ? '<span style="font-size:14px;font-weight:800;color:#0f172a;">' + fdMoney(o.total) + '</span>' : '')
            + '</div>'
            + '</div>';

        html += '<div class="fd-hist-resume">'
            + '<span style="min-width:0;">' + escapeHtml(resume || 'Sin productos registrados') + '</span>'
            + (o.status === 'delivered'
                ? (o.customer_rated && o.customer_rating
                    ? '<span style="margin-left:auto;color:#92400e;font-weight:700;white-space:nowrap;"><span class="fd-stars">&#9733;</span> Calificaste: ' + Number(o.customer_rating.stars) + '.0 (' + escapeHtml(fdRatingLabel(o.customer_rating.stars)) + ')</span>'
                    : '<button type="button" onclick="fdMyOrderOpenRate(' + o.id + ')" style="margin-left:auto;background:none;border:none;padding:0;font-size:12.5px;font-weight:700;color:var(--fd-orange);cursor:pointer;white-space:nowrap;">Calificar pedido</button>')
                : '')
            + '</div>';

        html += '<div class="fd-hist-foot">'
            + '<span style="font-size:11.5px;color:#94a3b8;">' + escapeHtml(FD_PAYMENT_METHOD_LABELS[o.payment_method] || o.payment_method || 'Pago a acordar') + '</span>'
            + '<button type="button" class="fd-btn fd-btn-outline" onclick="fdMyOrderRepeat(' + o.id + ')" title="Volver a pedir estos productos">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>'
            + 'Repetir este pedido</button>'
            + '</div>'
            + fdMyOrderRatePanel(o)
            + '</div>';

        return html;
    }

    function fdRatingLabel(stars) {
        var labels = { 5: 'Excelente', 4: 'Muy bueno', 3: 'Bueno', 2: 'Regular', 1: 'Malo' };
        return labels[Number(stars)] || '';
    }

    function fdMyOrderCopyNumber(orderId) {
        var order = null;
        fdMyOrdersAll.forEach(function (o) { if (Number(o.id) === Number(orderId)) order = o; });
        if (!order) return;

        var done = function () { fdToast('Número de pedido copiado.', false); };
        var text = order.order_number || '';

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { fdCopyFallback(text, done); });
        } else {
            fdCopyFallback(text, done);
        }
    }

    // Abre y despliega el panel de calificacion de un pedido del historial.
    function fdMyOrderOpenRate(orderId) {
        var cardEl = document.querySelector('.fd-myorders-card[data-order-id="' + orderId + '"]');
        if (!cardEl) return;
        var panel = cardEl.querySelector('.fd-myorders-panel');
        if (!panel) return;
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    }

    // "Repetir este pedido": lleva los productos del pedido al carrito de la
    // tienda del local. Lo que ya no este disponible se avisa y se omite.
    function fdMyOrderRepeat(orderId) {
        var order = null;
        fdMyOrdersAll.forEach(function (o) { if (Number(o.id) === Number(orderId)) order = o; });
        if (!order || !order.provider) return;

        var items = (order.items || []).map(function (it) {
            return { product_id: it.product_id, variant_id: it.variant_id, quantity: it.quantity };
        });
        if (!items.length) {
            fdToast('Este pedido no tiene productos para repetir.', true);
            return;
        }

        fdShopRepeatQueue = items;
        openFdShop(order.provider.id);
    }

    function fdMyOrderRatePanel(o) {
        var send = 'fdMyOrderSendRate(this.closest(\'.fd-myorders-panel\'))';
        var hide = 'this.closest(\'.fd-myorders-panel\').style.display=\'none\'';
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += '<button type="button" onclick="fdMyOrderStar(this,' + i + ')" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;font-size:22px;line-height:1;">★</button>';
        }
        return '<div class="fd-myorders-panel" data-order-id="' + o.id + '" data-stars="0" style="display:none;border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px;">' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Puntuación al comercio</label>' +
            '<div class="fd-myorders-stars" style="display:flex;gap:4px;">' + stars + '</div>' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Comentario (opcional)</label>' +
            '<textarea class="fd-myorders-comment" rows="2" placeholder="Excelente atención y entrega puntual..." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>' +
            '<div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">' +
                '<button type="button" class="fd-myorders-send" onclick="' + send + '" style="background:#D24C19;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar calificación</button>' +
                '<button type="button" onclick="' + hide + '" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cerrar</button>' +
            '</div>' +
        '</div>';
    }

    function fdMyOrderStar(btn, stars) {
        var panel = btn.closest('.fd-myorders-panel');
        if (!panel) return;
        panel.setAttribute('data-stars', String(stars));
        Array.prototype.forEach.call(panel.querySelectorAll('.fd-myorders-stars button'), function (b, idx) {
            b.style.color = idx < stars ? '#D24C19' : '#d1d5db';
        });
    }

    function fdMyOrderSendRate(panel) {
        if (!panel) return;
        var orderId = panel.getAttribute('data-order-id');
        var stars = parseInt(panel.getAttribute('data-stars') || '0', 10);
        if (stars < 1) { fdToast('Elegí una puntuación de 1 a 5 estrellas.', true); return; }
        var comment = (panel.querySelector('.fd-myorders-comment') || {}).value || '';
        var btn = panel.querySelector('.fd-myorders-send');

        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = 'Enviando...'; }

        var meta = document.querySelector('meta[name="csrf-token"]');
        fetch(window.APP_BASE+"/api/orders"+"/" + orderId + '/rate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': meta ? meta.content : ''
            },
            body: JSON.stringify({ stars: stars, comment: comment })
        })
        .then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (json) {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            if (!json || !json.success) {
                fdToast(json.message || 'No se pudo registrar la calificación.', true);
                return;
            }
            fdToast('¡Gracias por calificar al comercio!', false);
            panel.style.display = 'none';
            loadMyOrders(true);
        })
        .catch(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            fdToast('Error de conexión al calificar.', true);
        });
    }

    function fdOrderRatePanel(o) {
        var send = 'fdOrderSendRate(this.closest(\'.fd-orders-panel\'))';
        var hide = 'this.closest(\'.fd-orders-panel\').style.display=\'none\'';
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += '<button type="button" onclick="fdOrderStar(this,' + i + ')" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;font-size:22px;line-height:1;">★</button>';
        }
        return '<div class="fd-orders-panel" data-order-id="' + o.id + '" data-stars="0" style="display:none;border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px;">' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Puntuación al cliente</label>' +
            '<div class="fd-orders-stars" style="display:flex;gap:4px;">' + stars + '</div>' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Comentario (opcional)</label>' +
            '<textarea class="fd-orders-comment" rows="2" placeholder="Cliente puntual y amable..." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>' +
            '<div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">' +
                '<button type="button" class="fd-orders-send" onclick="' + send + '" style="background:#D24C19;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar calificación</button>' +
                '<button type="button" onclick="' + hide + '" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cerrar</button>' +
            '</div>' +
        '</div>';
    }

    function fdOrderStar(btn, stars) {
        var panel = btn.closest('.fd-orders-panel');
        if (!panel) return;
        panel.setAttribute('data-stars', String(stars));
        Array.prototype.forEach.call(panel.querySelectorAll('.fd-orders-stars button'), function (b, idx) {
            b.style.color = idx < stars ? '#D24C19' : '#d1d5db';
        });
    }

    function fdOrderSendRate(panel) {
        if (!panel) return;
        var orderId = panel.getAttribute('data-order-id');
        var stars = parseInt(panel.getAttribute('data-stars') || '0', 10);
        if (stars < 1) { fdToast('Elegí una puntuación de 1 a 5 estrellas.', true); return; }
        var comment = (panel.querySelector('.fd-orders-comment') || {}).value || '';
        var btn = panel.querySelector('.fd-orders-send');

        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = 'Enviando...'; }

        var meta = document.querySelector('meta[name="csrf-token"]');
        fetch(window.APP_BASE+"/api/orders"+"/" + orderId + '/rate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': meta ? meta.content : ''
            },
            body: JSON.stringify({ stars: stars, comment: comment })
        })
        .then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (json) {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            if (!json || !json.success) {
                fdToast(json.message || 'No se pudo registrar la calificación.', true);
                return;
            }
            fdToast('¡Gracias por calificar al cliente!', false);
            panel.style.display = 'none';
            loadProviderOrders(true);
        })
        .catch(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            fdToast('Error de conexión al calificar.', true);
        });
    }

    function fdOrderOpenRate(orderId) {
        var cardEl = document.querySelector('.fd-orders-card[data-order-id="' + orderId + '"]');
        if (!cardEl) return;
        var panel = cardEl.querySelector('.fd-orders-panel');
        if (!panel) return;
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    }

    // ============ SEGUIMIENTO EN VIVO ============
    // Cada seccion de pedidos se refresca sola mientras este a la vista, para
    // que el estado del pedido cambie sin recargar la pagina.

    var FD_POLL_SECTIONS = {
        'mis-pedidos': function () { loadMyOrders(true); },
        'pedidos': function () { loadProviderOrders(true); },
        'comercios': function () { loadComercios(true); },
        'ordenes-gestion': function () { if (window.fdOgLoad) window.fdOgLoad(true); }
    };
    var FD_POLL_MS = 8000;
    var fdPollTimer = null;
    var fdPollKey = null;

    function fdStopPolling() {
        if (fdPollTimer) clearInterval(fdPollTimer);
        fdPollTimer = null;
        fdPollKey = null;
    }

    function fdStartPolling(key) {
        if (fdPollKey === key) return;
        fdStopPolling();
        if (!FD_POLL_SECTIONS[key]) return;

        fdPollKey = key;
        fdPollTimer = setInterval(function () {
            if (document.hidden) return;
            var section = document.getElementById('dash-' + fdPollKey);
            if (!section || section.style.display === 'none') { fdStopPolling(); return; }
            FD_POLL_SECTIONS[fdPollKey]();
        }, FD_POLL_MS);
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && fdPollKey && FD_POLL_SECTIONS[fdPollKey]) FD_POLL_SECTIONS[fdPollKey]();
    });

    // ---------- Bloqueo global por direccion obligatoria ----------
    var fdDireccionBloqueada = false;
    var fdDireccionVerificada = false;

    function verificarDireccionCompleta(usuario) {
        var a = usuario && (usuario.primary_address || usuario.address || usuario.direccion || usuario);
        if (!a) return false;
        var pais = a.country_id || a.country || (a.country && a.country.id) || '';
        var provincia = a.province_id || a.province || (a.province && a.province.id) || '';
        var calle = String(a.street || '').trim();
        var numero = String(a.number || '').trim();
        var postal = String(a.postal_code || '').trim();
        return !!(pais && provincia && calle && numero && postal);
    }

    function fdDireccionOcultarNavegacion(ocultar) {
        ['nav .nav-dropdown', 'nav .nav-separator', '#menu-toggle', '#mobile-menu', '.footer-group-icons'].forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                el.style.display = ocultar ? 'none' : '';
            });
        });
        document.querySelectorAll('nav .nav-link').forEach(function (el) {
            el.disabled = !!ocultar;
        });
        var nav = document.querySelector('nav');
        if (nav) nav.setAttribute('aria-hidden', ocultar ? 'true' : 'false');
    }

    function fdForzarVistaDireccion() {
        fdDireccionBloqueada = true;
        fdDireccionOcultarNavegacion(true);
        showDashSection('perfil');
        if (typeof showProfilePanel === 'function') showProfilePanel('direccion');
    }

    function fdDesbloquearDireccion() {
        fdDireccionBloqueada = false;
        fdDireccionVerificada = true;
        fdDireccionOcultarNavegacion(false);
    }

    function fdVerificarDireccionGuard() {
        fetch(window.APP_BASE+"/profile/address", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            var address = (data && data.address) || null;
            fdDireccionVerificada = true;
            if (!verificarDireccionCompleta(address)) {
                fdForzarVistaDireccion();
            } else {
                fdDesbloquearDireccion();
            }
        })
        .catch(function () {});
    }

    function fdPaisAutomatico() {
        var tz = '';
        try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
        var lang = String(navigator.language || '').toLowerCase();
        if (tz.indexOf('Europe/') === 0 || /(^|-)es(-|$)/.test(lang)) return 'España';
        return 'Argentina';
    }

    function fdBloquearPais(sel, paisId) {
        var hidden = document.getElementById('addr-country-id');
        if (hidden) hidden.value = paisId || '';
        if (!sel) return;
        sel.disabled = true;
        sel.style.cursor = 'not-allowed';
        sel.style.backgroundColor = '#f1f3f5';
        sel.style.color = '#6b7280';
    }

    function fdSeleccionarPais(sel, nombre) {
        if (!sel || !nombre) return '';
        var normal = function (v) {
            return String(v || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        };
        var target = normal(nombre);
        for (var i = 0; i < sel.options.length; i++) {
            if (normal(sel.options[i].textContent) === target) {
                sel.value = sel.options[i].value;
                fdBloquearPais(sel, sel.value);
                if (typeof loadRegionsForCountry === 'function' && sel.value) loadRegionsForCountry(sel.value, null);
                return sel.value;
            }
        }
        return '';
    }

    function fdInicializarPaisDireccion(sel, tienePaisGuardado) {
        if (!sel) return;

        if (tienePaisGuardado && sel.value) {
            fdBloquearPais(sel, sel.value);
            return;
        }

        var aplicar = function (nombre) {
            if (sel.value) {
                fdBloquearPais(sel, sel.value);
                return;
            }
            var elegido = fdSeleccionarPais(sel, nombre);
            if (!elegido) fdSeleccionarPais(sel, fdPaisAutomatico());
        };

        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timer = controller ? setTimeout(function () { controller.abort(); }, 4000) : null;

        fetch('https://ipapi.co/json/', {
            headers: { 'Accept': 'application/json' },
            signal: controller ? controller.signal : undefined
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (timer) clearTimeout(timer);
            aplicar((data && (data.country_name || data.country)) || fdPaisAutomatico());
        })
        .catch(function () {
            if (timer) clearTimeout(timer);
            aplicar(fdPaisAutomatico());
        });
    }

    // Sidebar lateral acoplado a la pantalla Gestión de Usuarios (solo Admin):
    // mueve el nodo sin duplicar IDs y lo devuelve a su lugar al salir.
    var umSidebarHome = null;
    function dockProfileSidebar(dock) {
        var sidebar = document.getElementById('profile-sidebar');
        var slot = document.getElementById('um-sidebar-slot');
        var wrap = document.getElementById('um-with-sidebar');
        if (!sidebar || !slot || !wrap) return;
        if (!umSidebarHome) {
            umSidebarHome = { parent: sidebar.parentNode, next: sidebar.nextSibling };
        }
        if (dock) {
            if (sidebar.parentNode !== slot) slot.appendChild(sidebar);
            slot.style.display = '';
            wrap.classList.add('docked');
        } else {
            if (sidebar.parentNode !== umSidebarHome.parent) {
                umSidebarHome.parent.insertBefore(sidebar, umSidebarHome.next);
            }
            slot.style.display = 'none';
            wrap.classList.remove('docked');
        }
    }


    var allPages = [];
    var allPageModules = [];
    var deletePageId = null;

    function loadPageModules(callback) {
        fetch(window.APP_BASE+"/modules", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allPageModules = data;
            var select = document.getElementById('page-module-id');
            select.innerHTML = '<option value="">-- Seleccionar módulo --</option>';
            data.forEach(function(m) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.description;
                select.appendChild(opt);
            });
            fillPageModuleFilter();
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    function fillPageModuleFilter() {
        var select = document.getElementById('page-module-filter');
        if (!select) return;

        var current = select.value;
        select.innerHTML = '<option value="">Todos los módulos</option>';
        allPageModules.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.description;
            select.appendChild(opt);
        });

        select.value = current || '';
        if (select.selectedIndex === -1) select.value = '';
    }

    function loadPages() {
        document.getElementById('pages-loading').style.display = 'block';
        document.getElementById('pages-empty').style.display = 'none';
        document.getElementById('pages-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/pages", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allPages = data;
            if (allPageModules.length === 0) {
                loadPageModules();
            } else {
                fillPageModuleFilter();
            }
            filterPages();
        })
        .catch(function() {
            document.getElementById('pages-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar páginas.</p>';
        });
    }

    function renderPages(pages) {
        var tbody = document.getElementById('pages-tbody');
        var loading = document.getElementById('pages-loading');
        var empty = document.getElementById('pages-empty');
        var tableWrap = document.getElementById('pages-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (pages.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        pages.forEach(function(p) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', p.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(p.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editPage(' + p.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openPageDelete(' + p.id + ', \'' + escapeHtml(p.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterPages() {
        var q = document.getElementById('page-search').value.toLowerCase();
        var moduleId = document.getElementById('page-module-filter').value;
        var filtered = allPages.filter(function(p) {
            var matchesText = !q || (p.description && p.description.toLowerCase().indexOf(q) !== -1);
            var matchesModule = !moduleId || String(p.module_id || '') === moduleId;
            return matchesText && matchesModule;
        });
        renderPages(filtered);
    }

    function openPageModal(id) {
        var p = id ? allPages.find(function(pg) { return pg.id === id; }) : null;
        document.getElementById('page-id').value = id || '';
        document.getElementById('page-description').value = p ? p.description : '';
        document.getElementById('page-url').value = p && p.url ? p.url : '';
        document.getElementById('page-description-error').style.display = 'none';
        document.getElementById('page-module-error').style.display = 'none';
        document.getElementById('page-url-error').style.display = 'none';
        document.getElementById('page-modal-title').textContent = id ? 'Editar Página' : 'Nueva Página';
        document.getElementById('page-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';

        var open = function() {
            if (p && p.module_id) {
                document.getElementById('page-module-id').value = p.module_id;
            }
            document.getElementById('page-modal-overlay').style.display = 'flex';
            document.getElementById('page-description').focus();
        };

        if (allPageModules.length === 0) {
            loadPageModules(open);
        } else {
            open();
        }
    }

    function closePageModal() {
        document.getElementById('page-modal-overlay').style.display = 'none';
    }

    function editPage(id) {
        openPageModal(id);
    }

    function submitPage(e) {
        e.preventDefault();
        var id = document.getElementById('page-id').value;
        var desc = document.getElementById('page-description').value.trim();
        var urlVal = document.getElementById('page-url').value.trim();
        var moduleId = document.getElementById('page-module-id').value;
        var descError = document.getElementById('page-description-error');
        var moduleError = document.getElementById('page-module-error');
        var submitBtn = document.getElementById('page-submit-btn');

        descError.style.display = 'none';
        moduleError.style.display = 'none';

        if (!desc) {
            descError.textContent = 'La descripción es obligatoria.';
            descError.style.display = 'block';
            return;
        }

        if (!moduleId) {
            moduleError.textContent = 'Seleccione un módulo.';
            moduleError.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var apiUrl = id ? window.APP_BASE+"/pages"+"/" + id : window.APP_BASE+"/pages";
        var method = id ? 'PUT' : 'POST';

        fetch(apiUrl, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, url: urlVal, module_id: moduleId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] :
                          data.errors.module_id ? data.errors.module_id[0] :
                          data.errors.url ? data.errors.url[0] : 'Error de validación.';
                if (data.errors.module_id) {
                    moduleError.textContent = msg;
                    moduleError.style.display = 'block';
                } else {
                    descError.textContent = msg;
                    descError.style.display = 'block';
                }
                return;
            }

            if (data.success) {
                closePageModal();
                loadPages();
            } else {
                descError.textContent = data.message || 'Error al guardar.';
                descError.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            descError.textContent = 'Error de conexión.';
            descError.style.display = 'block';
        });
    }

    function openPageDelete(id, name) {
        deletePageId = id;
        document.getElementById('page-delete-name').textContent = name;
        document.getElementById('page-delete-warning').style.display = 'none';
        document.getElementById('page-delete-overlay').style.display = 'flex';
    }

    function closePageDelete() {
        document.getElementById('page-delete-overlay').style.display = 'none';
        deletePageId = null;
    }

    function confirmDeletePage() {
        if (!deletePageId) return;
        var btn = document.getElementById('page-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/pages"+"/" + deletePageId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closePageDelete();
                loadPages();
            } else {
                var warn = document.getElementById('page-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('page-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allTypeUsers = [];
    var deleteTypeUserId = null;

    function loadTypeUsers() {
        document.getElementById('typeusers-loading').style.display = 'block';
        document.getElementById('typeusers-empty').style.display = 'none';
        document.getElementById('typeusers-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/type-users", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allTypeUsers = data;
            renderTypeUsers(data);
        })
        .catch(function() {
            document.getElementById('typeusers-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar tipos de usuario.</p>';
        });
    }

    function renderTypeUsers(typeUsers) {
        var tbody = document.getElementById('typeusers-tbody');
        var loading = document.getElementById('typeusers-loading');
        var empty = document.getElementById('typeusers-empty');
        var tableWrap = document.getElementById('typeusers-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (typeUsers.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        typeUsers.forEach(function(t) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', t.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(t.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editTypeUser(' + t.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openTypeUserDelete(' + t.id + ', \'' + escapeHtml(t.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterTypeUsers() {
        var q = document.getElementById('typeuser-search').value.toLowerCase();
        var filtered = allTypeUsers.filter(function(t) {
            return t.description.toLowerCase().indexOf(q) !== -1;
        });
        renderTypeUsers(filtered);
    }

    function openTypeUserModal(id, description) {
        document.getElementById('typeuser-id').value = id || '';
        document.getElementById('typeuser-description').value = description || '';
        document.getElementById('typeuser-description-error').style.display = 'none';
        document.getElementById('typeuser-modal-title').textContent = id ? 'Editar Tipo de Usuario' : 'Nuevo Tipo de Usuario';
        document.getElementById('typeuser-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('typeuser-modal-overlay').style.display = 'flex';
        document.getElementById('typeuser-description').focus();
    }

    function closeTypeUserModal() {
        document.getElementById('typeuser-modal-overlay').style.display = 'none';
    }

    function editTypeUser(id) {
        var t = allTypeUsers.find(function(tu) { return tu.id === id; });
        if (t) openTypeUserModal(t.id, t.description);
    }

    function submitTypeUser(e) {
        e.preventDefault();
        var id = document.getElementById('typeuser-id').value;
        var desc = document.getElementById('typeuser-description').value.trim();
        var errorEl = document.getElementById('typeuser-description-error');
        var submitBtn = document.getElementById('typeuser-submit-btn');

        errorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/type-users"+"/" + id : window.APP_BASE+"/type-users";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeTypeUserModal();
                loadTypeUsers();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openTypeUserDelete(id, name) {
        deleteTypeUserId = id;
        document.getElementById('typeuser-delete-name').textContent = name;
        document.getElementById('typeuser-delete-warning').style.display = 'none';
        document.getElementById('typeuser-delete-overlay').style.display = 'flex';
    }

    function closeTypeUserDelete() {
        document.getElementById('typeuser-delete-overlay').style.display = 'none';
        deleteTypeUserId = null;
    }

    function confirmDeleteTypeUser() {
        if (!deleteTypeUserId) return;
        var btn = document.getElementById('typeuser-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/type-users"+"/" + deleteTypeUserId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeTypeUserDelete();
                loadTypeUsers();
            } else {
                var warn = document.getElementById('typeuser-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('typeuser-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allUsers = [];
    var deleteUserId = null;
    var selectedUser = null;
    var userSelectScope = 'all';
    var userCurrentPage = 1;
    var userPerPage = 10;
    var allUserStatuses = [];
    var deleteUserStatusId = null;

    // Gestion de usuarios de la cuenta (pantalla Usuarios).
    var umSelectedUserId = null;
    var umSelectedModuleId = null;
    var umDraftUserId = null;
    var umDraftPages = {};
    var umOriginalPages = {};
    var umSavingPermissions = false;

    // Contexto de administración: Admin gestionando a otro usuario (impersonación).
    // Se persiste en sessionStorage para que el sidebar lo conserve al navegar.
    var umManagedUserId = null;
    var umManagedProfile = null;
    var umManagedAccountEmail = null;
    try {
        umManagedUserId = window.sessionStorage.getItem('pideaca_managed_user_id');
        umManagedAccountEmail = window.sessionStorage.getItem('pideaca_managed_email');
    } catch (e) { umManagedUserId = null; umManagedAccountEmail = null; }
    function umManagedId() {
        return (typeof actorIsAdmin !== 'undefined' && actorIsAdmin && umManagedUserId) ? umManagedUserId : null;
    }
    function umAdminUserUrl(path) {
        return window.APP_BASE+"/admin/users"+"/" + umManagedUserId + path;
    }
    function umSetManagedUser(id, email) {
        umManagedUserId = (id === null || id === undefined || id === '') ? null : String(id);
        if (email) {
            umManagedAccountEmail = String(email).toLowerCase();
        } else if (!umManagedUserId) {
            umManagedAccountEmail = null;
        }
        try {
            if (umManagedUserId) {
                window.sessionStorage.setItem('pideaca_managed_user_id', umManagedUserId);
                if (umManagedAccountEmail) window.sessionStorage.setItem('pideaca_managed_email', umManagedAccountEmail);
            } else {
                window.sessionStorage.removeItem('pideaca_managed_user_id');
                window.sessionStorage.removeItem('pideaca_managed_email');
            }
            var url = new URL(window.location.href);
            if (umManagedUserId) url.searchParams.set('managed', umManagedUserId);
            else url.searchParams.delete('managed');
            window.history.replaceState(history.state, '', url.toString());
        } catch (e) {}
        umManagedProfile = null;
        profileDireccionCargada = false;
        umRenderManagedBadge();
        umRenderTopNavForManaged();
        umRefreshManagedScope();
        if (typeof umApplyManagedSidebar === 'function') umApplyManagedSidebar();
        if (typeof loadProfile === 'function' && document.getElementById('dash-perfil') && document.getElementById('dash-perfil').style.display !== 'none') loadProfile();
    }
    function umManagedEmail() {
        if (!umManagedId()) return null;
        var u = (allUsers || []).find(function(x) { return String(x.id) === String(umManagedId()); });
        if (u && u.email) return String(u.email).toLowerCase();
        return umManagedAccountEmail ? String(umManagedAccountEmail).toLowerCase() : null;
    }
    // Rol efectivo de la cuenta activa: gestionada si hay impersonación, si no la propia.
    // Las secciones comerciales solo aplican a Prestador/Comercio/Admin.
    function umManagedCanConfigureBusiness() {
        // Rol efectivo en tiempo real: gestionada si hay impersonación, si no la sesión.
        // No depende del flag server-side (queda obsoleto al cambiar de gestionado sin recargar).
        var mid = umManagedId();
        if (!mid) return profilePuedeConfigurarNegocio;
        var mu = (typeof umManagedProfile !== 'undefined') ? umManagedProfile : null;
        if (!mu) {
            var cached = (allUsers || []).find(function(x) { return String(x.id) === String(mid); });
            if (!cached) return profilePuedeConfigurarNegocio;
            mu = cached;
        }
        var t = mu.type_user || mu.typeUser;
        var d = String((t && (t.description || t.status)) || '').trim().toLowerCase();
        return d === 'prestador' || d === 'comercio' || d === 'comerciante' || d === 'admin' || d === 'administrador';
    }
    function umApplyManagedSidebar() {
        var can = umManagedCanConfigureBusiness();
        ['sidebar-link-negocio', 'sidebar-link-horarios', 'sidebar-link-servicios', 'sidebar-link-repartidores', 'sidebar-link-retiros', 'sidebar-link-imagenes', 'sidebar-link-categorias'].forEach(function(id) {
            var link = document.getElementById(id);
            if (link) link.style.display = can ? '' : 'none';
        });
    }
    function umRefreshManagedScope() {
        var id = umManagedId();
        if (!id) return;
        var apply = function(user) {
            if (user && user.email) {
                umManagedAccountEmail = String(user.email).toLowerCase();
                try { window.sessionStorage.setItem('pideaca_managed_email', umManagedAccountEmail); } catch (e) {}
            }
            if (user) umManagedProfile = user;
            if (typeof umApplyManagedSidebar === 'function') umApplyManagedSidebar();
            if (typeof renderUserManagement === 'function') renderUserManagement();
            if (typeof fillUserEditForm === 'function') {
                var current = (typeof getUserManagementSelectedUser === 'function') ? getUserManagementSelectedUser() : null;
                if (current) fillUserEditForm(current);
            }
        };
        var cached = (allUsers || []).find(function(x) { return String(x.id) === String(id); });
        if (cached) { apply(cached); return; }
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(window.APP_BASE+"/admin/users"+"/" + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(fresh) {
            if (!fresh || fresh.error) return;
            var idx = (allUsers || []).findIndex(function(x) { return String(x.id) === String(fresh.id); });
            if (idx !== -1) allUsers[idx] = fresh; else allUsers.push(fresh);
            apply(fresh);
        })
        .catch(function() {});
    }
    function umExitManagedUser() {
        umSetManagedUser(null);
        umRestoreTopNav();
        showUserPermissionMsg('', true);
        if (typeof renderUserManagement === 'function') renderUserManagement();
        if (typeof loadProfile === 'function') loadProfile();
    }
    function umRenderManagedBadge() {
        var badge = document.getElementById('profile-managed-badge');
        var nameEl = document.getElementById('profile-managed-name');
        var roleEl = document.getElementById('profile-managed-role');
        if (!badge) return;
        var id = umManagedId();
        if (!id) { badge.style.display = 'none'; return; }
        var done = function(u) {
            if (nameEl) nameEl.textContent = u ? (u.name || '') : '';
            if (roleEl) roleEl.textContent = getUserRoleLabel(u);
            badge.style.display = '';
        };
        var cached = (allUsers || []).find(function(x) { return String(x.id) === String(id); });
        if (cached) { done(cached); return; }
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(window.APP_BASE+"/admin/users"+"/" + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(u) { done(u && !u.error ? u : null); })
        .catch(function() { done(null); });
    }
    function umRestoreManagedUser() {
        if (umManagedId()) { umRenderManagedBadge(); umRenderTopNavForManaged(); umRefreshManagedScope(); }
    }
    // Navbar superior según el usuario en gestión (activeTargetUser ?? currentUser).
    // Filtra módulos/páginas por permisos activos; oculta el módulo sin hijos.
    function umRenderTopNavForManaged() {
        var id = umManagedId();
        if (!id) { umRestoreTopNav(); return; }
        var done = function(u) {
            if (!u) { umRestoreTopNav(); return; }
            var allowed = {};
            ((u && u.pages) || []).forEach(function(pg) { allowed[String(pg.id)] = true; });
            umRebuildTopNav(allowed);
        };
        var cached = (allUsers || []).find(function(x) { return String(x.id) === String(id); });
        if (cached && cached.pages) { done(cached); return; }
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(window.APP_BASE+"/admin/users"+"/" + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(u) { done(u && !u.error ? u : null); })
        .catch(function() { umRestoreTopNav(); });
    }
    var umNavIcons = {};
    var umSessionAllowedIds = null;
    function umSnapshotSessionNav() {
        if (umSessionAllowedIds !== null) return;
        var allowed = {};
        document.querySelectorAll('nav [data-page-id]').forEach(function(it) {
            allowed[String(it.getAttribute('data-page-id'))] = true;
        });
        umSessionAllowedIds = allowed;
        document.querySelectorAll('nav .nav-dropdown').forEach(function(dd) {
            var btn = dd.querySelector('.nav-link');
            var sp = btn ? btn.querySelector('span') : null;
            var key = dd.getAttribute('data-module-id');
            if (key && sp && !umNavIcons[key]) umNavIcons[key] = sp.outerHTML;
        });
    }
    function umNavSortedModules(modules) {
        return (modules || []).filter(function(m) {
            return m && m.pages && m.pages.length;
        }).sort(function(a, b) {
            var ad = String(a.description || ''), bd = String(b.description || '');
            var aA = ad.trim().toLowerCase() === 'administrar', bA = bd.trim().toLowerCase() === 'administrar';
            if (aA !== bA) return aA ? 1 : -1;
            return ad.localeCompare(bd);
        });
    }
    function umNavPageHref(pg) {
        if (!pg.url) return null;
        return (String(pg.url).charAt(0) === '/') ? (UM_BASE_URL + pg.url) : null;
    }
    // La url `reportes` existe en Mercado (cliente) y en Reparto. La de
    // Reparto ("Reportes Repartidor") debe abrir la seccion del repartidor
    // (billetera/retiros), nunca la del cliente.
    function umNavSectionKey(m, pg) {
        var mod = String((m && m.description) || '').trim().toLowerCase();
        var url = String(pg.url || '').trim();
        var desc = String(pg.description || '').trim().toLowerCase();
        if (desc === 'reportes repartidor' || url === '/repartidor/reportes' || url === 'repartidor/reportes' || url === 'reportes-repartidor' || (mod === 'reparto' && url === 'reportes')) {
            return 'reportes-repartidor';
        }
        return pg.url;
    }
    function umNavLogoutDesktop() {
        return '<div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>' +
            '<a href="#" onclick="event.preventDefault();closeDropdowns();doLogout()" class="dropdown-item" style="display:block;padding:8px 16px;color:#000000;font-size:13px;font-weight:400;text-decoration:none;white-space:nowrap;">Cerrar Sesi\u00f3n</a>';
    }
    function umRebuildTopNav(allowed) {
        allowed = allowed || {};
        console.log("NAVBAR_MODULOS_FILTRADOS:", Object.keys(allowed).length, "paginas permitidas");
        var catalog = Array.isArray(userParentModules) ? userParentModules.slice() : [];
        var mods = umNavSortedModules(catalog).map(function(m) {
            var pages = (m.pages || []).filter(function(pg) { return !!allowed[String(pg.id)]; });
            return { id: m.id, description: m.description, icon: m.icon, pages: pages };
        }).filter(function(m) { return m.pages.length > 0; });
        var container = document.querySelector('nav .nav-container');
        var toggle = document.getElementById('menu-toggle');
        if (container) {
            Array.prototype.slice.call(container.querySelectorAll('.nav-dropdown, .nav-separator')).forEach(function(n) { n.parentNode.removeChild(n); });
            if (!mods.length) {
                var only = document.createElement('a');
                only.href = '#';
                only.setAttribute('onclick', "event.preventDefault();doLogout()");
                only.className = 'nav-link';
                only.style.cssText = 'padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;';
                only.textContent = 'Cerrar Sesión';
                container.insertBefore(only, toggle);
            }
            mods.forEach(function(m, idx) {
                var sep = document.createElement('div');
                sep.className = 'nav-separator';
                sep.style.cssText = 'width:1px;height:20px;background:rgba(255,255,255,0.4);';
                container.insertBefore(sep, toggle);
                var dd = document.createElement('div');
                dd.className = 'nav-dropdown';
                dd.setAttribute('data-module-id', m.id);
                dd.style.cssText = 'position:relative;';
                var iconHtml = umNavIcons[String(m.id)] || (m.icon || '');
                var itemsHtml = m.pages.map(function(pg) {
                    var href = umNavPageHref(pg);
                    if (href) return '<a href="' + href + '" data-page-id="' + pg.id + '" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">' + escapeHtml(pg.description || '') + '</a>';
                    if (pg.url) return '<a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection(\'' + umNavSectionKey(m, pg) + '\')" data-page-id="' + pg.id + '" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">' + escapeHtml(pg.description || '') + '</a>';
                    return '<div class="dropdown-item" data-page-id="' + pg.id + '" title="Todavia no tiene seccion" style="display:block;padding:8px 16px;color:#9ca3af;font-size:13px;font-weight:500;white-space:nowrap;cursor:default;">' + escapeHtml(pg.description || '') + ' <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span></div>';
                }).join('');
                if (String(m.description).trim().toLowerCase() === 'administrar' || idx === mods.length - 1) itemsHtml += umNavLogoutDesktop();
                dd.innerHTML = '<button class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;">' + iconHtml + escapeHtml(m.description || '') +
                    ' <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg></button>' +
                    '<div class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;background:white;border-radius:0;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;padding:6px 0;z-index:100;">' + itemsHtml + '</div>';
                container.insertBefore(dd, toggle);
            });
        }
        var mWrap = document.querySelector('#mobile-menu > div');
        if (mWrap) {
            mWrap.innerHTML = '<div style="width:100%;height:1px;background:#e5e7eb;"></div>';
            if (!mods.length) {
                mWrap.innerHTML += '<button type="button" onclick="doLogout()" style="background:none;border:none;color:#000000;font-size:13px;font-weight:400;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>';
            }
            mods.forEach(function(m, idx) {
                var btn = document.createElement('button');
                btn.setAttribute('onclick', 'toggleMobileSub(this)');
                btn.setAttribute('data-module-id', m.id);
                btn.style.cssText = 'width:100%;background:none;border:none;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;padding:8px 0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;';
                var iconHtml = umNavIcons[String(m.id)] || (m.icon || '');
                btn.innerHTML = iconHtml + escapeHtml(m.description || '') +
                    ' <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>';
                var sub = document.createElement('div');
                sub.className = 'mobile-sub';
                sub.style.cssText = 'display:none;width:100%;text-align:center;';
                sub.innerHTML = m.pages.map(function(pg) {
                    var href = umNavPageHref(pg);
                    if (href) return '<a href="' + href + '" data-page-id="' + pg.id + '" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">' + escapeHtml(pg.description || '') + '</a>';
                    if (pg.url) return '<a href="#" onclick="event.preventDefault();showDashSection(\'' + umNavSectionKey(m, pg) + '\')" data-page-id="' + pg.id + '" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">' + escapeHtml(pg.description || '') + '</a>';
                    return '<div data-page-id="' + pg.id + '" title="Todavia no tiene seccion" style="display:block;color:#9ca3b8;font-size:13px;font-weight:500;padding:6px 0;cursor:default;">' + escapeHtml(pg.description || '') + ' <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span></div>';
                }).join('');
                if (String(m.description).trim().toLowerCase() === 'administrar' || idx === mods.length - 1) {
                    sub.innerHTML += '<div style="width:60%;height:1px;background:#e5e7eb;margin:6px auto;"></div>' +
                        '<button type="button" onclick="doLogout()" style="background:none;border:none;color:#000000;font-size:13px;font-weight:400;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>';
                }
                mWrap.appendChild(btn);
                mWrap.appendChild(sub);
            });
        }
    }
    function umRestoreTopNav() {
        umRebuildTopNav(umSessionAllowedIds || {});
    }
    function umRefreshManagedProfile() {
        var id = umManagedId();
        if (!id) return;
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(window.APP_BASE+"/admin/users"+"/" + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(u) {
            if (!u || u.error) return;
            umManagedProfile = u;
            var idx = (allUsers || []).findIndex(function(x) { return String(x.id) === String(u.id); });
            if (idx !== -1) allUsers[idx] = u;
            profileDireccionCargada = false;
            loadAddress();
        })
        .catch(function() {});
    }

    function loadUsers() {
        userCurrentPage = 1;
        var loading = document.getElementById('users-loading');
        var empty = document.getElementById('users-empty');
        var tableWrap = document.getElementById('users-table-wrap');
        var pagination = document.getElementById('users-pagination');
        if (loading) loading.style.display = 'block';
        if (empty) empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'none';
        if (pagination) pagination.style.display = 'none';

        return fetch(window.APP_BASE+"/admin/users", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUsers = Array.isArray(data) ? data : [];
            loadUserStatusOptions();
            loadTypeUserOptions();
            // La vista de usuarios rediseñada no usa el buscador legacy
            // (#user-search); sin esta guarda se cortaba el refresco de la lista.
            if (document.getElementById('user-search')) filterUsers();
            if (selectedUser) {
                var fresh = allUsers.find(function(x) { return String(x.id) === String(selectedUser.id); });
                if (fresh) {
                    if ((!fresh.addresses || !fresh.addresses.length) && selectedUser.addresses && selectedUser.addresses.length) {
                        fresh.addresses = selectedUser.addresses;
                    }
                    if ((!fresh.primary_address) && selectedUser.primary_address) {
                        fresh.primary_address = selectedUser.primary_address;
                    }
                    if (!fresh.provider && selectedUser.provider) {
                        fresh.provider = selectedUser.provider;
                    } else if (fresh.provider && selectedUser.provider && selectedUser.provider.images && (!fresh.provider.images || !fresh.provider.images.length)) {
                        fresh.provider.images = selectedUser.provider.images;
                    }
                    selectedUser = fresh;
                    var sIdx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
                    if (sIdx !== -1) allUsers[sIdx] = fresh;
                } else {
                    selectedUser = null;
                }
            }
            ensureUserManagementSelection();
            updateCreateUserBtnVisibility();
            renderUserManagement();
            return allUsers;
        })
        .catch(function() {
            if (loading) loading.innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar usuarios.</p>';
            renderUserManagement();
        });
    }

    function getActiveAccountEmail() {
        if (authEmail) return String(authEmail).toLowerCase();
        if (selectedUser && selectedUser.email) return String(selectedUser.email).toLowerCase();
        return '';
    }

    function getAccountUsers() {
        var email = getActiveAccountEmail();
        if (!email) return allUsers;
        return allUsers.filter(function(u) {
            return u.email && String(u.email).toLowerCase() === email;
        });
    }

    function getSelectableUsers() {
        return userSelectScope === 'account' ? getAccountUsers() : allUsers;
    }

    function openUserSelectModal(scope) {
        userSelectScope = (scope === 'account') ? 'account' : 'all';
        document.getElementById('user-select-overlay').style.display = 'flex';
        var search = document.getElementById('user-search');
        search.value = '';
        var statusFilter = document.getElementById('user-filter-status');
        var typeFilter = document.getElementById('user-filter-type');
        if (statusFilter) statusFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        loadUserStatusOptions();
        loadTypeUserOptions();
        if (!allUsers.length) {
            loadUsers();
        } else {
            userCurrentPage = 1;
            var scoped = getSelectableUsers();
            allUsers._filtered = scoped;
            renderUsers(scoped);
        }
        setTimeout(function() { search.focus(); }, 50);
    }

    function closeUserSelectModal() {
        document.getElementById('user-select-overlay').style.display = 'none';
    }

    var typeUserPagesTypes = [];
    var typeUserPagesAll = [];
    var typeUserPagesAssigned = [];
    var typeUserPagesModules = [];

    function openTypeUserPagesModal() {
        document.getElementById('typeuser-pages-overlay').style.display = 'flex';
        var msg = document.getElementById('typeuser-pages-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; msg.classList.remove('ok', 'err'); }
        var select = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        var empty = document.getElementById('typeuser-pages-empty');
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var loading = document.getElementById('typeuser-pages-loading');
        var hint = document.getElementById('typeuser-pages-hint');
        if (empty) empty.style.display = 'block';
        if (list) list.style.display = 'none';
        if (tbody) tbody.innerHTML = '';
        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
        if (loading) loading.style.display = 'none';
        if (hint) hint.style.display = 'none';
        if (select) select.value = '';
        if (moduleSelect) moduleSelect.value = '';
        loadTypeUserPagesData();
    }

    function closeTypeUserPagesModal() {
        document.getElementById('typeuser-pages-overlay').style.display = 'none';
    }

    function loadTypeUserPagesData() {
        var loading = document.getElementById('typeuser-pages-loading');
        var empty = document.getElementById('typeuser-pages-empty');
        var select = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        if (loading) loading.style.display = 'block';
        if (empty) empty.style.display = 'none';

        Promise.all([
            fetch(window.APP_BASE+"/type-users/pages-data", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); }),
            fetch(window.APP_BASE+"/modules", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); })
        ])
        .then(function(results) {
            if (loading) loading.style.display = 'none';
            var data = results[0] || {};
            var modules = results[1] || [];
            typeUserPagesTypes = data.types || [];
            typeUserPagesAll = data.pages || [];
            typeUserPagesModules = Array.isArray(modules) ? modules : [];
            if (select) {
                select.innerHTML = '<option value="">-- Seleccioná un tipo --</option>' +
                    typeUserPagesTypes.map(function(t) {
                        return '<option value="' + t.id + '">' + escapeHtml(t.description) + ' (' + (t.users_count || 0) + ' usuarios)</option>';
                    }).join('');
            }
            if (moduleSelect) {
                moduleSelect.innerHTML = '<option value="">-- Todos los módulos --</option>' +
                    typeUserPagesModules.map(function(m) {
                        return '<option value="' + m.id + '">' + escapeHtml(m.description || m.name || ('Módulo ' + m.id)) + '</option>';
                    }).join('');
            }
            if (!typeUserPagesTypes.length) {
                if (empty) {
                    empty.textContent = 'No hay tipos de usuario disponibles.';
                    empty.style.display = 'block';
                }
            } else {
                if (empty) empty.style.display = 'block';
            }
        })
        .catch(function() {
            if (loading) loading.style.display = 'none';
            if (empty) {
                empty.textContent = 'Error al cargar los tipos de usuario.';
                empty.style.display = 'block';
            }
        });
    }

    function loadTypeUserPagesForType() {
        var select = document.getElementById('typeuser-pages-select');
        var typeId = select ? parseInt(select.value, 10) : 0;
        var empty = document.getElementById('typeuser-pages-empty');
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var hint = document.getElementById('typeuser-pages-hint');
        var msg = document.getElementById('typeuser-pages-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; msg.classList.remove('ok', 'err'); }

        if (!typeId) {
            if (list) list.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un tipo de usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            typeUserPagesAssigned = [];
            return;
        }

        var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
        typeUserPagesAssigned = ((type && type.assigned_page_ids) || []).slice();
        if (hint && type) {
            hint.textContent = type.users_count + ' usuario(s) con este tipo. Al guardar, las páginas seleccionadas se aplican a todos ellos.';
            hint.style.display = 'block';
        }
        if (empty) empty.style.display = 'none';
        renderTypeUserPagesList();
    }

    function renderTypeUserPagesList() {
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var empty = document.getElementById('typeuser-pages-empty');
        var typeSelect = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        if (!list || !tbody) return;

        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var moduleFilter = moduleSelect ? moduleSelect.value : '';
        if (!typeId) {
            list.style.display = 'none';
            tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un tipo de usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            return;
        }

        if (!moduleFilter) {
            list.style.display = 'none';
            tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un módulo para ver las páginas.';
                empty.style.display = 'block';
            }
            return;
        }

        if (empty) empty.style.display = 'none';

        var mid = parseInt(moduleFilter, 10);
        var pages = typeUserPagesAll.filter(function(pg) {
            return pg.module_id === mid;
        });

        if (!pages.length) {
            tbody.innerHTML = '';
            list.style.display = 'block';
            if (gridEmpty) {
                gridEmpty.textContent = 'No hay páginas disponibles para este módulo.';
                gridEmpty.style.display = 'block';
            }
            return;
        }

        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }

        var assigned = {};
        typeUserPagesAssigned.forEach(function(id) { assigned[id] = true; });

        tbody.innerHTML = pages.map(function(pg, idx) {
            var checked = !!assigned[pg.id];
            var bg = idx % 2 === 0 ? '#fff' : '#f8fafc';
            var meta = [];
            if (pg.module) meta.push(escapeHtml(pg.module));
            if (pg.url) meta.push(escapeHtml(pg.url));
            return '<tr style="background:' + bg + ';border-bottom:1px solid #e5e7eb;">' +
                '<td style="padding:10px 16px;font-size:13px;color:#0f172a;vertical-align:middle;">' +
                    '<div style="font-weight:600;">' + escapeHtml(pg.description || '-') + '</div>' +
                    (meta.length ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">' + meta.join(' · ') + '</div>' : '') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;vertical-align:middle;">' +
                    '<input type="checkbox" class="typeuser-pages-check" value="' + pg.id + '"' + (checked ? ' checked' : '') + ' style="width:16px;height:16px;accent-color:#D24C19;cursor:pointer;" title="Asociar o quitar página" onchange="toggleTypeUserPage(' + pg.id + ', this.checked)" />' +
                    '<div style="font-size:10px;color:#64748b;margin-top:2px;">' + (checked ? 'Agregada' : 'Agregar') + '</div>' +
                '</td>' +
            '</tr>';
        }).join('');
        list.style.display = 'block';
        updateTypeUserPagesCheckAll();
    }

    function getVisibleTypeUserPageIds() {
        var moduleSelect = document.getElementById('typeuser-pages-module');
        var moduleFilter = moduleSelect ? moduleSelect.value : '';
        if (!moduleFilter) return [];
        var mid = parseInt(moduleFilter, 10);
        return typeUserPagesAll
            .filter(function(pg) { return pg.module_id === mid; })
            .map(function(pg) { return pg.id; });
    }

    function updateTypeUserPagesCheckAll() {
        var header = document.getElementById('typeuser-pages-check-all');
        if (!header) return;
        var list = document.getElementById('typeuser-pages-list');
        if (!list || list.style.display === 'none') {
            header.checked = false;
            header.indeterminate = false;
            header.disabled = false;
            return;
        }
        var visibleIds = getVisibleTypeUserPageIds();
        if (!visibleIds.length) {
            header.checked = false;
            header.indeterminate = false;
            return;
        }
        var onCount = 0;
        visibleIds.forEach(function(id) {
            if (typeUserPagesAssigned.indexOf(id) !== -1) onCount++;
        });
        header.disabled = false;
        header.checked = onCount === visibleIds.length;
        header.indeterminate = onCount > 0 && onCount < visibleIds.length;
    }

    function toggleTypeUserPagesAll(checked) {
        var typeSelect = document.getElementById('typeuser-pages-select');
        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var msg = document.getElementById('typeuser-pages-msg');
        var header = document.getElementById('typeuser-pages-check-all');
        var visibleIds = getVisibleTypeUserPageIds();

        function showMsg(text, ok) {
            if (!msg) return;
            msg.textContent = text;
            msg.style.display = 'block';
            msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        }

        if (!typeId) {
            if (header) header.checked = false;
            showMsg('Seleccioná un tipo de usuario.', false);
            return;
        }

        if (!visibleIds.length) {
            if (header) header.checked = false;
            showMsg('No hay páginas visibles para actualizar.', false);
            return;
        }

        var prevAssigned = typeUserPagesAssigned.slice();
        var prevHeader = header ? { checked: header.checked, indeterminate: header.indeterminate } : null;

        if (checked) {
            visibleIds.forEach(function(id) {
                if (typeUserPagesAssigned.indexOf(id) === -1) typeUserPagesAssigned.push(id);
            });
        } else {
            typeUserPagesAssigned = typeUserPagesAssigned.filter(function(id) {
                return visibleIds.indexOf(id) === -1;
            });
        }

        if (header) {
            header.checked = checked;
            header.indeterminate = false;
        }
        renderTypeUserPagesList();

        if (header) {
            header.checked = checked;
            header.indeterminate = false;
        }

        fetch(window.APP_BASE+"/type-users"+"/" + typeId + '/pages/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                action: checked ? 'attach' : 'detach',
                page_ids: visibleIds
            })
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok || !res.data.success) {
                typeUserPagesAssigned = prevAssigned;
                if (header && prevHeader) {
                    header.checked = prevHeader.checked;
                    header.indeterminate = prevHeader.indeterminate;
                }
                renderTypeUserPagesList();
                showMsg((res.data && res.data.message) || 'Error al actualizar las páginas.', false);
                return;
            }
            var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
            if (type) type.assigned_page_ids = typeUserPagesAssigned.slice();
            showMsg(res.data.message || (checked ? 'Páginas agregadas.' : 'Páginas quitadas.'), true);
            updateTypeUserPagesCheckAll();
            if (selectedUser && selectedUser.type_user_id === typeId) {
                refreshSelectedUser();
            }
        })
        .catch(function() {
            typeUserPagesAssigned = prevAssigned;
            if (header && prevHeader) {
                header.checked = prevHeader.checked;
                header.indeterminate = prevHeader.indeterminate;
            }
            renderTypeUserPagesList();
            showMsg('Error de conexión.', false);
        });
    }

    function toggleTypeUserPage(pageId, checked) {
        var typeSelect = document.getElementById('typeuser-pages-select');
        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var msg = document.getElementById('typeuser-pages-msg');
        var checkbox = document.querySelector('.typeuser-pages-check[value="' + pageId + '"]');

        function showMsg(text, ok) {
            if (!msg) return;
            msg.textContent = text;
            msg.style.display = 'block';
            msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        }

        function setLabel(isOn) {
            if (!checkbox) return;
            var label = checkbox.nextElementSibling;
            if (label) label.textContent = isOn ? 'Agregada' : 'Agregar';
        }

        function revert() {
            if (checkbox) checkbox.checked = !checked;
            var idx = typeUserPagesAssigned.indexOf(pageId);
            if (checked) {
                if (idx !== -1) typeUserPagesAssigned.splice(idx, 1);
            } else if (idx === -1) {
                typeUserPagesAssigned.push(pageId);
            }
            setLabel(typeUserPagesAssigned.indexOf(pageId) !== -1);
        }

        if (!typeId) {
            if (checkbox) checkbox.checked = !checked;
            showMsg('Seleccioná un tipo de usuario.', false);
            return;
        }

        if (checked) {
            if (typeUserPagesAssigned.indexOf(pageId) === -1) typeUserPagesAssigned.push(pageId);
        } else {
            var i = typeUserPagesAssigned.indexOf(pageId);
            if (i !== -1) typeUserPagesAssigned.splice(i, 1);
        }
        setLabel(checked);

        var method = checked ? 'POST' : 'DELETE';
        fetch(window.APP_BASE+"/type-users"+"/" + typeId + '/pages/' + pageId, {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok || !res.data.success) {
                revert();
                showMsg(res.data.message || 'Error al actualizar la página.', false);
                updateTypeUserPagesCheckAll();
                return;
            }
            var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
            if (type) type.assigned_page_ids = typeUserPagesAssigned.slice();
            showMsg(res.data.message || (checked ? 'Página agregada.' : 'Página quitada.'), true);
            updateTypeUserPagesCheckAll();
            if (selectedUser && selectedUser.type_user_id === typeId) {
                refreshSelectedUser();
            }
        })
        .catch(function() {
            revert();
            showMsg('Error de conexión.', false);
            updateTypeUserPagesCheckAll();
        });
    }

    function selectUser(id) {
        selectUserManagementUser(id);
        closeUserSelectModal();
    }

    function refreshSelectedUser() {
        if (!selectedUser) return Promise.resolve();
        var id = selectedUser.id;
        return fetch(window.APP_BASE+"/admin/users"+"/" + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(fresh) {
            if (!fresh || fresh.error) return;
            if (!selectedUser || String(selectedUser.id) !== String(fresh.id)) return;
            selectedUser = fresh;
            var idx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
            if (idx !== -1) allUsers[idx] = fresh;
            allUsers._filtered = getAccountUsers();
            renderUserManagement();
            fillUserEditForm(getUserManagementSelectedUser());
        })
        .catch(function() {});
    }

    function applyUserUpdate(user) {
        if (!user || !user.id) return;
        var idx = allUsers.findIndex(function(x) { return String(x.id) === String(user.id); });
        if (idx !== -1) {
            allUsers[idx] = user;
        } else {
            allUsers.push(user);
        }
        if (selectedUser && String(selectedUser.id) === String(user.id)) {
            selectedUser = user;
        }
        allUsers._filtered = getAccountUsers();
        ensureUserManagementSelection();
        updateCreateUserBtnVisibility();
        renderUserManagement();
        if (currentUserPanelTab() === 'config') loadUserAccountConfig();
    }

    function currentUserPanelTab() {
        var active = document.querySelector('#user-sidebar .sidebar-link[data-user-panel].active');
        return active ? active.getAttribute('data-user-panel') : 'demografia';
    }

    var userPanelLabels = {
        demografia: 'Demografía',
        direcciones: 'Direccion',
        negocio: 'Negocio',
        categorias: 'Categorias',
        cuenta: 'Cuentas',
        imagenes: 'Publicidad',
        config: 'Contraseñas'
    };

    function switchUserTab(tab) {
        if (tab === 'imagenes' && !userCanAdvertise()) tab = 'demografia';
        document.querySelectorAll('#user-sidebar .sidebar-link[data-user-panel]').forEach(function(link) {
            link.classList.toggle('active', link.getAttribute('data-user-panel') === tab);
        });
        document.querySelectorAll('.user-panel').forEach(function(panel) {
            panel.classList.toggle('active', panel.id === 'user-panel-' + tab);
        });
        var bc = document.getElementById('user-breadcrumb-current');
        if (bc) bc.textContent = userPanelLabels[tab] || tab;
        var sb = document.getElementById('user-sidebar');
        if (sb) sb.classList.remove('open');
        var ov = document.getElementById('user-sidebar-overlay');
        if (ov) ov.style.display = 'none';
        if (tab === 'categorias') loadUserCategorias();
        if (tab === 'imagenes' && selectedUser) renderUserImages(selectedUser);
        if (tab === 'config') loadUserAccountConfig();
    }

    function toggleUserSidebar() {
        var sb = document.getElementById('user-sidebar');
        var ov = document.getElementById('user-sidebar-overlay');
        if (!sb) return;
        var isOpen = sb.classList.toggle('open');
        if (ov) ov.style.display = isOpen ? 'block' : 'none';
    }

    function renderUserAddresses(u) {
        var list = getEffectiveAddresses(u);
        if (!list.length) {
            resetUserAddressForm();
            return;
        }
        var currentId = document.getElementById('user-addr-id') ? document.getElementById('user-addr-id').value : '';
        var stillThere = currentId && list.some(function(x) { return String(x.id) === String(currentId); });
        if (!stillThere) {
            var primaryAddr = list.find(function(x) { return x.is_primary; }) || list[0];
            fillUserAddressForm(primaryAddr.id);
        }
    }

    function renderUserProvider(u) {
        var p = getEffectiveProvider(u);
        var set = function(id, val) {
            var el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        set('user-prov-business', p && p.business_name);
        set('user-prov-description', p && p.description);
        set('user-prov-phone', p && p.phone);
        set('user-prov-whatsapp', p && p.whatsapp);
        set('user-prov-zone', p && p.zone);
        set('user-prov-promo', p && p.promo);
        var activeCb = document.getElementById('user-prov-active');
        if (activeCb) activeCb.checked = p ? !!p.is_active : false;
        var errBn = document.getElementById('user-prov-business-error');
        if (errBn) errBn.style.display = 'none';
        var errEl = document.getElementById('user-prov-error');
        if (errEl) errEl.style.display = 'none';
        var msg = document.getElementById('user-prov-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
    }

    var userCategoriasReqId = 0;

    function loadUserCategorias() {
        var list = document.getElementById('user-categorias-list');
        var loading = document.getElementById('user-categorias-loading');
        if (!list || !loading) return;
        var reqId = ++userCategoriasReqId;
        if (!selectedUser) {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="user-demo-empty-tab">Seleccioná un usuario para ver sus categorías.</div>';
            return;
        }
        var userId = selectedUser.id;
        loading.style.display = 'block';
        loading.textContent = 'Cargando categorías...';
        list.style.display = 'none';
        fetch(window.APP_BASE+"/admin/users"+"/" + userId + '/subgroups', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userCategoriasReqId) return;
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '';
            if (data.has_provider === false) {
                list.innerHTML = '<div class="user-demo-empty-tab">Este usuario no tiene negocio. Creá el negocio para asignar categorías.</div>';
                return;
            }
            if (!data.groups || !data.groups.length) {
                list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px;">No hay categorías disponibles.</p>';
                return;
            }
            data.groups.forEach(function(group) {
                if (!group.subgroups || !group.subgroups.length) return;
                var section = document.createElement('div');
                section.style.cssText = 'margin-bottom:20px;border:1px solid #e2e8f0;background:#fff;padding:16px;';
                var header = document.createElement('div');
                header.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:12px;';
                var icon = document.createElement('span');
                if (group.icon) icon.innerHTML = group.icon;
                icon.style.cssText = 'display:flex;align-items:center;color:#D24C19;';
                var title = document.createElement('h4');
                title.style.cssText = 'font-size:13px;font-weight:700;color:#0f172a;margin:0;';
                title.textContent = group.description;
                header.appendChild(icon);
                header.appendChild(title);
                section.appendChild(header);
                var grid = document.createElement('div');
                grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;';
                group.subgroups.forEach(function(sub) {
                    var isSelected = (data.selected || []).indexOf(sub.id) >= 0;
                    var label = document.createElement('label');
                    label.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid ' + (isSelected ? '#D24C19' : '#e2e8f0') + ';background:' + (isSelected ? '#fff7ed' : '#fff') + ';cursor:pointer;transition:all .15s;font-size:12px;font-weight:500;color:' + (isSelected ? '#D24C19' : '#475569') + ';';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = isSelected;
                    cb.style.cssText = 'accent-color:#D24C19;width:14px;height:14px;cursor:pointer;';
                    cb.addEventListener('change', function() {
                        toggleUserSubgroup(sub.id, label, cb);
                    });
                    var span = document.createElement('span');
                    span.textContent = sub.description;
                    label.appendChild(cb);
                    label.appendChild(span);
                    grid.appendChild(label);
                });
                section.appendChild(grid);
                list.appendChild(section);
            });
        })
        .catch(function() {
            if (reqId !== userCategoriasReqId) return;
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="user-demo-empty-tab">Error al cargar categorías.</div>';
        });
    }

    function toggleUserSubgroup(subgroupId, labelEl, cb) {
        if (!selectedUser) {
            cb.checked = !cb.checked;
            return;
        }
        fetch(window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/subgroups/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ subgroup_id: subgroupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isSelected = (data.selected || []).indexOf(subgroupId) >= 0;
                labelEl.style.borderColor = isSelected ? '#D24C19' : '#e2e8f0';
                labelEl.style.background = isSelected ? '#fff7ed' : '#fff';
                labelEl.style.color = isSelected ? '#D24C19' : '#475569';
                cb.checked = isSelected;
                showMsg('user-categorias-msg', data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg('user-categorias-msg', data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg('user-categorias-msg', 'Error de conexión.', false);
        });
    }

    var userAccountActiveTab = 'gestion';

    function switchUserAccountTab(tab) {
        userAccountActiveTab = (tab === 'paginas') ? 'paginas' : 'gestion';
        document.querySelectorAll('.user-account-tab').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-account-tab') === userAccountActiveTab);
        });
        var g = document.getElementById('user-account-pane-gestion');
        var p = document.getElementById('user-account-pane-paginas');
        if (g) g.style.display = userAccountActiveTab === 'gestion' ? 'block' : 'none';
        if (p) p.style.display = userAccountActiveTab === 'paginas' ? 'block' : 'none';
    }

    function renderUserAccount(u) {
        if (u && (!umSelectedUserId || String(umSelectedUserId) !== String(u.id))) {
            var accountUser = getUserManagementUsers().find(function(x) { return String(x.id) === String(u.id); });
            if (accountUser) {
                umSelectedUserId = accountUser.id;
                umDraftUserId = null;
                selectedUser = accountUser;
            }
        }
        renderUserManagement();
        updateCreateUserBtnVisibility();
    }

    var USER_MAX_SECONDARIES = 3;

    function getUserManagementAccountEmail() {
        return authEmail ? String(authEmail).toLowerCase() : '';
    }

    function getUserManagementUsers() {
        var email = umManagedId() ? (umManagedEmail() || getUserManagementAccountEmail()) : getUserManagementAccountEmail();
        if (!email) return allUsers.slice();
        return allUsers.filter(function(u) {
            return u.email && String(u.email).toLowerCase() === email;
        }).sort(function(a, b) { return Number(a.id) - Number(b.id); });
    }

    function getSessionUser() {
        if (authUserId !== null && authUserId !== undefined && authUserId !== '') {
            var me = allUsers.find(function(x) { return String(x.id) === String(authUserId); });
            if (me) return me;
        }
        var users = getUserManagementUsers();
        return users.length ? users[0] : null;
    }

    function getSessionAccountPrincipalUser() {
        var users = getUserManagementUsers();
        return users.length ? users[0] : null;
    }

    function isParentAccountUser(u) {
        if (!u) return false;
        var session = getSessionUser();
        var principal = getSessionAccountPrincipalUser();
        return !!((session && String(session.id) === String(u.id)) ||
            (principal && String(principal.id) === String(u.id)));
    }

    function getUserManagementSelectedUser() {
        if (umSelectedUserId === null || umSelectedUserId === undefined) return null;
        var found = getUserManagementUsers().find(function(u) {
            return String(u.id) === String(umSelectedUserId);
        });
        if (found) return found;
        if (selectedUser && String(selectedUser.id) === String(umSelectedUserId)) return selectedUser;
        return null;
    }

    function ensureUserManagementSelection() {
        var users = getUserManagementUsers();
        if (umSelectedUserId !== null && umSelectedUserId !== undefined) {
            var found = users.find(function(u) { return String(u.id) === String(umSelectedUserId); });
            if (!found && !(selectedUser && String(selectedUser.id) === String(umSelectedUserId))) umSelectedUserId = null;
        }
        if ((umSelectedUserId === null || umSelectedUserId === undefined) && selectedUser) {
            var fromSelected = users.find(function(u) { return String(u.id) === String(selectedUser.id); });
            if (fromSelected) umSelectedUserId = fromSelected.id;
        }
        var current = getUserManagementSelectedUser();
        if (current) selectedUser = current;
        return current;
    }

    function getUserRoleLabel(u) {
        var type = u && (u.type_user || u.typeUser);
        return (type && (type.description || type.status)) || 'Usuario';
    }

    function umUserIsInactive(u) {
        var st = u && u.status;
        var label = String((st && (st.status || st.description)) || '').trim().toLowerCase();
        return label.indexOf('inactiv') !== -1 || label.indexOf('inactive') !== -1;
    }

    function countAccountSecondaries() {
        return Math.max(0, getUserManagementUsers().length - 1);
    }

    function updateCreateUserBtnVisibility() {
        var max = USER_MAX_SECONDARIES;
        var count = countAccountSecondaries();
        var atLimit = count >= max;
        var text = document.getElementById('um-limit-text');
        var hint = document.getElementById('um-limit-hint');
        var bar = document.getElementById('um-limit-bar');
        var progress = document.getElementById('um-progress');
        var btn = document.getElementById('user-create-btn');

        if (text) text.textContent = count + ' de ' + max + ' creados';
        if (hint) {
            hint.textContent = atLimit
                ? 'Alcanzaste el límite de usuarios adicionales de la cuenta.'
                : 'Podés crear hasta ' + max + ' usuarios adicionales.';
        }
        if (bar) {
            bar.style.width = Math.min(100, (count / max) * 100) + '%';
            bar.style.background = atLimit ? '#dc2626' : '';
        }
        if (progress) progress.setAttribute('aria-valuenow', String(count));
        if (btn) {
            btn.disabled = atLimit;
            btn.style.display = '';
            btn.title = atLimit ? 'Límite de usuarios alcanzado' : 'Agregar nuevo usuario';
        }
    }

    function umSafeId(value) {
        var n = parseInt(value, 10);
        return isNaN(n) ? '0' : String(n);
    }

    function getAllowedPermissionModules() {
        var modules = Array.isArray(userParentModules) ? userParentModules.slice() : [];
        return modules.filter(function(m) {
            return m && m.pages && m.pages.length;
        }).sort(function(a, b) {
            var ad = String(a.description || a.name || '');
            var bd = String(b.description || b.name || '');
            var aAdmin = ad.trim().toLowerCase() === 'administrar';
            var bAdmin = bd.trim().toLowerCase() === 'administrar';
            if (aAdmin !== bAdmin) return aAdmin ? 1 : -1;
            return ad.localeCompare(bd);
        });
    }

    function getAllowedPermissionPagesMap() {
        var map = {};
        getAllowedPermissionModules().forEach(function(module) {
            (module.pages || []).forEach(function(pg) {
                map[String(pg.id)] = pg;
            });
        });
        return map;
    }

    function initUserPermissionDraft(user) {
        if (!user) return;
        umDraftUserId = String(user.id);
        umOriginalPages = getUserAssignedPageIds(user);
        var allowed = getAllowedPermissionPagesMap();
        umDraftPages = {};
        Object.keys(allowed).forEach(function(pid) {
            if (umOriginalPages[pid]) umDraftPages[pid] = true;
        });

        var modules = getAllowedPermissionModules();
        var keep = umSelectedModuleId !== null && modules.some(function(m) {
            return String(m.id) === String(umSelectedModuleId);
        });
        if (!keep) {
            var withSelection = modules.filter(function(m) {
                return (m.pages || []).some(function(pg) { return !!umDraftPages[String(pg.id)]; });
            })[0];
            umSelectedModuleId = withSelection ? withSelection.id : (modules.length ? modules[0].id : null);
        }
    }

    function renderUserManagement() {
        ensureUserManagementSelection();
        renderUserManagementTable();
        renderUserPermissionsPanel();
    }

    function renderUserManagementTable() {
        var tbody = document.getElementById('um-users-tbody');
        if (!tbody) return;
        var empty = document.getElementById('um-users-empty');
        var countEl = document.getElementById('um-users-count');
        var users = getUserManagementUsers();

        if (countEl) countEl.textContent = String(users.length);

        if (!users.length) {
            tbody.innerHTML = '';
            if (empty) empty.style.display = 'block';
            return;
        }
        if (empty) empty.style.display = 'none';

        tbody.innerHTML = users.map(function(u) {
            var id = umSafeId(u.id);
            var isParent = isParentAccountUser(u);
            var isSelected = umSelectedUserId !== null && String(umSelectedUserId) === String(u.id);
            var role = escapeHtml(getUserRoleLabel(u));
            var name = escapeHtml(u.name || 'Sin nombre');
            var email = escapeHtml(u.email || '');
            var inactiveBadge = umUserIsInactive(u)
                ? ' <span class="um-inactive-badge" title="Usuario inactivo">Inactivo</span>'
                : '';
            var actions =
                '<button type="button" class="um-action-btn perm" onclick="event.stopPropagation();selectUserManagementUser(' + id + ')" title="Editar permisos" aria-label="Editar permisos">' +
                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>' +
                '</button>' +
                '<button type="button" class="um-action-btn edit" onclick="event.stopPropagation();openUserEditModal(' + id + ')" title="Editar datos" aria-label="Editar datos">' +
                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                '</button>';
            if (!isParent) {
                actions +=
                    '<button type="button" class="um-action-btn del" onclick="event.stopPropagation();openUserDeleteFromList(' + id + ')" title="Eliminar usuario" aria-label="Eliminar usuario">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0c-.34-.059-.68-.114-1.022-.165m0 0a48.11 48.11 0 0 1-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>';
            }
            return '<tr class="um-user-row' + (isSelected ? ' active' : '') + '" onclick="selectUserManagementUser(' + id + ')">' +
                '<td>' +
                    '<div class="um-user-name">' + name + (isParent ? ' <span style="font-size:10px;font-weight:600;color:#D24C19;">(principal)</span>' : '') + inactiveBadge + '</div>' +
                    '<div class="um-user-email">' + (email || 'Sin email') + '</div>' +
                '</td>' +
                '<td style="text-align:center;"><span class="um-role' + (isParent ? ' parent' : '') + '">' + role + '</span></td>' +
                '<td style="white-space:nowrap;"><div class="um-actions">' + actions + '</div></td>' +
            '</tr>';
        }).join('');
    }

    function renderUserPermissionsPanel() {
        var user = getUserManagementSelectedUser();
        var label = document.getElementById('um-selected-user-label');
        var roleEl = document.getElementById('um-selected-user-role');
        var empty = document.getElementById('um-permissions-empty');
        var body = document.getElementById('um-permissions-body');
        var note = document.getElementById('um-parent-note');
        var saveBtn = document.getElementById('um-save-btn');
        var cancelBtn = document.querySelector('#um-permissions-panel .um-cancel-btn');

        if (!user) {
            umDraftUserId = null;
            umSelectedModuleId = null;
            umDraftPages = {};
            umOriginalPages = {};
            if (label) label.textContent = 'Seleccioná un usuario de la lista.';
            if (roleEl) { roleEl.style.display = 'none'; roleEl.textContent = ''; }
            if (empty) empty.style.display = 'block';
            if (body) body.style.display = 'none';
            if (note) note.style.display = 'none';
            showUserPermissionMsg('', true);
            fillUserEditForm(null);
            return;
        }

        if (umDraftUserId !== String(user.id)) {
            initUserPermissionDraft(user);
        }

        var isParent = isParentAccountUser(user);
        // El Admin edita permisos de cualquier usuario sin bloqueos.
        var umReadOnly = isParent && !actorIsAdmin;
        var modules = getAllowedPermissionModules();

        if (label) {
            label.textContent = (user.name || 'Usuario') + (user.email ? ' · ' + user.email : '');
        }
        if (roleEl) {
            roleEl.textContent = getUserRoleLabel(user);
            roleEl.className = 'um-role' + (isParent ? ' parent' : '');
            roleEl.style.display = '';
        }
        if (empty) empty.style.display = 'none';
        if (body) body.style.display = 'block';

        if (note) {
            if (isParent && !actorIsAdmin) {
                note.textContent = 'Este es un usuario principal de la cuenta. Sus permisos se administran desde los módulos de la barra superior y no se pueden modificar desde esta pantalla.';
                note.style.display = 'block';
            } else if (!modules.length) {
                note.textContent = 'Tu usuario no tiene módulos ni páginas habilitados para asignar.';
                note.style.display = 'block';
            } else {
                note.textContent = 'Solo se muestran los módulos y páginas que tu usuario tiene habilitados.';
                note.style.display = 'block';
            }
        }

        if (saveBtn) {
            saveBtn.style.display = umReadOnly ? 'none' : '';
            saveBtn.disabled = umReadOnly || umSavingPermissions;
            saveBtn.textContent = umSavingPermissions ? 'Guardando...' : 'Guardar Cambios';
        }
        if (cancelBtn) cancelBtn.style.display = umReadOnly ? 'none' : '';

        renderUserPermissionModules();
        renderUserPermissionPages();
    }

    function renderUserPermissionModules() {
        var list = document.getElementById('um-modules-list');
        if (!list) return;
        var modules = getAllowedPermissionModules();

        if (!modules.length) {
            list.innerHTML = '<div style="padding:16px;text-align:center;color:#64748b;font-size:12px;">No hay módulos disponibles para asignar.</div>';
            return;
        }

        list.innerHTML = modules.map(function(module) {
            var mid = umSafeId(module.id);
            var active = umSelectedModuleId !== null && String(umSelectedModuleId) === String(module.id);
            var selectedCount = (module.pages || []).filter(function(pg) {
                return !!umDraftPages[String(pg.id)];
            }).length;
            return '<button type="button" class="um-module-btn' + (active ? ' active' : '') + '" onclick="selectUserPermissionModule(' + mid + ')">' +
                '<span class="um-module-dot"></span>' +
                '<span class="um-module-name">' + escapeHtml(module.description || module.name || ('Módulo ' + mid)) + '</span>' +
                '<span class="um-module-count">' + selectedCount + '</span>' +
            '</button>';
        }).join('');
    }

    function renderUserPermissionPages() {
        var list = document.getElementById('um-pages-list');
        if (!list) return;
        var user = getUserManagementSelectedUser();
        var modules = getAllowedPermissionModules();
        var module = modules.filter(function(m) {
            return umSelectedModuleId !== null && String(m.id) === String(umSelectedModuleId);
        })[0];

        if (!user || !module) {
            list.innerHTML = '<div style="padding:16px;text-align:center;color:#64748b;font-size:12px;">Seleccioná un módulo para ver sus páginas.</div>';
            updateUserPermissionSummary();
            return;
        }

        var pages = module.pages || [];
        if (!pages.length) {
            list.innerHTML = '<div style="padding:16px;text-align:center;color:#64748b;font-size:12px;">Este módulo no tiene páginas disponibles.</div>';
            updateUserPermissionSummary();
            return;
        }

        var disabled = (isParentAccountUser(user) && !actorIsAdmin) || umSavingPermissions;
        list.innerHTML = pages.map(function(pg) {
            var pid = umSafeId(pg.id);
            var checked = !!umDraftPages[String(pg.id)];
            return '<label class="um-page-item' + (checked ? ' checked' : '') + '">' +
                '<input type="checkbox" value="' + pid + '"' + (checked ? ' checked' : '') + (disabled ? ' disabled' : '') +
                    ' onchange="toggleUserPermissionPage(' + pid + ', this.checked)" />' +
                '<span class="um-page-text">' +
                    '<span class="um-page-name">' + escapeHtml(pg.description || 'Página sin nombre') + '</span>' +
                    (pg.url ? '<span class="um-page-url">' + escapeHtml(pg.url) + '</span>' : '') +
                '</span>' +
            '</label>';
        }).join('');

        updateUserPermissionSummary();
    }

    function selectUserPermissionModule(id) {
        umSelectedModuleId = id;
        renderUserPermissionModules();
        renderUserPermissionPages();
    }

    function toggleUserPermissionPage(id, checked) {
        if (isParentAccountUser(getUserManagementSelectedUser()) && !actorIsAdmin) return;
        var key = String(id);
        if (checked) {
            umDraftPages[key] = true;
        } else {
            delete umDraftPages[key];
        }
        showUserPermissionMsg('', true);
        renderUserPermissionModules();
        renderUserPermissionPages();
    }

    function updateUserPermissionSummary() {
        var summary = document.getElementById('um-selection-summary');
        if (!summary) return;
        var total = Object.keys(umDraftPages).length;
        summary.textContent = total + (total === 1 ? ' seleccionada' : ' seleccionadas');
    }

    function showUserPermissionMsg(text, ok) {
        var el = document.getElementById('um-permissions-msg');
        if (!el) return;
        if (!text) {
            el.textContent = '';
            el.style.display = 'none';
            el.className = 'um-msg';
            return;
        }
        el.textContent = text;
        el.style.display = 'block';
        el.className = 'um-msg ' + (ok ? 'ok' : 'err');
    }

    function selectUserManagementUser(id) {
        var user = getUserManagementUsers().find(function(u) {
            return String(u.id) === String(id);
        }) || allUsers.find(function(u) {
            return String(u.id) === String(id);
        });
        if (!user) return;

        umSelectedUserId = user.id;
        selectedUser = user;
        umDraftUserId = null;
        umSelectedModuleId = null;
        // Modo administración: el Admin opera sobre el usuario elegido.
        if (typeof actorIsAdmin !== 'undefined' && actorIsAdmin) {
            umSetManagedUser(String(user.id) === String(authUserId) ? null : user.id, user.email);
        }
        showUserPermissionMsg('', true);
        renderUserManagement();
        fillUserEditForm(user);

        if (isInactiveUserStatus(user)) {
            if (confirm('\u00bfEl usuario ' + (user.name || '') + ' est\u00e1 inactivo. \u00bfQuer\u00e9s activarlo?')) {
                activateInactiveUser(user);
            }
        }
        refreshSelectedUser();
    }

    // ---------- Columna 3: editar datos del usuario ----------

    var umEditAddressId = null;
    var umEditMetaPromise = null;
    var umEditStatuses = [];
    var umEditTypes = [];
    var umEditCountries = [];

    function umGetUserPrimaryAddress(u) {
        if (!u) return null;
        if (u.primaryAddress) return u.primaryAddress;
        if (u.primary_address) return u.primary_address;
        var list = u.addresses || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].is_primary) return list[i];
        }
        return list.length ? list[0] : null;
    }

    function umFillSelect(id, items, labelKey, selected) {
        var sel = document.getElementById(id);
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Seleccionar --</option>';
        (items || []).forEach(function(it) {
            var opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = it[labelKey] || '';
            sel.appendChild(opt);
        });
        if (selected) sel.value = String(selected);
    }

    function umDistinctFromUsers(key, labelKey) {
        var seen = {};
        var out = [];
        (allUsers || []).forEach(function(u) {
            var obj = key === 'status' ? u.status : (u.type_user || u.typeUser);
            if (!obj || !obj.id || seen[obj.id]) return;
            seen[obj.id] = true;
            var item = { id: obj.id };
            item[labelKey] = obj[labelKey];
            out.push(item);
        });
        return out;
    }

    function umEnsureEditMeta() {
        if (umEditMetaPromise) return umEditMetaPromise;
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var getJSON = function(url) {
            return fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(function(r) { if (!r.ok) throw 0; return r.json(); });
        };
        var statusReq = (userStatusOptionsCache && userStatusOptionsCache.length)
            ? Promise.resolve(userStatusOptionsCache)
            : getJSON(window.APP_BASE+"/user-statuses").catch(function() { return umDistinctFromUsers('status', 'status'); });
        var typeReq = (userTypeOptionsCache && userTypeOptionsCache.length)
            ? Promise.resolve(userTypeOptionsCache)
            : getJSON(window.APP_BASE+"/type-users").catch(function() { return umDistinctFromUsers('type', 'description'); });
        umEditMetaPromise = Promise.all([
            statusReq, typeReq, getJSON(window.APP_BASE+"/countries").catch(function() { return []; })
        ]).then(function(res) {
            umEditStatuses = Array.isArray(res[0]) ? res[0] : [];
            umEditTypes = Array.isArray(res[1]) ? res[1] : [];
            umEditCountries = Array.isArray(res[2]) ? res[2] : [];
        });
        return umEditMetaPromise;
    }

    function umLoadEditProvinces(countryId, selectedId) {
        var sel = document.getElementById('um-edit-province');
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Seleccionar --</option>';
        if (!countryId) return;
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(window.APP_BASE + "/regions?country_id=" + countryId, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(regions) {
            (regions || []).forEach(function(rg) {
                var opt = document.createElement('option');
                opt.value = rg.id;
                opt.textContent = rg.name;
                sel.appendChild(opt);
            });
            if (selectedId) sel.value = String(selectedId);
        })
        .catch(function() {});
    }

    function umSetVal(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = (value === null || value === undefined) ? '' : value;
    }

    function umGetVal(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function umGetAccountPrincipalFor(u) {
        // Devuelve el usuario principal (menor id) que comparte el email de la cuenta.
        if (!u) return null;
        if (!u.email) return u;
        var email = String(u.email).toLowerCase();
        var candidates = (allUsers || []).filter(function(x) {
            return x.email && String(x.email).toLowerCase() === email;
        }).sort(function(a, b) { return Number(a.id) - Number(b.id); });
        return candidates.length ? candidates[0] : u;
    }

    function umGetUserTypeId(u) {
        if (!u) return '';
        return u.type_user_id || (u.type_user && u.type_user.id) || (u.typeUser && u.typeUser.id) || '';
    }

    function fillUserEditForm(u) {
        var label = document.getElementById('um-edit-user-label');
        var empty = document.getElementById('um-edit-empty');
        var body = document.getElementById('um-edit-body');
        if (!label || !empty || !body) return;
        if (!u) {
            label.textContent = 'Seleccioná un usuario de la lista.';
            empty.style.display = 'block';
            body.style.display = 'none';
            return;
        }
        label.textContent = (u.name || 'Usuario') + (u.email ? ' · ' + u.email : '');
        empty.style.display = 'none';
        body.style.display = 'block';
        umSetVal('um-edit-name', u.name || '');
        umSetVal('um-edit-email', u.email || '');
        umSetVal('um-edit-street', '');
        umSetVal('um-edit-number', '');
        showMsg('um-edit-msg', '', true);

        var statusId = u.user_status_id || (u.status && u.status.id) || '';
        var principal = umGetAccountPrincipalFor(u) || u;
        var typeId = umGetUserTypeId(principal);
        var roleLabelEl = document.getElementById('um-edit-role-label');
        if (roleLabelEl) roleLabelEl.textContent = getUserRoleLabel(principal) || 'Usuario';
        umSetVal('um-edit-type', typeId);
        umEnsureEditMeta().then(function() {
            umFillSelect('um-edit-status', umEditStatuses, 'status', statusId);
            umFillSelect('um-edit-country', umEditCountries, 'name', null);
            var addr = umGetUserPrimaryAddress(u);
            umEditAddressId = addr ? addr.id : null;
            if (addr && addr.country_id) {
                document.getElementById('um-edit-country').value = String(addr.country_id);
                umLoadEditProvinces(addr.country_id, addr.province_id);
            }
            umSetVal('um-edit-street', addr ? (addr.street || '') : '');
            umSetVal('um-edit-number', addr ? (addr.number || '') : '');
        });
    }

    function openUserEditModal(id) {
        selectUserManagementUser(id);
        var overlay = document.getElementById('um-edit-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
            overlay.setAttribute('aria-hidden', 'false');
        }
    }
    function closeUserEditModal() {
        var overlay = document.getElementById('um-edit-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }
    }
    function umCancelUserData() {
        var u = getUserManagementSelectedUser() || selectedUser;
        fillUserEditForm(u);
        showMsg('um-edit-msg', 'Cambios descartados.', true);
        closeUserEditModal();
    }

    function umSaveUserData() {
        var user = getUserManagementSelectedUser() || selectedUser;
        if (!user) return;
        var btn = document.getElementById('um-edit-save-btn');
        var name = umGetVal('um-edit-name').trim();
        if (!name) {
            showMsg('um-edit-msg', 'El nombre es obligatorio.', false);
            return;
        }
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var statusVal = umGetVal('um-edit-status');
        // El rol NO es editable: se conserva/enviar siempre el del usuario principal.
        var principal = umGetAccountPrincipalFor(user) || user;
        var typeVal = umGetVal('um-edit-type') || umGetUserTypeId(principal);
        if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }
        showMsg('um-edit-msg', 'Guardando cambios...', true);

        fetch(window.APP_BASE+"/admin/users"+"/" + user.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                name: name,
                email: umGetVal('um-edit-email').trim() || user.email,
                user_status_id: statusVal ? parseInt(statusVal, 10) : null,
                type_user_id: typeVal ? parseInt(typeVal, 10) : null
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                throw new Error(data.errors[firstKey][0]);
            }
            if (!data.success) throw new Error(data.message || 'Error al guardar.');
            if (data.user) applyUserUpdate(data.user);
            var countryId = umGetVal('um-edit-country');
            if (!countryId) return null;
            var addrPayload = {
                country_id: parseInt(countryId, 10),
                province_id: umGetVal('um-edit-province') ? parseInt(umGetVal('um-edit-province'), 10) : null,
                street: umGetVal('um-edit-street').trim(),
                number: umGetVal('um-edit-number').trim(),
                is_primary: true
            };
            var addrUrl = window.APP_BASE+"/admin/users"+"/" + user.id + '/addresses' + (umEditAddressId ? '/' + umEditAddressId : '');
            return fetch(addrUrl, {
                method: umEditAddressId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(addrPayload)
            }).then(function(r) { return r.json(); });
        })
        .then(function() {
            return refreshSelectedUser().then(function() { return loadUsers(); });
        })
        .then(function() {
            if (btn) { btn.disabled = false; btn.textContent = 'Guardar Cambios'; }
            var fresh = getUserManagementSelectedUser() || selectedUser;
            if (fresh) fillUserEditForm(fresh);
            showMsg('um-edit-msg', 'Datos guardados correctamente.', true);
            closeUserEditModal();
        })
        .catch(function(err) {
            if (btn) { btn.disabled = false; btn.textContent = 'Guardar Cambios'; }
            showMsg('um-edit-msg', (err && err.message) || 'Error de conexión.', false);
        });
    }

    function cancelUserPermissions() {
        var user = getUserManagementSelectedUser();
        if (!user) return;
        umDraftUserId = null;
        umSelectedModuleId = null;
        renderUserManagement();
        showUserPermissionMsg('Cambios descartados.', true);
    }

    function saveUserPermissions() {
        var user = getUserManagementSelectedUser();
        if (!user || umSavingPermissions) return;
        if (isParentAccountUser(user) && !actorIsAdmin) {
            showUserPermissionMsg('El usuario principal no puede modificarse desde esta pantalla.', false);
            return;
        }

        var allowedPages = getAllowedPermissionPagesMap();
        var added = [];
        var removed = [];

        Object.keys(umDraftPages).forEach(function(pid) {
            if (allowedPages[pid] && !umOriginalPages[pid]) added.push(pid);
        });
        Object.keys(umOriginalPages).forEach(function(pid) {
            if (allowedPages[pid] && !umDraftPages[pid]) removed.push(pid);
        });

        if (!added.length && !removed.length) {
            showUserPermissionMsg('No hay cambios para guardar.', true);
            return;
        }

        umSavingPermissions = true;
        renderUserPermissionsPanel();
        showUserPermissionMsg('Guardando cambios...', true);

        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var requests = added.map(function(pid) {
            return fetch(window.APP_BASE+"/admin/users"+"/" + user.id + '/pages/' + pid, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).then(function(r) { return r.json(); }).catch(function() { return { success: false }; });
        }).concat(removed.map(function(pid) {
            return fetch(window.APP_BASE+"/admin/users"+"/" + user.id + '/pages/' + pid, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).then(function(r) { return r.json(); }).catch(function() { return { success: false }; });
        }));

        Promise.all(requests).then(function(results) {
            var failed = (results || []).filter(function(r) { return !r || !r.success; });
            var lastUser = null;
            (results || []).forEach(function(r) {
                if (r && r.user) lastUser = r.user;
            });
            umSavingPermissions = false;
            umDraftUserId = null;
            if (lastUser) applyUserUpdate(lastUser);

            return refreshSelectedUser().then(function() {
                return loadUsers();
            }).then(function() {
                umDraftUserId = null;
                renderUserManagement();
                if (typeof umRebuildTopNav === 'function' && umManagedId()) umRenderTopNavForManaged();
                if (typeof umRebuildTopNav === 'function' && String(user.id) === String(authUserId)) {
                    var me = (allUsers || []).find(function(x) { return String(x.id) === String(authUserId); });
                    if (me && me.pages) {
                        var selfAllowed = {};
                        me.pages.forEach(function(pg) { selfAllowed[String(pg.id)] = true; });
                        umRebuildTopNav(selfAllowed);
                    }
                }
                if (failed.length) {
                    showUserPermissionMsg('Algunos permisos no se pudieron guardar (' + failed.length + ').', false);
                } else {
                    showUserPermissionMsg('Permisos guardados correctamente.', true);
                }
            });
        }).catch(function() {
            umSavingPermissions = false;
            umDraftUserId = null;
            renderUserManagement();
            refreshSelectedUser();
            showUserPermissionMsg('Error de conexión al guardar los permisos.', false);
        });
    }

    function openUserDeleteFromList(id) {
        var user = getUserManagementUsers().find(function(u) {
            return String(u.id) === String(id);
        });
        if (!user) return;
        if (isParentAccountUser(user)) {
            alert('No podés eliminar al usuario principal de la cuenta.');
            return;
        }
        openUserDelete(user.id, user.name || user.email || '');
    }

    function renderUserAccountGestion(u) {
        var pane = document.getElementById('user-account-pane-gestion');
        if (!pane) return;
        pane.setAttribute('data-user-id', (u && u.id != null) ? String(u.id) : '');

        if (!u) {
            pane.innerHTML = '';
            return;
        }
        var selectedName = u.name || '-';
        var isSecondary = false;
        var accEmail = (u.email || '').toLowerCase();
        if (accEmail) {
            var principal = allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === accEmail;
            }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0];
            isSecondary = !!principal && String(principal.id) !== String(u.id);
        }
        var editBtnHtml = isSecondary
            ? '<button type="button" id="user-demo-edit-btn-account" title="Editar usuario" aria-label="Editar usuario" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border-radius:4px;background:#fff;color:#D24C19;border:1px solid #D24C19;cursor:pointer;transition:all 0.2s;" onclick="editUserAccount(\'' + u.id + '\')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z"/><path d="m15 5 4 4"/></svg></button>'
            : '';

        pane.innerHTML =
            '<div class="user-demo-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));align-items:end;">' +
                '<div style="grid-column:span 1;"><div class="user-demo-label">Usuario seleccionado</div><div class="user-demo-value" style="font-weight:700;color:#0c2a4d;">' + escapeHtml(selectedName) + '</div></div>' +
                '<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
                    '<div><div class="user-demo-label">Páginas asignadas</div><div class="user-demo-value">' + (u.pages || []).length + '</div></div>' +
                    editBtnHtml +
                '</div>' +
            '</div>';

        var countEl = document.getElementById('user-tab-count-gestion');
        if (countEl) {
            var accCount = accEmail ? allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === accEmail;
            }).length : 1;
            countEl.textContent = String(accCount || 1);
        }
    }

    var userPagesModuleFilter = '';
    var userPagesModulesCache = [];
    var userPagesAllCache = [];

    function getPrincipalAccountUser() {
        var acc = getAccountUsers();
        if (!acc.length) return selectedUser;
        return acc.slice().sort(function(a, b) { return Number(a.id) - Number(b.id); })[0];
    }

    function getEffectiveProvider(u) {
        var target = u || selectedUser;
        if (!target) return null;
        var email = target.email ? String(target.email).toLowerCase() : '';
        var peer = null;
        if (email) {
            peer = allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === email && String(x.id) !== String(target.id) && x.provider;
            }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0] || null;
        }
        if (target.provider) {
            var p = target.provider;
            if ((!p.images || !p.images.length) && peer && peer.provider && peer.provider.images && peer.provider.images.length) {
                p = Object.assign({}, p, { images: peer.provider.images });
            }
            return p;
        }
        return peer ? peer.provider : null;
    }

    function getAccountPeer(u, predicate) {
        var target = u || selectedUser;
        if (!target) return null;
        var email = target.email ? String(target.email).toLowerCase() : '';
        if (!email) return null;
        return allUsers.filter(function(x) {
            return x.email && String(x.email).toLowerCase() === email && String(x.id) !== String(target.id) && predicate(x);
        }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0] || null;
    }

    function getEffectiveAddresses(u) {
        var target = u || selectedUser;
        if (!target) return [];
        if (target.addresses && target.addresses.length) return target.addresses;
        var peer = getAccountPeer(target, function(x) { return x.addresses && x.addresses.length; });
        return peer ? peer.addresses : (target.addresses || []);
    }

    function getEffectivePrimaryAddress(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.primary_address) return target.primary_address;
        if (target.primaryAddress) return target.primaryAddress;
        var list = getEffectiveAddresses(target);
        var primary = list.filter(function(a) { return a.is_primary; })[0];
        if (primary) return primary;
        if (list.length) return list[0];
        var peer = getAccountPeer(target, function(x) { return !!(x.primary_address || x.primaryAddress); });
        return peer ? (peer.primary_address || peer.primaryAddress) : null;
    }

    function getEffectiveStatus(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.status) return target.status;
        var peer = getAccountPeer(target, function(x) { return !!x.status; });
        return peer ? peer.status : null;
    }

    function getEffectiveTypeUser(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.type_user || target.typeUser) return target.type_user || target.typeUser;
        var peer = getAccountPeer(target, function(x) { return !!(x.type_user || x.typeUser); });
        return peer ? (peer.type_user || peer.typeUser) : null;
    }

    // Publicidad: solo usuarios tipo Prestador con estado Activo o Prueba.
    var ADVERTISING_STATUSES = ['activo', 'prueba'];

    function userCanAdvertise(u) {
        var target = u || selectedUser;
        if (!target) return false;
        var typeObj = getEffectiveTypeUser(target);
        var statusObj = getEffectiveStatus(target);
        var type = String(typeObj && (typeObj.description || typeObj.status) || '').trim().toLowerCase();
        var status = String(statusObj && (statusObj.status || statusObj.description) || '').trim().toLowerCase();
        return type === 'prestador' && ADVERTISING_STATUSES.indexOf(status) !== -1;
    }

    function updateUserAdTabVisibility(u) {
        var link = document.querySelector('#user-sidebar .sidebar-link[data-user-panel="imagenes"]');
        if (!link) return;
        var allowed = userCanAdvertise(u);
        link.style.display = allowed ? '' : 'none';
        if (!allowed && currentUserPanelTab() === 'imagenes') {
            switchUserTab('demografia');
        }
    }

    function ensureUserPagesModules() {
        if (userPagesModulesCache.length) return Promise.resolve(userPagesModulesCache);
        if (typeUserPagesModules && typeUserPagesModules.length) {
            userPagesModulesCache = typeUserPagesModules;
            return Promise.resolve(userPagesModulesCache);
        }
        return fetch(window.APP_BASE+"/modules", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(modules) {
            userPagesModulesCache = Array.isArray(modules) ? modules : [];
            if (typeUserPagesModules && !typeUserPagesModules.length) {
                typeUserPagesModules = userPagesModulesCache;
            }
            return userPagesModulesCache;
        })
        .catch(function() { return []; });
    }

    function ensureUserPagesCatalog() {
        if (userPagesAllCache.length) return Promise.resolve(userPagesAllCache);
        if (typeUserPagesAll && typeUserPagesAll.length) {
            userPagesAllCache = typeUserPagesAll;
            return Promise.resolve(userPagesAllCache);
        }
        return fetch(window.APP_BASE+"/pages", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(pages) {
            userPagesAllCache = Array.isArray(pages) ? pages : [];
            if (typeUserPagesAll && !typeUserPagesAll.length) {
                typeUserPagesAll = userPagesAllCache;
            }
            return userPagesAllCache;
        })
        .catch(function() { return []; });
    }

    function getUserPagesModuleId(pg) {
        if (pg.module_id) return Number(pg.module_id);
        if (pg.module && typeof pg.module === 'object' && pg.module.id) return Number(pg.module.id);
        return 0;
    }

    function getUserAssignedPageIds(user) {
        var ids = {};
        ((user && user.pages) || []).forEach(function(pg) {
            ids[String(pg.id)] = true;
        });
        return ids;
    }

    function isSecondaryAccountUser() {
        if (!selectedUser) return false;
        var principal = getPrincipalAccountUser();
        if (!principal) return false;
        return String(principal.id) !== String(selectedUser.id);
    }

    function getPrincipalAllowedPages() {
        var principal = getPrincipalAccountUser();
        if (!principal) return [];
        if (!isSecondaryAccountUser()) return userPagesAllCache;
        var allowed = getUserAssignedPageIds(principal);
        return userPagesAllCache.filter(function(pg) {
            return !!allowed[String(pg.id)];
        });
    }

    function getUserPagesModulesForSelection(allModules) {
        if (!isSecondaryAccountUser()) return allModules;
        var allowedPages = getPrincipalAllowedPages();
        if (!allowedPages.length) return [];
        var moduleIds = {};
        allowedPages.forEach(function(pg) {
            moduleIds[String(getUserPagesModuleId(pg))] = true;
        });
        return allModules.filter(function(m) {
            return !!moduleIds[String(m.id)];
        });
    }

    function getVisibleUserAccountPages() {
        if (!userPagesModuleFilter) return [];
        var mid = parseInt(userPagesModuleFilter, 10);
        return getPrincipalAllowedPages().filter(function(pg) {
            return getUserPagesModuleId(pg) === mid;
        });
    }

    function renderUserAccountPaginas(u) {
        var pane = document.getElementById('user-account-pane-paginas');
        if (!pane) return;

        var target = u || selectedUser;
        var countEl = document.getElementById('user-tab-count-paginas');
        if (countEl) countEl.textContent = String((target && target.pages ? target.pages.length : 0));

        if (!pane.querySelector('#user-pages-module')) {
        pane.innerHTML =
            '<div style="margin-bottom:12px;max-width:320px;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Módulo</label>' +
                '<select id="user-pages-module" onchange="onUserPagesModuleChange()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;box-sizing:border-box;">' +
                    '<option value="">-- Seleccionar módulo --</option>' +
                '</select>' +
            '</div>' +
            '<div id="user-pages-loading" style="padding:24px;text-align:center;display:none;font-size:13px;color:#6b7280;">Cargando...</div>' +
            '<div id="user-pages-empty" class="user-demo-empty-tab" style="display:none;"></div>' +
            '<div id="user-pages-list" style="display:none;overflow-x:auto;">' +
                '<table class="user-pages-grid">' +
                    '<thead>' +
                        '<tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">' +
                            '<th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:70%;">Página</th>' +
                            '<th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:30%;">' +
                                '<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;text-transform:uppercase;">' +
                                    '<input type="checkbox" id="user-pages-check-all" onchange="toggleUserAccountPagesAll(this.checked)" style="width:15px;height:15px;accent-color:#D24C19;cursor:pointer;" title="Agregar o quitar todo" />' +
                                    '<span id="user-pages-check-all-label">Agregar todo</span>' +
                                '</label>' +
                            '</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody id="user-pages-tbody"></tbody>' +
                '</table>' +
                '<div id="user-pages-grid-empty" style="display:none;padding:24px;text-align:center;font-size:13px;color:#6b7280;background:#fff;border:1px solid #e5e7eb;border-top:none;"></div>' +
            '</div>' +
            '<div id="user-pages-msg" class="user-pages-msg" style="display:none;"></div>' +
            '<p id="user-pages-hint" style="margin:12px 0 0;font-size:12px;color:#94a3b8;display:none;"></p>';
        }

        var loading = document.getElementById('user-pages-loading');
        var moduleSel = document.getElementById('user-pages-module');
        if (loading && (!moduleSel || !moduleSel.getAttribute('data-modules'))) loading.style.display = 'block';

        Promise.all([ensureUserPagesModules(), ensureUserPagesCatalog()]).then(function(results) {
            var modules = results[0] || [];
            modules = getUserPagesModulesForSelection(modules);
            if (loading) loading.style.display = 'none';
            if (moduleSel) {
                var preservedModule = userPagesModuleFilter;
                var moduleSignature = modules.map(function(m) { return m.id; }).join(',');
                if (moduleSel.getAttribute('data-modules') !== moduleSignature) {
                    moduleSel.innerHTML = '<option value="">-- Seleccionar módulo --</option>' +
                        modules.map(function(m) {
                            return '<option value="' + m.id + '">' + escapeHtml(m.description || m.name || ('Módulo ' + m.id)) + '</option>';
                        }).join('');
                    moduleSel.setAttribute('data-modules', moduleSignature);
                }
                var moduleExists = !!preservedModule && modules.some(function(m) {
                    return String(m.id) === String(preservedModule);
                });
                if (moduleExists) {
                    moduleSel.value = String(preservedModule);
                } else {
                    userPagesModuleFilter = '';
                    moduleSel.value = '';
                }
            }
            renderUserAccountPaginasRows();
        });
    }

    function onUserPagesModuleChange() {
        var sel = document.getElementById('user-pages-module');
        var value = sel ? sel.value : '';
        if (value === userPagesModuleFilter) return;
        userPagesModuleFilter = value;
        renderUserAccountPaginasRows();
    }

    function renderUserAccountPaginasRows() {
        var tbody = document.getElementById('user-pages-tbody');
        var list = document.getElementById('user-pages-list');
        var empty = document.getElementById('user-pages-empty');
        var gridEmpty = document.getElementById('user-pages-grid-empty');
        var hint = document.getElementById('user-pages-hint');
        if (!tbody || !list) return;

        if (!selectedUser) {
            if (list) list.style.display = 'none';
            if (empty) {
                empty.textContent = 'Seleccioná un usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            updateUserPagesCheckAll();
            return;
        }

        if (isSecondaryAccountUser() && !getPrincipalAllowedPages().length) {
            if (list) list.style.display = 'none';
            if (empty) {
                empty.textContent = 'El usuario principal no tiene módulos ni páginas asignadas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            updateUserPagesCheckAll();
            return;
        }

        if (!userPagesModuleFilter) {
            if (list) list.style.display = 'none';
            if (empty) empty.style.display = 'none';
            if (hint) hint.style.display = 'none';
            updateUserPagesCheckAll();
            return;
        }

        if (empty) empty.style.display = 'none';

        var pages = getVisibleUserAccountPages();
        var selectedIds = getUserAssignedPageIds(selectedUser);

        if (!pages.length) {
            tbody.innerHTML = '';
            list.style.display = 'block';
            if (gridEmpty) {
                gridEmpty.textContent = 'No hay páginas disponibles para este módulo.';
                gridEmpty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            updateUserPagesCheckAll();
            return;
        }

        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }

        tbody.innerHTML = pages.map(function(pg, idx) {
            var moduleName = (pg.module && (typeof pg.module === 'object' ? (pg.module.description || pg.module.name) : pg.module)) || 'Sin módulo';
            var assigned = !!selectedIds[String(pg.id)];
            var bg = idx % 2 === 0 ? '#fff' : '#f8fafc';
            var meta = [];
            if (moduleName) meta.push(escapeHtml(moduleName));
            if (pg.url) meta.push(escapeHtml(pg.url));
            return '<tr style="background:' + bg + ';border-bottom:1px solid #e5e7eb;">' +
                '<td style="padding:10px 16px;font-size:13px;color:#0f172a;vertical-align:middle;">' +
                    '<div style="font-weight:600;">' + escapeHtml(pg.description || '-') + '</div>' +
                    (meta.length ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">' + meta.join(' · ') + '</div>' : '') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;vertical-align:middle;">' +
                    '<input type="checkbox" class="user-pages-check" value="' + pg.id + '"' + (assigned ? ' checked' : '') + ' style="width:16px;height:16px;accent-color:#D24C19;cursor:pointer;" title="Agregar o quitar página" onchange="toggleUserAccountPage(' + pg.id + ', this.checked)" />' +
                    '<div style="font-size:10px;color:#64748b;margin-top:2px;">' + (assigned ? 'Agregada' : 'Agregar') + '</div>' +
                '</td>' +
            '</tr>';
        }).join('');

        list.style.display = 'block';
        if (hint) {
            if (isSecondaryAccountUser()) {
                hint.textContent = 'Solo módulos y páginas visibles para el usuario principal. El check guarda en la tabla de páginas de usuarios de ' + (selectedUser.name || 'este usuario') + '.';
            } else {
                hint.textContent = 'Todas las páginas del módulo. El check guarda en la tabla de páginas de usuarios del usuario seleccionado (' + (selectedUser.name || '') + ').';
            }
            hint.style.display = 'block';
        }
        updateUserPagesCheckAll();
    }

    function getVisibleUserAccountPageIds() {
        return getVisibleUserAccountPages().map(function(pg) { return pg.id; });
    }

    function paintUserPagesCheckAll(checked, indeterminate) {
        var header = document.getElementById('user-pages-check-all');
        var label = document.getElementById('user-pages-check-all-label');
        if (header) {
            header.checked = !!checked;
            header.indeterminate = !!indeterminate;
            header.disabled = false;
        }
        if (label) label.textContent = checked ? 'Quitar todo' : 'Agregar todo';
    }

    function updateUserPagesCheckAll() {
        var header = document.getElementById('user-pages-check-all');
        if (!header) return;
        var list = document.getElementById('user-pages-list');
        if (!list || list.style.display === 'none') {
            paintUserPagesCheckAll(false, false);
            return;
        }
        var visibleIds = getVisibleUserAccountPageIds();
        if (!visibleIds.length) {
            paintUserPagesCheckAll(false, false);
            return;
        }
        var assigned = getUserAssignedPageIds(selectedUser);
        var onCount = 0;
        visibleIds.forEach(function(id) {
            if (assigned[String(id)]) onCount++;
        });
        paintUserPagesCheckAll(
            onCount === visibleIds.length,
            onCount > 0 && onCount < visibleIds.length
        );
    }

    function toggleUserAccountPagesAll(checked) {
        var visibleIds = getVisibleUserAccountPageIds();
        if (!selectedUser || !visibleIds.length) {
            paintUserPagesCheckAll(false, false);
            showUserPagesMsg('No hay páginas visibles para actualizar.', false);
            return;
        }

        visibleIds.forEach(function(id) {
            var sel = document.querySelector('.user-pages-check[value="' + id + '"]');
            if (sel) sel.checked = checked;
        });
        paintUserPagesCheckAll(checked, false);

        Promise.all(visibleIds.map(function(id) {
            var url = window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/pages/' + id;
            return fetch(url, {
                method: checked ? 'POST' : 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(function(r) { return r.json(); }).catch(function() { return { success: false }; });
        }))
        .then(function(results) {
            var failed = results.filter(function(r) { return !r.success; });
            var lastOk = results.find(function(r) { return r.success && r.user; });
            if (lastOk && lastOk.user) applyUserUpdate(lastOk.user);
            refreshSelectedUser().then(function() {
                return loadUsers();
            }).then(function() {
                renderUserAccountPaginasRows();
            });
            if (failed.length) {
                showUserPagesMsg('Algunas páginas no se pudieron actualizar (' + failed.length + ').', false);
            } else {
                showUserPagesMsg(checked ? 'Páginas agregadas.' : 'Páginas quitadas.', true);
            }
        });
    }

    function showUserPagesMsg(text, ok) {
        var el = document.getElementById('user-pages-msg');
        if (!el) return;
        el.textContent = text;
        el.style.display = 'block';
        el.classList.toggle('ok', !!ok);
        el.classList.toggle('err', !ok);
    }

    function toggleUserAccountPage(pageId, checked) {
        if (!selectedUser || !pageId) return;
        var checkbox = document.querySelector('.user-pages-check[value="' + pageId + '"]');
        if (checkbox) checkbox.disabled = true;

        var url = window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/pages/' + pageId;
        fetch(url, {
            method: checked ? 'POST' : 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (checkbox) checkbox.disabled = false;
            if (data.success) {
                showUserPagesMsg(data.message || (checked ? 'Página agregada.' : 'Página quitada.'), true);
                if (data.user) applyUserUpdate(data.user);
                refreshSelectedUser().then(function() {
                    return loadUsers();
                }).then(function() {
                    renderUserAccountPaginasRows();
                });
            } else {
                showUserPagesMsg(data.message || 'No se pudo actualizar la página.', false);
                if (checkbox) checkbox.checked = !checked;
                updateUserPagesCheckAll();
            }
        })
        .catch(function() {
            if (checkbox) {
                checkbox.disabled = false;
                checkbox.checked = !checked;
            }
            showUserPagesMsg('Error de conexión.', false);
            updateUserPagesCheckAll();
        });
    }

    function renderUserImages(u) {
        var dropzone = document.getElementById('user-demo-images-dropzone');
        var noProvider = document.getElementById('user-demo-images-no-provider');
        var grid = document.getElementById('user-demo-images-grid');
        var empty = document.getElementById('user-demo-images-empty');
        var msg = document.getElementById('user-demo-images-msg');
        if (!dropzone || !noProvider || !grid || !empty) return;

        if (msg) {
            msg.style.display = 'none';
            msg.textContent = '';
            msg.classList.remove('ok', 'err');
        }

        var p = getEffectiveProvider(u);
        if (!p) {
            dropzone.style.display = 'none';
            noProvider.style.display = 'block';
            grid.style.display = 'none';
            grid.innerHTML = '';
            empty.style.display = 'none';
            return;
        }

        noProvider.style.display = 'none';
        var limitNote = document.getElementById('user-demo-images-limit');
        if (limitNote) limitNote.style.display = 'none';

        var images = p.images || [];
        if (!images.length) {
            dropzone.style.display = '';
            grid.style.display = 'none';
            grid.innerHTML = '';
            empty.style.display = 'block';
            setupUserDemoImagesDropzone();
            return;
        }

        dropzone.style.display = 'none';
        empty.style.display = 'none';
        grid.style.display = 'grid';
        grid.innerHTML = images.map(function(img) {
            var src = window.APP_BASE+"/images/publicidad"+"/" + img.image_path + '?v=' + Date.now();
            var fileName = img.image_path || ('publicidad_' + img.id);
            return '<div style="position:relative;border:1px solid #e2e8f0;background:#fff;overflow:hidden;">' +
                '<img src="' + src + '" style="width:100%;height:180px;object-fit:cover;display:block;" />' +
                '<span class="img-type-badge">Publicidad</span>' +
                '<button type="button" onclick="downloadUserDemoImage(\'' + encodeURIComponent(src) + '\', \'' + String(fileName).replace(/'/g, '') + '\')" title="Descargar imagen" style="position:absolute;top:6px;right:36px;background:rgba(12,42,77,0.92);color:#fff;border:none;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#8595;</button>' +
                '<button type="button" onclick="deleteUserDemoImage(' + img.id + ')" title="Eliminar imagen" style="position:absolute;top:6px;right:6px;background:rgba(220,38,38,0.95);color:#fff;border:none;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#10005;</button>' +
                '</div>';
        }).join('');

        if (!limitNote) {
            limitNote = document.createElement('div');
            limitNote.id = 'user-demo-images-limit';
            limitNote.style.cssText = 'margin-top:12px;padding:10px 14px;background:#fff7ed;border:1px solid #fed7aa;font-size:12px;color:#9a3412;';
            limitNote.innerHTML = '&#9432; Solo se permite una imagen publicitaria. Eliminá la actual para subir una nueva.';
            grid.parentNode.insertBefore(limitNote, grid.nextSibling);
        }
        limitNote.style.display = 'block';
    }

    function setupUserDemoImagesDropzone() {
        var zone = document.getElementById('user-demo-images-dropzone');
        var input = document.getElementById('user-demo-images-input');
        if (!zone || !input || zone._userDemoBound) return;
        zone._userDemoBound = true;

        zone.addEventListener('click', function(e) {
            if (e.target !== input) input.click();
        });
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', function() {
            zone.classList.remove('dragover');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                uploadUserDemoImage(e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                uploadUserDemoImage(input.files[0]);
                input.value = '';
            }
        });
    }

    function showUserDemoImagesMsg(text, ok) {
        var msg = document.getElementById('user-demo-images-msg');
        if (!msg) return;
        msg.textContent = text;
        msg.style.display = 'block';
        msg.classList.toggle('ok', !!ok);
        msg.classList.toggle('err', !ok);
    }

    function uploadUserDemoImage(file) {
        if (!selectedUser || !file) return;
        if (file.size > 5 * 1024 * 1024) {
            showUserDemoImagesMsg('La imagen no puede superar 5MB.', false);
            return;
        }

        var formData = new FormData();
        formData.append('image', file);

        showUserDemoImagesMsg('Subiendo imagen...', true);

        fetch(window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/images', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showUserDemoImagesMsg(data.message || 'Imagen subida correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
            } else {
                showUserDemoImagesMsg(data.message || 'No se pudo subir la imagen.', false);
            }
        })
        .catch(function() {
            showUserDemoImagesMsg('Error de conexión.', false);
        });
    }

    function downloadUserDemoImage(encodedSrc, fileName) {
        var src = decodeURIComponent(encodedSrc);
        fetch(src, { cache: 'no-cache' })
            .then(function(r) {
                if (!r.ok) throw new Error('fail');
                return r.blob();
            })
            .then(function(blob) {
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(function() {
                var a = document.createElement('a');
                a.href = src;
                a.download = fileName;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                a.remove();
            });
    }

    function deleteUserDemoImage(imageId) {
        if (!selectedUser) return;
        if (!confirm('¿Eliminar esta imagen?')) return;

        fetch(window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/images/' + imageId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showUserDemoImagesMsg(data.message || 'Imagen eliminada correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
            } else {
                showUserDemoImagesMsg(data.message || 'No se pudo eliminar.', false);
            }
        })
        .catch(function() {
            showUserDemoImagesMsg('Error de conexión.', false);
        });
    }

    function clearUserDemographic() {
        selectedUser = null;
        umSelectedUserId = null;
        umDraftUserId = null;
        var empty = document.getElementById('user-demo-empty');
        var panel = document.getElementById('user-demo-panel');
        if (empty) empty.style.display = 'block';
        if (panel) panel.style.display = 'none';
        ['user-demo-save-btn', 'user-prov-save-btn', 'user-addr-save-btn'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        renderUserManagement();
    }

    function renderUserDemographic(u) {
        var demoEmpty = document.getElementById('user-demo-empty');
        var demoPanel = document.getElementById('user-demo-panel');
        if (!demoEmpty || !demoPanel) {
            if (u) {
                umSelectedUserId = u.id;
                selectedUser = u;
                umDraftUserId = null;
                renderUserManagement();
            }
            return;
        }
        demoEmpty.style.display = 'none';
        demoPanel.style.display = 'block';

        ['user-demo-save-btn', 'user-prov-save-btn', 'user-addr-save-btn'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = '';
        });

        switchUserTab(currentUserPanelTab() || 'demografia');

        var nameInput = document.getElementById('user-demo-name');
        var emailInput = document.getElementById('user-demo-email');
        var createdInput = document.getElementById('user-demo-created');
        var verifiedCb = document.getElementById('user-demo-verified');
        var statusSel = document.getElementById('user-demo-status-id');
        var typeSel = document.getElementById('user-demo-type-id');
        var msg = document.getElementById('user-demo-msg');
        ['user-demo-name-error', 'user-demo-email-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; }

        var formU = getPrincipalAccountUser() || u;
        if (nameInput) nameInput.value = formU.name || '';
        if (emailInput) emailInput.value = formU.email || '';
        if (createdInput) createdInput.value = formU.created_at ? new Date(formU.created_at).toLocaleString('es-AR') : '';
        if (verifiedCb) verifiedCb.checked = !!formU.email_verified_at;

        var hdrU = formU;
        var hdrName = document.getElementById('user-demo-hdr-name');
        var hdrEmail = document.getElementById('user-demo-hdr-email');
        var hdrAvatar = document.getElementById('user-demo-avatar');
        var hdrStatus = document.getElementById('user-demo-status');
        var hdrType = document.getElementById('user-demo-type');
        if (hdrName) hdrName.textContent = hdrU.name || '';
        if (hdrEmail) hdrEmail.textContent = hdrU.email || '';
        if (hdrAvatar) hdrAvatar.textContent = ((hdrU.name || '?').trim().charAt(0) || '?').toUpperCase();

        var statusObj = getEffectiveStatus(formU) || formU.status || null;
        var typeObj = getEffectiveTypeUser(formU) || formU.type_user || formU.typeUser || null;
        var statusId = formU.user_status_id || (statusObj && statusObj.id) || '';
        var typeId = formU.type_user_id || (typeObj && typeObj.id) || '';
        var hdrStatusObj = hdrU.status || getEffectiveStatus(hdrU);
        var hdrTypeObj = hdrU.type_user || hdrU.typeUser || getEffectiveTypeUser(hdrU);
        if (hdrStatus) {
            hdrStatus.textContent = (hdrStatusObj && (hdrStatusObj.status || hdrStatusObj.description)) || 'Activo';
            var stKey = String(hdrStatus.textContent).trim().toLowerCase();
            if (stKey === 'inactive' || stKey === 'inactivo' || stKey === 'inactiva') {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;';
            } else if (stKey === 'prueba') {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#fffbeb;border:1px solid #fde68a;color:#b45309;';
            } else {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;';
            }
        }
        if (hdrType) hdrType.textContent = (hdrTypeObj && (hdrTypeObj.description || hdrTypeObj.status)) || 'Prestador';
        if (statusSel) {
            statusSel._pendingValue = statusId ? String(statusId) : '';
            if (userStatusOptionsCache) fillUserStatusSelect(statusSel, statusSel._pendingValue);
            else loadUserStatusOptions();
        }
        if (typeSel) {
            typeSel._pendingValue = typeId ? String(typeId) : '';
            if (userTypeOptionsCache) fillUserTypeSelect(typeSel, typeSel._pendingValue);
            else loadTypeUserOptions();
        }

        renderUserAddresses(u);
        renderUserProvider(u);
        renderUserAccount(u);
        renderUserImages(u);
        updateUserAdTabVisibility(u);
    }

    function submitUserDemographic() {
        if (!selectedUser) return;
        var target = getPrincipalAccountUser() || selectedUser;
        var name = document.getElementById('user-demo-name').value.trim();
        var email = document.getElementById('user-demo-email').value.trim();
        var statusSel = document.getElementById('user-demo-status-id');
        var typeSel = document.getElementById('user-demo-type-id');
        var verifiedCb = document.getElementById('user-demo-verified');
        var nameErr = document.getElementById('user-demo-name-error');
        var emailErr = document.getElementById('user-demo-email-error');
        var msg = document.getElementById('user-demo-msg');
        var btn = document.getElementById('user-demo-save-btn');

        nameErr.style.display = 'none';
        emailErr.style.display = 'none';
        msg.style.display = 'none';

        if (!name) {
            nameErr.textContent = 'El nombre es obligatorio.';
            nameErr.style.display = 'block';
            return;
        }
        if (!email) {
            emailErr.textContent = 'El email es obligatorio.';
            emailErr.style.display = 'block';
            return;
        }

        var body = {
            name: name,
            email: email,
            user_status_id: statusSel && statusSel.value ? parseInt(statusSel.value, 10) : null,
            type_user_id: typeSel && typeSel.value ? parseInt(typeSel.value, 10) : null,
            email_verified: !!(verifiedCb && verifiedCb.checked)
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(window.APP_BASE+"/admin/users"+"/" + target.id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar datos';
            if (data.errors) {
                if (data.errors.name) { nameErr.textContent = data.errors.name[0]; nameErr.style.display = 'block'; }
                if (data.errors.email) { emailErr.textContent = data.errors.email[0]; emailErr.style.display = 'block'; }
                return;
            }
            if (data.success) {
                msg.textContent = 'Guardado correctamente.';
                msg.className = 'save-msg ok';
                msg.style.display = 'inline';
                if (data.user) applyUserUpdate(data.user);
                refreshSelectedUser();
                setTimeout(function() { msg.style.display = 'none'; }, 2500);
            } else {
                msg.textContent = data.message || 'Error al guardar.';
                msg.className = 'save-msg err';
                msg.style.display = 'inline';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar datos';
            msg.textContent = 'Error de conexión.';
            msg.className = 'save-msg err';
            msg.style.display = 'inline';
        });
    }

    var userStatusOptionsCache = null;
    var userTypeOptionsCache = null;
    var userStatusOptReqId = 0;
    var userTypeOptReqId = 0;

    function eachStatusSelect(fn) {
        ['user-demo-status-id', 'user-filter-status'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) fn(el);
        });
    }

    function eachTypeSelect(fn) {
        ['user-demo-type-id', 'user-filter-type'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) fn(el);
        });
    }

    function fillUserStatusSelect(select, selectedValue) {
        var isFilter = select.id.indexOf('filter') !== -1;
        select.innerHTML = isFilter ? '<option value="">Todos los estados</option>' : '<option value="">-- Sin estado --</option>';
        (userStatusOptionsCache || []).forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.status;
            select.appendChild(opt);
        });
        if (selectedValue) select.value = String(selectedValue);
        select._pendingValue = '';
    }

    function fillUserTypeSelect(select, selectedValue) {
        var isFilter = select.id.indexOf('filter') !== -1;
        select.innerHTML = isFilter ? '<option value="">Todos los tipos</option>' : '<option value="">-- Sin tipo --</option>';
        (userTypeOptionsCache || []).forEach(function(t) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.description;
            select.appendChild(opt);
        });
        if (selectedValue) select.value = String(selectedValue);
        select._pendingValue = '';
    }

    function loadUserStatusOptions() {
        if (userStatusOptionsCache) {
            eachStatusSelect(function(sel) {
                fillUserStatusSelect(sel, sel._pendingValue || sel.value || '');
            });
            return;
        }
        var reqId = ++userStatusOptReqId;
        fetch(window.APP_BASE+"/user-statuses", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userStatusOptReqId) return;
            userStatusOptionsCache = Array.isArray(data) ? data : [];
            eachStatusSelect(function(sel) {
                fillUserStatusSelect(sel, sel._pendingValue || sel.value || '');
            });
        })
        .catch(function() {});
    }

    function loadTypeUserOptions() {
        if (userTypeOptionsCache) {
            eachTypeSelect(function(sel) {
                fillUserTypeSelect(sel, sel._pendingValue || sel.value || '');
            });
            return;
        }
        var reqId = ++userTypeOptReqId;
        fetch(window.APP_BASE+"/type-users", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userTypeOptReqId) return;
            userTypeOptionsCache = Array.isArray(data) ? data : [];
            eachTypeSelect(function(sel) {
                fillUserTypeSelect(sel, sel._pendingValue || sel.value || '');
            });
        })
        .catch(function() {});
    }

    function renderUsers(users) {
        var tbody = document.getElementById('users-tbody');
        var loading = document.getElementById('users-loading');
        var empty = document.getElementById('users-empty');
        var tableWrap = document.getElementById('users-table-wrap');
        var pagination = document.getElementById('users-pagination');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (users.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            pagination.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        var totalPages = Math.ceil(users.length / userPerPage);
        if (userCurrentPage > totalPages) userCurrentPage = totalPages;
        if (userCurrentPage < 1) userCurrentPage = 1;

        var start = (userCurrentPage - 1) * userPerPage;
        var end = start + userPerPage;
        var pageItems = users.slice(start, end);

        pageItems.forEach(function(u) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', u.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            var uStatus = (u.status && u.status.status) || '';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(u.name) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;">' + escapeHtml(u.email) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;">' + escapeHtml(getUserRoleLabel(u)) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;">' + escapeHtml(uStatus) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="selectUser(' + u.id + ')" title="Seleccionar / Editar" aria-label="Seleccionar / Editar" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:4px;background:#D24C19;border:1px solid #D24C19;cursor:pointer;color:#fff;font-size:12px;font-weight:600;transition:all 0.2s;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>Seleccionar</button>' +
                '</td>';
            tbody.appendChild(tr);
        });

        if (totalPages <= 1) { pagination.style.display = 'none'; return; }
        pagination.style.display = 'flex';

        var dots = document.getElementById('users-page-dots');
        dots.innerHTML = '';
        for (var p = 1; p <= totalPages; p++) {
            var d = document.createElement('span');
            d.textContent = p;
            d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (p === userCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
            d.onmouseenter = function () { if (parseInt(this.textContent) !== userCurrentPage) this.style.color = '#374151'; };
            d.onmouseleave = function () { if (parseInt(this.textContent) !== userCurrentPage) this.style.color = '#9ca3af'; };
            d.onclick = function () { userCurrentPage = parseInt(this.textContent); renderUsers(allUsers._filtered || allUsers); };
            dots.appendChild(d);
        }
        document.getElementById('users-prev-page').style.opacity = userCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('users-prev-page').style.pointerEvents = userCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('users-next-page').style.opacity = userCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('users-next-page').style.pointerEvents = userCurrentPage === totalPages ? 'none' : 'auto';
    }

    function userPage(dir) {
        var data = allUsers._filtered || allUsers;
        var totalPages = Math.ceil(data.length / userPerPage);
        if (dir === 1 && userCurrentPage > 1) { userCurrentPage--; renderUsers(data); }
        if (dir === 2 && userCurrentPage < totalPages) { userCurrentPage++; renderUsers(data); }
    }

    function filterUsers() {
        userCurrentPage = 1;
        var q = document.getElementById('user-search').value.toLowerCase();
        var statusId = document.getElementById('user-filter-status') ? document.getElementById('user-filter-status').value : '';
        var typeId = document.getElementById('user-filter-type') ? document.getElementById('user-filter-type').value : '';
        var filtered = getSelectableUsers().filter(function(u) {
            var matchQ = u.name.toLowerCase().indexOf(q) !== -1 || u.email.toLowerCase().indexOf(q) !== -1;
            var uStatusId = u.user_status_id || (u.status && u.status.id) || '';
            var uTypeId = u.type_user_id || (u.type_user && u.type_user.id) || (u.typeUser && u.typeUser.id) || '';
            var matchStatus = !statusId || String(uStatusId) === String(statusId);
            var matchType = !typeId || String(uTypeId) === String(typeId);
            return matchQ && matchStatus && matchType;
        });
        allUsers._filtered = filtered;
        renderUsers(filtered);
    }

    function toggleUserPasswordVisibility(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input || !btn) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = show
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="M2 2l20 20"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>';
    }

    function resetUserForm() {
        var el = function(name) { return document.getElementById(name); };
        ['user-id', 'user-name', 'user-email', 'user-password', 'user-password-confirm'].forEach(function(name) {
            var input = el(name);
            if (input) input.value = '';
        });
        setErr('user-name-error', '');
        setErr('user-email-error', '');
        setErr('user-password-error', '');
        setErr('user-password-confirm-error', '');
        setErr('user-form-error', '');

        var submitBtn = el('user-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar';
        }
        var title = el('user-modal-title');
        if (title) title.textContent = 'Nuevo Usuario';
        var deleteBtn = el('user-modal-delete-btn');
        if (deleteBtn) deleteBtn.style.display = 'none';
        var confirmField = el('user-password-confirm-field');
        if (confirmField) confirmField.style.display = 'block';
        var confirmReq = el('user-password-confirm-required');
        if (confirmReq) confirmReq.style.display = 'inline';
        var pwReq = el('user-password-required');
        if (pwReq) pwReq.style.display = 'inline';
        var pwHint = el('user-password-hint');
        if (pwHint) pwHint.style.display = 'none';
    }

    function openUserModal(id) {
        var isCreating = (id === null || id === undefined || id === '');
        if (isCreating && countAccountSecondaries() >= USER_MAX_SECONDARIES) {
            alert('Ya alcanzaste el límite de ' + USER_MAX_SECONDARIES + ' usuarios adicionales para esta cuenta.');
            updateCreateUserBtnVisibility();
            return;
        }

        var overlay = document.getElementById('user-modal-overlay');
        if (overlay) {
            // Abrimos el modal primero: aunque falte algun campo opcional, el
            // clic siempre tiene que producir una accion visible.
            overlay.style.display = 'flex';
            overlay.setAttribute('aria-hidden', 'false');
        }
        resetUserForm();

        var u = null;
        if (id !== null && id !== undefined && id !== '') {
            u = allUsers.find(function(x) { return String(x.id) === String(id); });
            if (!u && selectedUser && String(selectedUser.id) === String(id)) {
                u = selectedUser;
            }
        }
        if (!u && selectedUser && (id === selectedUser.id || String(id) === String(selectedUser.id))) {
            u = selectedUser;
        }

        var el = function(name) { return document.getElementById(name); };
        var setDisplay = function(name, value) {
            var node = el(name);
            if (node) node.style.display = value;
        };

        var emailInput = el('user-email');
        var confirmField = el('user-password-confirm-field');
        var confirmReq = el('user-password-confirm-required');
        var deleteBtn = el('user-modal-delete-btn');
        var idInput = el('user-id');
        var nameInput = el('user-name');
        if (idInput) idInput.value = u ? u.id : '';
        if (nameInput) nameInput.value = u ? u.name : '';
        var principal = getPrincipalAccountUser() || u || selectedUser;
        var managedEmail = (typeof umManagedEmail === 'function') ? umManagedEmail() : null;
        // El email propio manda: los secundarios ya lo comparten con el
        // titular; las cuentas independientes conservan el suyo.
        var accountEmail = (u && u.email) || managedEmail || (principal && principal.email) || (selectedUser && selectedUser.email) || authEmail || '';
        if (emailInput) emailInput.value = accountEmail;
        if (emailInput) emailInput.disabled = true;
        if (deleteBtn) deleteBtn.style.display = u ? 'inline-block' : 'none';
        var pwInput = el('user-password');
        if (pwInput) {
            pwInput.value = '';
            pwInput.type = 'password';
        }
        var confirmInput = el('user-password-confirm');
        if (confirmInput) {
            confirmInput.value = '';
            confirmInput.type = 'password';
        }
        document.querySelectorAll('.user-pw-toggle').forEach(function(btn) {
            if (!btn.dataset.target) return;
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>';
        });
        setDisplay('user-name-error', 'none');
        setDisplay('user-password-error', 'none');
        setDisplay('user-password-confirm-error', 'none');
        setDisplay('user-password-required', u ? 'none' : 'inline');
        setDisplay('user-password-hint', u ? 'block' : 'none');
        if (confirmField) confirmField.style.display = u ? 'none' : 'block';
        if (confirmReq) confirmReq.style.display = u ? 'none' : 'inline';
        var title = el('user-modal-title');
        if (title) title.textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        var submitBtn = el('user-submit-btn');
        if (submitBtn) submitBtn.textContent = u ? 'Actualizar' : 'Guardar';
        if (nameInput) nameInput.focus();
    }

    window.openUserModal = openUserModal;

    // Respaldo por delegacion: si el onclick inline del boton no se ejecuta
    // (sanitizadores, extensiones, CSP o un handler removido), este listener
    // abre igualmente el modal cuando el overlay todavia esta cerrado.
    document.addEventListener('click', function(event) {
        var target = event.target;
        var btn = target && target.closest ? target.closest('#user-create-btn') : null;
        if (!btn) return;
        var overlay = document.getElementById('user-modal-overlay');
        if (overlay && overlay.style.display === 'flex') return;
        event.preventDefault();
        openUserModal(null);
    });

    // Respaldo por delegacion para el submit del formulario: si el onsubmit
    // inline no se ejecuta, el submit igual llega al backend.
    document.addEventListener('submit', function(event) {
        var form = event.target;
        if (!form || form.id !== 'user-form') return;
        if (event.defaultPrevented) return;
        event.preventDefault();
        if (typeof submitUser === 'function') submitUser(event);
    });

    function closeUserModal() {
        var overlay = document.getElementById('user-modal-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }
    }

    function userModalDelete() {
        var id = document.getElementById('user-id').value;
        if (!id) {
            closeUserModal();
            return;
        }
        var u = allUsers.find(function(x) { return String(x.id) === String(id); });
        if (!u && selectedUser && String(selectedUser.id) === String(id)) u = selectedUser;
        var name = (u && u.name) || document.getElementById('user-name').value || '';
        closeUserModal();
        openUserDelete(id, name);
    }

    function editUser(id) {
        editUserAccount(id);
    }

    function isInactiveUserStatus(u) {
        var st = (u && u.status) ? String(u.status.status || u.status.description || '') : '';
        st = st.trim().toLowerCase();
        return st === 'inactive' || st === 'inactivo';
    }

    function editUserAccount(id) {
        var u = null;
        if (id !== null && id !== undefined && id !== '') {
            u = allUsers.find(function(x) { return String(x.id) === String(id); });
            if (!u && selectedUser && String(selectedUser.id) === String(id)) u = selectedUser;
        }
        if (u && isInactiveUserStatus(u)) {
            if (!confirm('\u00bfEl usuario ' + (u.name || '') + ' est\u00e1 inactivo. \u00bfQuer\u00e9s activarlo?')) {
                return;
            }
            activateInactiveUser(u);
            return;
        }
        openUserModal(id);
    }

    function activateInactiveUser(u) {
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var jsonHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        };
        var statusesPromise = userStatusOptionsCache
            ? Promise.resolve(userStatusOptionsCache)
            : fetch(window.APP_BASE+"/user-statuses", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                userStatusOptionsCache = Array.isArray(data) ? data : [];
                return userStatusOptionsCache;
            });

        statusesPromise.then(function(list) {
            var active = null;
            (list || []).forEach(function(s) {
                var n = String(s.status || '').trim().toLowerCase();
                if (!active && (n === 'active' || n === 'activo')) active = s;
            });
            if (!active) return;
            // El email es siempre el del propio usuario: nunca se pisa con
            // el del titular (los secundarios ya lo comparten en la base).
            var body = {
                name: u.name,
                user_status_id: active.id,
                type_user_id: u.type_user_id || (u.type_user && u.type_user.id) || null
            };
            if (u.email) body.email = u.email;
            return fetch(window.APP_BASE+"/admin/users"+"/" + u.id, {
                method: 'PUT',
                headers: jsonHeaders,
                body: JSON.stringify(body)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (data.user) applyUserUpdate(data.user);
                    loadUsers().then(function() { refreshSelectedUser(); });
                }
            });
        }).catch(function() {});
    }

    function submitUser(e) {
        if (e && e.preventDefault) e.preventDefault();
        var el = function(name) { return document.getElementById(name); };
        var id = el('user-id') ? el('user-id').value : '';
        var name = el('user-name') ? el('user-name').value.trim() : '';
        var emailEl = el('user-email');
        var emailInputVal = emailEl ? emailEl.value.trim() : '';
        var principal = getPrincipalAccountUser() || selectedUser;
        var managedEmail = (typeof umManagedEmail === 'function') ? umManagedEmail() : null;
        // El email propio manda (el input viene prellenado con el del usuario
        // en edicion; los secundarios ya lo comparten con el titular).
        var email = emailInputVal || (selectedUser && selectedUser.email) || managedEmail || (principal && principal.email) || authEmail || '';
        var password = el('user-password') ? el('user-password').value : '';
        var passwordConfirm = el('user-password-confirm') ? el('user-password-confirm').value : '';
        var target = selectedUser;
        if (id) {
            target = allUsers.find(function(x) { return String(x.id) === String(id); }) || selectedUser;
            if (selectedUser && String(selectedUser.id) === String(id)) target = selectedUser;
        }
        var statusId = null;
        var typeId = null;
        if (target) {
            statusId = target.user_status_id || (target.status && target.status.id) || null;
            typeId = target.type_user_id || (target.type_user && target.type_user.id) || null;
        }

        setErr('user-name-error', '');
        setErr('user-email-error', '');
        setErr('user-password-error', '');
        setErr('user-password-confirm-error', '');
        setErr('user-form-error', '');

        var submitBtn = el('user-submit-btn');
        var hasError = false;

        if (!name) {
            setErr('user-name-error', 'El nombre es obligatorio.');
            hasError = true;
        }
        if (!email) {
            setErr('user-email-error', 'El email es obligatorio.');
            hasError = true;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setErr('user-email-error', 'Ingresá un email válido.');
            hasError = true;
        }
        if (!id && password.length < 4) {
            setErr('user-password-error', 'La contraseña debe tener al menos 4 caracteres.');
            hasError = true;
        }
        if (!id) {
            if (!passwordConfirm) {
                setErr('user-password-confirm-error', 'Confirmá la contraseña.');
                hasError = true;
            } else if (password !== passwordConfirm) {
                setErr('user-password-confirm-error', 'Las contraseñas no coinciden.');
                hasError = true;
            }
        }
        if (id && password) {
            if (password.length < 4) {
                setErr('user-password-error', 'La contraseña debe tener al menos 4 caracteres.');
                hasError = true;
            }
            if (passwordConfirm && password !== passwordConfirm) {
                setErr('user-password-confirm-error', 'Las contraseñas no coinciden.');
                hasError = true;
            }
        }
        if (hasError) return;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';
        }

        var body = {
            name: name,
            email: email,
            user_status_id: statusId,
            type_user_id: typeId
        };
        if (password) body.password = password;
        if (!id && passwordConfirm) body.password_confirmation = passwordConfirm;

        var url = id ? window.APP_BASE+"/admin/users"+"/" + id : window.APP_BASE+"/admin/users";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) {
            return r.json().catch(function() {
                return { success: false, message: 'El servidor devolvió una respuesta inválida.' };
            }).then(function(data) {
                return { ok: r.ok, status: r.status, data: data || {} };
            });
        })
        .then(function(result) {
            var data = result.data || {};
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            }

            if (data.errors) {
                var fieldTargets = {
                    name: 'user-name-error',
                    email: 'user-email-error',
                    password: 'user-password-error',
                    password_confirmation: 'user-password-confirm-error'
                };
                var general = [];
                Object.keys(data.errors).forEach(function(field) {
                    var targetId = fieldTargets[field];
                    var message = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                    if (targetId) {
                        setErr(targetId, message);
                    } else {
                        general.push(message);
                    }
                });
                if (general.length) setErr('user-form-error', general.join(' '));
                return;
            }

            if (!result.ok || !data.success) {
                setErr('user-form-error', data.message || 'Error al guardar el usuario.');
                return;
            }

            closeUserModal();
            resetUserForm();
            var createdId = (!id && data.user) ? data.user.id : null;
            if (data.user) {
                applyUserUpdate(data.user);
                if (createdId !== null) {
                    umSelectedUserId = createdId;
                    selectedUser = data.user;
                    umDraftUserId = null;
                    umSelectedModuleId = null;
                    renderUserManagement();
                }
            }
            loadUsers().then(function() {
                // Al crear un usuario, seleccionarlo en la tabla y cargar sus
                // permisos en el panel derecho sin recargar la pagina.
                if (createdId !== null) {
                    var created = getUserManagementUsers().find(function(u) {
                        return String(u.id) === String(createdId);
                    });
                    if (created) {
                        umSelectedUserId = created.id;
                        selectedUser = created;
                        umDraftUserId = null;
                        umSelectedModuleId = null;
                    }
                } else if (umSelectedUserId !== null && umSelectedUserId !== undefined) {
                    selectedUser = getUserManagementSelectedUser() || selectedUser;
                }
                ensureUserManagementSelection();
                renderUserManagement();
                refreshSelectedUser();
                updateCreateUserBtnVisibility();
            });
        })
        .catch(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            }
            setErr('user-form-error', 'Error de conexión. Intentá nuevamente.');
        });
    }

    function setErr(id, text) {
        var el = document.getElementById(id);
        if (!el) return;
        if (text) {
            el.textContent = text;
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }

    function showUserAddrMsg(text, ok) {
        var msg = document.getElementById('user-addr-msg');
        if (!msg) return;
        msg.textContent = text || '';
        msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        msg.style.display = text ? 'inline' : 'none';
    }

    function resetUserAddressForm() {
        document.getElementById('user-addr-id').value = '';
        document.getElementById('user-addr-form-title').textContent = 'Mi Dirección';
        document.getElementById('user-addr-street').value = '';
        document.getElementById('user-addr-number').value = '';
        document.getElementById('user-addr-floor').value = '';
        document.getElementById('user-addr-postal').value = '';
        document.getElementById('user-addr-notes').value = '';
        setErr('user-addr-error', '');
        setErr('user-addr-country-error', '');
        showUserAddrMsg('', true);
        var countrySel = document.getElementById('user-addr-country');
        var provinceSel = document.getElementById('user-addr-province');
        if (countrySel && countrySel.options.length <= 1) {
            countrySel.innerHTML = '<option value="">Cargando...</option>';
            fetch(window.APP_BASE+"/countries", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(countries) {
                if (!countrySel.isConnected) return;
                countrySel.innerHTML = '<option value="">-- Seleccionar --</option>';
                countries.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    countrySel.appendChild(opt);
                });
            })
            .catch(function() {
                countrySel.innerHTML = '<option value="">Error al cargar países</option>';
            });
        }
        if (provinceSel) {
            provinceSel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
            provinceSel.disabled = true;
        }
    }

    function fillUserAddressForm(addressId, switchTab) {
        if (!selectedUser) return;
        var addresses = getEffectiveAddresses(selectedUser);
        var a = addressId ? addresses.find(function(x) { return String(x.id) === String(addressId); }) : null;
        document.getElementById('user-addr-id').value = a ? a.id : '';
        document.getElementById('user-addr-form-title').textContent = a ? 'Editar dirección' : 'Mi Dirección';
        document.getElementById('user-addr-street').value = a ? (a.street || '') : '';
        document.getElementById('user-addr-number').value = a ? (a.number || '') : '';
        document.getElementById('user-addr-floor').value = a ? (a.floor_apartment || '') : '';
        document.getElementById('user-addr-postal').value = a ? (a.postal_code || '') : '';
        document.getElementById('user-addr-notes').value = a ? (a.notes || '') : '';
        setErr('user-addr-error', '');
        setErr('user-addr-country-error', '');
        showUserAddrMsg('', true);

        var countrySel = document.getElementById('user-addr-country');
        var provinceSel = document.getElementById('user-addr-province');
        countrySel.innerHTML = '<option value="">Cargando...</option>';
        provinceSel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
        provinceSel.disabled = true;

        fetch(window.APP_BASE+"/countries", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(countries) {
            countrySel.innerHTML = '<option value="">-- Seleccionar --</option>';
            countries.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                countrySel.appendChild(opt);
            });
            if (a && a.country_id) {
                countrySel.value = String(a.country_id);
                loadUserAddressRegions(a.country_id, a.province_id);
            }
        })
        .catch(function() {
            countrySel.innerHTML = '<option value="">Error al cargar países</option>';
        });

        if (a && switchTab) {
            switchUserTab('direcciones');
            var form = document.getElementById('user-addr-street');
            if (form) form.focus();
        }
    }

    function loadUserAddressRegions(countryId, selectedId) {
        var sel = document.getElementById('user-addr-province');
        if (!sel) return;
        sel.innerHTML = '<option value="">Cargando...</option>';
        sel.disabled = true;
        if (!countryId) {
            sel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
            return;
        }
        fetch(window.APP_BASE + "/regions?country_id=" + countryId, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(regions) {
            sel.innerHTML = '<option value="">-- Seleccionar --</option>';
            regions.forEach(function(r) {
                var opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                sel.appendChild(opt);
            });
            if (selectedId) sel.value = String(selectedId);
            sel.disabled = false;
        })
        .catch(function() {
            sel.innerHTML = '<option value="">Error al cargar regiones</option>';
        });
    }

    function submitUserAddress(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (!selectedUser) return;
        var addressId = document.getElementById('user-addr-id').value;
        var countryId = document.getElementById('user-addr-country').value;
        var errEl = document.getElementById('user-addr-error');
        var countryErr = document.getElementById('user-addr-country-error');
        var btn = document.getElementById('user-addr-save-btn');
        errEl.style.display = 'none';
        countryErr.style.display = 'none';
        showUserAddrMsg('', true);

        if (!countryId) {
            countryErr.textContent = 'El país es obligatorio.';
            countryErr.style.display = 'block';
            return;
        }

        var body = {
            country_id: parseInt(countryId, 10),
            province_id: document.getElementById('user-addr-province').value ? parseInt(document.getElementById('user-addr-province').value, 10) : null,
            street: document.getElementById('user-addr-street').value.trim(),
            number: document.getElementById('user-addr-number').value.trim(),
            floor_apartment: document.getElementById('user-addr-floor').value.trim(),
            postal_code: document.getElementById('user-addr-postal').value.trim(),
            notes: document.getElementById('user-addr-notes').value.trim(),
            is_primary: true
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        var base = window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/addresses';
        var url = addressId ? base + '/' + addressId : base;
        var method = addressId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar dirección';
            if (data.errors) {
                var msgs = [];
                Object.keys(data.errors).forEach(function(k) { msgs.push(data.errors[k][0]); });
                errEl.textContent = msgs.join(' ');
                errEl.style.display = 'block';
                return;
            }
            if (data.success) {
                showUserAddrMsg('Guardada correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
                setTimeout(function() { showUserAddrMsg('', true); }, 2500);
            } else {
                errEl.textContent = data.message || 'Error al guardar.';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar dirección';
            errEl.textContent = 'Error de conexión.';
            errEl.style.display = 'block';
        });
    }

    function openUserProviderModal() {
        if (!selectedUser) return;
        renderUserProvider(selectedUser);
        switchUserTab('negocio');
        var business = document.getElementById('user-prov-business');
        if (business) business.focus();
    }

    function closeUserProviderModal() {}

    function submitUserProvider(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (!selectedUser) return;
        var business = document.getElementById('user-prov-business').value.trim();
        var errBn = document.getElementById('user-prov-business-error');
        var errEl = document.getElementById('user-prov-error');
        var msg = document.getElementById('user-prov-msg');
        var btn = document.getElementById('user-prov-save-btn');
        errBn.style.display = 'none';
        errEl.style.display = 'none';
        if (msg) { msg.style.display = 'none'; }

        if (!business) {
            errBn.textContent = 'El nombre del negocio es obligatorio.';
            errBn.style.display = 'block';
            return;
        }

        var body = {
            business_name: business,
            description: document.getElementById('user-prov-description').value.trim(),
            phone: document.getElementById('user-prov-phone').value.trim(),
            whatsapp: document.getElementById('user-prov-whatsapp').value.trim(),
            zone: document.getElementById('user-prov-zone').value.trim(),
            promo: document.getElementById('user-prov-promo').value.trim(),
            is_active: document.getElementById('user-prov-active').checked
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(window.APP_BASE+"/admin/users"+"/" + selectedUser.id + '/provider', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar negocio';
            if (data.errors) {
                var msgs = [];
                Object.keys(data.errors).forEach(function(k) { msgs.push(data.errors[k][0]); });
                errEl.textContent = msgs.join(' ');
                errEl.style.display = 'block';
                return;
            }
            if (data.success) {
                if (msg) {
                    msg.textContent = 'Guardado correctamente.';
                    msg.className = 'save-msg ok';
                    msg.style.display = 'inline';
                }
                applyUserUpdate(data.user);
                refreshSelectedUser();
                if (msg) setTimeout(function() { msg.style.display = 'none'; }, 2500);
            } else {
                errEl.textContent = data.message || 'Error al guardar.';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar negocio';
            errEl.textContent = 'Error de conexión.';
            errEl.style.display = 'block';
        });
    }

    function openUserDelete(id, name) {
        deleteUserId = id;
        var overlay = document.getElementById('user-delete-overlay');
        if (overlay) {
            overlay.setAttribute('data-user-id', (id === null || id === undefined) ? '' : String(id));
            overlay.style.display = 'flex';
        }
        var nameEl = document.getElementById('user-delete-name');
        if (nameEl) nameEl.textContent = name || '';
        var warn = document.getElementById('user-delete-warning');
        if (warn) {
            warn.textContent = '';
            warn.style.display = 'none';
        }
        var btn = document.getElementById('user-delete-btn');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
        }
    }

    function closeUserDelete() {
        var overlay = document.getElementById('user-delete-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            overlay.removeAttribute('data-user-id');
        }
        var btn = document.getElementById('user-delete-btn');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
        }
        deleteUserId = null;
    }

    function umFindUserStatusByName(fragment) {
        var list = (typeof userStatusOptionsCache !== 'undefined' && userStatusOptionsCache) ? userStatusOptionsCache : [];
        var needle = String(fragment || '').toLowerCase();
        for (var i = 0; i < list.length; i++) {
            var label = String(list[i].status || list[i].description || '').toLowerCase();
            if (label.indexOf(needle) === 0) return list[i];
        }
        return null;
    }

    function markUserInactiveLocally(id) {
        if (!Array.isArray(allUsers)) return;
        var target = allUsers.find(function(u) { return String(u.id) === String(id); });
        if (!target) return;
        var st = umFindUserStatusByName('inactiv');
        var statusId = st ? st.id : (target.user_status_id || (target.status && target.status.id) || null);
        target.user_status_id = statusId;
        target.status = { id: statusId, status: st ? st.status : 'Inactivo' };
    }

    function confirmDeleteUser() {
        var overlay = document.getElementById('user-delete-overlay');
        // El ID sale del estado o del dataset del modal (respaldo por si el
        // estado se perdió entre abrir y confirmar).
        var pendingId = (deleteUserId === null || deleteUserId === undefined || deleteUserId === '')
            ? (overlay ? overlay.getAttribute('data-user-id') : null)
            : deleteUserId;
        if (pendingId === null || pendingId === undefined || pendingId === '') {
            closeUserDelete();
            return;
        }
        var deletingId = String(pendingId);
        var btn = document.getElementById('user-delete-btn');
        var meta = document.querySelector('meta[name="csrf-token"]');
        var csrf = meta ? meta.content : '';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Eliminando...';
        }

        // Se cierra el modal de inmediato: la interfaz nunca queda bloqueada
        // esperando la respuesta. Si algo falla, se reabre con el error.
        closeUserDelete();

        fetch(window.APP_BASE+"/admin/users"+"/" + encodeURIComponent(deletingId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        })
        .then(function(r) {
            return r.json().catch(function() { return {}; }).then(function(data) {
                return { ok: r.ok, data: data || {} };
            });
        })
        .then(function(res) {
            if (!res.ok || res.data.success !== true) {
                throw new Error(res.data.message || 'No se pudo inactivar el usuario.');
            }
            // Reflejar la inactivación en la tabla al instante (sin esperar refetch).
            markUserInactiveLocally(deletingId);
            var wasSelected = umSelectedUserId !== null && String(umSelectedUserId) === deletingId;
            if (wasSelected) {
                umSelectedUserId = null;
                selectedUser = null;
            }
            renderUserManagement();
            return loadUsers().then(function() {
                if (wasSelected) {
                    var principal = getSessionAccountPrincipalUser();
                    if (principal && String(principal.id) !== deletingId) {
                        umSelectedUserId = principal.id;
                        selectedUser = principal;
                    }
                }
                umDraftUserId = null;
                umSelectedModuleId = null;
                renderUserManagement();
                updateCreateUserBtnVisibility();
            });
        })
        .catch(function(err) {
            var msg = (err && err.message) ? err.message : 'Error de conexión.';
            // Reabrir el modal con el error para poder reintentar.
            if (overlay) {
                overlay.setAttribute('data-user-id', deletingId);
                overlay.style.display = 'flex';
            }
            deleteUserId = deletingId;
            var warn = document.getElementById('user-delete-warning');
            if (warn) {
                warn.textContent = msg;
                warn.style.display = 'block';
            }
            var b = document.getElementById('user-delete-btn');
            if (b) {
                b.disabled = false;
                b.textContent = 'Eliminar';
            }
        });
    }

    function loadUserStatuses() {
        document.getElementById('userstatuses-loading').style.display = 'block';
        document.getElementById('userstatuses-empty').style.display = 'none';
        document.getElementById('userstatuses-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/user-statuses", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUserStatuses = data;
            renderUserStatuses(data);
        })
        .catch(function() {
            document.getElementById('userstatuses-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar estados.</p>';
        });
    }

    function renderUserStatuses(statuses) {
        var tbody = document.getElementById('userstatuses-tbody');
        var loading = document.getElementById('userstatuses-loading');
        var empty = document.getElementById('userstatuses-empty');
        var tableWrap = document.getElementById('userstatuses-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (statuses.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        statuses.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', s.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.status) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + Number(s.users_count || 0) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editUserStatus(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openUserStatusDelete(' + s.id + ', \'' + escapeHtml(s.status).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterUserStatuses() {
        var q = document.getElementById('userstatus-search').value.toLowerCase();
        var filtered = allUserStatuses.filter(function(s) {
            return s.status.toLowerCase().indexOf(q) !== -1;
        });
        renderUserStatuses(filtered);
    }

    function openUserStatusModal(id, status) {
        document.getElementById('userstatus-id').value = id || '';
        document.getElementById('userstatus-status').value = status || '';
        document.getElementById('userstatus-status-error').style.display = 'none';
        document.getElementById('userstatus-modal-title').textContent = id ? 'Editar Estado' : 'Nuevo Estado';
        document.getElementById('userstatus-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('userstatus-modal-overlay').style.display = 'flex';
        document.getElementById('userstatus-status').focus();
    }

    function closeUserStatusModal() {
        document.getElementById('userstatus-modal-overlay').style.display = 'none';
    }

    function editUserStatus(id) {
        var s = allUserStatuses.find(function(st) { return st.id === id; });
        if (s) openUserStatusModal(s.id, s.status);
    }

    function submitUserStatus(e) {
        e.preventDefault();
        var id = document.getElementById('userstatus-id').value;
        var statusVal = document.getElementById('userstatus-status').value.trim();
        var errorEl = document.getElementById('userstatus-status-error');
        var submitBtn = document.getElementById('userstatus-submit-btn');

        errorEl.style.display = 'none';

        if (!statusVal) {
            errorEl.textContent = 'El estado es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/user-statuses"+"/" + id : window.APP_BASE+"/user-statuses";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: statusVal })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.status ? data.errors.status[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeUserStatusModal();
                loadUserStatuses();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openUserStatusDelete(id, name) {
        deleteUserStatusId = id;
        document.getElementById('userstatus-delete-name').textContent = name;
        document.getElementById('userstatus-delete-warning').style.display = 'none';
        document.getElementById('userstatus-delete-overlay').style.display = 'flex';
    }

    function closeUserStatusDelete() {
        document.getElementById('userstatus-delete-overlay').style.display = 'none';
        deleteUserStatusId = null;
    }

    function confirmDeleteUserStatus() {
        if (!deleteUserStatusId) return;
        var btn = document.getElementById('userstatus-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/user-statuses"+"/" + deleteUserStatusId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeUserStatusDelete();
                loadUserStatuses();
            } else {
                var warn = document.getElementById('userstatus-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('userstatus-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ==================== UNIDADES DE MEDIDA ====================

    var allUnitsOfMeasure = [];
    var deleteUnitId = null;

    function loadUnitsOfMeasure() {
        document.getElementById('uom-loading').style.display = 'block';
        document.getElementById('uom-empty').style.display = 'none';
        document.getElementById('uom-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/units-of-measure", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUnitsOfMeasure = data;
            renderUnitsOfMeasure(data);
        })
        .catch(function() {
            document.getElementById('uom-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar unidades.</p>';
        });
    }

    function renderUnitsOfMeasure(units) {
        var tbody = document.getElementById('uom-tbody');
        var loading = document.getElementById('uom-loading');
        var empty = document.getElementById('uom-empty');
        var tableWrap = document.getElementById('uom-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (units.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        units.forEach(function(u) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', u.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(u.name) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">' + escapeHtml(u.symbol) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">1 ' + escapeHtml(u.name) + ' = ' + Number(u.base_conversion_factor) + ' und</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    (u.is_integer_only
                        ? '<span style="background:#fff7ed;color:#9a3412;border:1px solid #fde68a;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Solo enteras</span>'
                        : '<span style="background:#d1fae5;color:#047857;border:1px solid #a7f3d0;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Admite decimales</span>') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:' + (Number(u.products_count || 0) ? '#1f2937' : '#94a3b8') + ';">' + Number(u.products_count || 0) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editUnit(' + u.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openUnitDelete(' + u.id + ', \'' + escapeHtml(u.name).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterUnitsOfMeasure() {
        var q = document.getElementById('uom-search').value.toLowerCase();
        var filtered = allUnitsOfMeasure.filter(function(u) {
            return u.name.toLowerCase().indexOf(q) !== -1 || u.symbol.toLowerCase().indexOf(q) !== -1;
        });
        renderUnitsOfMeasure(filtered);
    }

    function openUnitModal(id, name, symbol, factor, isInteger) {
        document.getElementById('uom-id').value = id || '';
        document.getElementById('uom-name').value = name || '';
        document.getElementById('uom-symbol').value = symbol || '';
        document.getElementById('uom-factor').value = factor != null ? factor : '1';
        document.getElementById('uom-integer').checked = isInteger === undefined ? true : !!isInteger;
        document.getElementById('uom-error').style.display = 'none';
        document.getElementById('uom-modal-title').textContent = id ? 'Editar Unidad' : 'Nueva Unidad';
        document.getElementById('uom-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('uom-modal-overlay').style.display = 'flex';
        document.getElementById('uom-name').focus();
    }

    function closeUnitModal() {
        document.getElementById('uom-modal-overlay').style.display = 'none';
    }

    function editUnit(id) {
        var u = allUnitsOfMeasure.find(function(unit) { return unit.id === id; });
        if (u) openUnitModal(u.id, u.name, u.symbol, u.base_conversion_factor, u.is_integer_only);
    }

    function submitUnit(e) {
        e.preventDefault();
        var id = document.getElementById('uom-id').value;
        var nameVal = document.getElementById('uom-name').value.trim();
        var symbolVal = document.getElementById('uom-symbol').value.trim();
        var factorVal = document.getElementById('uom-factor').value.trim();
        var integerVal = document.getElementById('uom-integer').checked;
        var errorEl = document.getElementById('uom-error');
        var submitBtn = document.getElementById('uom-submit-btn');

        errorEl.style.display = 'none';

        if (!nameVal) {
            errorEl.textContent = 'La unidad es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }
        if (!symbolVal) {
            errorEl.textContent = 'El símbolo es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }
        if (!factorVal || Number(factorVal) <= 0) {
            errorEl.textContent = 'La equivalencia debe ser un número mayor a cero.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/units-of-measure"+"/" + id : window.APP_BASE+"/units-of-measure";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: nameVal,
                symbol: symbolVal,
                base_conversion_factor: factorVal,
                is_integer_only: integerVal
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                errorEl.textContent = data.errors[firstKey][0];
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeUnitModal();
                loadUnitsOfMeasure();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openUnitDelete(id, name) {
        deleteUnitId = id;
        document.getElementById('uom-delete-name').textContent = name;
        document.getElementById('uom-delete-warning').style.display = 'none';
        document.getElementById('uom-delete-overlay').style.display = 'flex';
    }

    function closeUnitDelete() {
        document.getElementById('uom-delete-overlay').style.display = 'none';
        deleteUnitId = null;
    }

    function confirmDeleteUnit() {
        if (!deleteUnitId) return;
        var btn = document.getElementById('uom-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/units-of-measure"+"/" + deleteUnitId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeUnitDelete();
                loadUnitsOfMeasure();
            } else {
                var warn = document.getElementById('uom-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('uom-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ==================== TIPO DE MONEDAS ====================

    var allCurrencies = [];
    var allCurrencyCountries = null;
    var deleteCurrencyId = null;

    function loadCurrencies() {
        document.getElementById('cur-loading').style.display = 'block';
        document.getElementById('cur-empty').style.display = 'none';
        document.getElementById('cur-table-wrap').style.display = 'none';

        var currenciesReq = fetch(window.APP_BASE+"/currencies", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); });

        var countriesReq = allCurrencyCountries
            ? Promise.resolve(allCurrencyCountries)
            : fetch(window.APP_BASE+"/countries", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(list) {
                allCurrencyCountries = list || [];
                return allCurrencyCountries;
            })
            .catch(function() { return []; });

        Promise.all([currenciesReq, countriesReq])
        .then(function(res) {
            allCurrencies = res[0] || [];
            renderCurrencies(allCurrencies);
        })
        .catch(function() {
            document.getElementById('cur-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar monedas.</p>';
        });
    }

    function formatCurrencyPreview(c, value) {
        var n = Number(value == null ? 0 : value);
        var negative = n < 0;
        n = Math.abs(n);

        var decimals = Number(c.decimals == null ? 2 : c.decimals);
        var parts = n.toFixed(decimals).split('.');
        var thousand = c.thousand_separator == null ? '.' : c.thousand_separator;
        var decimal = c.decimal_separator == null ? ',' : c.decimal_separator;
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
        var decPart = decimals > 0 ? decimal + (parts[1] || '') : '';
        var number = intPart + decPart;

        var space = c.symbol_space === false ? '' : ' ';
        var out = c.symbol_position === 'after'
            ? number + space + (c.symbol || '')
            : (c.symbol || '') + space + number;

        return negative ? '-' + out : out;
    }

    function renderCurrencies(currencies) {
        var tbody = document.getElementById('cur-tbody');
        var loading = document.getElementById('cur-loading');
        var empty = document.getElementById('cur-empty');
        var tableWrap = document.getElementById('cur-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (currencies.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        currencies.forEach(function(c) {
            var countryName = c.country ? c.country.name : '—';

            var statusBadges = '';
            if (c.is_default) {
                statusBadges += '<span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;margin-right:4px;">Por defecto</span>';
            }
            statusBadges += c.is_active
                ? '<span style="background:#d1fae5;color:#047857;border:1px solid #a7f3d0;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Activa</span>'
                : '<span style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Inactiva</span>';

            var editBtn = '<button onclick="openCurrencyModal(' + c.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg></button>';

            var deleteBtn = c.is_default
                ? '<button type="button" disabled title="La moneda por defecto no se puede eliminar" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;padding:4px 8px;color:#cbd5e1;font-size:12px;cursor:not-allowed;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg></button>'
                : '<button onclick="openCurrencyDelete(' + c.id + ', \'' + escapeHtml(c.code).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg></button>';

            var tr = document.createElement('tr');
            tr.setAttribute('data-id', c.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(countryName) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;"><span style="font-family:monospace;font-weight:600;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;padding:1px 6px;">' + escapeHtml(c.code) + '</span></td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">' + escapeHtml(c.name) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">' + escapeHtml(c.symbol) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:600;white-space:nowrap;">' + escapeHtml(formatCurrencyPreview(c, 1234.56)) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;white-space:nowrap;">' + statusBadges + '</td>' +
                '<td style="padding:10px 16px;text-align:center;white-space:nowrap;">' + editBtn + deleteBtn + '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterCurrencies() {
        var q = (document.getElementById('cur-search').value || '').toLowerCase();
        var filtered = allCurrencies.filter(function(c) {
            return (c.code || '').toLowerCase().indexOf(q) !== -1
                || (c.name || '').toLowerCase().indexOf(q) !== -1
                || (c.symbol || '').toLowerCase().indexOf(q) !== -1
                || (c.country && (c.country.name || '').toLowerCase().indexOf(q) !== -1);
        });
        renderCurrencies(filtered);
    }

    function fillCurrencyCountryOptions() {
        var sel = document.getElementById('cur-country');
        if (!sel || sel.options.length > 1) return;
        var list = allCurrencyCountries || [];
        sel.innerHTML = '<option value="">-- Seleccionar --</option>';
        list.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            sel.appendChild(opt);
        });
    }

    function updateCurrencyPreview() {
        var preview = document.getElementById('cur-preview');
        if (!preview) return;
        preview.textContent = formatCurrencyPreview({
            symbol: document.getElementById('cur-symbol').value || '$',
            symbol_position: document.getElementById('cur-position').value,
            symbol_space: document.getElementById('cur-space').checked,
            decimals: document.getElementById('cur-decimals').value,
            decimal_separator: document.getElementById('cur-decimal-sep').value,
            thousand_separator: document.getElementById('cur-thousand-sep').value
        }, 1234.56);
    }

    function openCurrencyModal(id) {
        var c = id ? allCurrencies.find(function(x) { return x.id === id; }) : null;

        fillCurrencyCountryOptions();

        document.getElementById('cur-id').value = c ? c.id : '';
        document.getElementById('cur-country').value = c ? c.country_id : '';
        document.getElementById('cur-code').value = c ? c.code : '';
        document.getElementById('cur-name').value = c ? c.name : '';
        document.getElementById('cur-symbol').value = c ? c.symbol : '';
        document.getElementById('cur-position').value = c ? c.symbol_position : 'before';
        document.getElementById('cur-space').checked = c ? !!c.symbol_space : true;
        document.getElementById('cur-decimals').value = c ? c.decimals : 2;
        document.getElementById('cur-decimal-sep').value = c ? c.decimal_separator : ',';
        document.getElementById('cur-thousand-sep').value = c ? c.thousand_separator : '.';
        document.getElementById('cur-locale').value = c ? c.locale : '';
        document.getElementById('cur-active').checked = c ? !!c.is_active : true;
        document.getElementById('cur-error').style.display = 'none';
        document.getElementById('cur-modal-title').textContent = c ? 'Editar Moneda' : 'Nueva Moneda';
        document.getElementById('cur-submit-btn').textContent = c ? 'Actualizar' : 'Guardar';

        updateCurrencyPreview();
        document.getElementById('cur-modal-overlay').style.display = 'flex';
        document.getElementById('cur-code').focus();
    }

    function closeCurrencyModal() {
        document.getElementById('cur-modal-overlay').style.display = 'none';
    }

    function submitCurrency(e) {
        e.preventDefault();
        var id = document.getElementById('cur-id').value;
        var countryVal = document.getElementById('cur-country').value;
        var codeVal = document.getElementById('cur-code').value.trim().toUpperCase();
        var nameVal = document.getElementById('cur-name').value.trim();
        var symbolVal = document.getElementById('cur-symbol').value.trim();
        var positionVal = document.getElementById('cur-position').value;
        var spaceVal = document.getElementById('cur-space').checked;
        var decimalsVal = document.getElementById('cur-decimals').value.trim();
        var decimalSepVal = document.getElementById('cur-decimal-sep').value;
        var thousandSepVal = document.getElementById('cur-thousand-sep').value;
        var localeVal = document.getElementById('cur-locale').value.trim();
        var activeVal = document.getElementById('cur-active').checked;
        var errorEl = document.getElementById('cur-error');
        var submitBtn = document.getElementById('cur-submit-btn');

        errorEl.style.display = 'none';

        if (!countryVal) {
            errorEl.textContent = 'El país es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }
        if (!/^[A-Z]{3}$/.test(codeVal)) {
            errorEl.textContent = 'El código debe tener 3 letras (ej: ARS).';
            errorEl.style.display = 'block';
            return;
        }
        if (!nameVal) {
            errorEl.textContent = 'El nombre es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }
        if (!symbolVal) {
            errorEl.textContent = 'El símbolo es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }
        if (decimalsVal === '' || Number(decimalsVal) < 0 || Number(decimalsVal) > 4) {
            errorEl.textContent = 'Los decimales deben estar entre 0 y 4.';
            errorEl.style.display = 'block';
            return;
        }
        if (!localeVal) {
            errorEl.textContent = 'El locale es obligatorio (ej: es-AR).';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/currencies"+"/" + id : window.APP_BASE+"/currencies";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                country_id: countryVal,
                code: codeVal,
                name: nameVal,
                symbol: symbolVal,
                symbol_position: positionVal,
                symbol_space: spaceVal,
                decimals: Number(decimalsVal),
                decimal_separator: decimalSepVal,
                thousand_separator: thousandSepVal,
                locale: localeVal,
                is_active: activeVal
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                errorEl.textContent = data.errors[firstKey][0];
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeCurrencyModal();
                loadCurrencies();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openCurrencyDelete(id, code) {
        deleteCurrencyId = id;
        document.getElementById('cur-delete-name').textContent = code;
        document.getElementById('cur-delete-warning').style.display = 'none';
        document.getElementById('cur-delete-overlay').style.display = 'flex';
    }

    function closeCurrencyDelete() {
        document.getElementById('cur-delete-overlay').style.display = 'none';
        deleteCurrencyId = null;
    }

    function confirmDeleteCurrency() {
        if (!deleteCurrencyId) return;
        var btn = document.getElementById('cur-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/currencies"+"/" + deleteCurrencyId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeCurrencyDelete();
                loadCurrencies();
            } else {
                var warn = document.getElementById('cur-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('cur-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }


    // ==================== CONSULTAS Y SUGERENCIAS ====================

    var allContactInquiries = [];
    var contactInquiryFilter = 'all';
    var contactInquiryDetailId = null;

    function loadContactInquiries() {
        document.getElementById('ci-loading').innerHTML = '<p style="font-size:13px;color:#6b7280;">Cargando consultas...</p>';
        document.getElementById('ci-loading').style.display = 'block';
        document.getElementById('ci-empty').style.display = 'none';
        document.getElementById('ci-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/contact-inquiries", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!Array.isArray(data)) {
                throw new Error(data && data.message ? data.message : 'No se pudieron cargar las consultas.');
            }
            allContactInquiries = data;
            renderContactInquiries();
        })
        .catch(function(error) {
            document.getElementById('ci-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">' + escapeHtml(error.message || 'Error al cargar las consultas.') + '</p>';
        });
    }

    function contactInquiryStatusLabel(status) {
        return { new: 'Nueva', in_progress: 'En proceso', resolved: 'Resuelta' }[status] || 'Nueva';
    }

    function contactInquiryStatusBadge(status) {
        var styles = {
            new: 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;',
            in_progress: 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;',
            resolved: 'background:#d1fae5;color:#047857;border:1px solid #a7f3d0;'
        };
        return '<span style="' + (styles[status] || styles.new) + 'border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;white-space:nowrap;">' + contactInquiryStatusLabel(status) + '</span>';
    }

    function contactInquiryTypeLabel(type) {
        return { consulta: 'Consulta', sugerencia: 'Sugerencia', reclamo: 'Reclamo' }[type] || 'Consulta';
    }

    function contactInquiryTypeBadge(type) {
        var styles = {
            consulta: 'background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;',
            sugerencia: 'background:#fdf4ff;color:#a21caf;border:1px solid #f5d0fe;',
            reclamo: 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;'
        };
        return '<span style="' + (styles[type] || styles.consulta) + 'border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;white-space:nowrap;">' + contactInquiryTypeLabel(type) + '</span>';
    }

    function contactInquiryDate(value) {
        var date = new Date(value);
        if (isNaN(date.getTime())) return '—';
        return date.toLocaleString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function replyToContactInquiry(id) {
        var item = allContactInquiries.find(function(x) { return x.id === id; });
        if (!item) return;
        var subject = encodeURIComponent('Respuesta a tu ' + contactInquiryTypeLabel(item.inquiry_type).toLowerCase() + ' - PideAca');
        window.location.href = 'mailto:' + (item.email || '') + '?subject=' + subject;
    }

    function applyContactInquiryFilterStyles() {
        document.querySelectorAll('#ci-filters [data-status]').forEach(function(btn) {
            var active = btn.getAttribute('data-status') === contactInquiryFilter;
            btn.style.background = active ? '#D24C19' : '#ffffff';
            btn.style.borderColor = active ? '#D24C19' : '#d1d5db';
            btn.style.color = active ? '#ffffff' : '#374151';
        });
    }

    function updateContactInquiryCounts() {
        var counts = { all: allContactInquiries.length, new: 0, in_progress: 0, resolved: 0 };
        allContactInquiries.forEach(function(item) {
            if (counts[item.status] !== undefined && item.status !== 'all') {
                counts[item.status] += 1;
            }
        });
        Object.keys(counts).forEach(function(key) {
            var el = document.getElementById('ci-count-' + key);
            if (el) el.textContent = counts[key];
        });
    }

    function renderContactInquiries() {
        var tbody = document.getElementById('ci-tbody');
        var loading = document.getElementById('ci-loading');
        var empty = document.getElementById('ci-empty');
        var tableWrap = document.getElementById('ci-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';
        applyContactInquiryFilterStyles();
        updateContactInquiryCounts();

        var items = contactInquiryFilter === 'all'
            ? allContactInquiries
            : allContactInquiries.filter(function(item) { return item.status === contactInquiryFilter; });

        if (items.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        items.forEach(function(item) {
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;white-space:nowrap;">' + escapeHtml(item.full_name || '—') + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;white-space:nowrap;"><a href="mailto:' + escapeHtml(item.email || '') + '" style="color:#D24C19;text-decoration:none;">' + escapeHtml(item.email || '—') + '</a></td>' +
                '<td style="padding:10px 16px;">' + contactInquiryTypeBadge(item.inquiry_type) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;max-width:280px;"><span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escapeHtml(item.message || '') + '">' + escapeHtml(item.message || '') + '</span></td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;white-space:nowrap;">' + contactInquiryDate(item.created_at) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' + contactInquiryStatusBadge(item.status) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;white-space:nowrap;">' +
                    '<button type="button" onclick="openContactInquiryDetail(' + item.id + ')" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;">Ver</button>' +
                    '<button type="button" onclick="replyToContactInquiry(' + item.id + ')" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;">Responder</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function setContactInquiryFilter(status) {
        contactInquiryFilter = status;
        renderContactInquiries();
    }

    function openContactInquiryDetail(id) {
        var item = allContactInquiries.find(function(x) { return x.id === id; });
        if (!item) return;

        contactInquiryDetailId = id;

        document.getElementById('ci-detail-name').textContent = item.full_name || '—';
        document.getElementById('ci-detail-email').textContent = item.email || '—';
        document.getElementById('ci-detail-email').href = 'mailto:' + (item.email || '');
        document.getElementById('ci-detail-type').textContent = contactInquiryTypeLabel(item.inquiry_type);
        document.getElementById('ci-detail-date').textContent = contactInquiryDate(item.created_at);
        document.getElementById('ci-detail-message').textContent = item.message || '';
        document.getElementById('ci-detail-status').value = item.status || 'new';

        var msg = document.getElementById('ci-detail-msg');
        msg.style.display = 'none';
        msg.textContent = '';

        var subject = encodeURIComponent('Respuesta a tu ' + contactInquiryTypeLabel(item.inquiry_type).toLowerCase() + ' - PideAca');
        document.getElementById('ci-detail-reply').href = 'mailto:' + (item.email || '') + '?subject=' + subject;

        document.getElementById('ci-detail-overlay').style.display = 'flex';
    }

    function closeContactInquiryDetail() {
        document.getElementById('ci-detail-overlay').style.display = 'none';
        contactInquiryDetailId = null;
    }

    function saveContactInquiryStatus() {
        if (!contactInquiryDetailId) return;

        var status = document.getElementById('ci-detail-status').value;
        var btn = document.getElementById('ci-detail-save');
        var msg = document.getElementById('ci-detail-msg');

        msg.style.display = 'none';
        msg.textContent = '';
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(window.APP_BASE+"/contact-inquiries"+"/" + contactInquiryDetailId + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: status })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar estado';

            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                msg.textContent = data.errors[firstKey][0];
                msg.style.color = '#dc2626';
                msg.style.display = 'block';
                return;
            }

            if (!data.success) {
                msg.textContent = data.message || 'No se pudo actualizar el estado.';
                msg.style.color = '#dc2626';
                msg.style.display = 'block';
                return;
            }

            var item = allContactInquiries.find(function(x) { return x.id === contactInquiryDetailId; });
            if (item) {
                item.status = data.contact_inquiry ? data.contact_inquiry.status : status;
            }

            renderContactInquiries();
            msg.textContent = data.message || 'Estado actualizado correctamente.';
            msg.style.color = '#047857';
            msg.style.display = 'block';
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar estado';
            msg.textContent = 'Error de conexión.';
            msg.style.color = '#dc2626';
            msg.style.display = 'block';
        });
    }

    // ==================== ESTADO DE LOS GRUPOS ====================

    var allGroupStatuses = [];
    var deleteGroupStatusId = null;

    function loadGroupStatuses() {
        document.getElementById('groupstatuses-loading').style.display = 'block';
        document.getElementById('groupstatuses-empty').style.display = 'none';
        document.getElementById('groupstatuses-table-wrap').style.display = 'none';

        fetch(window.APP_BASE+"/group-statuses", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allGroupStatuses = data;
            renderGroupStatuses(data);

            var summary = document.getElementById('groupstatuses-summary');
            if (summary) {
                var enUso = data.filter(function(s) { return (s.groups_count || 0) > 0; }).length;
                summary.textContent = data.length + ' estados · ' + enUso + ' en uso por grupos';
            }
        })
        .catch(function() {
            document.getElementById('groupstatuses-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar estados.</p>';
        });
    }

    function renderGroupStatuses(statuses) {
        var tbody = document.getElementById('groupstatuses-tbody');
        var loading = document.getElementById('groupstatuses-loading');
        var empty = document.getElementById('groupstatuses-empty');
        var tableWrap = document.getElementById('groupstatuses-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (statuses.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        statuses.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', s.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + Number(s.groups_count || 0) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editGroupStatus(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openGroupStatusDelete(' + s.id + ', \'' + escapeHtml(s.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterGroupStatuses() {
        var q = document.getElementById('groupstatus-search').value.toLowerCase();
        var filtered = allGroupStatuses.filter(function(s) {
            return s.description.toLowerCase().indexOf(q) !== -1;
        });
        renderGroupStatuses(filtered);
    }

    function openGroupStatusModal(id, description) {
        document.getElementById('grupostatus-id').value = id || '';
        document.getElementById('grupostatus-description').value = description || '';
        document.getElementById('grupostatus-description-error').style.display = 'none';
        document.getElementById('grupostatus-modal-title').textContent = id ? 'Editar Estado' : 'Nuevo Estado';
        document.getElementById('grupostatus-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('grupostatus-modal-overlay').style.display = 'flex';
        document.getElementById('grupostatus-description').focus();
    }

    function closeGroupStatusModal() {
        document.getElementById('grupostatus-modal-overlay').style.display = 'none';
    }

    function editGroupStatus(id) {
        var s = allGroupStatuses.find(function(st) { return st.id === id; });
        if (s) openGroupStatusModal(s.id, s.description);
    }

    function submitGroupStatus(e) {
        e.preventDefault();
        var id = document.getElementById('grupostatus-id').value;
        var value = document.getElementById('grupostatus-description').value.trim();
        var errorEl = document.getElementById('grupostatus-description-error');
        var submitBtn = document.getElementById('grupostatus-submit-btn');

        errorEl.style.display = 'none';

        if (!value) {
            errorEl.textContent = 'El estado es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? window.APP_BASE+"/group-statuses"+"/" + id : window.APP_BASE+"/group-statuses";
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: value })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                errorEl.textContent = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeGroupStatusModal();
                loadGroupStatuses();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openGroupStatusDelete(id, name) {
        deleteGroupStatusId = id;
        document.getElementById('grupostatus-delete-name').textContent = name;
        document.getElementById('grupostatus-delete-warning').style.display = 'none';
        document.getElementById('grupostatus-delete-overlay').style.display = 'flex';
    }

    function closeGroupStatusDelete() {
        document.getElementById('grupostatus-delete-overlay').style.display = 'none';
        deleteGroupStatusId = null;
    }

    function confirmDeleteGroupStatus() {
        if (!deleteGroupStatusId) return;
        var btn = document.getElementById('grupostatus-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch(window.APP_BASE+"/group-statuses"+"/" + deleteGroupStatusId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeGroupStatusDelete();
                loadGroupStatuses();
            } else {
                var warn = document.getElementById('grupostatus-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('grupostatus-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ==================== MI PANEL / MI PERFIL ====================

    var profileData = null;
    var profileProvider = null;
    var profileIsPrestador = false;
    var profileDireccionCargada = false;
    var profileCanAdvertise = false;

    var PROFILE_DAYS = [
        { key: 'mon', label: 'Lunes' },
        { key: 'tue', label: 'Martes' },
        { key: 'wed', label: 'Miércoles' },
        { key: 'thu', label: 'Jueves' },
        { key: 'fri', label: 'Viernes' },
        { key: 'sat', label: 'Sábado' },
        { key: 'sun', label: 'Domingo' }
    ];

    function showMsg(id, text, ok) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        el.className = 'save-msg ' + (ok ? 'ok' : 'err');
        el.style.display = 'inline';
        clearTimeout(el._t);
        el._t = setTimeout(function() { el.style.display = 'none'; }, 3500);
    }

    var profileBreadcrumbLabels = {
        negocio: 'Datos del Negocio',
        horarios: 'Horarios de Atención',
        servicios: 'Mis Servicios',
        repartidores: 'Repartidores de confianza',
        retiros: 'Retiros de repartidores',
        imagenes: 'Publicidad',
        categorias: 'Categorias',
        direccion: 'Mi Dirección',
        usuarios: 'Usuarios',
        cuenta: 'Contraseñas',
        vehiculo: 'Mi Vehículo',
        'app-movil': 'App móvil'
    };

    var profileUsuariosActive = false;
    var profilePrevUser = null;
    var profilePrevScope = 'all';
    var profilePrevPanelId = null;
    var profilePrevPaneG = null;
    var profilePrevPaneP = null;
    var profilePrevCountG = null;
    var profilePrevCountP = null;

    function focusProfileAccountSelection() {
        userSelectScope = 'account';
        var apply = function() {
            var me = getSessionUser();
            if (!me) return;
            umSelectedUserId = me.id;
            selectedUser = me;
            umDraftUserId = null;
            umSelectedModuleId = null;
            renderUserManagement();
            updateCreateUserBtnVisibility();
        };
        if (!allUsers.length) {
            loadUsers().then(apply);
        } else {
            apply();
        }
    }

    function mountProfileUsuariosPanel() {
        var host = document.getElementById('user-panel-cuenta');
        var slot = document.getElementById('profile-usuarios-slot');
        if (host && slot) {
            while (host.firstChild) slot.appendChild(host.firstChild);
        }
        if (!profileUsuariosActive) {
            profileUsuariosActive = true;
            profilePrevUser = selectedUser;
            profilePrevScope = userSelectScope;
            var prevPanel = document.querySelector('.user-panel.active');
            profilePrevPanelId = prevPanel ? prevPanel.id : null;
            if (host) host.classList.add('active');
        }
        focusProfileAccountSelection();
    }

    function unmountProfileUsuariosPanel() {
        var host = document.getElementById('user-panel-cuenta');
        var slot = document.getElementById('profile-usuarios-slot');
        if (host && slot) {
            while (slot.firstChild) host.appendChild(slot.firstChild);
        }
        if (!profileUsuariosActive) return;
        profileUsuariosActive = false;
        if (host) host.classList.remove('active');
        if (profilePrevPanelId) {
            var prevPanel = document.getElementById(profilePrevPanelId);
            if (prevPanel) prevPanel.classList.add('active');
        }
        profilePrevPanelId = null;
        selectedUser = profilePrevUser;
        userSelectScope = profilePrevScope;
        umSelectedUserId = selectedUser ? selectedUser.id : null;
        umDraftUserId = null;
        umSelectedModuleId = null;
        renderUserManagement();
        updateCreateUserBtnVisibility();
    }

    /* ---------- App móvil: checklist de cuenta completa (Fase 2) ---------- */

    function appMovilChecklist() {
        var prov = (typeof profileProvider !== 'undefined') ? profileProvider : null;
        var user = (typeof profileData !== 'undefined' && profileData) ? profileData : null;
        var addr = user ? (user.primary_address || user.primaryAddress || null) : null;

        return [
            {
                label: 'Billetera activa para operar',
                ok: false,
                pending: 'wallets',
                panel: 'billetera'
            },
            {
                label: 'Datos del negocio con nombre',
                ok: !!(prov && prov.business_name && String(prov.business_name).trim() !== ''),
                panel: 'negocio'
            },
            {
                label: 'Dirección con calle y número',
                ok: !!(addr && addr.street && addr.number),
                panel: 'direccion'
            }
        ];
    }

    function appMovilRender(items, canOperate) {
        var box = document.getElementById('appmovil-checklist');
        var acts = document.getElementById('appmovil-actions');
        if (!box) return;

        var full = items.map(function (it) {
            return '<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:4px;margin-bottom:8px;background:#fff;">'
                + '<span style="font-weight:800;color:' + (it.ok ? '#047857' : '#b91c1d') + ';">' + (it.ok ? '✓' : '✗') + '</span>'
                + '<span style="flex:1;font-size:13px;color:#0f172a;">' + it.label + '</span>'
                + (it.ok ? '' : '<button type="button" onclick="showProfilePanel(\'' + it.panel + '\')" style="background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:12px;font-weight:600;color:#0c2a4d;cursor:pointer;">Completar</button>')
                + '</div>';
        }).join('');

        box.innerHTML = full;

        if (acts) {
            if (items.every(function (it) { return it.ok; })) {
                var apkUrl = ((acts.dataset && acts.dataset.apkUrl) || '').trim();
                var phoneDigits = '';
                try {
                    var me = (typeof profileData !== 'undefined' && profileData) ? profileData : null;
                    phoneDigits = String((me && me.phone) || '').replace(/\D/g, '');
                } catch (e) { phoneDigits = ''; }
                if (apkUrl) {
                    var wa = phoneDigits
                        ? '<a href="https://wa.me/' + phoneDigits + '?text=' + encodeURIComponent('Descargá PideAca: ' + apkUrl) + '" target="_blank" rel="noopener" style="display:inline-block;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:8px 16px;font-size:13px;font-weight:700;color:#0c2a4d;text-decoration:none;">Enviarme por WhatsApp</a>'
                        : '';
                    acts.innerHTML = '<div style="padding:10px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:4px;font-size:13px;color:#047857;margin-bottom:12px;">Cuenta lista. Descargá la app en tu teléfono.</div>'
                        + '<div style="display:flex;gap:8px;flex-wrap:wrap;">'
                        + '<a href="' + apkUrl + '" style="display:inline-block;background:#D24C19;color:#fff;border-radius:4px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;">Descargar para Android</a>'
                        + wa + '</div>';
                } else {
                    acts.innerHTML = '<div style="padding:10px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:4px;font-size:13px;color:#047857;">Cuenta lista. La descarga estará disponible próximamente.</div>';
                }
            } else {
                acts.innerHTML = '<p style="font-size:12px;color:#64748b;">Completá los puntos pendientes para habilitar la descarga.</p>';
            }
        }

        void canOperate;
    }

    function appMovilInit() {
        var box = document.getElementById('appmovil-checklist');
        if (!box) return;

        try {
            box.innerHTML = '<p style="font-size:13px;color:#64748b;">Cargando…</p>';

            var items = appMovilChecklist();
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.content : '';

            var controller = null;
            var signal = null;
            if (typeof AbortController !== 'undefined') {
                controller = new AbortController();
                signal = controller.signal;
                setTimeout(function () { try { controller.abort(); } catch (e) {} }, 15000);
            }

            fetch(window.APP_BASE + '/profile/billing/status', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                signal: signal
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var can = !!(res && res.data && res.data.can_operate);
                appMovilRender(items.map(function (it) {
                    if (it.pending === 'wallets') return { label: it.label, ok: can, panel: it.panel };
                    return it;
                }), can);
            })
            .catch(function () {
                appMovilRenderError();
            });
        } catch (e) {
            appMovilRenderError();
        }
    }

    function appMovilRenderError() {
        var box = document.getElementById('appmovil-checklist');
        if (!box) return;
        box.innerHTML = '<div style="padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;font-size:13px;color:#b91c1d;">'
            + 'No pudimos cargar tu estado. Revisá tu conexión.'
            + ' <button type="button" onclick="appMovilInit()" style="margin-left:8px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:12px;font-weight:600;color:#0c2a4d;cursor:pointer;">Reintentar</button>'
            + '</div>';
        var acts = document.getElementById('appmovil-actions');
        if (acts) acts.innerHTML = '';
    }

    function setProfileSidebarActive(key) {
        // Limpia el resaltado de todas las opciones y marca solo la
        // correspondiente a la seccion visible (desktop y movil usan
        // los mismos nodos: el sidebar se mueve al acoplar, no se duplica).
        document.querySelectorAll('.sidebar-link[data-panel]').forEach(function (link) {
            link.classList.toggle('active', link.dataset.panel === key);
        });
        var bc = document.getElementById('breadcrumb-current');
        if (bc) bc.textContent = (typeof profileBreadcrumbLabels !== 'undefined' && profileBreadcrumbLabels[key]) || key;
    }

    function showProfilePanel(key) {
        // En movil el sidebar es un drawer: cerrarlo siempre al elegir una
        // seccion, incluso en las salidas anticipadas de abajo (guards de rol,
        // admin->usuarios, errores en loaders). Sin esto el drawer y el overlay
        // quedaban abiertos tapando la pantalla.
        var sbTop = document.getElementById('profile-sidebar');
        if (sbTop) sbTop.classList.remove('open');
        var ovTop = document.getElementById('sidebar-overlay');
        if (ovTop) ovTop.style.display = 'none';
        // Guard de paneles de negocio: el secundario no los ve ni los edita,
        // se lo lleva a su primera página autorizada. Con impersonación se
        // evalúa el rol de la cuenta administrada.
        var panelesNegocio = ['negocio', 'horarios', 'servicios', 'repartidores', 'retiros', 'imagenes', 'categorias'];
        if (!umManagedCanConfigureBusiness() && panelesNegocio.indexOf(key) !== -1) {
            if (firstAuthorizedSection && document.getElementById('dash-' + firstAuthorizedSection)) {
                showDashSection(firstAuthorizedSection);
            } else if (firstAuthorizedSection && firstAuthorizedSection.charAt(0) === '/') {
                window.location.href = window.APP_BASE+"" + firstAuthorizedSection;
            }
            return;
        }
        // Guard de Mi Dirección: el secundario sin permiso vuelve a su primera página.
        if (key === 'direccion' && !profilePuedeVerDireccion) {
            if (firstAuthorizedSection && document.getElementById('dash-' + firstAuthorizedSection)) {
                showDashSection(firstAuthorizedSection);
            } else if (firstAuthorizedSection && firstAuthorizedSection.charAt(0) === '/') {
                window.location.href = window.APP_BASE+"" + firstAuthorizedSection;
            }
            return;
        }
        // Publicidad abre siempre su modulo: el backend (ProfileController@storeImage)
        // ya rechaza con 403/400 a quien no pueda anunciar o no tenga proveedor.
        if (key === 'usuarios' && !document.querySelector('.sidebar-link[data-panel="usuarios"]')) key = 'cuenta';
        // El Admin gestiona usuarios en la pantalla dedicada (con sidebar acoplado).
        if (key === 'usuarios' && actorIsAdmin && document.getElementById('dash-usuarios')) {
            setProfileSidebarActive('usuarios');
            showDashSection('usuarios');
            return;
        }
        // El sidebar puede estar acoplado en otra sección (Gestión de Usuarios):
        // volver a Mi Panel antes de activar el tab, sin recargar datos.
        var perfilSection = document.getElementById('dash-perfil');
        if (perfilSection && perfilSection.style.display === 'none') {
            document.querySelectorAll('.dash-section').forEach(function (s) { s.style.display = 'none'; });
            perfilSection.style.display = 'block';
            if (typeof unmountProfileUsuariosPanel === 'function') unmountProfileUsuariosPanel();
            if (typeof dockProfileSidebar === 'function') dockProfileSidebar(false);
            // Recarga con el contexto activo y aterriza en el tab pedido.
            window.__pendingProfilePanel = key;
            if (typeof loadProfile === 'function') loadProfile();
            return;
        }
        try {
            if (key === 'usuarios') mountProfileUsuariosPanel();
            else unmountProfileUsuariosPanel();
        } catch (e) {
            console.error('Error al cambiar de panel de perfil:', e);
        }
        if (key === 'direccion') loadAddress();
        if (key === 'repartidores') loadTrustedDrivers();
        if (key === 'dinero' && typeof dnInitDinero === 'function') dnInitDinero();
        if (key === 'billeteras-pais' && typeof wtLoad === 'function') wtLoad();
        if (key === 'billetera' && typeof loadPayoutAccounts === 'function') loadPayoutAccounts();
        if (key === 'app-movil' && typeof appMovilInit === 'function') appMovilInit();
        if (key === 'retiros' && typeof pvWdLoad === 'function') pvWdLoad();
        if (key === 'imagenes' && typeof loadProviderImages === 'function') loadProviderImages();
        setProfileSidebarActive(key);
        document.querySelectorAll('.profile-panel').forEach(function(p) {
            p.classList.toggle('active', p.dataset.panel === key);
        });
    }

    function toggleProfileSidebar() {
        var sb = document.getElementById('profile-sidebar');
        var ov = document.getElementById('sidebar-overlay');
        if (!sb) return;
        var isOpen = sb.classList.toggle('open');
        if (ov) ov.style.display = isOpen ? 'block' : 'none';
        setTimeout(adjustSidebarHeight, 50);
    }

    function loadProfile() {
        if (!profileEsPrestador && !umManagedId()) {
            // Vuelta desde una seccion con sidebar acoplado (p. ej. Gestion de
            // Usuarios): el panel pedido quedo en __pendingProfilePanel y no hay
            // fetch que lo consuma en esta rama. Sin esto, el primer clic no
            // cambiaba de vista y hacia falta un segundo clic.
            if (window.__pendingProfilePanel) {
                var pendingKey = window.__pendingProfilePanel;
                window.__pendingProfilePanel = null;
                showProfilePanel(pendingKey);
                return;
            }
            if (profilePanelInicial === 'direccion') showProfilePanel('direccion');
            return;
        }

        var loading = document.getElementById('profile-loading');
        var panels = document.getElementById('profile-panels');
        panels.style.display = 'none';
        loading.style.display = 'block';

        fetch(umManagedId() ? umAdminUserUrl('') : window.APP_BASE+"/profile", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(user) {
            profileData = user;
            profileProvider = user.provider || null;
            if (umManagedId()) umManagedProfile = user; else umManagedProfile = null;
            var typeObj = user.type_user || user.typeUser || null;
            profileIsPrestador = typeObj && typeObj.description === 'Prestador';
            var statusObj = user.status || user.user_status || null;
            var statusName = String((statusObj && (statusObj.status || statusObj.description)) || '').trim().toLowerCase();
            profileCanAdvertise = !!profileIsPrestador && ADVERTISING_STATUSES.indexOf(statusName) !== -1;

            document.getElementById('profile-name').value = user.name || '';
            document.getElementById('profile-email').value = user.email || '';
            setProfileStatusBadge(user);
            paintMembershipCard();
            var statusSel = document.getElementById('profile-status-select');
            if (statusSel) {
                statusSel.innerHTML = '<option value="">Cargando…</option>';
                fetch(window.APP_BASE+"/user-statuses", {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(function(r) { return r.json(); })
                .then(function(list) {
                    statusSel.innerHTML = '<option value="">-- Seleccionar --</option>';
                    (Array.isArray(list) ? list : []).forEach(function(s) {
                        var opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.status;
                        statusSel.appendChild(opt);
                    });
                    var sid = user.user_status_id || (user.status && user.status.id) || null;
                    if (sid) statusSel.value = String(sid);
                })
                .catch(function() {
                    statusSel.innerHTML = '<option value="">Error al cargar estados</option>';
                });
            }
            document.getElementById('profile-type').textContent = typeObj ? typeObj.description : '-';

            var toggleSidebarLink = function (id, visible) {
                var link = document.getElementById(id);
                if (link) link.style.display = visible ? '' : 'none';
            };

            toggleSidebarLink('sidebar-link-negocio', profilePuedeConfigurarNegocio);
            toggleSidebarLink('sidebar-link-horarios', profilePuedeConfigurarNegocio);
            toggleSidebarLink('sidebar-link-servicios', profilePuedeConfigurarNegocio);
            toggleSidebarLink('sidebar-link-imagenes', profilePuedeConfigurarNegocio);
            toggleSidebarLink('sidebar-link-categorias', profilePuedeConfigurarNegocio);
            if (umManagedId()) umApplyManagedSidebar();

            loading.style.display = 'none';
            panels.style.display = 'block';

            var targetPanel = window.__pendingProfilePanel || profilePanelInicial;
            window.__pendingProfilePanel = null;

            if (profileIsPrestador && profilePuedeConfigurarNegocio) {
                showProfilePanel(targetPanel);
                fillProviderForm();
                buildHoursGrid();
                loadProviderServices();
                loadProviderImages();
                loadProviderSubgroups();
                setupImageDropzones();
                loadAddress();
            } else {
                showProfilePanel(targetPanel);
            }
        })
        .catch(function() {
            window.__pendingProfilePanel = null;
            loading.textContent = 'Error al cargar el perfil.';
        });
    }

    function setProfileStatusBadge(user) {
        var el = document.getElementById('profile-status');
        var stObj = user.status || user.user_status || null;
        var st = stObj ? (stObj.status || stObj.description || '-') : '-';
        el.textContent = st;
        var stLower = String(st).toLowerCase();
        if (stLower === 'active' || stLower === 'activo' || stLower === 'activa') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:12px;font-weight:600;';
        } else if (stLower === 'inactive' || stLower === 'inactivo' || stLower === 'inactiva') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:600;';
        } else if (stLower === 'prueba') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#fffbeb;border:1px solid #fde68a;color:#b45309;font-size:12px;font-weight:600;';
        } else {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#eef2f7;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:600;';
        }
    }

    // ---------- Tab 1: Datos del negocio ----------

    function fillProviderForm() {
        if (!profileProvider) return;
        document.getElementById('provider-business-name').value = profileProvider.business_name || '';
        document.getElementById('provider-description').value = profileProvider.description || '';
        document.getElementById('provider-phone').value = profileProvider.phone || '';
        document.getElementById('provider-whatsapp').value = profileProvider.whatsapp || '';
        document.getElementById('provider-zone').value = profileProvider.zone || '';
        document.getElementById('provider-promo').value = profileProvider.promo || '';

        // Tarifa de envío configurable por el comercio.
        if (document.getElementById('provider-delivery-mode')) {
            var mode = profileProvider.delivery_fee_mode || 'global';
            document.getElementById('provider-delivery-mode').value = mode;
            document.getElementById('provider-delivery-flat').value = fdDeliveryNumber(profileProvider.delivery_fee_flat);
            document.getElementById('provider-delivery-base').value = fdDeliveryNumber(profileProvider.delivery_fee_base);
            document.getElementById('provider-delivery-per-km').value = fdDeliveryNumber(profileProvider.delivery_fee_per_km);
            document.getElementById('provider-delivery-min').value = fdDeliveryNumber(profileProvider.delivery_fee_min);
            document.getElementById('provider-delivery-max').value = fdDeliveryNumber(profileProvider.delivery_fee_max);
            fdDeliverySync();
        }
    }

    function fdDeliveryNumber(value) {
        var n = Number(value || 0);
        return n > 0 ? n : '';
    }

    /**
     * Muestra los campos que corresponden a la modalidad elegida y arma una
     * vista previa del precio que verá el cliente.
     */
    function fdDeliverySync() {
        var modeEl = document.getElementById('provider-delivery-mode');
        if (!modeEl) return;

        var mode = modeEl.value;
        var flatWrap = document.getElementById('provider-delivery-flat-wrap');
        var distWrap = document.getElementById('provider-delivery-distance-wrap');

        if (flatWrap) flatWrap.style.display = mode === 'flat' ? '' : 'none';
        if (distWrap) distWrap.style.display = mode === 'distance' ? '' : 'none';

        var preview = document.getElementById('provider-delivery-preview');
        if (!preview) return;

        var globalFee = Number(window.__FD_DELIVERY_FEE) || 0;

        if (mode === 'flat') {
            var flat = parseFloat(document.getElementById('provider-delivery-flat').value || '0') || 0;
            preview.innerHTML = flat > 0
                ? 'Tus clientes verán un envío fijo de <strong>' + fdMoney(flat) + '</strong>. El repartidor recibe el 100%.'
                : 'Ingresá el monto fijo que vas a cobrar por el envío.';
            return;
        }

        if (mode === 'distance') {
            var base = parseFloat(document.getElementById('provider-delivery-base').value || '0') || 0;
            var perKm = parseFloat(document.getElementById('provider-delivery-per-km').value || '0') || 0;
            var min = parseFloat(document.getElementById('provider-delivery-min').value || '0') || 0;
            var max = parseFloat(document.getElementById('provider-delivery-max').value || '0') || 0;

            var parts = ['Costo base ' + fdMoney(base)];
            parts.push('+' + fdMoney(perKm) + ' por km');
            if (min > 0) parts.push('mínimo ' + fdMoney(min));
            if (max > 0) parts.push('techo ' + fdMoney(max));
            else parts.push('sin techo');

            preview.innerHTML = 'El envío se calcula como <strong>' + parts.join(' · ') + '</strong> según la distancia al cliente.';
            return;
        }

        preview.innerHTML = 'Se aplica la tarifa general de PideAca (<strong>' + fdMoney(globalFee) + '</strong>). El repartidor recibe el 100% del envío.';
    }

    function submitProvider(msgId) {
        var errBn = document.getElementById('provider-business-name-error');
        var nameEl = document.getElementById('provider-business-name');
        errBn.style.display = 'none';
        nameEl.classList.remove('err');

        var businessName = nameEl.value.trim();
        if (!businessName) {
            nameEl.classList.add('err');
            errBn.textContent = 'El nombre del negocio es obligatorio.';
            errBn.style.display = 'block';
            showProfilePanel('negocio');
            return;
        }

        var payload = {
            business_name: businessName,
            description: document.getElementById('provider-description').value.trim(),
            phone: document.getElementById('provider-phone').value.trim(),
            whatsapp: document.getElementById('provider-whatsapp').value.trim(),
            zone: document.getElementById('provider-zone').value.trim(),
            promo: document.getElementById('provider-promo').value.trim(),
            hours: collectHours()
        };

        // Motor de tarifas de envío: solo se envía si el panel está presente.
        if (document.getElementById('provider-delivery-mode')) {
            payload.delivery_fee_mode = document.getElementById('provider-delivery-mode').value;
            payload.delivery_fee_flat = parseFloat(document.getElementById('provider-delivery-flat').value || '0') || 0;
            payload.delivery_fee_base = parseFloat(document.getElementById('provider-delivery-base').value || '0') || 0;
            payload.delivery_fee_per_km = parseFloat(document.getElementById('provider-delivery-per-km').value || '0') || 0;
            payload.delivery_fee_min = parseFloat(document.getElementById('provider-delivery-min').value || '0') || 0;
            payload.delivery_fee_max = parseFloat(document.getElementById('provider-delivery-max').value || '0') || 0;
        }

        fetch(umManagedId() ? umAdminUserUrl('/provider') : window.APP_BASE+"/profile/provider", {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.business_name) {
                    nameEl.classList.add('err');
                    errBn.textContent = data.errors.business_name[0];
                    errBn.style.display = 'block';
                }
                var firstError = Object.keys(data.errors)[0];
                showMsg(msgId, data.errors[firstError][0], false);
                return;
            }
            if (data.success) {
                profileProvider = data.provider;
                if (umManagedId() && umManagedProfile) umManagedProfile.provider = data.provider;
                var successMsg = (msgId === 'tab2-msg') ? 'Horarios de atención actualizados correctamente.' : data.message;
                showMsg(msgId, successMsg, true);
            } else {
                showMsg(msgId, data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            showMsg(msgId, 'Error de conexión.', false);
        });
    }

    // ---------- Tab 2: Horarios ----------

    function buildHoursGrid() {
        var container = document.getElementById('hours-list');
        if (!container) return;
        container.innerHTML = '';
        var hoursStyle = document.getElementById('hours-toggle-style');
        if (!hoursStyle) {
            hoursStyle = document.createElement('style');
            hoursStyle.id = 'hours-toggle-style';
            hoursStyle.textContent = '.hours-toggle:checked + span { background:#22c55e !important; } .hours-toggle:checked + span > span { transform:translateX(20px); }';
            document.head.appendChild(hoursStyle);
        }
        var hours = (profileProvider && profileProvider.hours) ? profileProvider.hours : {};

        PROFILE_DAYS.forEach(function(day) {
            var dayData = hours[day.key] || {};
            var isOpen = dayData.open !== false && (dayData.shifts && dayData.shifts.length > 0);

            var row = document.createElement('div');
            row.setAttribute('data-day', day.key);
            row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:0;flex-wrap:wrap;';

            var label = document.createElement('span');
            label.style.cssText = 'min-width:110px;font-size:13px;font-weight:600;color:#0f172a;';
            label.textContent = day.label;
            row.appendChild(label);

            var toggleWrap = document.createElement('label');
            toggleWrap.style.cssText = 'position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;';
            var toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = isOpen;
            toggle.className = 'hours-toggle';
            toggle.setAttribute('data-day', day.key);
            toggle.style.cssText = 'opacity:0;width:0;height:0;';
            toggle.addEventListener('change', function() {
                toggleDay(day.key, this.checked);
            });
            var slider = document.createElement('span');
            slider.style.cssText = 'position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:.2s;';
            var sliderBefore = document.createElement('span');
            sliderBefore.style.cssText = 'position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s;';
            slider.appendChild(sliderBefore);
            toggleWrap.appendChild(toggle);
            toggleWrap.appendChild(slider);
            row.appendChild(toggleWrap);

            var statusLabel = document.createElement('span');
            statusLabel.className = 'hours-status';
            statusLabel.setAttribute('data-day', day.key);
            statusLabel.style.cssText = 'font-size:12px;font-weight:600;min-width:60px;';
            statusLabel.textContent = isOpen ? 'Abierto' : 'Cerrado';
            statusLabel.style.color = isOpen ? '#16a34a' : '#94a3b8';
            row.appendChild(statusLabel);

            var shiftsWrap = document.createElement('div');
            shiftsWrap.className = 'hours-shifts';
            shiftsWrap.setAttribute('data-day', day.key);
            shiftsWrap.style.cssText = 'display:flex;align-items:center;gap:8px;flex-wrap:wrap;width:100%;' + (isOpen ? '' : 'opacity:0.4;pointer-events:none;');

            var shifts = (dayData.shifts && dayData.shifts.length > 0) ? dayData.shifts : [{ from: '09:00', to: '18:00' }];

            shifts.forEach(function(shift, si) {
                var shiftGroup = document.createElement('div');
                shiftGroup.className = 'shift-group';
                shiftGroup.setAttribute('data-day', day.key);
                shiftGroup.setAttribute('data-shift', si);
                shiftGroup.style.cssText = 'display:flex;align-items:center;gap:6px;flex-wrap:wrap;';

                if (si > 0) {
                    var dash1 = document.createElement('span');
                    dash1.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
                    dash1.textContent = '—';
                    shiftGroup.appendChild(dash1);
                }

                var fromInput = document.createElement('input');
                fromInput.type = 'time';
                fromInput.value = shift.from || '09:00';
                fromInput.className = 'hours-from';
                fromInput.setAttribute('data-day', day.key);
                fromInput.setAttribute('data-shift', si);
                fromInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
                fromInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
                fromInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
                shiftGroup.appendChild(fromInput);

                var dash = document.createElement('span');
                dash.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
                dash.textContent = 'a';
                shiftGroup.appendChild(dash);

                var toInput = document.createElement('input');
                toInput.type = 'time';
                toInput.value = shift.to || '18:00';
                toInput.className = 'hours-to';
                toInput.setAttribute('data-day', day.key);
                toInput.setAttribute('data-shift', si);
                toInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
                toInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
                toInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
                shiftGroup.appendChild(toInput);

                if (si > 0) {
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#dc2626;padding:2px;';
                    removeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>';
                    removeBtn.addEventListener('click', function() {
                        removeShift(day.key, si);
                    });
                    shiftGroup.appendChild(removeBtn);
                }

                shiftsWrap.appendChild(shiftGroup);
            });

            if (isOpen) {
                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'add-shift-btn';
                addBtn.title = 'Agregar turno';
                addBtn.setAttribute('aria-label', 'Agregar turno');
                addBtn.setAttribute('data-day', day.key);
                addBtn.style.cssText = 'background:#D24C19;color:#fff;border:none;padding:5px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;';
                addBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>';
                addBtn.addEventListener('click', function() {
                    addShift(day.key);
                });
                shiftsWrap.appendChild(addBtn);
            }

            row.appendChild(shiftsWrap);

            var copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.title = 'Copiar al día siguiente';
            copyBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;flex-shrink:0;';
            copyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>';
            copyBtn.addEventListener('click', function() {
                copyDayToNext(day.key);
            });
            row.appendChild(copyBtn);

            container.appendChild(row);
        });
    }

    function toggleDay(dayKey, isOpen) {
        var row = document.querySelector('#hours-list [data-day="' + dayKey + '"]');
        if (!row) return;
        var shiftsWrap = row.querySelector('.hours-shifts');
        var status = row.querySelector('.hours-status');
        if (isOpen) {
            shiftsWrap.style.opacity = '1';
            shiftsWrap.style.pointerEvents = 'auto';
            status.textContent = 'Abierto';
            status.style.color = '#16a34a';
            if (!shiftsWrap.querySelector('.shift-group')) {
                addShift(dayKey);
            }
        } else {
            shiftsWrap.style.opacity = '0.4';
            shiftsWrap.style.pointerEvents = 'none';
            status.textContent = 'Cerrado';
            status.style.color = '#94a3b8';
        }
    }

    function addShift(dayKey) {
        var shiftsWrap = document.querySelector('.hours-shifts[data-day="' + dayKey + '"]');
        if (!shiftsWrap) return;
        var existingShifts = shiftsWrap.querySelectorAll('.shift-group').length;
        if (existingShifts >= 3) return;

        var shiftGroup = document.createElement('div');
        shiftGroup.className = 'shift-group';
        shiftGroup.setAttribute('data-day', dayKey);
        shiftGroup.setAttribute('data-shift', existingShifts);
        shiftGroup.style.cssText = 'display:flex;align-items:center;gap:6px;flex-wrap:wrap;';

        var dash1 = document.createElement('span');
        dash1.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
        dash1.textContent = '—';
        shiftGroup.appendChild(dash1);

        var fromInput = document.createElement('input');
        fromInput.type = 'time';
        fromInput.value = '13:00';
        fromInput.className = 'hours-from';
        fromInput.setAttribute('data-day', dayKey);
        fromInput.setAttribute('data-shift', existingShifts);
        fromInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
        fromInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
        fromInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
        shiftGroup.appendChild(fromInput);

        var dash = document.createElement('span');
        dash.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
        dash.textContent = 'a';
        shiftGroup.appendChild(dash);

        var toInput = document.createElement('input');
        toInput.type = 'time';
        toInput.value = '17:00';
        toInput.className = 'hours-to';
        toInput.setAttribute('data-day', dayKey);
        toInput.setAttribute('data-shift', existingShifts);
        toInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
        toInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
        toInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
        shiftGroup.appendChild(toInput);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#dc2626;padding:2px;';
        removeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>';
        removeBtn.addEventListener('click', function() {
            removeShift(dayKey, existingShifts);
        });
        shiftGroup.appendChild(removeBtn);

        var addBtn = shiftsWrap.querySelector('.add-shift-btn');
        shiftsWrap.insertBefore(shiftGroup, addBtn);
    }

    function removeShift(dayKey, shiftIndex) {
        var shiftsWrap = document.querySelector('.hours-shifts[data-day="' + dayKey + '"]');
        if (!shiftsWrap) return;
        var groups = shiftsWrap.querySelectorAll('.shift-group');
        if (groups.length <= 1) return;
        groups[shiftIndex].remove();
        var remaining = shiftsWrap.querySelectorAll('.shift-group');
        remaining.forEach(function(g, i) {
            g.setAttribute('data-shift', i);
            g.querySelectorAll('input').forEach(function(inp) {
                inp.setAttribute('data-shift', i);
            });
        });
    }

    function collectHours() {
        var out = {};
        PROFILE_DAYS.forEach(function(day) {
            var toggle = document.querySelector('.hours-toggle[data-day="' + day.key + '"]');
            if (!toggle || !toggle.checked) {
                out[day.key] = { open: false, shifts: [] };
                return;
            }
            var shifts = [];
            var groups = document.querySelectorAll('.hours-shifts[data-day="' + day.key + '"] .shift-group');
            groups.forEach(function(g) {
                var from = g.querySelector('.hours-from');
                var to = g.querySelector('.hours-to');
                if (from && to) {
                    shifts.push({ from: from.value, to: to.value });
                }
            });
            out[day.key] = { open: true, shifts: shifts };
        });
        return out;
    }

    function copyMonFri() {
        var monData = null;
        var monToggle = document.querySelector('.hours-toggle[data-day="mon"]');
        if (monToggle && monToggle.checked) {
            var monShifts = [];
            document.querySelectorAll('.hours-shifts[data-day="mon"] .shift-group').forEach(function(g) {
                var from = g.querySelector('.hours-from');
                var to = g.querySelector('.hours-to');
                if (from && to) monShifts.push({ from: from.value, to: to.value });
            });
            monData = { open: true, shifts: monShifts };
        } else {
            monData = { open: false, shifts: [] };
        }

        ['tue','wed','thu','fri'].forEach(function(d) {
            var toggle = document.querySelector('.hours-toggle[data-day="' + d + '"]');
            if (toggle) {
                toggle.checked = monData.open;
                toggle.dispatchEvent(new Event('change'));
            }
            var groups = document.querySelectorAll('.hours-shifts[data-day="' + d + '"] .shift-group');
            groups.forEach(function(g, i) { if (i > 0) g.remove(); });
            if (monData.shifts.length > 0) {
                var firstFrom = document.querySelector('.hours-shifts[data-day="' + d + '"] .hours-from');
                var firstTo = document.querySelector('.hours-shifts[data-day="' + d + '"] .hours-to');
                if (firstFrom) firstFrom.value = monData.shifts[0].from;
                if (firstTo) firstTo.value = monData.shifts[0].to;
                for (var si = 1; si < monData.shifts.length; si++) {
                    addShift(d);
                    var newGroup = document.querySelector('.hours-shifts[data-day="' + d + '"] .shift-group[data-shift="' + si + '"]');
                    if (newGroup) {
                        var nf = newGroup.querySelector('.hours-from');
                        var nt = newGroup.querySelector('.hours-to');
                        if (nf) nf.value = monData.shifts[si].from;
                        if (nt) nt.value = monData.shifts[si].to;
                    }
                }
            }
        });
    }

    function copyDayToNext(dayKey) {
        var days = ['mon','tue','wed','thu','fri','sat','sun'];
        var idx = days.indexOf(dayKey);
        if (idx < 0 || idx >= days.length - 1) return;
        var nextDay = days[idx + 1];

        var sourceData = collectHours()[dayKey];

        var nextToggle = document.querySelector('.hours-toggle[data-day="' + nextDay + '"]');
        if (nextToggle) {
            nextToggle.checked = sourceData.open;
            nextToggle.dispatchEvent(new Event('change'));
        }
        var groups = document.querySelectorAll('.hours-shifts[data-day="' + nextDay + '"] .shift-group');
        groups.forEach(function(g, i) { if (i > 0) g.remove(); });
        if (sourceData.shifts.length > 0) {
            var firstFrom = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .hours-from');
            var firstTo = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .hours-to');
            if (firstFrom) firstFrom.value = sourceData.shifts[0].from;
            if (firstTo) firstTo.value = sourceData.shifts[0].to;
            for (var si = 1; si < sourceData.shifts.length; si++) {
                addShift(nextDay);
                var newGroup = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .shift-group[data-shift="' + si + '"]');
                if (newGroup) {
                    var nf = newGroup.querySelector('.hours-from');
                    var nt = newGroup.querySelector('.hours-to');
                    if (nf) nf.value = sourceData.shifts[si].from;
                    if (nt) nt.value = sourceData.shifts[si].to;
                }
            }
        }
    }

    // ---------- Tab 3: Servicios e imágenes ----------

    function loadProviderServices() {
        var list = document.getElementById('services-list');
        var loading = document.getElementById('services-loading');
        var empty = document.getElementById('services-empty');

        fetch(umManagedId() ? umAdminUserUrl('/services') : window.APP_BASE+"/profile/services", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            if (!data.services || data.services.length === 0) {
                empty.style.display = 'block';
                list.style.display = 'none';
                return;
            }
            empty.style.display = 'none';
            list.style.display = 'flex';
            list.innerHTML = '';

            data.services.forEach(function(s, i) {
                var card = document.createElement('div');
                card.className = 'service-card';

                var top = document.createElement('div');
                top.style.cssText = 'display:flex;align-items:center;justify-content:space-between;';
                var left = document.createElement('div');
                left.style.cssText = 'display:flex;align-items:center;gap:8px;';
                var idx = document.createElement('span');
                idx.className = 'service-index';
                idx.textContent = (i + 1 < 10 ? '0' : '') + (i + 1);
                var name = document.createElement('span');
                name.style.cssText = 'font-size:13px;font-weight:600;color:#0f172a;';
                name.textContent = s.name;
                left.appendChild(idx);
                left.appendChild(name);
                var actions = document.createElement('div');
                actions.style.cssText = 'display:flex;gap:6px;';
                actions.appendChild(makeIconBtn('edit', "openEditServiceModal(" + s.id + ", '" + escapeHtml(s.name).replace(/'/g, "\\'") + "', '" + escapeHtml(s.description || '').replace(/'/g, "\\'") + "')"));
                actions.appendChild(makeIconBtn('del', "openServiceDeleteModal(" + s.id + ")"));
                top.appendChild(left);
                top.appendChild(actions);
                card.appendChild(top);

                if (s.description) {
                    var desc = document.createElement('p');
                    desc.style.cssText = 'font-size:12px;color:#64748b;margin:8px 0 0;line-height:1.4;';
                    desc.textContent = s.description;
                    card.appendChild(desc);
                }

                list.appendChild(card);
            });
        })
        .catch(function() {
            loading.textContent = 'Error al cargar servicios.';
        });
    }

    function makeIconBtn(kind, onclick) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'icon-btn ' + (kind === 'edit' ? 'edit' : 'del');
        b.setAttribute('onclick', onclick);
        if (kind === 'edit') {
            b.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
        } else {
            b.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>';
        }
        return b;
    }

    function openServiceModal() {
        document.getElementById('service-edit-id').value = '';
        document.getElementById('service-name').value = '';
        document.getElementById('service-name').classList.remove('err');
        document.getElementById('service-name-error').style.display = 'none';
        document.getElementById('service-msg').style.display = 'none';
        document.getElementById('service-modal-title').textContent = 'Agregar servicio';
        document.getElementById('service-modal-overlay').style.display = 'flex';
    }

    function openEditServiceModal(id, name, description) {
        document.getElementById('service-edit-id').value = id;
        document.getElementById('service-name').value = name;
        document.getElementById('service-name').classList.remove('err');
        document.getElementById('service-name-error').style.display = 'none';
        document.getElementById('service-msg').style.display = 'none';
        document.getElementById('service-modal-title').textContent = 'Editar servicio';
        document.getElementById('service-modal-overlay').style.display = 'flex';
    }

    function closeServiceModal() {
        document.getElementById('service-modal-overlay').style.display = 'none';
    }

    function submitService() {
        var editId = document.getElementById('service-edit-id').value;
        var nameEl = document.getElementById('service-name');
        var name = nameEl.value.trim();
        var errEl = document.getElementById('service-name-error');
        var msgEl = document.getElementById('service-msg');
        errEl.style.display = 'none';
        msgEl.style.display = 'none';
        nameEl.classList.remove('err');

        if (!name) {
            nameEl.classList.add('err');
            errEl.textContent = 'El nombre es obligatorio.';
            errEl.style.display = 'block';
            return;
        }

        var url = editId
            ? (umManagedId() ? umAdminUserUrl('/services/') + editId : window.APP_BASE+"/profile/services"+"/" + editId)
            : (umManagedId() ? umAdminUserUrl('/services') : window.APP_BASE+"/profile/services");
        var method = editId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ name: name })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                nameEl.classList.add('err');
                if (data.errors.name) { errEl.textContent = data.errors.name[0]; errEl.style.display = 'block'; }
                return;
            }
            if (data.success) {
                closeServiceModal();
                loadProviderServices();
            } else {
                msgEl.textContent = data.message;
                msgEl.className = 'save-msg err';
                msgEl.style.display = 'inline';
            }
        })
        .catch(function() {
            msgEl.textContent = 'Error de conexión.';
            msgEl.className = 'save-msg err';
            msgEl.style.display = 'inline';
        });
    }

    var deleteServiceId = null;

    function openServiceDeleteModal(id) {
        deleteServiceId = id;
        document.getElementById('service-delete-overlay').style.display = 'flex';
    }

    function closeServiceDeleteModal() {
        deleteServiceId = null;
        document.getElementById('service-delete-overlay').style.display = 'none';
    }

    function confirmDeleteService() {
        if (!deleteServiceId) return;
        fetch(umManagedId() ? umAdminUserUrl('/services/') + deleteServiceId : window.APP_BASE+"/profile/services"+"/" + deleteServiceId, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            closeServiceDeleteModal();
            loadProviderServices();
        });
    }

    function loadProviderImages() {
        var grid = document.getElementById('images-grid');
        var loading = document.getElementById('images-loading');
        var empty = document.getElementById('images-empty');
        var dropzone = document.getElementById('dropzone-publicidad');
        var publicidadSection = document.getElementById('publicidad-limit-msg');

        if (!publicidadSection) {
            var dz = document.getElementById('dropzone-publicidad');
            if (dz) {
                var msg = document.createElement('div');
                msg.id = 'publicidad-limit-msg';
                msg.style.cssText = 'display:none;margin-top:12px;padding:10px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:12px;color:#9a3412;';
                msg.innerHTML = '&#9432; Solo se permite una imagen publicitaria. Eliminá la actual para subir una nueva.';
                dz.parentNode.insertBefore(msg, dz.nextSibling);
                publicidadSection = msg;
            }
        }

        if (!profileProvider || !profileProvider.images || profileProvider.images.length === 0) {
            loading.style.display = 'none';
            empty.style.display = 'block';
            grid.style.display = 'none';
            if (dropzone) dropzone.style.display = '';
            if (publicidadSection) publicidadSection.style.display = 'none';
            return;
        }

        loading.style.display = 'none';
        empty.style.display = 'none';
        grid.style.display = 'grid';
        grid.innerHTML = '';

        if (dropzone) dropzone.style.display = 'none';
        if (publicidadSection) publicidadSection.style.display = 'block';

        profileProvider.images.forEach(function(img) {
            var card = document.createElement('div');
            card.style.cssText = 'position:relative;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;background:#fff;';
            var src = window.APP_BASE+"/images/publicidad"+"/" + img.image_path + '?v=' + Date.now();
            var typeLabel = 'Publicidad';
            card.innerHTML = '<img src="' + src + '" style="width:100%;height:180px;object-fit:cover;display:block;" />' +
                '<span class="img-type-badge">' + typeLabel + '</span>' +
                '<button type="button" onclick="deleteImage(' + img.id + ')" style="position:absolute;top:6px;right:6px;background:rgba(220,38,38,0.95);color:#fff;border:none;border-radius:8px;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#10005;</button>';
            grid.appendChild(card);
        });
    }

    function setupImageDropzones() {
        setupDropzone('dropzone-publicidad', 'publicidad', 'image-upload-publicidad');
    }

    function setupDropzone(zoneId, type, inputId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        if (!zone || !input) return;

        zone.addEventListener('click', function(e) {
            if (e.target !== input) input.click();
        });
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', function() {
            zone.classList.remove('dragover');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                uploadImage(type, e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                uploadImage(type, input.files[0]);
                input.value = '';
            }
        });
    }

    function uploadImage(type, file) {
        if (!file) return;
        var formData = new FormData();
        formData.append('image', file);
        formData.append('image_type', type);

        fetch(umManagedId() ? umAdminUserUrl('/images') : window.APP_BASE+"/profile/images", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                profileProvider.images.push(data.image);
                loadProviderImages();
                showMsg('images-msg', data.message, true);
            } else {
                showMsg('images-msg', data.message || 'No se pudo subir la imagen.', false);
            }
        })
        .catch(function() {
            showMsg('images-msg', 'Error de conexión.', false);
        });
    }

    function deleteImage(id) {
        if (!confirm('¿Eliminar esta imagen?')) return;
        fetch(umManagedId() ? umAdminUserUrl('/images/') + id : window.APP_BASE+"/profile/images"+"/" + id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                profileProvider.images = profileProvider.images.filter(function(img) { return img.id != id; });
                loadProviderImages();
            }
        });
    }

    // ---------- Categorías y subgrupos ----------

    function loadProviderSubgroups() {
        var list = document.getElementById('categorias-list');
        var loading = document.getElementById('categorias-loading');

        fetch(umManagedId() ? umAdminUserUrl('/subgroups') : window.APP_BASE+"/profile/subgroups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            list.style.display = 'block';
            fdHogarSubgroupIds = collectHogarSubgroupIds(data.groups);
            renderProviderSubgroups(list, data, {
                msgId: 'categorias-msg',
                onSelectionChange: updateServiceTypesCardVisibility
            });
            updateServiceTypesCardVisibility(data.selected);
        })
        .catch(function() {
            loading.textContent = 'Error al cargar categorías.';
        });
    }

    function renderProviderSubgroups(list, data, ctx) {
        ctx = ctx || {};
        list.innerHTML = '';

        if (!data.groups || data.groups.length === 0) {
            list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px;">No hay categorías disponibles.</p>';
            return;
        }

        data.groups.forEach(function(group) {
            if (!group.subgroups || group.subgroups.length === 0) return;

            var section = document.createElement('div');
            section.style.cssText = 'margin-bottom:20px;border:1px solid #e2e8f0;background:#fff;padding:16px;';

            var header = document.createElement('div');
            header.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:12px;';
            var icon = document.createElement('span');
            if (group.icon) icon.innerHTML = group.icon;
            icon.style.cssText = 'display:flex;align-items:center;color:#D24C19;';
            var title = document.createElement('h4');
            title.style.cssText = 'font-size:13px;font-weight:700;color:#0f172a;margin:0;';
            title.textContent = group.description;
            header.appendChild(icon);
            header.appendChild(title);
            section.appendChild(header);

            var grid = document.createElement('div');
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;';

            group.subgroups.forEach(function(sub) {
                var isSelected = data.selected.indexOf(sub.id) >= 0;
                var label = document.createElement('label');
                label.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid ' + (isSelected ? '#D24C19' : '#e2e8f0') + ';background:' + (isSelected ? '#fff7ed' : '#fff') + ';cursor:pointer;transition:all .15s;font-size:12px;font-weight:500;color:' + (isSelected ? '#D24C19' : '#475569') + ';';
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.checked = isSelected;
                cb.style.cssText = 'accent-color:#D24C19;width:14px;height:14px;cursor:pointer;';
                cb.addEventListener('change', function() {
                    toggleSubgroup(sub.id, label, cb, ctx);
                });
                var span = document.createElement('span');
                span.textContent = sub.description;
                label.appendChild(cb);
                label.appendChild(span);
                grid.appendChild(label);
            });

            section.appendChild(grid);
            list.appendChild(section);
        });
    }

    function toggleSubgroup(subgroupId, labelEl, cb, ctx) {
        ctx = ctx || {};
        var msgId = ctx.msgId || 'categorias-msg';
        fetch(umManagedId() ? umAdminUserUrl('/subgroups/toggle') : window.APP_BASE+"/profile/subgroups/toggle", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ subgroup_id: subgroupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isSelected = data.selected.indexOf(subgroupId) >= 0;
                labelEl.style.borderColor = isSelected ? '#D24C19' : '#e2e8f0';
                labelEl.style.background = isSelected ? '#fff7ed' : '#fff';
                labelEl.style.color = isSelected ? '#D24C19' : '#475569';
                cb.checked = isSelected;
                if (ctx.onSelectionChange) ctx.onSelectionChange(data.selected);
                showMsg(msgId, data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg(msgId, data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg(msgId, 'Error de conexión.', false);
        });
    }

    // ---------- Tipos de servicio (Módulo B) ----------

    var fdHogarSubgroupIds = [];
    var fdServiceTypesLoaded = false;

    // La seccion "Tipos de servicio" solo se muestra si el prestador tiene
    // marcado al menos un subgrupo del grupo de Hogar & Bienestar.
    function isHogarGroup(group) {
        return String((group && group.description) || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase() === 'servicios del hogar & bienestar';
    }

    function collectHogarSubgroupIds(groups) {
        var ids = [];
        (groups || []).forEach(function(group) {
            if (!isHogarGroup(group)) return;
            (group.subgroups || []).forEach(function(s) { ids.push(s.id); });
        });
        return ids;
    }

    function updateServiceTypesCardVisibility(selected) {
        var card = document.getElementById('service-types-card');
        if (!card) return;
        var sel = selected || [];
        var visible = fdHogarSubgroupIds.some(function(id) { return sel.indexOf(id) >= 0; });
        card.style.display = visible ? '' : 'none';
        if (visible && !fdServiceTypesLoaded) {
            fdServiceTypesLoaded = true;
            loadServiceTypes();
        }
    }

    function loadServiceTypes() {
        var list = document.getElementById('service-types-list');
        var loading = document.getElementById('service-types-loading');
        if (!list || !loading) return;

        fetch(umManagedId() ? umAdminUserUrl('/service-types') : window.APP_BASE+"/profile/service-types", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            list.style.display = 'grid';
            renderServiceTypes(list, data);
        })
        .catch(function() {
            loading.textContent = 'Error al cargar los tipos de servicio.';
        });
    }

    function renderServiceTypes(list, data) {
        list.innerHTML = '';

        var types = (data && data.service_types) || [];

        if (!types.length) {
            list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px;grid-column:1/-1;">No hay tipos de servicio disponibles.</p>';
            return;
        }

        types.forEach(function(type) {
            var isSelected = (data.selected || []).indexOf(type.id) >= 0;
            var label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid ' + (isSelected ? '#D24C19' : '#e2e8f0') + ';background:' + (isSelected ? '#fff7ed' : '#fff') + ';cursor:pointer;transition:all .15s;';
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = isSelected;
            cb.style.cssText = 'accent-color:#D24C19;width:15px;height:15px;cursor:pointer;margin-top:2px;';
            cb.addEventListener('change', function() {
                toggleServiceType(type.id, label, cb);
            });
            var info = document.createElement('span');
            info.style.cssText = 'display:flex;flex-direction:column;gap:2px;';
            var name = document.createElement('span');
            name.className = 'fd-st-name';
            name.style.cssText = 'font-size:13px;font-weight:700;color:' + (isSelected ? '#D24C19' : '#0f172a') + ';';
            name.textContent = type.name;
            var desc = document.createElement('span');
            desc.style.cssText = 'font-size:11px;color:#64748b;line-height:1.35;';
            desc.textContent = type.description || '';
            info.appendChild(name);
            info.appendChild(desc);
            label.appendChild(cb);
            label.appendChild(info);
            list.appendChild(label);
        });
    }

    function toggleServiceType(serviceTypeId, labelEl, cb) {
        fetch(umManagedId() ? umAdminUserUrl('/service-types/toggle') : window.APP_BASE+"/profile/service-types/toggle", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ service_type_id: serviceTypeId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isSelected = data.selected.indexOf(serviceTypeId) >= 0;
                labelEl.style.borderColor = isSelected ? '#D24C19' : '#e2e8f0';
                labelEl.style.background = isSelected ? '#fff7ed' : '#fff';
                labelEl.querySelector('.fd-st-name').style.color = isSelected ? '#D24C19' : '#0f172a';
                cb.checked = isSelected;
                showMsg('service-types-msg', data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg('service-types-msg', data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg('service-types-msg', 'Error de conexión.', false);
        });
    }

    // ---------- Primer ingreso: categorías obligatorias ----------

    var onboardingSelected = [];
    var onboardingAvailable = true;

    function checkCategoryOnboarding() {
        fetch(window.APP_BASE+"/profile/subgroups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            if (data.selected && data.selected.length > 0) return;
            openCategoryOnboarding();
        })
        .catch(function() {});
    }

    function openCategoryOnboarding() {
        var overlay = document.getElementById('onboard-cats-overlay');
        if (!overlay) return;

        onboardingSelected = [];
        updateOnboardingHint();
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        var list = document.getElementById('onboard-cats-list');
        var loading = document.getElementById('onboard-cats-loading');
        list.style.display = 'none';
        list.innerHTML = '';
        loading.style.display = 'block';
        loading.textContent = 'Cargando categorías...';

        fetch(window.APP_BASE+"/profile/subgroups", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            list.style.display = 'block';
            onboardingSelected = (data.selected || []).slice();
            onboardingAvailable = false;
            (data.groups || []).forEach(function(g) {
                if (g.subgroups && g.subgroups.length > 0) onboardingAvailable = true;
            });
            updateOnboardingHint();
            renderProviderSubgroups(list, data, {
                msgId: 'onboard-cats-msg',
                onSelectionChange: function(selected) {
                    onboardingSelected = (selected || []).slice();
                    updateOnboardingHint();
                }
            });
        })
        .catch(function() {
            loading.textContent = 'No pudimos cargar las categorías. Recargá la página para intentar de nuevo.';
            onboardingAvailable = false;
            updateOnboardingHint();
        });
    }

    function updateOnboardingHint() {
        var hint = document.getElementById('onboard-cats-hint');
        if (!hint) return;
        if (!onboardingAvailable) {
            hint.textContent = 'No hay categorías disponibles ahora. Cerrá esta ventana y volvé a intentar más tarde.';
            hint.style.color = '#64748b';
            return;
        }
        var count = onboardingSelected.length;
        if (count > 0) {
            hint.textContent = 'Listo: seleccionaste ' + count + (count === 1 ? ' categoría.' : ' categorías.') + ' Ya podés cerrar esta ventana.';
            hint.style.color = '#059669';
        } else {
            hint.textContent = 'Debés seleccionar al menos una categoría para cerrar esta ventana.';
            hint.style.color = '#dc2626';
        }
    }

    function closeOnboarding() {
        if (onboardingAvailable && onboardingSelected.length === 0) {
            showMsg('onboard-cats-msg', 'Elegí al menos una categoría para poder continuar.', false);
            return;
        }
        var overlay = document.getElementById('onboard-cats-overlay');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
        loadProviderSubgroups();
    }

    // ---------- Tab 4: Cuenta y perfil ----------

    var userAccountConfigLoaded = false;
    var userAccountConfigUserId = null;
    var userAccountConfigIsAuth = false;

    function fillUserAccountConfig(user) {
        if (!user) return;
        var isAuth = !authEmail || (user.email && String(user.email).toLowerCase() === String(authEmail).toLowerCase());
        userAccountConfigIsAuth = isAuth;
        userAccountConfigUserId = user.id || null;

        var pwCard = document.getElementById('user-cfg-password-card');
        if (pwCard) pwCard.style.display = '';
        var pwSub = document.getElementById('user-cfg-pw-sub');
        if (pwSub) pwSub.textContent = 'Establecé una nueva contraseña para este usuario (mínimo 4 caracteres).';

        ['user-cfg-pw-new', 'user-cfg-pw-confirm'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['user-cfg-pw-new-error', 'user-cfg-pw-confirm-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        ['user-cfg-pw-new', 'user-cfg-pw-confirm'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('err');
        });
        var pwMsg = document.getElementById('user-cfg-pw-msg');
        if (pwMsg) { pwMsg.style.display = 'none'; pwMsg.textContent = ''; }
    }

    function loadUserAccountConfig() {
        var pwCard = document.getElementById('user-cfg-password-card');
        if (!pwCard) return;

        var principal = getPrincipalAccountUser() || selectedUser;
        if (principal && principal.id) {
            userAccountConfigUserId = principal.id;
            fetch(window.APP_BASE+"/admin/users"+"/" + principal.id, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(fresh) {
                if (!fresh || fresh.error) {
                    fillUserAccountConfig(principal);
                    return;
                }
                var idx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
                if (idx !== -1) allUsers[idx] = fresh;
                if (selectedUser && String(selectedUser.id) === String(fresh.id)) selectedUser = fresh;
                userAccountConfigLoaded = true;
                fillUserAccountConfig(fresh);
            })
            .catch(function() {
                fillUserAccountConfig(principal);
            });
            return;
        }

        userAccountConfigUserId = null;
        fetch(window.APP_BASE+"/profile", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(user) {
            profileData = user;
            userAccountConfigLoaded = true;
            fillUserAccountConfig(user);
        })
        .catch(function() {
            fillUserAccountConfig({ id: null, email: authEmail });
        });
    }

    function submitUserAccountPassword() {
        var nwEl = document.getElementById('user-cfg-pw-new');
        var conEl = document.getElementById('user-cfg-pw-confirm');
        var errNew = document.getElementById('user-cfg-pw-new-error');
        var errCon = document.getElementById('user-cfg-pw-confirm-error');
        var nw = nwEl.value;
        var con = conEl.value;
        var principal = getPrincipalAccountUser() || selectedUser;
        var targetId = (principal && principal.id) || userAccountConfigUserId;
        var isAuthMode = userAccountConfigIsAuth || !targetId ||
            (authEmail && principal && principal.email && String(principal.email).toLowerCase() === String(authEmail).toLowerCase());

        [errNew, errCon].forEach(function(e) { e.style.display = 'none'; });
        [nwEl, conEl].forEach(function(e) { if (e) e.classList.remove('err'); });

        if (nw.length < 4) {
            nwEl.classList.add('err');
            errNew.textContent = 'Mínimo 4 caracteres.';
            errNew.style.display = 'block';
            return;
        }
        if (nw !== con) {
            conEl.classList.add('err');
            errCon.textContent = 'Las contraseñas no coinciden.';
            errCon.style.display = 'block';
            return;
        }

        var doneOk = function(message) {
            nwEl.value = '';
            conEl.value = '';
            showMsg('user-cfg-pw-msg', message || 'Contraseña actualizada correctamente.', true);
        };

        if (isAuthMode) {
            fetch(window.APP_BASE+"/profile/password", {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ password: nw, password_confirmation: con })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.errors) {
                    if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                    return;
                }
                if (data.success) doneOk(data.message);
            })
            .catch(function() {
                showMsg('user-cfg-pw-msg', 'Error de conexión.', false);
            });
            return;
        }

        if (!targetId) {
            showMsg('user-cfg-pw-msg', 'No se pudo identificar el usuario.', false);
            return;
        }

        var statusId = principal.user_status_id || (principal.status && principal.status.id) || null;
        var typeId = principal.type_user_id || (principal.type_user && principal.type_user.id) || null;
        fetch(window.APP_BASE+"/admin/users"+"/" + targetId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                name: principal.name,
                email: principal.email,
                password: nw,
                user_status_id: statusId,
                type_user_id: typeId
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                if (data.errors.name || data.errors.email) {
                    showMsg('user-cfg-pw-msg', (data.errors.name || data.errors.email)[0], false);
                }
                return;
            }
            if (data.success) {
                if (data.user) applyUserUpdate(data.user);
                doneOk('Contraseña del usuario actualizada correctamente.');
            }
        })
        .catch(function() {
            showMsg('user-cfg-pw-msg', 'Error de conexión.', false);
        });
    }

    function submitProfile() {
        var nameEl = document.getElementById('profile-name');
        var errName = document.getElementById('profile-name-error');
        var emailEl = document.getElementById('profile-email');
        var errEmail = document.getElementById('profile-email-error');
        var statusSel = document.getElementById('profile-status-select');
        var errStatus = document.getElementById('profile-status-error');
        var managedId = (typeof umManagedId === 'function') ? umManagedId() : null;
        var name = nameEl.value.trim();

        errName.style.display = 'none';
        nameEl.classList.remove('err');
        if (errEmail) { errEmail.style.display = 'none'; }
        if (emailEl) { emailEl.classList.remove('err'); }
        if (errStatus) { errStatus.style.display = 'none'; }

        if (!name) { nameEl.classList.add('err'); errName.textContent = 'El nombre es obligatorio.'; errName.style.display = 'block'; return; }

        var body = { name: name };
        var url = window.APP_BASE+"/profile";
        if (managedId) {
            // Gestionado: nombre + correo + estado van al usuario gestionado,
            // nunca a la sesion del admin.
            var emailVal = emailEl ? emailEl.value.trim() : '';
            if (!emailVal || emailVal.indexOf('@') < 0) {
                if (emailEl) emailEl.classList.add('err');
                if (errEmail) { errEmail.textContent = 'Ingresá un email válido.'; errEmail.style.display = 'block'; }
                return;
            }
            body.email = emailVal;
            if (statusSel && statusSel.value) body.user_status_id = parseInt(statusSel.value, 10);
            url = umAdminUserUrl('');
        }

        fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(r) {
            var data = r.data || {};
            if (data.errors) {
                if (data.errors.name) { nameEl.classList.add('err'); errName.textContent = data.errors.name[0]; errName.style.display = 'block'; }
                if (data.errors.email && errEmail) { if (emailEl) emailEl.classList.add('err'); errEmail.textContent = data.errors.email[0]; errEmail.style.display = 'block'; }
                if (data.errors.user_status_id && errStatus) { errStatus.textContent = data.errors.user_status_id[0]; errStatus.style.display = 'block'; }
                if (data.message && !data.errors.name && !data.errors.email && !data.errors.user_status_id) showMsg('profile-msg', data.message, false);
                return;
            }
            if (data.success) {
                showMsg('profile-msg', data.message, true);
                // Repintar con lo guardado (insignia de estado incluida).
                if (managedId && typeof loadProfile === 'function') loadProfile();
            }
        })
        .catch(function() {
            showMsg('profile-msg', 'Error de conexión.', false);
        });
    }

    /* ---------- Billetera de cobro (Fase 1) ---------- */

    function walletCsrf() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function walletJsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': walletCsrf()
        };
    }

    function paintMembershipCard() {
        var empty = document.getElementById('profile-membership-empty');
        var box = document.getElementById('profile-membership-box');
        var matrix = document.getElementById('profile-membership-matrix');
        if (!box) return;
        box.style.display = 'none';
        if (matrix) matrix.style.display = 'none';
        if (empty) empty.style.display = 'block';

        var mid = (typeof umManagedId === 'function') ? umManagedId() : null;
        var url = mid
            ? window.APP_BASE + '/admin/billing/status?user_id=' + encodeURIComponent(mid)
            : window.APP_BASE + '/profile/billing/status';

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var data = (res && res.data) || {};
            if (!data.membership || !data.membership.subscribed) return;
            if (empty) empty.style.display = 'none';
            var inv = data.membership.open_invoice;
            var html = 'Suscripción <strong>activa</strong>.';
            if (inv) {
                html += '<br>Factura #' + inv.id + ': <strong>$' + inv.amount + ' ' + inv.currency + '</strong> · vence ' + (inv.due_date || '—') + ' · estado: ' + inv.status + '.';
                var m = data.matrix || {};
                var pay = m.matrix_AR_CBU ? ('Transferí a CBU ' + m.matrix_AR_CBU + (m.matrix_AR_ALIAS ? ' / alias ' + m.matrix_AR_ALIAS : '') + ' y avisanos para imputarlo.') : '';
                if (matrix && pay) {
                    matrix.textContent = pay;
                    matrix.style.display = 'block';
                }
            } else {
                html += '<br>Sin facturas pendientes. Al día.';
            }
            box.innerHTML = html;
            box.style.display = 'block';
        })
        .catch(function () {});
    }

    function paintOperateBanner(canOperate) {
        var el = document.getElementById('wallet-operate-banner');
        if (!el) return;
        if (canOperate) {
            el.style.display = 'block';
            el.style.background = '#ecfdf5';
            el.style.border = '1px solid #a7f3d0';
            el.style.color = '#047857';
            el.textContent = 'Billetera activa: podés operar y recibir liquidaciones.';
        } else {
            el.style.display = 'block';
            el.style.background = '#fef2f2';
            el.style.border = '1px solid #fecaca';
            el.style.color = '#b91c1d';
            el.textContent = 'Agregá al menos una billetera para operar (recibir pedidos, trabajos o repartos).';
        }
    }

    function paintPayoutAccounts(list) {
        var box = document.getElementById('wallet-list');
        if (!box) return;
        box.innerHTML = '';

        if (!list || !list.length) {
            box.innerHTML = '<div style="font-size:12.5px;color:#64748b;">Todavía no cargaste billeteras.</div>';
            return;
        }

        list.forEach(function (a) {
            var card = document.createElement('div');
            card.style.cssText = 'border:1px solid #e2e8f0;border-radius:4px;padding:10px 12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#fff;';

            var info = document.createElement('div');
            info.style.flex = '1';
            info.style.minWidth = '180px';
            var title = (a.label || a.bank || a.currency || 'Billetera') + (a.is_default ? ' · Predeterminada' : '') + (a.is_active ? '' : ' · Inactiva');
            info.innerHTML = '<div style="font-size:13px;font-weight:700;color:#0f172a;"></div>'
                + '<div style="font-size:12px;color:#64748b;"></div>';
            info.children[0].textContent = title;
            info.children[1].textContent = (a.holder_name || '')
                + (a.cbu ? ' · CBU ' + a.cbu : '')
                + (a.alias_cbu ? ' · ' + a.alias_cbu : '')
                + (a.account_ref ? ' · ' + a.account_ref : '')
                + (a.iban ? ' · IBAN ' + a.iban : '');
            card.appendChild(info);

            if (!a.is_default && a.is_active) {
                var def = document.createElement('button');
                def.type = 'button';
                def.textContent = 'Predeterminada';
                def.style.cssText = 'background:#fff;color:#D24C19;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;';
                def.onclick = function () { setDefaultPayoutAccount(a.id); };
                card.appendChild(def);
            }

            if (a.is_active) {
                var off = document.createElement('button');
                off.type = 'button';
                off.textContent = 'Desactivar';
                off.style.cssText = 'background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;';
                off.onclick = function () { togglePayoutAccount(a.id, 'deactivate'); };
                card.appendChild(off);
            } else {
                var on = document.createElement('button');
                on.type = 'button';
                on.textContent = 'Activar';
                on.style.cssText = 'background:#D24C19;color:#fff;border:1px solid #D24C19;border-radius:4px;padding:6px 10px;font-size:12px;font-weight:700;cursor:pointer;';
                on.onclick = function () { togglePayoutAccount(a.id, 'reactivate'); };
                card.appendChild(on);
            }

            box.appendChild(card);
        });
    }

    function loadPayoutAccounts() {
        loadWalletTypes(function () {
            fetch(window.APP_BASE+"/profile/payout-accounts", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': walletCsrf() }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                paintOperateBanner(!!(data && data.can_operate));
                paintPayoutAccounts(data && data.data);
            })
            .catch(function () {
                var box = document.getElementById('wallet-list');
                if (box) box.innerHTML = '<div style="font-size:12.5px;color:#b91c1d;">No pudimos cargar tus billeteras.</div>';
            });
        });
    }

    var walletTypesCache = null;

    function loadWalletTypes(done) {
        var sel = document.getElementById('wallet-type');
        if (!sel) { if (done) done(); return; }
        var apply = function (list) {
            walletTypesCache = Array.isArray(list) ? list : [];
            paintWalletTypes();
            if (done) done();
        };
        if (walletTypesCache) { apply(walletTypesCache); return; }
        fetch(window.APP_BASE+"/wallet-types", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': walletCsrf() }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { apply(data && data.data); })
        .catch(function () { apply([]); });
    }

    function paintWalletTypes() {
        var sel = document.getElementById('wallet-type');
        var countryEl = document.getElementById('wallet-country');
        if (!sel || !countryEl) return;
        var country = countryEl.value || 'AR';
        var prev = sel.value;
        sel.innerHTML = '';
        (walletTypesCache || []).forEach(function (t) {
            if (String(t.country || '').toUpperCase() !== country) return;
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            sel.appendChild(opt);
        });
        if (!sel.options.length) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Sin tipos para este país';
            sel.appendChild(opt);
        } else if (prev) {
            sel.value = prev;
        }
        paintWalletFields();
    }

    function paintWalletFields() {
        var sel = document.getElementById('wallet-type');
        if (!sel) return;
        var type = null;
        (walletTypesCache || []).forEach(function (t) {
            if (String(t.id) === String(sel.value)) type = t;
        });
        var fields = (type && type.fields) || ['holder', 'cbu', 'alias', 'iban', 'bank'];
        var show = function (name, visible) {
            document.querySelectorAll('[data-wfield="' + name + '"]').forEach(function (el) {
                el.style.display = visible ? '' : 'none';
            });
        };
        show('cbu', fields.indexOf('cbu') !== -1);
        show('alias', fields.indexOf('alias') !== -1);
        show('iban', fields.indexOf('iban') !== -1);
        var needsRef = fields.indexOf('email') !== -1 || fields.indexOf('phone') !== -1;
        show('ref', needsRef);
        var refLabel = document.getElementById('wallet-ref-label');
        if (refLabel) {
            refLabel.textContent = fields.indexOf('email') !== -1 ? 'Email de la cuenta' : 'Celular asociado';
        }
    }

    document.addEventListener('change', function (event) {
        var t = event.target;
        if (!t || !t.id) return;
        if (t.id === 'wallet-country') paintWalletTypes();
        if (t.id === 'wallet-type') paintWalletFields();
    });

    function walletFieldError(id, text) {
        var el = document.getElementById(id);
        var inp = document.getElementById(id.replace('-error', ''));
        if (text) {
            el.textContent = text;
            el.style.display = 'block';
            if (inp) inp.classList.add('err');
        } else {
            el.textContent = '';
            el.style.display = 'none';
            if (inp) inp.classList.remove('err');
        }
    }

    function submitPayoutAccount() {
        ['wallet-label-error', 'wallet-holder-error', 'wallet-cbu-error', 'wallet-alias-error', 'wallet-ref-error', 'wallet-iban-error', 'wallet-bank-error'].forEach(function (id) { walletFieldError(id, ''); });

        var typeSel = document.getElementById('wallet-type');
        var body = {
            wallet_type_id: typeSel && typeSel.value ? parseInt(typeSel.value, 10) : null,
            country: (document.getElementById('wallet-country') || {}).value || 'AR',
            label: document.getElementById('wallet-label').value.trim(),
            holder_name: document.getElementById('wallet-holder').value.trim(),
            cbu: document.getElementById('wallet-cbu').value.trim(),
            alias_cbu: document.getElementById('wallet-alias').value.trim(),
            account_ref: document.getElementById('wallet-ref').value.trim(),
            iban: document.getElementById('wallet-iban').value.trim(),
            bank: document.getElementById('wallet-bank').value.trim()
        };

        // El requisito depende del tipo elegido, igual que el backend
        // (validateForType): Mercado Pago pide alias, PayPal email, Bizum
        // celular, transferencia ES pide IBAN, y solo el generico con CBU e
        // IBAN acepta uno u otro. Asi el error siempre cae en un campo
        // visible para ese tipo.
        var selType = null;
        (walletTypesCache || []).forEach(function (t) { if (String(t.id) === String(typeSel.value)) selType = t; });
        var typeCode = selType ? String(selType.code || '').toLowerCase() : '';
        var typeCountry = selType ? String(selType.country || '').toUpperCase() : (((document.getElementById('wallet-country') || {}).value) || 'AR').toUpperCase();
        var typeFields = (selType && selType.fields) || ['holder', 'cbu', 'alias', 'iban', 'bank'];

        if (!body.holder_name) { walletFieldError('wallet-holder-error', 'Ingresá el titular.'); return; }
        if (typeCode === 'mercadopago') {
            if (!body.alias_cbu) { walletFieldError('wallet-alias-error', 'Ingresá tu alias de Mercado Pago o el CVU.'); return; }
        } else if (typeCode === 'paypal') {
            if (!body.account_ref) { walletFieldError('wallet-ref-error', 'Ingresá el email de tu cuenta PayPal.'); return; }
        } else if (typeCode === 'bizum') {
            if (!body.account_ref) { walletFieldError('wallet-ref-error', 'Ingresá el celular asociado a Bizum.'); return; }
        } else if (typeCode === 'transferencia' && typeCountry === 'ES') {
            if (!body.iban) { walletFieldError('wallet-iban-error', 'Ingresá el IBAN.'); return; }
        } else if (typeFields.indexOf('cbu') !== -1 && typeFields.indexOf('iban') === -1) {
            if (!body.cbu) { walletFieldError('wallet-cbu-error', 'Ingresá el CBU (22 dígitos).'); return; }
        } else if (typeFields.indexOf('iban') !== -1 && typeFields.indexOf('cbu') === -1) {
            if (!body.iban) { walletFieldError('wallet-iban-error', 'Ingresá el IBAN.'); return; }
        } else if (!body.cbu && !body.iban) {
            walletFieldError('wallet-cbu-error', 'Ingresá un CBU o un IBAN.'); return;
        }

        fetch(window.APP_BASE+"/profile/payout-accounts", {
            method: 'POST',
            headers: walletJsonHeaders(),
            body: JSON.stringify(body)
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (r) {
            if (r.ok && r.data.success) {
                ['wallet-label', 'wallet-holder', 'wallet-cbu', 'wallet-alias', 'wallet-iban', 'wallet-bank'].forEach(function (id) { document.getElementById(id).value = ''; });
                showMsg('wallet-msg', r.data.message, true);
                loadPayoutAccounts();
            } else {
                var errs = (r.data && r.data.errors) || {};
                var map = { label: 'wallet-label-error', holder_name: 'wallet-holder-error', cbu: 'wallet-cbu-error', alias_cbu: 'wallet-alias-error', account_ref: 'wallet-ref-error', iban: 'wallet-iban-error', bank: 'wallet-bank-error', wallet_type_id: 'wallet-type-error' };
                Object.keys(errs).forEach(function (k) {
                    if (map[k]) walletFieldError(map[k], errs[k][0]);
                });
                if (r.data && r.data.message && !Object.keys(errs).length) showMsg('wallet-msg', r.data.message, false);
            }
        })
        .catch(function () { showMsg('wallet-msg', 'Error de conexión.', false); });
    }

    function setDefaultPayoutAccount(id) {
        fetch(window.APP_BASE+"/profile/payout-accounts/" + id + "/default", {
            method: 'POST',
            headers: walletJsonHeaders()
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            showMsg('wallet-msg', data.message, !!data.success);
            loadPayoutAccounts();
        })
        .catch(function () { showMsg('wallet-msg', 'Error de conexión.', false); });
    }

    function togglePayoutAccount(id, action) {
        fetch(window.APP_BASE+"/profile/payout-accounts/" + id + "/" + action, {
            method: 'POST',
            headers: walletJsonHeaders()
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (r) {
            showMsg('wallet-msg', r.data.message, !!r.ok && !!r.data.success);
            loadPayoutAccounts();
        })
        .catch(function () { showMsg('wallet-msg', 'Error de conexión.', false); });
    }

    /* ---------- Backoffice Dinero (Fase 1) ---------- */

    function dnMsg(text, ok) {
        showMsg('dn-msg', text, !!ok);
    }

    function dnToggleSidebar(force) {
        var sb = document.getElementById('dn-sidebar');
        var ov = document.getElementById('dn-sidebar-overlay');
        if (!sb) return;
        var open = typeof force === 'boolean' ? force : !sb.classList.contains('open');
        sb.classList.toggle('open', open);
        if (ov) ov.style.display = open ? 'block' : 'none';
    }

    function dnSwitchTab(name) {
        document.querySelectorAll('#dn-tabs .dn-tab').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-dntab') === name);
        });
        document.querySelectorAll('[data-dnpanel]').forEach(function (p) {
            p.style.display = p.getAttribute('data-dnpanel') === name ? '' : 'none';
        });
    }

    async function dnFetch(url, options) {
        var res = await fetch(window.APP_BASE + url, Object.assign({
            headers: Object.assign({ 'Accept': 'application/json' }, (options && options.headers) || {})
        }, options || {}));
        var data = await res.json().catch(function () { return {}; });
        return { ok: res.ok, data: data };
    }

    function dnCsrf() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        };
    }

    async function dnLoadRules() {
        var r = await dnFetch('/admin/billing/fee-rules');
        var body = document.getElementById('dn-rules-body');
        if (!body) return;
        if (!r.ok || !r.data.success) {
            body.innerHTML = '<tr><td colspan="7" style="padding:8px;border:1px solid #e2e8f0;color:#b91c1d;">No se pudieron cargar.</td></tr>';
            return;
        }
        if (!r.data.data.length) {
            body.innerHTML = '<tr><td colspan="7" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin reglas. Creá la primera abajo.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (rule) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td style="padding:8px;border:1px solid #e2e8f0;"></td>'.repeat(0);
            var cells = [rule.module, rule.country || '—', rule.currency || '—', rule.percent + '%', rule.min_fee, rule.active ? 'Sí' : 'No'];
            cells.forEach(function (t) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = t;
                tr.appendChild(td);
            });
            var act = document.createElement('td');
            act.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = rule.active ? 'Desactivar' : 'Activar';
            btn.style.cssText = 'background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer;';
            btn.onclick = function () { dnToggleRule(rule.id, !rule.active); };
            act.appendChild(btn);
            tr.appendChild(act);
            body.appendChild(tr);
        });
    }

    async function dnSaveRule() {
        var body = {
            module: document.getElementById('dn-rule-module').value,
            country: document.getElementById('dn-rule-country').value.trim() || null,
            currency: document.getElementById('dn-rule-currency').value.trim() || null,
            percent: parseFloat(document.getElementById('dn-rule-percent').value) || 0,
            min_fee: parseFloat(document.getElementById('dn-rule-min').value) || 0,
            active: document.getElementById('dn-rule-active').value === '1'
        };
        var r = await dnFetch('/admin/billing/fee-rules', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Guardado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadRules();
    }

    async function dnToggleRule(id, active) {
        var r = await dnFetch('/admin/billing/fee-rules/' + id, { method: 'PUT', headers: dnCsrf(), body: JSON.stringify({ active: active }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Actualizado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadRules();
    }

    async function dnLoadMatrix() {
        var r = await dnFetch('/admin/billing/company');
        if (!r.ok || !r.data.success) return;
        var d = r.data.data || {};
        var map = {
            'matrix_AR_CBU': 'dn-mx-ar-cbu', 'matrix_AR_ALIAS': 'dn-mx-ar-alias',
            'matrix_AR_HOLDER': 'dn-mx-ar-holder', 'matrix_AR_BANK': 'dn-mx-ar-bank',
            'matrix_ES_IBAN': 'dn-mx-es-iban', 'matrix_ES_HOLDER': 'dn-mx-es-holder',
            'matrix_ES_BANK': 'dn-mx-es-bank'
        };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.value = d[k] || '';
        });
    }

    async function dnSaveMatrix() {
        var get = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
        var settings = {
            'matrix_AR_CBU': get('dn-mx-ar-cbu'),
            'matrix_AR_ALIAS': get('dn-mx-ar-alias'),
            'matrix_AR_HOLDER': get('dn-mx-ar-holder'),
            'matrix_AR_BANK': get('dn-mx-ar-bank'),
            'matrix_ES_IBAN': get('dn-mx-es-iban'),
            'matrix_ES_HOLDER': get('dn-mx-es-holder'),
            'matrix_ES_BANK': get('dn-mx-es-bank')
        };
        var r = await dnFetch('/admin/billing/company', { method: 'PUT', headers: dnCsrf(), body: JSON.stringify({ settings: settings }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Guardado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
    }

    async function dnLoadSettlements() {
        var r = await dnFetch('/admin/billing/settlements?status=pending');
        var body = document.getElementById('dn-settlements-body');
        if (!body) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            body.innerHTML = '<tr><td colspan="7" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin liquidaciones pendientes.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (s) {
            var tr = document.createElement('tr');
            [['id', s.id], ['user', (s.user && (s.user.name || s.user.email)) || ('#' + s.user_id)], ['gross', s.gross], ['fee', s.fee], ['net', s.net], ['status', s.status]].forEach(function (pair) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = pair[1];
                tr.appendChild(td);
            });
            var act = document.createElement('td');
            act.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Pagar';
            btn.style.cssText = 'background:#D24C19;color:#fff;border:none;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:700;cursor:pointer;';
            btn.onclick = function () {
                var ref = window.prompt('Comprobante de la transferencia al comercio/prestador:');
                if (!ref) return;
                dnPaySettlement(s.id, ref);
            };
            act.appendChild(btn);
            tr.appendChild(act);
            body.appendChild(tr);
        });
    }

    async function dnLoadCharges() {
        var r = await dnFetch('/admin/billing/charges?status=pending_verification');
        var body = document.getElementById('dn-charges-body');
        if (!body) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            body.innerHTML = '<tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin cobros pendientes.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (c) {
            var tr = document.createElement('tr');
            [[c.id, c.id], ['o', c.order_type + ' #' + c.order_id], ['a', c.amount], ['f', c.fee], ['m', c.method]].forEach(function (pair) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = pair[1];
                tr.appendChild(td);
            });
            var act = document.createElement('td');
            act.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
            if (c.method === 'transferencia') {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = 'Verificar';
                btn.style.cssText = 'background:#D24C19;color:#fff;border:none;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:700;cursor:pointer;';
                btn.onclick = function () { dnVerifyCharge(c.id); };
                act.appendChild(btn);
            } else {
                act.textContent = 'Auto (efectivo)';
            }
            tr.appendChild(act);
            body.appendChild(tr);
        });
    }

    async function dnRegisterCharge() {
        var body = {
            module: document.getElementById('dn-ch-module').value,
            order_type: document.getElementById('dn-ch-ordertype').value,
            order_id: parseInt(document.getElementById('dn-ch-order').value, 10),
            amount: parseFloat(document.getElementById('dn-ch-amount').value),
            method: document.getElementById('dn-ch-method').value,
            reference: document.getElementById('dn-ch-ref').value.trim() || null,
            currency: 'ARS'
        };
        var r = await dnFetch('/admin/billing/charges', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Registrado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) { document.getElementById('dn-ch-order').value = ''; document.getElementById('dn-ch-amount').value = ''; document.getElementById('dn-ch-ref').value = ''; dnLoadCharges(); }
    }

    async function dnVerifyCharge(id) {
        var r = await dnFetch('/admin/billing/charges/' + id + '/verify', { method: 'POST', headers: dnCsrf(), body: JSON.stringify({}) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Verificado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadCharges();
    }

    async function dnCreateSettlement() {        var ids = document.getElementById('dn-settle-charges').value.split(',').map(function (x) { return parseInt(x.trim(), 10); }).filter(function (x) { return !isNaN(x); });
        var body = {
            user_id: parseInt(document.getElementById('dn-settle-user').value, 10),
            currency: (document.getElementById('dn-settle-currency').value || 'ARS').toUpperCase(),
            charge_ids: ids,
            notes: document.getElementById('dn-settle-notes').value.trim() || null
        };
        var r = await dnFetch('/admin/billing/settlements', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Creada.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) { document.getElementById('dn-settle-charges').value = ''; dnLoadSettlements(); }
    }

    async function dnPaySettlement(id, reference) {
        var r = await dnFetch('/admin/billing/settlements/' + id + '/pay', { method: 'POST', headers: dnCsrf(), body: JSON.stringify({ reference: reference }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Pagada.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadSettlements();
    }

    async function dnCheckDebt() {
        var box = document.getElementById('dn-debt-result');
        var uid = document.getElementById('dn-cash-user').value.trim();
        var cur = (document.getElementById('dn-cash-currency').value || 'ARS').toUpperCase();
        if (!uid) { box.textContent = 'Ingresá el ID del usuario.'; return; }
        var r = await dnFetch('/admin/billing/status?user_id=' + encodeURIComponent(uid) + '&currency=' + encodeURIComponent(cur));
        if (!r.ok || !r.data.success) { box.textContent = 'No se pudo consultar.'; return; }
        var d = r.data.data;
        box.textContent = 'Puede operar: ' + (d.can_operate ? 'Sí' : 'No') + ' · Deuda efectivo: ' + d.cash_debt + ' ' + d.currency;
    }

    async function dnLoadLedger() {
        var type = document.getElementById('dn-led-type').value;
        var cur = (document.getElementById('dn-led-currency').value || 'ARS').toUpperCase();
        var q = '?currency=' + encodeURIComponent(cur) + (type ? '&type=' + encodeURIComponent(type) : '');
        var r = await dnFetch('/admin/billing/ledger' + q);
        var body = document.getElementById('dn-ledger-body');
        if (!body) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            body.innerHTML = '<tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin movimientos.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (m) {
            var tr = document.createElement('tr');
            [[m.id, m.id], ['t', m.type], ['u', (m.user && (m.user.name || m.user.email)) || 'PideAca'], ['a', m.amount + ' ' + m.currency], ['r', (m.reference_type || '-') + ' ' + (m.reference_id || '')], ['d', m.description || '']].forEach(function (pair) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = pair[1];
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });
    }

    async function dnLoadPlans() {
        var r = await dnFetch('/admin/billing/plans');
        var box = document.getElementById('dn-plans-list');
        if (!box) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            box.textContent = 'Sin planes. Creá el primero abajo.';
            return;
        }
        box.innerHTML = '';
        r.data.data.forEach(function (p) {
            var row = document.createElement('div');
            row.style.cssText = 'border:1px solid #e2e8f0;border-radius:4px;padding:8px 12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#fff;';
            var t = document.createElement('span');
            t.style.cssText = 'font-weight:700;color:#0f172a;';
            t.textContent = '#' + p.id + ' ' + p.name + ' · $' + p.price + ' ' + p.currency + ' · ' + p.billing_days + 'd + ' + p.grace_days + 'd gracia';
            row.appendChild(t);
            var st = document.createElement('span');
            st.style.cssText = 'font-size:12px;color:' + (p.active ? '#047857' : '#b91c1d') + ';';
            st.textContent = p.active ? 'Activo' : 'Inactivo';
            row.appendChild(st);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = p.active ? 'Desactivar' : 'Activar';
            btn.style.cssText = 'background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer;';
            btn.onclick = function () { dnTogglePlan(p.id, !p.active); };
            row.appendChild(btn);
            box.appendChild(row);
        });
    }

    async function dnSavePlan() {
        var body = {
            name: document.getElementById('dn-plan-name').value.trim(),
            currency: (document.getElementById('dn-plan-currency').value || 'ARS').toUpperCase(),
            price: parseFloat(document.getElementById('dn-plan-price').value) || 0,
            billing_days: parseInt(document.getElementById('dn-plan-days').value, 10) || 30,
            grace_days: parseInt(document.getElementById('dn-plan-grace').value, 10) || 15,
            active: document.getElementById('dn-plan-active').value === '1'
        };
        if (!body.name) { dnMsg('El plan necesita un nombre.', false); return; }
        var r = await dnFetch('/admin/billing/plans', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Guardado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) { document.getElementById('dn-plan-name').value = ''; document.getElementById('dn-plan-price').value = ''; dnLoadPlans(); }
    }

    async function dnTogglePlan(id, active) {
        var r = await dnFetch('/admin/billing/plans/' + id, { method: 'PUT', headers: dnCsrf(), body: JSON.stringify({ active: active }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Actualizado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadPlans();
    }

    async function dnSubscribe() {
        var body = {
            user_id: parseInt(document.getElementById('dn-sub-user').value, 10),
            plan_id: parseInt(document.getElementById('dn-sub-plan').value, 10)
        };
        var r = await dnFetch('/admin/billing/subscribe', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Suscripto.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadInvoices();
    }

    async function dnLoadInvoices() {
        var st = document.getElementById('dn-inv-status').value;
        var r = await dnFetch('/admin/billing/invoices' + (st ? ('?status=' + encodeURIComponent(st)) : ''));
        var body = document.getElementById('dn-invoices-body');
        if (!body) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            body.innerHTML = '<tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin facturas.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (inv) {
            var tr = document.createElement('tr');
            [[inv.id, inv.id], ['u', (inv.user && (inv.user.name || inv.user.email)) || ('#' + inv.user_id)], ['a', inv.amount + ' ' + inv.currency], ['d', inv.due_date || '—'], ['s', inv.status]].forEach(function (pair) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = pair[1];
                tr.appendChild(td);
            });
            var act = document.createElement('td');
            act.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
            if (inv.status === 'pending' || inv.status === 'overdue') {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = 'Marcar pagada';
                btn.style.cssText = 'background:#D24C19;color:#fff;border:none;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:700;cursor:pointer;';
                btn.onclick = function () {
                    var ref = window.prompt('Comprobante del pago (opcional):') || '';
                    dnPayInvoice(inv.id, ref);
                };
                act.appendChild(btn);
            } else {
                act.textContent = '—';
            }
            tr.appendChild(act);
            body.appendChild(tr);
        });
    }

    async function dnPayInvoice(id, reference) {
        var r = await dnFetch('/admin/billing/invoices/' + id + '/pay', { method: 'POST', headers: dnCsrf(), body: JSON.stringify({ reference: reference || null }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Pagada.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) dnLoadInvoices();
    }

    function dnInitDinero() {
        dnSwitchTab('comisiones');
        dnLoadRules();
        dnLoadMatrix();
        dnLoadCharges();
        dnLoadSettlements();
        dnLoadPlans();
        dnLoadInvoices();
    }

    document.addEventListener('click', function (event) {
        var btn = event.target && event.target.closest ? event.target.closest('[data-dntab]') : null;
        if (!btn || !document.getElementById('dash-dinero')) return;
        dnSwitchTab(btn.getAttribute('data-dntab'));
        dnToggleSidebar(false);
    });

    /* ---------- Catálogo de billeteras por país ---------- */

    async function wtLoad() {
        var r = await dnFetch('/admin/billing/wallet-types');
        var body = document.getElementById('wt-body');
        if (!body) return;
        if (!r.ok || !r.data.success || !r.data.data.length) {
            body.innerHTML = '<tr><td colspan="6" style="padding:8px;border:1px solid #e2e8f0;color:#64748b;">Sin tipos cargados.</td></tr>';
            return;
        }
        body.innerHTML = '';
        r.data.data.forEach(function (t) {
            var tr = document.createElement('tr');
            [t.country, t.name, t.code, (t.fields || []).join(', '), t.active ? 'Sí' : 'No'].forEach(function (v) {
                var td = document.createElement('td');
                td.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
                td.textContent = v;
                tr.appendChild(td);
            });
            var act = document.createElement('td');
            act.style.cssText = 'padding:8px;border:1px solid #e2e8f0;';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = t.active ? 'Desactivar' : 'Activar';
            btn.style.cssText = 'background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer;';
            btn.onclick = function () { wtToggle(t.id, !t.active); };
            act.appendChild(btn);
            tr.appendChild(act);
            body.appendChild(tr);
        });
    }

    async function wtSave() {
        var fields = document.getElementById('wt-fields').value.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
        var body = {
            country: document.getElementById('wt-country').value,
            code: document.getElementById('wt-code').value.trim().toLowerCase(),
            name: document.getElementById('wt-name').value.trim(),
            fields: fields,
            active: true
        };
        if (!body.code || !body.name) { dnMsg('Completá código y nombre.', false); return; }
        var r = await dnFetch('/admin/billing/wallet-types', { method: 'POST', headers: dnCsrf(), body: JSON.stringify(body) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Guardado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) {
            document.getElementById('wt-code').value = '';
            document.getElementById('wt-name').value = '';
            document.getElementById('wt-fields').value = '';
            wtLoad();
        }
    }

    async function wtToggle(id, active) {
        var r = await dnFetch('/admin/billing/wallet-types/' + id, { method: 'PUT', headers: dnCsrf(), body: JSON.stringify({ active: active }) });
        dnMsg(r.data && r.data.message ? r.data.message : (r.ok ? 'Actualizado.' : 'Error.'), !!(r.ok && r.data && r.data.success));
        if (r.ok) wtLoad();
    }

    /* ── Vehículo del repartidor: ficha en la Terminal Central + Perfil ── */
    // La Terminal Central muestra el vehículo del perfil como ficha de solo
    // lectura (#drv-vehicle-badge); quien edita es el panel "Mi Vehículo"
    // (#profile-vehicle), y al guardar se refresca también esa ficha.
    var vehicleSaving = false;

    function setVehicleFeedback(sourceEl, text, tone) {
        var el = document.getElementById('profile-vehicle-msg');
        if (!el) return;
        clearTimeout(el._vehicleTimer);
        if (!text) { el.style.display = 'none'; el.textContent = ''; return; }
        el.textContent = text;
        el.className = 'save-msg';
        el.style.color = tone === 'error' ? '#dc2626' : (tone === 'success' ? '#059669' : '#64748b');
        el.style.display = 'inline';
        if (tone !== 'loading') {
            el._vehicleTimer = setTimeout(function () { el.style.display = 'none'; }, 3500);
        }
    }

    function vehicleSelects() {
        return ['profile-vehicle']
            .map(function (id) { return document.getElementById(id); })
            .filter(Boolean);
    }

    function setVehicleSaving(on) {
        vehicleSaving = on;
        vehicleSelects().forEach(function (sel) { sel.disabled = on; });
        var btn = document.querySelector('button[onclick="submitVehicle()"]');
        if (btn) {
            btn.disabled = on;
            btn.style.opacity = on ? '0.6' : '1';
            btn.style.cursor = on ? 'wait' : 'pointer';
        }
    }

    /* Refresca la ficha de la Terminal Central (#drv-vehicle-badge). Si el
       repartidor todavía no tenía unidad, la ficha se crea en el momento. */
    function paintDrvVehicleBadge(type, plate) {
        var bar = document.querySelector('.drv-toolbar');
        var tool = document.getElementById('drv-vehicle-tool');
        if (!bar || !tool) return;

        var label = (type && VEHICLE_TYPES[type]) || VEHICLE_TYPES[VEHICLE_DEFAULT_TYPE] || type || '';
        var plateHtml = plate
            ? ' • <span class="drv-vehicle-plate">' + String(plate).replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>'
            : '';
        var editBtn = '<button type="button" class="drv-vehicle-edit" onclick="drvGoVehicleProfile()" title="Cambiar vehículo" aria-label="Cambiar vehículo">'
            + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path></svg>'
            + 'Cambiar</button>';

        tool.innerHTML = '<span class="drv-tool-label">Vehículo:</span>'
            + '<span class="drv-vehicle-badge" id="drv-vehicle-badge" title="Unidad con la que trabajás (configurada en Mi Vehículo)">'
            + '🚘 ' + label + plateHtml + '</span>'
            + editBtn;
        bar.dataset.vehicleConfigured = '1';
    }

    function applyVehicle(type, plate) {
        var profileSel = document.getElementById('profile-vehicle');
        if (profileSel && type && VEHICLE_TYPES[type]) {
            profileSel.value = type;
            profileSel.dataset.savedValue = type;
        }
        if (plate !== undefined) {
            var plateEl = document.getElementById('profile-vehicle-plate');
            if (plateEl) {
                plateEl.value = plate || '';
                plateEl.dataset.savedValue = (plate || '').trim();
            }
            paintDrvVehicleBadge(type, plate);
        }
    }

    window.pideacaSaveVehicle = function (sourceEl) {
        if (!sourceEl || vehicleSaving) return;

        var fromProfile = sourceEl.id === 'profile-vehicle';
        var plateEl = document.getElementById('profile-vehicle-plate');
        var payload = { vehicle_type: sourceEl.value };
        if (fromProfile && plateEl) payload.vehicle_plate = plateEl.value.trim();

        var previous = sourceEl.dataset.savedValue || sourceEl.value;

        // Nada cambió respecto de lo guardado: no malgastamos el request.
        if (previous === payload.vehicle_type
            && (!fromProfile || (plateEl && plateEl.value.trim() === (plateEl.dataset.savedValue || '')))) {
            return;
        }

        setVehicleFeedback(sourceEl, 'Guardando…', 'loading');
        setVehicleSaving(true);

        fetch(window.APP_BASE+"/api/driver/vehicle", {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        })
        .then(function (res) {
            var data = res.data || {};
            if (!res.ok || data.status !== 'success') {
                var firstError = data.errors
                    ? ((data.errors.vehicle_type && data.errors.vehicle_type[0]) || (data.errors.vehicle_plate && data.errors.vehicle_plate[0]))
                    : '';
                var msg = data.message || firstError || 'No se pudo actualizar el vehículo.';
                sourceEl.value = previous;
                setVehicleFeedback(sourceEl, msg, 'error');
                fdToast(msg, true);
                return;
            }
            applyVehicle(data.vehicle_type, data.vehicle_plate);
            setVehicleFeedback(sourceEl, data.message || 'Vehículo actualizado', 'success');
            fdToast('Vehículo actualizado', false);
        })
        .catch(function () {
            sourceEl.value = previous;
            setVehicleFeedback(sourceEl, 'Error de conexión. Intentá de nuevo.', 'error');
            fdToast('No se pudo guardar el vehículo.', true);
        })
        .finally(function () {
            setVehicleSaving(false);
        });
    };

    function submitVehicle() {
        var sel = document.getElementById('profile-vehicle');
        if (sel) pideacaSaveVehicle(sel);
    }

    (function initVehiclePersistence() {
        var profileSel = document.getElementById('profile-vehicle');
        var plateEl = document.getElementById('profile-vehicle-plate');

        // En el perfil el tipo de vehículo se guarda apenas cambia.
        if (profileSel) {
            profileSel.dataset.savedValue = profileSel.value || VEHICLE_DEFAULT_TYPE;
            profileSel.addEventListener('change', submitVehicle);
        }

        // La patente se confirma al salir del campo, y solo si se tocó.
        if (plateEl) {
            plateEl.dataset.savedValue = (plateEl.value || '').trim();
            plateEl.addEventListener('input', function () {
                plateEl.dataset.dirty = '1';
            });
            plateEl.addEventListener('blur', function () {
                if (!plateEl.dataset.dirty) return;
                delete plateEl.dataset.dirty;
                submitVehicle();
            });
        }

        // La Terminal Central solo muestra la ficha del vehículo: quien
        // edita es el panel de perfil, y al guardar se refresca la ficha.
    })();

    var PW_EYE_ON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>';
    var PW_EYE_OFF = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.774 3.162 10.066 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.63.6M17.25 12a7.5 7.5 0 0 1-12.75 3.75"/></svg>';

    function togglePwVis(id, btn) {
        var el = document.getElementById(id);
        if (!el) return;
        var show = el.type === 'password';
        el.type = show ? 'text' : 'password';
        btn.innerHTML = show ? PW_EYE_OFF : PW_EYE_ON;
    }

    function fdShopStartMercadoPago(orderId) {
        var box = document.getElementById('fd-shop-cart');
        if (box) {
            box.innerHTML = '<div style="text-align:center;padding:6px 0;"><div style="font-size:14px;font-weight:700;color:#0c2a4d;margin-bottom:6px;">Pedido registrado</div><div style="font-size:12px;color:#64748b;">Te llevamos a Mercado Pago para completar el pago…</div></div>';
        }
        fetch(window.APP_BASE + '/checkout/mercadopago/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ order_type: 'order', order_id: orderId })
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (r) {
            if (r.ok && r.data && r.data.init_point) {
                window.location.href = r.data.init_point;
                return;
            }
            fdShopShowMsg((r.data && r.data.message) || 'No pudimos iniciar el pago con Mercado Pago.', true);
            fdRenderShopCart();
        })
        .catch(function () {
            fdShopShowMsg('No pudimos conectar con Mercado Pago.', true);
            fdRenderShopCart();
        });
    }

    function submitPassword() {
        var nwEl = document.getElementById('pw-new');
        var conEl = document.getElementById('pw-confirm');
        var errNew = document.getElementById('pw-new-error');
        var errCon = document.getElementById('pw-confirm-error');
        var nw = nwEl.value;
        var con = conEl.value;

        [errNew, errCon].forEach(function(e) { e.style.display = 'none'; });
        [nwEl, conEl].forEach(function(e) { e.classList.remove('err'); });

        if (nw.length < 4) { nwEl.classList.add('err'); errNew.textContent = 'Mínimo 4 caracteres.'; errNew.style.display = 'block'; return; }
        if (nw !== con) { conEl.classList.add('err'); errCon.textContent = 'Las contraseñas no coinciden.'; errCon.style.display = 'block'; return; }

        var managedId = (typeof umManagedId === 'function') ? umManagedId() : null;
        var url = window.APP_BASE+"/profile/password";
        var payload = { password: nw, password_confirmation: con };
        if (managedId) {
            // Gestionado: la clave nueva es para el usuario gestionado, no
            // para la sesion del admin (el endpoint del gestionado exige nombre).
            url = umAdminUserUrl('');
            var managedName = document.getElementById('profile-name');
            payload.name = (managedName && managedName.value.trim()) || '';
        }

        fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                if (data.errors.password_confirmation) { conEl.classList.add('err'); errCon.textContent = data.errors.password_confirmation[0]; errCon.style.display = 'block'; }
                return;
            }
            if (data.success) {
                nwEl.value = ''; conEl.value = '';
                showMsg('pw-msg', data.message, true);
            }
        })
        .catch(function() {
            showMsg('pw-msg', 'Error de conexión.', false);
        });
    }

    // ---------- Sidebar height ----------

    function adjustSidebarHeight() {
        var sidebar = document.getElementById('profile-sidebar');
        var footer = document.querySelector('footer');
        if (!sidebar || !footer) return;
        var navBottom = 70;
        var footerTop = footer.getBoundingClientRect().top;
        var viewportBottom = window.innerHeight;
        var h;
        if (window.innerWidth <= 768) {
            if (sidebar.classList.contains('open')) {
                h = footerTop - navBottom;
                if (h < 100) h = viewportBottom - navBottom;
                sidebar.style.height = h + 'px';
            } else {
                sidebar.style.height = '';
            }
            return;
        }
        h = footerTop - navBottom;
        if (h < 100) h = 100;
        sidebar.style.height = h + 'px';
    }

    window.addEventListener('resize', adjustSidebarHeight);
    window.addEventListener('scroll', adjustSidebarHeight);
    adjustSidebarHeight();

    // ---------- Mi Dirección ----------

    function paintAddressForm(data) {
        // Dirección heredada del principal: la vista queda en solo lectura.
        window.__addressSharedReadOnly = !!(data && data.shared);
        ['addr-country', 'addr-province', 'addr-street', 'addr-number', 'addr-floor', 'addr-postal', 'addr-notes'].forEach(function(id) {
            var input = document.getElementById(id);
            if (input) input.disabled = false;
        });
        var addrSaveBtn0 = document.querySelector('button[onclick="submitAddress()"]');
        if (addrSaveBtn0) addrSaveBtn0.style.display = '';
        fetch(window.APP_BASE+"/countries", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(countries) {
            var sel = document.getElementById('addr-country');
                sel.innerHTML = '<option value="">-- Seleccionar --</option>';
                countries.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    sel.appendChild(opt);
                });

                if (data.address) {
                    var a = data.address;
                    if (a.country_id) {
                        sel.value = a.country_id;
                        loadRegionsForCountry(a.country_id, a.province_id);
                        fdInicializarPaisDireccion(sel, true);
                    } else {
                        fdInicializarPaisDireccion(sel, false);
                    }
                    if (a.street) document.getElementById('addr-street').value = a.street;
                    if (a.number) document.getElementById('addr-number').value = a.number;
                    if (a.floor_apartment) document.getElementById('addr-floor').value = a.floor_apartment;
                    if (a.postal_code) document.getElementById('addr-postal').value = a.postal_code;
                    if (a.notes) document.getElementById('addr-notes').value = a.notes;
                } else {
                    fdInicializarPaisDireccion(sel, false);
                }

                if (window.__addressSharedReadOnly) {
                    ['addr-country', 'addr-province', 'addr-street', 'addr-number', 'addr-floor', 'addr-postal', 'addr-notes'].forEach(function(id) {
                        var input = document.getElementById(id);
                        if (input) input.disabled = true;
                    });
                    var addrSaveBtn = document.querySelector('button[onclick="submitAddress()"]');
                    if (addrSaveBtn) addrSaveBtn.style.display = 'none';
                    showMsg('direccion-msg', 'Dirección del usuario principal (solo lectura).', true);
                }
            });
    }

    function loadAddress() {
        if (profileDireccionCargada) return;
        profileDireccionCargada = true;

        // Administrado por el Admin: se pinta la dirección del usuario en gestión.
        if (umManagedId() && umManagedProfile) {
            paintAddressForm({ address: umGetUserPrimaryAddress(umManagedProfile) });
            return;
        }

        fetch(window.APP_BASE+"/profile/address", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            paintAddressForm(data);
        })
        .catch(function() { profileDireccionCargada = false; });
    }

    function loadRegionsForCountry(countryId, selectedId) {
        var sel = document.getElementById('addr-province');
        sel.innerHTML = '<option value="">Cargando...</option>';
        sel.disabled = true;
        if (!countryId) {
            sel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
            return;
        }
        fetch(window.APP_BASE + "/regions?country_id=" + countryId, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(regions) {
            sel.innerHTML = '<option value="">-- Seleccionar --</option>';
            regions.forEach(function(r) {
                var opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                sel.appendChild(opt);
            });
            if (selectedId) sel.value = selectedId;
            sel.disabled = false;
        })
        .catch(function() {
            sel.innerHTML = '<option value="">Error al cargar regiones</option>';
        });
    }

    function submitAddress() {
        if (window.__addressSharedReadOnly) {
            showMsg('direccion-msg', 'La dirección la administra el usuario principal de la cuenta.', false);
            return;
        }
        var hiddenCountry = document.getElementById('addr-country-id');
        var countryId = (hiddenCountry && hiddenCountry.value) || document.getElementById('addr-country').value;
        var provinceId = document.getElementById('addr-province').value;
        var street = document.getElementById('addr-street').value.trim();
        var number = document.getElementById('addr-number').value.trim();
        var floor = document.getElementById('addr-floor').value.trim();
        var postal = document.getElementById('addr-postal').value.trim();
        var notes = document.getElementById('addr-notes').value.trim();

        if (!countryId) {
            showMsg('direccion-msg', 'Seleccioná un país.', false);
            return;
        }

        // Administrado por el Admin: guarda sobre la cuenta en gestión.
        var addrUrl = window.APP_BASE+"/profile/address";
        var addrMethod = 'PUT';
        if (umManagedId()) {
            var mAddr = umManagedProfile ? umGetUserPrimaryAddress(umManagedProfile) : null;
            if (mAddr && mAddr.id) {
                addrUrl = umAdminUserUrl('/addresses/' + mAddr.id);
            } else {
                addrUrl = umAdminUserUrl('/addresses');
                addrMethod = 'POST';
            }
        }

        fetch(addrUrl, {
            method: addrMethod,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                country_id: parseInt(countryId),
                province_id: provinceId ? parseInt(provinceId) : null,
                street: street,
                number: number,
                floor_apartment: floor,
                postal_code: postal,
                notes: notes,
                is_primary: true
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                showMsg('direccion-msg', data.errors[firstKey][0], false);
                return;
            }
            if (data.success) {
                showMsg('direccion-msg', data.message, true);
                if (umManagedId()) {
                    umRefreshManagedProfile();
                    return;
                }
                if (typeof fdProfileAddressState !== 'undefined' && fdProfileAddressState) {
                    fdProfileAddressState.done = false;
                    fdProfileAddressState.promise = null;
                    fdProfileAddressState.address = null;
                }
                var estabaBloqueada = fdDireccionBloqueada;
                fdDesbloquearDireccion();
                if (estabaBloqueada) {
                    var destino = new URLSearchParams(window.location.search).get('section') || 'perfil';
                    if (!document.getElementById('dash-' + destino)) destino = 'perfil';
                    showDashSection(destino);
                }
            }
        })
        .catch(function() {
            showMsg('direccion-msg', 'Error de conexión.', false);
        });
    }