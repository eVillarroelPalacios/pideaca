{{-- Configuración dinámica del formulario de Presupuesto por rubro. --}}
{{-- Los subgrupos solo tienen 'description' (no hay code estable), por eso --}}
{{-- se resuelve por keywords normalizadas hacia un slug de rubro. --}}
var FD_QUOTE_FALLBACK = {
    slug: 'generic',
    label: 'Servicios del hogar',
    icon: '🏠',
    quickTags: [
        { value: 'Necesito una visita para evaluar el trabajo' },
        { value: 'Es un trabajo urgente' },
        { value: 'Quiero comparar precios antes de decidir' },
        { value: 'Tengo fotos del problema' }
    ],
    questions: [
        {
            id: 'property_type',
            label: '¿Dónde hay que hacer el trabajo?',
            type: 'radio',
            default: 'casa',
            options: [
                { value: 'casa', label: 'Casa' },
                { value: 'departamento', label: 'Departamento' },
                { value: 'local', label: 'Local / Comercio' },
                { value: 'otro', label: 'Otro' }
            ]
        },
        {
            id: 'materials',
            label: '¿Quién provee los materiales?',
            type: 'radio',
            default: 'profesional',
            options: [
                { value: 'profesional', label: 'Los provee el profesional' },
                { value: 'cliente', label: 'Los compro yo' },
                { value: 'combinar', label: 'Lo vemos juntos' }
            ]
        }
    ],
    placeholders: {
        details: 'Ej: Contame qué necesitás, hace cuánto pasa y cualquier detalle que ayude a cotizar.',
        photos: 'Subí fotos del problema, lugar o material (1 a 5).'
    }
};

var FD_QUOTE_CONFIGS = {
    plomeria: {
        slug: 'plomeria',
        label: 'Plomería y Gas',
        icon: '🚿',
        keywords: ['plom', 'agua', 'gas', 'destap', 'cano', 'canilla', 'termotanque', 'calefon'],
        quickTags: [
            { value: 'Pérdida / filtración de agua' },
            { value: 'Destapación de cañería' },
            { value: 'Termotanque o calefón' },
            { value: 'Instalación de grifería' },
            { value: 'Cambio de caño' },
            { value: 'Sin agua caliente' }
        ],
        questions: [
            {
                id: 'property_type',
                label: '¿Dónde hay que hacer el trabajo?',
                type: 'radio',
                default: 'casa',
                options: [
                    { value: 'casa', label: 'Casa' },
                    { value: 'departamento', label: 'Departamento' },
                    { value: 'local', label: 'Local / Comercio' },
                    { value: 'otro', label: 'Otro' }
                ]
            },
            {
                id: 'urgency',
                label: '¿Qué tan urgente es?',
                type: 'radio',
                default: 'esta_semana',
                options: [
                    { value: 'ahora', label: 'Es una emergencia ahora' },
                    { value: 'hoy', label: 'Hoy o mañana' },
                    { value: 'esta_semana', label: 'Esta semana' },
                    { value: 'sin_apuro', label: 'Sin apuro' }
                ]
            },
            {
                id: 'materials',
                label: '¿Quién provee los materiales?',
                type: 'radio',
                default: 'profesional',
                options: [
                    { value: 'profesional', label: 'Los provee el profesional' },
                    { value: 'cliente', label: 'Los compro yo' },
                    { value: 'combinar', label: 'Lo vemos juntos' }
                ]
            }
        ],
        placeholders: {
            details: 'Ej: Pérdida debajo de la bacha de la cocina, gotea desde ayer y ya cerré la llave de paso.',
            photos: 'Subí fotos de la pérdida, cañería o equipo (1 a 5).'
        }
    },
    electricidad: {
        slug: 'electricidad',
        label: 'Electricidad',
        icon: '⚡',
        keywords: ['elect', 'luz', 'cable', 'tablero', 'disyunt', 'enchufe', 'luminaria', 'cortocircuito'],
        quickTags: [
            { value: 'Salta la térmica / disyuntor' },
            { value: 'Corto circuito' },
            { value: 'Instalación de enchufes o tomas' },
            { value: 'Colocación de luminarias' },
            { value: 'Renovación de tablero' },
            { value: 'Sin luz en un ambiente' }
        ],
        questions: [
            {
                id: 'property_type',
                label: '¿Dónde hay que hacer el trabajo?',
                type: 'radio',
                default: 'departamento',
                options: [
                    { value: 'casa', label: 'Casa' },
                    { value: 'departamento', label: 'Departamento' },
                    { value: 'local', label: 'Local / Comercio' },
                    { value: 'otro', label: 'Otro' }
                ]
            },
            {
                id: 'urgency',
                label: '¿Qué tan urgente es?',
                type: 'radio',
                default: 'esta_semana',
                options: [
                    { value: 'ahora', label: 'Es una emergencia ahora' },
                    { value: 'hoy', label: 'Hoy o mañana' },
                    { value: 'esta_semana', label: 'Esta semana' },
                    { value: 'sin_apuro', label: 'Sin apuro' }
                ]
            },
            {
                id: 'materials',
                label: '¿Quién provee los materiales?',
                type: 'radio',
                default: 'profesional',
                options: [
                    { value: 'profesional', label: 'Los provee el profesional' },
                    { value: 'cliente', label: 'Los compro yo' },
                    { value: 'combinar', label: 'Lo vemos juntos' }
                ]
            }
        ],
        placeholders: {
            details: 'Ej: Al encender el aire acondicionado salta la térmica del tablero principal.',
            photos: 'Subí fotos del tablero, toma o sector afectado (1 a 5).'
        }
    },
    pintura: {
        slug: 'pintura',
        label: 'Pintura',
        icon: '🎨',
        keywords: ['pint', 'yeso', 'durlock', 'empapel', 'latex', 'revoque'],
        quickTags: [
            { value: 'Pintar un ambiente' },
            { value: 'Pintar toda la casa' },
            { value: 'Reparar humedad / goteras' },
            { value: 'Enduido y lijado' },
            { value: 'Pintura exterior / fachada' },
            { value: 'Colocar durlock' }
        ],
        questions: [
            {
                id: 'property_type',
                label: '¿Dónde hay que hacer el trabajo?',
                type: 'radio',
                default: 'departamento',
                options: [
                    { value: 'casa', label: 'Casa' },
                    { value: 'departamento', label: 'Departamento' },
                    { value: 'local', label: 'Local / Comercio' },
                    { value: 'otro', label: 'Otro' }
                ]
            },
            {
                id: 'surface',
                label: '¿Qué superficie aproximada?',
                type: 'radio',
                default: 'un_ambiente',
                options: [
                    { value: 'un_ambiente', label: 'Un ambiente' },
                    { value: 'dos_tres', label: '2 a 3 ambientes' },
                    { value: 'toda_la_casa', label: 'Toda la casa' },
                    { value: 'exterior', label: 'Exterior / fachada' }
                ]
            },
            {
                id: 'materials',
                label: '¿Quién provee la pintura y materiales?',
                type: 'radio',
                default: 'cliente',
                options: [
                    { value: 'profesional', label: 'Los provee el profesional' },
                    { value: 'cliente', label: 'Los compro yo' },
                    { value: 'combinar', label: 'Lo vemos juntos' }
                ]
            }
        ],
        placeholders: {
            details: 'Ej: Quiero pintar un living-comedor de 20 m², las paredes están en buen estado.',
            photos: 'Subí fotos de las paredes / ambiente a pintar (1 a 5).'
        }
    }
};

// Resuelve la config del rubro a partir del prestador elegido.
function fdQuoteSlugOf(provider) {
    var raw = '';
    if (provider && provider.subgroups && provider.subgroups.length) {
        raw = provider.subgroups[0].description || '';
    }
    var text = raw.toString().toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    var keys = Object.keys(FD_QUOTE_CONFIGS);
    for (var i = 0; i < keys.length; i++) {
        var words = FD_QUOTE_CONFIGS[keys[i]].keywords || [];
        for (var j = 0; j < words.length; j++) {
            if (text.indexOf(words[j]) !== -1) return keys[i];
        }
    }
    return 'generic';
}

function fdQuoteConfigFor(provider) {
    return FD_QUOTE_CONFIGS[fdQuoteSlugOf(provider)] || FD_QUOTE_FALLBACK;
}
