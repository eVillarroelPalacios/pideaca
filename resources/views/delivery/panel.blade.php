<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PideAca - Panel de Repartidor (Delivery)</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:'Inter',sans-serif;color:#1e293b;">
    <div style="max-width:1000px;margin:0 auto;">
        
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:20px;font-weight:700;color:#0c2a4d;margin:0;">Panel de Repartidores (Módulo C)</h1>
                <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Hola, {{ $user->name }} | Zona de Entregas PideAca</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <div id="wallet-balance-box" style="background:#fff;border:1px solid #cbd5e1;padding:8px 14px;border-radius:6px;font-size:13px;font-weight:600;color:#0c2a4d;">
                    Saldo Billetera: $ <span id="wallet-balance">0.00</span>
                </div>
                <a href="{{ url('/dashboard') }}" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">← Volver al Panel</a>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:24px;">
            <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0 0 14px;">Pedidos Disponibles para Retirar y Entregar</h3>
            <div id="delivery-orders-list">
                <p style="color:#64748b;font-size:13px;">Cargando pedidos disponibles...</p>
            </div>
        </div>

    </div>

    <script>
        function loadDeliveryOrders() {
            fetch('{{ route("api.delivery.orders") }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (!json.success) return;
                var list = json.orders || [];
                var container = document.getElementById('delivery-orders-list');
                if (!list.length) {
                    container.innerHTML = '<p style="color:#94a3b8;font-style:italic;font-size:13px;margin:0;">No hay pedidos de plataforma disponibles en este momento.</p>';
                    return;
                }

                var html = '';
                list.forEach(function(o) {
                    var shop = o.provider || {};
                    var addr = o.address || {};
                    html += '<div style="border:1px solid #e2e8f0;border-radius:6px;padding:14px;margin-bottom:12px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">'
                        + '<div style="min-width:0;flex:1;">'
                        + '<div style="font-size:13px;font-weight:700;color:#0c2a4d;">Pedido #' + (o.order_number || o.id) + ' — ' + (shop.business_name || 'Comercio') + '</div>'
                        + '<div style="font-size:12px;color:#475569;margin-top:4px;"><strong>Entrega en:</strong> ' + (addr.street || '') + ' ' + (addr.number || '') + '</div>'
                        + '<div style="font-size:12px;color:#d97706;font-weight:600;margin-top:2px;">Tarifa de envío: $ ' + Number(o.delivery_fee || 500).toFixed(2) + '</div>'
                        + '<div style="font-size:11px;color:#64748b;margin-top:2px;">Estado: ' + (o.status || '') + ' | delivery_status: ' + (o.delivery_status || '-') + ' | driver_id: ' + (o.driver_id || '-') + '</div>'
                        + '</div>'
                        + '<div style="display:flex;gap:8px;align-items:center;">'
                        + '<button type="button" onclick="takeOrder(' + o.id + ')" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Asignarme este pedido</button>'
                        + '</div>'
                        + '</div>';
                });
                container.innerHTML = html;
            });
        }

        function takeOrder(orderId) {
            var meta = document.querySelector('meta[name="csrf-token"]');
            fetch('{{ url("/api/v1/delivery/orders") }}/' + orderId + '/take', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': meta ? meta.content : ''
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (!json.success) {
                    alert(json.message || 'No se pudo tomar el pedido.');
                    return;
                }
                alert('¡Pedido tomado con éxito!');
                loadDeliveryOrders();
            });
        }

        loadDeliveryOrders();
        setInterval(loadDeliveryOrders, 10000);
    </script>
</body>
</html>
