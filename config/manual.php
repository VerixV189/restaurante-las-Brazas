<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subdominio dedicado del Manual de Usuario Web
    |--------------------------------------------------------------------------
    |
    | Permite definir un dominio o subdominio explícito (ej. manual.lasbrazas.bo).
    | Además, cualquier host cuyo primer segmento sea "manual." será enrutado
    | automáticamente al Manual Interactivo en la raíz "/".
    |
    */
    'domain' => env('MANUAL_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Archivo PDF descargable del Manual Completo
    |--------------------------------------------------------------------------
    */
    'pdf_filename' => 'Manual_Usuario_Completo_Las_Brazas.pdf',

    /*
    |--------------------------------------------------------------------------
    | Versión de documentación sincronizada con el sistema
    |--------------------------------------------------------------------------
    */
    'version' => '2.4.0',
];
