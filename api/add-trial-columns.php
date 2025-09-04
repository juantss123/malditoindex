<?php
// Script para agregar columnas faltantes a trial_requests
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    echo "=== AGREGANDO COLUMNAS FALTANTES ===\n";
    
    // Verificar si las columnas existen
    $columns = ['trial_website', 'trial_username', 'trial_password'];
    $columnsToAdd = [];
    
    foreach ($columns as $column) {
        $stmt = $db->query("SHOW COLUMNS FROM trial_requests LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $columnsToAdd[] = $column;
            echo "Columna '$column': FALTA\n";
        } else {
            echo "Columna '$column': EXISTE\n";
        }
    }
    
    if (empty($columnsToAdd)) {
        echo "\nTodas las columnas ya existen. No se necesitan cambios.\n";
    } else {
        echo "\nAgregando columnas faltantes...\n";
        
        // Agregar columnas una por una
        if (in_array('trial_website', $columnsToAdd)) {
            $db->exec("ALTER TABLE trial_requests ADD COLUMN trial_website VARCHAR(255) DEFAULT NULL");
            echo "✓ Columna 'trial_website' agregada\n";
        }
        
        if (in_array('trial_username', $columnsToAdd)) {
            $db->exec("ALTER TABLE trial_requests ADD COLUMN trial_username VARCHAR(100) DEFAULT NULL");
            echo "✓ Columna 'trial_username' agregada\n";
        }
        
        if (in_array('trial_password', $columnsToAdd)) {
            $db->exec("ALTER TABLE trial_requests ADD COLUMN trial_password VARCHAR(100) DEFAULT NULL");
            echo "✓ Columna 'trial_password' agregada\n";
        }
        
        echo "\n✅ Todas las columnas agregadas exitosamente!\n";
    }
    
    // Verificar estructura final
    echo "\n=== ESTRUCTURA FINAL ===\n";
    $stmt = $db->query("DESCRIBE trial_requests");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== COMPLETADO ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>