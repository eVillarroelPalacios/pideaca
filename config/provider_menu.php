<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Menú del prestador según sus categorías
    |--------------------------------------------------------------------------
    |
    | El menú (navbar web y menú móvil) se arma con las páginas asignadas
    | al usuario, pero para el Prestador además se ocultan los módulos de
    | los grupos que NO eligió en sus categorías. Administrar va siempre.
    | Los módulos sin mapeo se conservan. Insensible a mayúsculas.
    |
    | Grupo (groups.description) => módulos (modules.description) del grupo.
    |
    */

    'always_visible' => ['administrar'],

    'groups' => [
        'comercio' => ['mercado'],
        'hogar' => ['hogar'],
    ],

];
