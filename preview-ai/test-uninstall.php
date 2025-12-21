<?php
// Cargar el entorno de WordPress
require_once('../../../wp-load.php');

// Forzar la constante que pide uninstall.php
define('WP_UNINSTALL_PLUGIN', true);

echo "Iniciando limpieza...\n";
include('uninstall.php');
echo "Limpieza completada. Ya puedes borrar este archivo.";