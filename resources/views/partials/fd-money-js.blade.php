{{-- Formateador de moneda compartido (dashboard, panel de repartidor, etc.).
     La configuracion sale de la tabla currencies (relacionada con countries),
     por lo que el simbolo y los separadores cambian segun el pais del usuario. --}}
@php($fdMoneyCurrency = $currency ?? \App\Support\CurrencyResolver::current())
    window.FD_CURRENCY = {
        code: @json($fdMoneyCurrency->code),
        symbol: @json($fdMoneyCurrency->symbol),
        position: @json($fdMoneyCurrency->symbol_position),
        space: @json((bool) $fdMoneyCurrency->symbol_space),
        decimals: {{ (int) $fdMoneyCurrency->decimals }},
        decimal_separator: @json($fdMoneyCurrency->decimal_separator),
        thousand_separator: @json($fdMoneyCurrency->thousand_separator)
    };

    function fdMoney(value) {
        var c = window.FD_CURRENCY || {
            symbol: '$', position: 'before', space: true, decimals: 2,
            decimal_separator: ',', thousand_separator: '.'
        };
        var n = Number(value || 0);
        var negative = n < 0;
        n = Math.abs(n);

        var decimals = Number(c.decimals == null ? 2 : c.decimals);
        var parts = n.toFixed(decimals).split('.');
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, c.thousand_separator || '.');
        var decPart = decimals > 0 ? (c.decimal_separator || ',') + (parts[1] || '') : '';
        var number = intPart + decPart;

        var space = c.space === false ? '' : ' ';
        var out = c.position === 'after'
            ? number + space + c.symbol
            : c.symbol + space + number;

        return negative ? '-' + out : out;
    }
