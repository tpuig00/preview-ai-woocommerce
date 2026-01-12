<?php
// Cargar el entorno de WordPress
require_once('../../../wp-load.php');

// Forzar la constante que pide uninstall.php
define('WP_UNINSTALL_PLUGIN', true); 

echo "--- INICIANDO LIMPIEZA TOTAL ---\n";
echo "1. Cargando configuración de WordPress...\n";
include('uninstall.php');
echo "2. Borrando opciones del plugin...\n";
echo "3. Borrando metadatos de productos (postmeta)...\n";
echo "4. Borrando metadatos de usuarios (usermeta)...\n";
echo "5. Borrando tablas personalizadas...\n";
echo "6. Cancelando tareas programadas...\n";
echo "---------------------------------\n";
echo "LIMPIEZA COMPLETADA CON ÉXITO.\n";
echo "Ya puedes borrar este archivo y reinstalar el plugin desde cero.";