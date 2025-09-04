<?php
// Versión mínima para diagnosticar el error 500
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test básico
echo json_encode(['test' => 'API funcionando', 'timestamp' => date('Y-m-d H:i:s')]);
exit();
?>