            <section id="dash-repartidor" class="dash-section" data-csrf="{{ csrf_token() }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                @php
                    // La sección se puede incluir desde /dashboard/repartidor
                    // (con $stats) o desde el dashboard unificado (sin $stats):
                    // en ese caso se calculan acá para no romper el render.
                    if (! isset($stats)) {
                        $stats = \App\Http\Controllers\DeliveryOrderController::driverStats($user);
                    }
                    $nameParts = preg_split('/\s+/', trim($user->name));
                    $initials = strtoupper(substr($nameParts[0], 0, 1))
                        . (isset($nameParts[1]) ? strtoupper(substr($nameParts[1], 0, 1)) : '');
                    $acceptanceLabel = $stats['acceptance'] !== null ? $stats['acceptance'] . '%' : '—';
                    $ratingLabel = $stats['rating'] !== null ? number_format($stats['rating'], 1, ',', '.') : '—';
                @endphp

                <style>
                    #dash-repartidor{font-family:'Inter','Instrument Sans',system-ui,-apple-system,sans-serif;}
                    #dash-repartidor .drv-shell{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:0;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.06);}

                    /* ── Header ── */
                    #dash-repartidor .drv-header{background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);border-bottom:3px solid #D24C19;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-radius:0;}
                    #dash-repartidor .drv-brand{display:flex;align-items:center;gap:13px;}
                    #dash-repartidor .drv-logo{width:46px;height:46px;border-radius:0;background:#fff;display:flex;align-items:center;justify-content:center;color:#D24C19;box-shadow:0 6px 16px rgba(0,0,0,.22);flex-shrink:0;}
                    #dash-repartidor .drv-logo svg{width:25px;height:25px;}
                    #dash-repartidor .drv-title{color:#fff;font-size:20px;font-weight:800;letter-spacing:-.3px;line-height:1.1;display:flex;align-items:center;gap:9px;}
                    #dash-repartidor .drv-badge{background:#D24C19;color:#fff;font-size:10px;font-weight:800;padding:4px 8px;border-radius:0;letter-spacing:.8px;}
                    #dash-repartidor .drv-subtitle{color:rgba(255,255,255,.68);font-size:12.5px;margin-top:4px;font-weight:500;}
                    #dash-repartidor .drv-head-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
                    #dash-repartidor .drv-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.22);color:#fff;font-size:12px;font-weight:700;padding:8px 15px;border-radius:0;cursor:pointer;transition:.15s;font-family:inherit;}
                    #dash-repartidor .drv-pill:hover{background:rgba(255,255,255,.18);}
                    #dash-repartidor .drv-dot{width:8px;height:8px;border-radius:0;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.28);}
                    #dash-repartidor .drv-dot.paused{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.28);}
                    #dash-repartidor .drv-avatar{width:42px;height:42px;border-radius:0;background:#D24C19;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;border:2px solid rgba(255,255,255,.38);flex-shrink:0;}
                    #dash-repartidor .drv-driver{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);padding:6px 12px 6px 6px;}
                    #dash-repartidor .drv-driver-meta{display:flex;flex-direction:column;line-height:1.25;min-width:0;}
                    #dash-repartidor .drv-driver-name{color:#fff;font-size:13px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;}
                    #dash-repartidor .drv-driver-id{color:rgba(255,255,255,.62);font-size:10.5px;font-weight:700;letter-spacing:.7px;}

                    /* ── Barra de contexto: vehículo / zona / radar / GPS ── */
                    #dash-repartidor .drv-toolbar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 24px;background:#fff;border-bottom:1px solid #e8edf3;}
                    #dash-repartidor .drv-tool{display:inline-flex;align-items:center;gap:8px;min-width:0;}
                    #dash-repartidor .drv-tool--grow{flex:1;min-width:220px;}
                    #dash-repartidor .drv-tool-label{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#94a3b8;white-space:nowrap;}
                    #dash-repartidor .drv-tool-select,.drv-tool-input{font-family:inherit;font-size:13px;font-weight:700;color:#0f172a;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0;padding:8px 11px;outline:none;transition:border-color .15s;}
                    #dash-repartidor .drv-tool-select:focus,.drv-tool-input:focus{border-color:#D24C19;}
                    #dash-repartidor .drv-tool-select:disabled{opacity:.55;cursor:wait;}
                    #dash-repartidor .drv-tool .save-msg{align-self:center;white-space:nowrap;}
                    #dash-repartidor .drv-tool-input{width:100%;font-weight:600;}

                    /* ── KPIs ── */
                    #dash-repartidor .drv-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;padding:20px 24px 6px;}
                    #dash-repartidor .drv-kpi{background:#fff;border:1px solid #e8edf3;border-radius:0;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(15,23,42,.05);}
                    #dash-repartidor .drv-kpi.accent{background:#FFF7ED;border-color:#FED7AA;}
                    #dash-repartidor .drv-kpi-label{font-size:10.5px;font-weight:800;color:#94a3b8;letter-spacing:.7px;text-transform:uppercase;line-height:1.4;}
                    #dash-repartidor .drv-kpi-body{min-width:0;flex:1;}
                    #dash-repartidor .drv-kpi-value{font-size:27px;font-weight:800;color:#0f172a;line-height:1.1;margin-top:7px;letter-spacing:-.5px;}
                    #dash-repartidor .drv-kpi-value.orange{color:#D24C19;}
                    #dash-repartidor .drv-kpi-value.green{color:#16a34a;}
                    #dash-repartidor .drv-kpi-value small{font-size:14px;font-weight:800;color:#0f172a;letter-spacing:0;}
                    #dash-repartidor .drv-kpi-value .drv-kpi-delta{font-size:13px;font-weight:800;color:#16a34a;letter-spacing:0;margin-left:4px;}
                    #dash-repartidor .drv-kpi-value .drv-kpi-goal-of{font-size:13px;font-weight:700;color:#64748b;letter-spacing:0;}
                    #dash-repartidor .drv-kpi-sub{font-size:11px;font-weight:600;color:#94a3b8;margin-top:7px;}
                    #dash-repartidor .drv-kpi-progress{height:6px;background:#e8edf3;margin-top:10px;overflow:hidden;}
                    #dash-repartidor .drv-kpi-progress span{display:block;height:100%;background:#D24C19;transition:width .4s ease;}
                    #dash-repartidor .drv-kpi.kpi-goal{cursor:pointer;}
                    #dash-repartidor .drv-kpi-icon{width:46px;height:46px;border-radius:0;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
                    #dash-repartidor .drv-kpi-icon svg{width:21px;height:21px;}
                    #dash-repartidor .ico-earn{background:#FFE3D0;color:#D24C19;}
                    #dash-repartidor .ico-done{background:#EEF2F7;color:#64748b;}
                    #dash-repartidor .ico-acc{background:#DCFCE7;color:#16a34a;}
                    #dash-repartidor .ico-star{background:#FFF1E4;color:#D24C19;}

                    /* ── Tabs ── */
                    #dash-repartidor .drv-tabs-wrap{padding:18px 24px 0;}
                    #dash-repartidor .drv-tabs{display:inline-flex;background:#e8edf3;border:1px solid #e2e8f0;border-radius:0;padding:5px;gap:4px;flex-wrap:wrap;}
                    #dash-repartidor .drv-tab{display:inline-flex;align-items:center;gap:9px;border:none;background:transparent;color:#64748b;font-size:13px;font-weight:700;padding:10px 20px;border-radius:0;cursor:pointer;transition:.18s;font-family:inherit;}
                    #dash-repartidor .drv-tab:hover{color:#0f172a;}
                    #dash-repartidor .drv-tab.active{background:#fff;color:#D24C19;box-shadow:0 3px 8px rgba(15,23,42,.12);}
                    #dash-repartidor .drv-count{min-width:23px;height:23px;padding:0 7px;border-radius:0;background:#D24C19;color:#fff;font-size:11px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;}
                    #dash-repartidor .drv-tab:not(.active) .drv-count{background:#cbd5e1;color:#475569;}

                    /* ── Radar row ── */
                    #dash-repartidor .drv-radar-row{display:flex;align-items:center;gap:16px;padding:16px 24px 0;flex-wrap:wrap;}
                    #dash-repartidor .drv-radar{display:inline-flex;align-items:center;gap:8px;background:#FFF7ED;border:1px solid #FED7AA;color:#D24C19;font-size:12.5px;font-weight:800;padding:9px 16px;border-radius:0;cursor:pointer;transition:.15s;font-family:inherit;}
                    #dash-repartidor .drv-radar:hover{background:#FFE8D6;}
                    #dash-repartidor .drv-radar.off{background:#fff;border-color:#e2e8f0;color:#94a3b8;}
                    #dash-repartidor .drv-radar svg{width:15px;height:15px;}
                    #dash-repartidor .drv-live{font-size:12.5px;color:#94a3b8;font-weight:500;display:flex;align-items:center;gap:7px;}
                    #dash-repartidor .drv-classic{margin-left:auto;font-size:12px;font-weight:700;color:#D24C19;text-decoration:none;display:inline-flex;align-items:center;gap:5px;}
                    #dash-repartidor .drv-classic:hover{text-decoration:underline;}

                    /* ── Section head ── */
                    #dash-repartidor .drv-section-head{display:flex;align-items:center;gap:11px;padding:22px 24px 6px;flex-wrap:wrap;}
                    #dash-repartidor .drv-section-dot{width:12px;height:12px;border-radius:0;background:#D24C19;flex-shrink:0;box-shadow:0 0 0 4px rgba(210,76,25,.14);}
                    #dash-repartidor .drv-section-title{font-size:18px;font-weight:800;color:#0f172a;letter-spacing:-.3px;}
                    #dash-repartidor .drv-section-hint{margin-left:auto;font-size:12px;color:#94a3b8;font-weight:500;}
                    #dash-repartidor .drv-scroll-btn{width:36px;height:36px;border-radius:0;background:#fff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;box-shadow:0 3px 8px rgba(15,23,42,.10);transition:.15s;}
                    #dash-repartidor .drv-scroll-btn:hover{color:#D24C19;border-color:#FED7AA;transform:translateY(2px);}
                    #dash-repartidor .drv-scroll-btn svg{width:17px;height:17px;}

                    /* ── List & cards ── */
                    #dash-repartidor .drv-list{padding:14px 24px 26px;display:flex;flex-direction:column;gap:16px;}
                    #dash-repartidor .drv-card{background:#fff;border:1px solid #e8edf3;border-radius:0;padding:20px;box-shadow:0 4px 16px rgba(15,23,42,.05);transition:.18s;}
                    #dash-repartidor .drv-card:hover{box-shadow:0 10px 26px rgba(15,23,42,.09);border-color:#e2e8f0;}
                    #dash-repartidor .drv-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;}
                    #dash-repartidor .drv-order-no{font-size:11.5px;font-weight:800;color:#D24C19;background:#FFF7ED;border:1px solid #FED7AA;padding:4px 11px;border-radius:0;display:inline-block;letter-spacing:.2px;}
                    #dash-repartidor .drv-shop{font-size:16px;font-weight:800;color:#0f172a;margin-top:9px;letter-spacing:-.2px;}
                    #dash-repartidor .drv-shop-sub{font-size:12.5px;color:#64748b;margin-top:3px;}
                    #dash-repartidor .drv-fee{text-align:right;flex-shrink:0;}
                    #dash-repartidor .drv-fee-value{font-size:32px;font-weight:800;color:#D24C19;line-height:1;letter-spacing:-1px;}
                    #dash-repartidor .drv-fee-label{font-size:10.5px;color:#94a3b8;font-weight:700;margin-top:6px;text-transform:uppercase;letter-spacing:.6px;}

                    /* ── Route ── */
                    #dash-repartidor .drv-route{display:flex;gap:15px;margin:18px 0 6px;padding:16px;background:#f8fafc;border:1px solid #eef2f7;border-radius:0;}
                    #dash-repartidor .drv-rail{display:flex;flex-direction:column;align-items:center;padding-top:4px;}
                    #dash-repartidor .drv-node{width:13px;height:13px;border-radius:0;flex-shrink:0;}
                    #dash-repartidor .drv-node.origin{background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.16);}
                    #dash-repartidor .drv-node.dest{background:#D24C19;box-shadow:0 0 0 4px rgba(210,76,25,.16);}
                    #dash-repartidor .drv-line{width:2px;flex:1;min-height:40px;background:repeating-linear-gradient(to bottom,#cbd5e1 0 5px,transparent 5px 10px);margin:5px 0;}
                    #dash-repartidor .drv-legs{flex:1;display:flex;flex-direction:column;justify-content:space-between;gap:18px;min-width:0;}
                    #dash-repartidor .drv-leg-label{font-size:10px;font-weight:800;letter-spacing:.9px;text-transform:uppercase;color:#94a3b8;}
                    #dash-repartidor .drv-leg-main{font-size:14px;font-weight:700;color:#0f172a;margin-top:3px;overflow:hidden;text-overflow:ellipsis;}
                    #dash-repartidor .drv-leg-sub{font-size:12.5px;color:#64748b;margin-top:2px;}

                    /* ── Chips ── */
                    #dash-repartidor .drv-chips{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 18px;}
                    #dash-repartidor .drv-chip{font-size:11px;font-weight:700;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;padding:6px 11px;border-radius:0;display:inline-flex;align-items:center;gap:5px;}
                    #dash-repartidor .drv-chip svg{width:12px;height:12px;}
                    #dash-repartidor .drv-chip.hot{background:#FFF7ED;border-color:#FED7AA;color:#D24C19;}
                    #dash-repartidor .drv-chip.ok{background:#F0FDF4;border-color:#BBF7D0;color:#16a34a;}

                    /* ── Buttons ── */
                    #dash-repartidor .drv-take{width:100%;background:#D24C19;color:#fff;border:none;border-radius:0;padding:16px;font-size:15px;font-weight:800;letter-spacing:.3px;cursor:pointer;box-shadow:0 8px 20px rgba(210,76,25,.30);transition:.16s;display:flex;align-items:center;justify-content:center;gap:9px;font-family:inherit;}
                    #dash-repartidor .drv-take:hover{background:#bd4115;transform:translateY(-2px);box-shadow:0 12px 26px rgba(210,76,25,.36);}
                    #dash-repartidor .drv-take:active{transform:translateY(0);}
                    #dash-repartidor .drv-take svg{width:17px;height:17px;}

                    /* ── Viaje en curso ── */
                    #dash-repartidor .drv-section-dot.green{background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.15);}
                    #dash-repartidor .drv-card--trip{border:2px solid #BBF7D0;background:#fff;box-shadow:0 6px 22px rgba(22,163,74,.08);border-radius:0;}
                    #dash-repartidor .drv-card--trip:hover{border-color:#86EFAC;box-shadow:0 12px 30px rgba(22,163,74,.13);}
                    
                    #dash-repartidor .drv-trip-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;}
                    #dash-repartidor .drv-trip-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;min-width:0;}
                    #dash-repartidor .drv-badge-progress{display:inline-flex;align-items:center;gap:7px;background:#16a34a;color:#fff;font-size:11.5px;font-weight:800;padding:6px 14px;border-radius:0;letter-spacing:.2px;box-shadow:0 4px 10px rgba(22,163,74,.25);}
                    #dash-repartidor .drv-badge-progress .pulse{width:7px;height:7px;border-radius:0;background:#fff;animation:drvPulse 1.4s ease-in-out infinite;}
                    @keyframes drvPulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.45;transform:scale(.7);}}
                    #dash-repartidor .drv-gain{text-align:right;line-height:1.1;}
                    #dash-repartidor .drv-gain-label{font-size:12.5px;color:#64748b;font-weight:600;display:block;}
                    #dash-repartidor .drv-gain-value{font-size:29px;font-weight:800;color:#D24C19;letter-spacing:-.9px;}

                    #dash-repartidor .drv-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:17px 0 18px;}
                    #dash-repartidor .drv-step{display:flex;align-items:center;justify-content:center;gap:7px;padding:12px 8px;border-radius:0;font-size:12.5px;font-weight:800;background:#eef2f7;color:#94a3b8;text-align:center;transition:.2s;letter-spacing:-.1px;}
                    #dash-repartidor .drv-step.done{background:#D24C19;color:#fff;box-shadow:0 4px 11px rgba(210,76,25,.24);}
                    #dash-repartidor .drv-step.current{background:#D24C19;color:#fff;box-shadow:0 5px 14px rgba(210,76,25,.34);}
                    #dash-repartidor .drv-step svg{width:14px;height:14px;flex-shrink:0;}

                    #dash-repartidor .drv-info{background:#f8fafc;border:1px solid #eef2f7;border-radius:0;padding:17px 19px;display:flex;flex-direction:column;gap:15px;}
                    #dash-repartidor .drv-info-label{font-size:10.5px;font-weight:800;letter-spacing:.9px;text-transform:uppercase;color:#94a3b8;}
                    #dash-repartidor .drv-info-name{font-size:15px;font-weight:800;color:#0f172a;margin-top:5px;letter-spacing:-.2px;}
                    #dash-repartidor .drv-info-line{font-size:13px;color:#64748b;margin-top:3px;line-height:1.5;}
                    #dash-repartidor .drv-info-phone{font-size:13px;font-weight:700;color:#D24C19;margin-top:6px;display:flex;align-items:center;gap:6px;}
                    #dash-repartidor .drv-info-phone svg{width:13px;height:13px;}
                    
                    #dash-repartidor .drv-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:17px;}
                    #dash-repartidor .drv-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:0;padding:14px 20px;font-size:13.5px;font-weight:800;cursor:pointer;text-decoration:none;transition:.16s;font-family:inherit;border:none;}
                    #dash-repartidor .drv-btn svg{width:16px;height:16px;}
                    #dash-repartidor .drv-btn-maps{background:#0c2a4d;color:#fff;box-shadow:0 6px 14px rgba(12,42,77,.22);}
                    #dash-repartidor .drv-btn-maps:hover{background:#123a63;transform:translateY(-1px);}
                    #dash-repartidor .drv-btn-waze{background:#fff;color:#0c2a4d;border:1.5px solid #cbd5e1;}
                    #dash-repartidor .drv-btn-waze:hover{border-color:#0c2a4d;transform:translateY(-1px);}
                    #dash-repartidor .drv-btn-pickup{background:#D24C19;color:#fff;box-shadow:0 6px 16px rgba(210,76,25,.30);margin-left:auto;}
                    #dash-repartidor .drv-btn-pickup:hover{background:#bd4115;transform:translateY(-1px);}
                    #dash-repartidor .drv-btn-done{background:#16a34a;color:#fff;box-shadow:0 6px 16px rgba(22,163,74,.30);margin-left:auto;}
                    #dash-repartidor .drv-btn-done:hover{background:#15803d;transform:translateY(-1px);}
                    
                    #dash-repartidor .drv-pin-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;padding:14px;background:#F0FDF4;border:1px dashed #BBF7D0;border-radius:0;}
                    #dash-repartidor .drv-pin{flex:1;min-width:170px;border:1px solid #cbd5e1;border-radius:0;padding:14px 15px;font-size:15px;font-weight:700;letter-spacing:3px;outline:none;font-family:inherit;background:#fff;text-align:center;}
                    #dash-repartidor .drv-pin:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.14);}
                    #dash-repartidor .drv-pin-hint{font-size:11.5px;color:#64748b;width:100%;font-weight:600;}

                    /* ── Empty / loading ── */
                    #dash-repartidor .drv-empty{background:#fff;border:2px dashed #e2e8f0;border-radius:0;padding:48px 26px;text-align:center;}
                    #dash-repartidor .drv-empty-icon{width:80px;height:80px;border-radius:0;background:#FFF1E8;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#D24C19;}
                    #dash-repartidor .drv-empty-icon svg{width:34px;height:34px;}
                    #dash-repartidor .drv-empty-title{font-size:18px;font-weight:800;color:#0f172a;letter-spacing:-.3px;}
                    #dash-repartidor .drv-empty-sub{font-size:13.5px;color:#94a3b8;margin-top:9px;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.65;}
                    #dash-repartidor .drv-loading{padding:34px;text-align:center;color:#94a3b8;font-size:13px;font-weight:600;}

                    @media (max-width:640px){
                        #dash-repartidor .drv-header{padding:14px 16px;}
                        #dash-repartidor .drv-kpis{padding:16px 16px 4px;grid-template-columns:repeat(2,1fr);gap:10px;}
                        #dash-repartidor .drv-kpi{padding:13px;flex-direction:column;align-items:flex-start;gap:8px;}
                        #dash-repartidor .drv-tabs-wrap,#dash-repartidor .drv-radar-row,#dash-repartidor .drv-section-head,#dash-repartidor .drv-list{padding-left:16px;padding-right:16px;}
                        #dash-repartidor .drv-card{padding:16px;}
                        #dash-repartidor .drv-fee-value{font-size:26px;}
                    }
                </style>

                <div class="drv-shell">

                    {{-- ═══ Header ═══ --}}
                    <header class="drv-header">
                        <div class="drv-brand">
                            <div class="drv-logo">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </div>
                            <div>
                                <div class="drv-title">PideAca <span class="drv-badge">DRIVER PRO</span></div>
                                <div class="drv-subtitle">Terminal Central de Repartos</div>
                            </div>
                        </div>
                        <div class="drv-head-actions">
                            <button type="button" class="drv-pill" id="drv-conn-btn" onclick="drvToggleConnection()">
                                <span class="drv-dot" id="drv-conn-dot"></span>
                                <span id="drv-conn-text">EN LÍNEA</span>
                            </button>
                            <button type="button" class="drv-pill" id="drv-pause-btn" onclick="drvToggleConnection()">Pausar</button>
                            <div class="drv-driver">
                                <div class="drv-avatar" title="{{ $user->name }}">{{ $initials }}</div>
                                <div class="drv-driver-meta">
                                    <span class="drv-driver-name">{{ $user->name }}</span>
                                    <span class="drv-driver-id">ID: DRV-{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </div>
                        </div>
                    </header>

                    {{-- ═══ Barra de contexto: vehículo, zona, radar y GPS ═══ --}}
                    <div class="drv-toolbar">
                        <label class="drv-tool">
                            <span class="drv-tool-label">Vehículo:</span>
                            @php
                                $selectedVehicle = $user->vehicle_type ?: \App\Models\User::DEFAULT_VEHICLE_TYPE;
                            @endphp
                            <select class="drv-tool-select" id="drv-vehicle">
                                @foreach(\App\Models\User::VEHICLE_TYPES as $vehicleValue => $vehicleLabel)
                                    <option value="{{ $vehicleValue }}" @selected($selectedVehicle === $vehicleValue)>{{ $vehicleLabel }}</option>
                                @endforeach
                            </select>
                            <span class="save-msg" id="drv-vehicle-msg" aria-live="polite"></span>
                        </label>
                        <label class="drv-tool drv-tool--grow">
                            <span class="drv-tool-label">Zona de cobertura:</span>
                            <input type="text" class="drv-tool-input" id="drv-zone" placeholder="Ej: Almagro &amp; Caballito" />
                        </label>
                        <button type="button" class="drv-radar" id="drv-radar-btn" onclick="drvToggleRadar()">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                            Radar Sonoro DiDi
                        </button>
                        <span class="drv-live" id="drv-gps"><span class="drv-dot"></span> GPS Activo (100% Precisión)</span>
                    </div>

                    {{-- ═══ KPIs ═══ --}}
                    @php
                        $goal = 8; // Meta diaria de pedidos (ajustable en el JS con click)
                        $goalDone = (int) $stats['deliveredToday'];
                        $goalPct = min(100, round(($goalDone / max(1, $goal)) * 100));
                    @endphp
                    <div class="drv-kpis">
                        <div class="drv-kpi accent">
                            <div class="drv-kpi-body">
                                <div class="drv-kpi-label">Ganancias hoy</div>
                                <div class="drv-kpi-value orange">${{ number_format($stats['earningsToday'], 0, ',', '.') }}
                                    <small class="drv-kpi-delta">+{{ $stats['deliveredToday'] }} {{ $stats['deliveredToday'] === 1 ? 'viaje' : 'viajes' }}</small>
                                </div>
                                <div class="drv-kpi-sub">Base: ${{ number_format($stats['baseToday'], 0, ',', '.') }} | Propinas: ${{ number_format($stats['tipsToday'], 0, ',', '.') }}</div>
                            </div>
                            <div class="drv-kpi-icon ico-earn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                        </div>

                        <div class="drv-kpi kpi-goal" id="drv-goal-card" title="Clic para cambiar la meta">
                            <div class="drv-kpi-body">
                                <div class="drv-kpi-label">Meta del día</div>
                                <div class="drv-kpi-value">{{ $goalDone }} <small class="drv-kpi-goal-of">de <span id="drv-goal-total">{{ $goal }}</span> pedidos</small></div>
                                <div class="drv-kpi-progress"><span id="drv-goal-bar" style="width:{{ $goalPct }}%;"></span></div>
                            </div>
                            <div class="drv-kpi-icon ico-done">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                        </div>

                        <div class="drv-kpi">
                            <div class="drv-kpi-body">
                                <div class="drv-kpi-label">Aceptación</div>
                                <div class="drv-kpi-value green">{{ $acceptanceLabel }}</div>
                            </div>
                            <div class="drv-kpi-icon ico-acc">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                            </div>
                        </div>

                        <div class="drv-kpi">
                            <div class="drv-kpi-body">
                                <div class="drv-kpi-label">Calificación repartidor</div>
                                <div class="drv-kpi-value orange">{{ $ratingLabel }} <small>★</small></div>
                            </div>
                            <div class="drv-kpi-icon ico-star">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ Tabs ═══ --}}
                    <div class="drv-tabs-wrap">
                        <div class="drv-tabs">
                            <button type="button" class="drv-tab active" id="drv-tab-available" onclick="drvSwitchTab('available')">
                                Pedidos Disponibles <span class="drv-count" id="drv-count-available">0</span>
                            </button>
                            <button type="button" class="drv-tab" id="drv-tab-inprogress" onclick="drvSwitchTab('inprogress')">
                                En Curso / Asignados <span class="drv-count" id="drv-count-inprogress">0</span>
                            </button>
                        </div>
                    </div>

                    {{-- ═══ Fila auxiliar ═══ --}}
                    <div class="drv-radar-row">
                        <span class="drv-live"><span class="drv-dot" id="drv-live-dot"></span> Actualización en tiempo real</span>
                        <a class="drv-classic" href="{{ url('/delivery') }}">Panel clásico
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                    </div>

                    {{-- ═══ Panel: disponibles ═══ --}}
                    <div id="drv-panel-available">
                        <div class="drv-section-head">
                            <span class="drv-section-dot"></span>
                            <span class="drv-section-title">Ofertas de Viaje para Aceptar</span>
                            <button type="button" class="drv-scroll-btn" onclick="drvScrollList()" title="Ver más" aria-label="Ver más pedidos">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            </button>
                            <span class="drv-section-hint">Prioridad según cercanía</span>
                        </div>
                        <div class="drv-list" id="dash-delivery-orders-list">
                            <div class="drv-loading">Buscando pedidos disponibles…</div>
                        </div>
                    </div>

                    {{-- ═══ Panel: en curso ═══ --}}
                    <div id="drv-panel-inprogress" style="display:none;">
                        <div class="drv-section-head">
                            <span class="drv-section-dot green"></span>
                            <span class="drv-section-title">Pedidos en Curso Asignados a Ti</span>
                            <span class="drv-section-hint">Sigue las instrucciones de entrega</span>
                        </div>
                        <div class="drv-list" id="dash-my-orders-list">
                            <div class="drv-loading">Cargando tus pedidos…</div>
                        </div>
                    </div>

                </div>

                <script>
                (function () {
                    /* ── Estado ── */
                    var radarOn = true;
                    var connected = true;
                    var lastAvailableIds = [];
                    var ZONE_KEY = 'drv_zone';
                    var GOAL_KEY = 'drv_goal';

                    /* ── Utilidades ── */
                    function money(v) {
                        return '$' + Number(v || 0).toLocaleString('es-AR', { maximumFractionDigits: 0 });
                    }
                    function esc(s) {
                        return String(s == null ? '' : s)
                            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    }
                    function csrf() {
                        // El meta del layout solo existe dentro del dashboard;
                        // servida la sección por URL directa se usa el token
                        // propio de la sección y, en ultimo caso, la cookie
                        // XSRF-TOKEN que Laravel también valida.
                        var m = document.querySelector('meta[name="csrf-token"]');
                        if (m && m.content) return m.content;
                        var sec = document.getElementById('dash-repartidor');
                        if (sec && sec.dataset.csrf) return sec.dataset.csrf;
                        var ck = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
                        return ck ? decodeURIComponent(ck[1]) : '';
                    }
                    function addressLine(a) {
                        if (!a) return 'Sin dirección cargada';
                        var parts = [];
                        if (a.street) parts.push(a.street);
                        if (a.number) parts.push(a.number);
                        if (a.floor_apartment) parts.push(a.floor_apartment);
                        return parts.length ? parts.join(' ') : 'Sin dirección cargada';
                    }
                    function statusLabel(s) {
                        var map = {
                            pending: 'Pendiente', confirmed: 'Confirmado', in_preparation: 'En preparación',
                            on_the_way: 'En camino', delivered: 'Entregado', cancelled: 'Cancelado'
                        };
                        return map[s] || (s || '—');
                    }
                    function deliveryLabel(s) {
                        var map = { pending: 'Sin asignar', assigned: 'Asignado', on_the_way: 'En camino', delivered: 'Entregado' };
                        return map[s] || (s || '—');
                    }

                    /* ── Radar sonoro ── */
                    window.drvToggleRadar = function () {
                        radarOn = !radarOn;
                        var b = document.getElementById('drv-radar-btn');
                        b.classList.toggle('off', !radarOn);
                        if (radarOn) beep(660, 0.12);
                    };
                    function beep(freq, dur) {
                        if (!radarOn) return;
                        try {
                            var Ctx = window.AudioContext || window.webkitAudioContext;
                            if (!Ctx) return;
                            if (!window.__drvCtx) window.__drvCtx = new Ctx();
                            var ctx = window.__drvCtx;
                            var osc = ctx.createOscillator(), gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.value = freq || 880;
                            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (dur || 0.3));
                            osc.connect(gain); gain.connect(ctx.destination);
                            osc.start(); osc.stop(ctx.currentTime + (dur || 0.3) + 0.05);
                        } catch (e) { /* silencio */ }
                    }

                    /* ── Conexión ── */
                    window.drvToggleConnection = function () {
                        connected = !connected;
                        document.getElementById('drv-conn-dot').classList.toggle('paused', !connected);
                        document.getElementById('drv-conn-text').textContent = connected ? 'EN LÍNEA' : 'PAUSADO';
                        document.getElementById('drv-pause-btn').textContent = connected ? 'Pausar' : 'Reanudar';
                        document.getElementById('drv-live-dot').style.background = connected ? '#22c55e' : '#f59e0b';
                    };

                    /* ── Barra de contexto: vehículo (BD) y zona (local) ── */
                    // El selector de vehiculo lo sincroniza y guarda el modulo
                    // compartido del dashboard (#drv-vehicle / #profile-vehicle).
                    function initToolbar() {
                        var z = document.getElementById('drv-zone');
                        try {
                            if (localStorage.getItem(ZONE_KEY) && z) z.value = localStorage.getItem(ZONE_KEY);
                        } catch (e) { /* sin storage */ }
                        if (z) z.addEventListener('input', function () {
                            try { localStorage.setItem(ZONE_KEY, z.value); } catch (e) {}
                        });
                    }

                    /* ── GPS: estado de precisión en la barra ── */
                    function initGps() {
                        var el = document.getElementById('drv-gps');
                        if (!el) return;
                        if (!navigator.geolocation) {
                            el.innerHTML = '<span class="drv-dot paused"></span> GPS no disponible';
                            return;
                        }
                        navigator.geolocation.getCurrentPosition(function (pos) {
                            var acc = Math.round(pos.coords.accuracy);
                            el.innerHTML = '<span class="drv-dot"></span> GPS Activo (' + acc + 'm de precisión)';
                        }, function () {
                            el.innerHTML = '<span class="drv-dot paused"></span> GPS sin señal';
                        }, { enableHighAccuracy: true, timeout: 8000 });
                    }

                    /* ── Meta del día: clic para ajustar ── */
                    function initGoal() {
                        var card = document.getElementById('drv-goal-card');
                        var totalEl = document.getElementById('drv-goal-total');
                        var bar = document.getElementById('drv-goal-bar');
                        if (!card || !totalEl || !bar) return;
                        var goal = 8;
                        try {
                            var stored = parseInt(localStorage.getItem(GOAL_KEY), 10);
                            if (stored > 0) goal = stored;
                        } catch (e) {}
                        var done = parseInt(card.querySelector('.drv-kpi-value').textContent, 10) || 0;
                        function paint() {
                            totalEl.textContent = goal;
                            bar.style.width = Math.min(100, Math.round((done / Math.max(1, goal)) * 100)) + '%';
                        }
                        paint();
                        card.addEventListener('click', function () {
                            var val = prompt('Meta de pedidos del día:', goal);
                            var n = parseInt(val, 10);
                            if (n > 0) {
                                goal = n;
                                try { localStorage.setItem(GOAL_KEY, String(goal)); } catch (e) {}
                                paint();
                            }
                        });
                    }

                    /* ── Tabs ── */
                    window.drvSwitchTab = function (tab) {
                        var av = tab === 'available';
                        document.getElementById('drv-tab-available').classList.toggle('active', av);
                        document.getElementById('drv-tab-inprogress').classList.toggle('active', !av);
                        document.getElementById('drv-panel-available').style.display = av ? 'block' : 'none';
                        document.getElementById('drv-panel-inprogress').style.display = av ? 'none' : 'block';
                        if (!av) loadMyOrders(); else loadAvailable();
                    };
                    window.drvScrollList = function () {
                        var el = document.getElementById('dash-delivery-orders-list');
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    };

                    /* ── Tarjeta de pedido disponible ── */
                    function cardHtml(o) {
                        var shop = o.provider || {};
                        var addr = o.address || {};
                        var fee = money(o.delivery_fee || 500);
                        return ''
                            + '<article class="drv-card">'
                            +   '<div class="drv-card-top">'
                            +     '<div style="min-width:0;">'
                            +       '<span class="drv-order-no">#' + esc(o.order_number || o.id) + '</span>'
                            +       '<div class="drv-shop">' + esc(shop.business_name || 'Comercio') + '</div>'
                            +       '<div class="drv-shop-sub">' + esc(shop.zone || 'Zona de entrega') + '</div>'
                            +     '</div>'
                            +     '<div class="drv-fee">'
                            +       '<div class="drv-fee-value">' + fee + '</div>'
                            +       '<div class="drv-fee-label">Tarifa de envío</div>'
                            +     '</div>'
                            +   '</div>'

                            +   '<div class="drv-route">'
                            +     '<div class="drv-rail"><span class="drv-node origin"></span><span class="drv-line"></span><span class="drv-node dest"></span></div>'
                            +     '<div class="drv-legs">'
                            +       '<div>'
                            +         '<div class="drv-leg-label">Origen · Recogida</div>'
                            +         '<div class="drv-leg-main">' + esc(shop.business_name || 'Restaurante') + '</div>'
                            +         '<div class="drv-leg-sub">' + esc(shop.zone || 'Punto de retiro del pedido') + '</div>'
                            +       '</div>'
                            +       '<div>'
                            +         '<div class="drv-leg-label">Destino · Cliente</div>'
                            +         '<div class="drv-leg-main">' + esc(addressLine(addr)) + '</div>'
                            +         '<div class="drv-leg-sub">' + esc(addr.postal_code || 'Entrega a domicilio') + '</div>'
                            +       '</div>'
                            +     '</div>'
                            +   '</div>'

                            +   '<div class="drv-chips">'
                            +     '<span class="drv-chip hot">' + esc(statusLabel(o.status)) + '</span>'
                            +     '<span class="drv-chip">Pago: ' + esc(o.payment_method || 'efectivo') + '</span>'
                            +     (o.total ? '<span class="drv-chip">Total $' + Number(o.total).toLocaleString('es-AR', { maximumFractionDigits: 0 }) + '</span>' : '')
                            +   '</div>'

                            +   '<button type="button" class="drv-take" onclick="drvTakeOrder(' + o.id + ')">'
                            +     '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>'
                            +     'Tomar pedido'
                            +   '</button>'
                            + '</article>';
                    }

                    /* ── Helpers de la tarjeta de viaje ── */
                    function stepIndexOf(status) {
                        if (status === 'delivered') return 3;
                        if (status === 'picked_up' || status === 'on_the_way') return 2;
                        return 1;
                    }
                    function addrLocality(a) {
                        if (!a) return '';
                        var bits = [];
                        if (a.region && a.region.name) bits.push(a.region.name);
                        else if (a.postal_code) bits.push(a.postal_code);
                        if (a.department && a.department.name && bits.indexOf(a.department.name) === -1) bits.push(a.department.name);
                        if (a.province && a.province.name && bits.indexOf(a.province.name) === -1) bits.push(a.province.name);
                        return bits.join(', ');
                    }
                    function addrFull(a) {
                        if (!a) return 'Sin dirección cargada';
                        var line = [a.street, a.number].filter(Boolean).join(' ');
                        if (a.floor_apartment) line += (line ? ', ' : '') + a.floor_apartment;
                        var loc = addrLocality(a);
                        return [line, loc].filter(Boolean).join(', ') || 'Sin dirección cargada';
                    }
                    function destQuery(a, fallback) {
                        if (a) {
                            if (a.latitude && a.longitude) return a.latitude + ',' + a.longitude;
                            var t = [a.street, a.number].filter(Boolean).join(' ');
                            if (t) return [t, addrLocality(a)].filter(Boolean).join(', ');
                        }
                        return fallback || '';
                    }
                    function mapUrl(a, fallback) {
                        var q = destQuery(a, fallback);
                        return q ? 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(q) : null;
                    }
                    function wazeUrl(a, fallback) {
                        var q = destQuery(a, fallback);
                        if (!q) return null;
                        if (a && a.latitude && a.longitude) {
                            return 'https://waze.com/ul?ll=' + encodeURIComponent(a.latitude + ',' + a.longitude) + '&navigate=yes';
                        }
                        return 'https://waze.com/ul?q=' + encodeURIComponent(q);
                    }

                    /* ── Tarjeta de viaje en curso ── */
                    function myCardHtml(o) {
                        var shop = o.provider || {};
                        var shopUser = shop.user || {};
                        var pickupAddr = shopUser.primary_address || null;
                        var dest = o.address || {};
                        var customer = o.user || {};
                        var hasPin = o.has_delivery_pin === true;
                        var cur = stepIndexOf(o.delivery_status);
                        var delivered = cur === 3;

                        var pickupLine = pickupAddr
                            ? addrFull(pickupAddr)
                            : (shop.zone || 'Punto de retiro en el comercio');

                        var gMaps = mapUrl(dest, addrFull(dest));
                        var wMaps = wazeUrl(dest, addrFull(dest));

                        var labels = ['1. En retiro', '2. En entrega', '3. Entregado'];
                        var steps = '';
                        for (var i = 1; i <= 3; i++) {
                            var cls = i < cur ? 'done' : (i === cur ? 'current' : '');
                            steps += '<div class="drv-step ' + cls + '">' + labels[i - 1] + '</div>';
                        }

                        var actions = '';
                        if (gMaps) {
                            actions += '<a class="drv-btn drv-btn-maps" href="' + gMaps + '" target="_blank" rel="noopener">'
                                + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>'
                                + 'Abrir en Google Maps / Waze</a>';
                        }
                        if (wMaps) {
                            actions += '<a class="drv-btn drv-btn-waze" href="' + wMaps + '" target="_blank" rel="noopener">'
                                + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>'
                                + 'Waze</a>';
                        }

                        if (delivered) {
                            actions += '<span class="drv-chip ok" style="margin-left:auto;padding:12px 18px;font-size:13px;">✓ Entregado</span>';
                        } else if (cur === 1) {
                            actions += '<button type="button" class="drv-btn drv-btn-pickup" onclick="drvPickup(' + o.id + ')">'
                                + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>'
                                + 'Ya retiré el pedido</button>';
                        } else {
                            actions += '<button type="button" class="drv-btn drv-btn-done" onclick="drvOpenPin(' + o.id + ')">'
                                + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                                + 'Completar Entrega</button>';
                        }

                        var pinRow = '';
                        if (hasPin && !delivered) {
                            pinRow = '<div class="drv-pin-row" id="drv-pin-row-' + o.id + '" hidden>'
                                + '<span class="drv-pin-hint">Pedile al cliente el PIN de entrega y ingresalo acá para confirmar.</span>'
                                + '<input type="password" class="drv-pin" id="drv-pin-' + o.id + '" placeholder="PIN de entrega" maxlength="12" autocomplete="off" />'
                                + '<button type="button" class="drv-btn drv-btn-done" onclick="drvConfirmDelivery(' + o.id + ')">'
                                + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                                + 'Confirmar entrega</button>'
                                + '</div>';
                        }

                        return ''
                            + '<article class="drv-card drv-card--trip">'
                            +   '<div class="drv-trip-top">'
                            +     '<div class="drv-trip-left">'
                            +       '<span class="drv-badge-progress"><span class="pulse"></span>' + (delivered ? 'Viaje Completado' : 'Viaje en Progreso') + '</span>'
                            +       '<span class="drv-order-no">#' + esc(o.order_number || o.id) + '</span>'
                            +     '</div>'
                            +     '<div class="drv-gain">'
                            +       '<span class="drv-gain-label">Ganancia al finalizar:</span>'
                            +       '<span class="drv-gain-value">' + money(o.delivery_fee || 500) + '</span>'
                            +     '</div>'
                            +   '</div>'

                            +   '<div class="drv-steps">' + steps + '</div>'

                            +   '<div class="drv-info">'
                            +     '<div>'
                            +       '<div class="drv-info-label">Retirar en</div>'
                            +       '<div class="drv-info-name">' + esc(shop.business_name || 'Comercio') + '</div>'
                            +       '<div class="drv-info-line">' + esc(pickupLine) + '</div>'
                            +     '</div>'
                            +     '<div>'
                            +       '<div class="drv-info-label">Entregar a</div>'
                            +       '<div class="drv-info-name">' + esc(customer.name || 'Cliente') + '</div>'
                            +       '<div class="drv-info-line">' + esc(addrFull(dest)) + '</div>'
                            +       (customer.phone
                                ? '<div class="drv-info-phone"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>Tel: ' + esc(customer.phone) + '</div>'
                                : '')
                            +     '</div>'
                            +   '</div>'

                            +   '<div class="drv-actions">' + actions + '</div>'
                            +   pinRow
                            + '</article>';
                    }

                    function emptyHtml() {
                        return ''
                            + '<div class="drv-empty">'
                            +   '<div class="drv-empty-icon">'
                            +     '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>'
                            +   '</div>'
                            +   '<div class="drv-empty-title">No hay pedidos disponibles por ahora</div>'
                            +   '<div class="drv-empty-sub">Mantente conectado en esta pantalla. Las solicitudes de Pizzerías y Restaurantes aparecerán aquí al instante.</div>'
                            + '</div>';
                    }
                    function emptyMyHtml() {
                        return ''
                            + '<div class="drv-empty">'
                            +   '<div class="drv-empty-icon">'
                            +     '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>'
                            +   '</div>'
                            +   '<div class="drv-empty-title">Todavía no tenés pedidos en curso</div>'
                            +   '<div class="drv-empty-sub">Cuando tomes una oferta de la pestaña anterior, el pedido aparecerá acá para que puedas gestionarlo y confirmar la entrega.</div>'
                            + '</div>';
                    }

                    /* ── Cargas ── */
                    function loadAvailable() {
                        var box = document.getElementById('dash-delivery-orders-list');
                        fetch('{{ route("api.delivery.orders") }}', { headers: { 'Accept': 'application/json' } })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                var list = (json && json.success && json.orders) ? json.orders : [];
                                document.getElementById('drv-count-available').textContent = list.length;

                                var newOnes = list.filter(function (o) { return lastAvailableIds.indexOf(o.id) === -1; });
                                if (lastAvailableIds.length && newOnes.length && connected) beep(880, 0.16);
                                lastAvailableIds = list.map(function (o) { return o.id; });

                                box.innerHTML = list.length ? list.map(cardHtml).join('') : emptyHtml();
                            })
                            .catch(function () { box.innerHTML = emptyHtml(); });
                    }

                    function loadMyOrders() {
                        var box = document.getElementById('dash-my-orders-list');
                        if (!box) return;

                        // Si el repartidor está tipeando el PIN no se re-renderiza:
                        // el polling cada 10s le borraría lo escrito.
                        var typingPin = box.querySelector('.drv-pin-row:not([hidden])');

                        fetch('{{ route("api.delivery.orders.mine") }}', { headers: { 'Accept': 'application/json' } })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                var list = (json && json.success && json.orders) ? json.orders : [];
                                document.getElementById('drv-count-inprogress').textContent = list.length;
                                if (typingPin && box.querySelector('.drv-pin-row:not([hidden])')) return;
                                box.innerHTML = list.length ? list.map(myCardHtml).join('') : emptyMyHtml();
                            })
                            .catch(function () { if (!box.querySelector('.drv-pin-row:not([hidden])')) box.innerHTML = emptyMyHtml(); });
                    }

                    window.drvLoadAll = function () { loadAvailable(); loadMyOrders(); };

                    /* ── Acciones ── */
                    window.drvTakeOrder = function (orderId) {
                        fetch('{{ url("/api/v1/delivery/orders") }}/' + orderId + '/take', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (!json.success) { alert(json.message || 'No se pudo tomar el pedido.'); return; }
                                beep(1046, 0.2);
                                alert('¡Pedido tomado con éxito! Ya está en tus pedidos en curso.');
                                drvLoadAll();
                                drvSwitchTab('inprogress');
                            });
                    };

                    /* Paso 1 -> 2: el repartidor confirma que retiró el pedido. */
                    window.drvPickup = function (orderId) {
                        fetch('{{ url("/api/v1/delivery/orders") }}/' + orderId + '/pickup', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (!json.success) { alert(json.message || 'No se pudo actualizar el pedido.'); return; }
                                beep(988, 0.18);
                                drvLoadAll();
                            });
                    };

                    /* Abre el campo del PIN sin perder lo que el repartidor ya tipeó. */
                    window.drvOpenPin = function (orderId) {
                        var row = document.getElementById('drv-pin-row-' + orderId);
                        if (!row) { drvConfirmDelivery(orderId); return; }
                        row.hidden = false;
                        var input = document.getElementById('drv-pin-' + orderId);
                        if (input) input.focus();
                    };

                    window.drvConfirmDelivery = function (orderId) {
                        var input = document.getElementById('drv-pin-' + orderId);
                        var pin = input ? (input.value || '').trim() : '0';
                        if (input && !pin) {
                            alert('Ingresá el PIN de entrega para confirmar.');
                            input.focus();
                            return;
                        }
                        fetch('{{ url("/api/v1/delivery/orders") }}/' + orderId + '/confirm', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                            body: JSON.stringify({ delivery_pin: pin || '0' })
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (!json.success) { alert(json.message || 'No se pudo confirmar la entrega.'); return; }
                                beep(1318, 0.25);
                                alert(json.message || '¡Entrega confirmada!');
                                drvLoadAll();
                            });
                    };

                    /* ── Init ── */
                    initToolbar();
                    initGps();
                    initGoal();
                    drvLoadAll();
                    setInterval(function () { if (connected) drvLoadAll(); }, 10000);
                })();
                </script>
            </section>
