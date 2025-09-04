<?php
// Script para eliminar la restricción problemática de invoices
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
    
    echo "=== ELIMINANDO RESTRICCIÓN PROBLEMÁTICA ===\n";
    
    // 1. Verificar si la restricción existe
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'invoices' 
        AND CONSTRAINT_NAME = 'fk_invoices_patient'
    ");
    
    if ($stmt->rowCount() > 0) {
        echo "Restricción 'fk_invoices_patient': EXISTE\n";
        echo "Eliminando restricción...\n";
        
        // Eliminar la restricción
        $db->exec("ALTER TABLE invoices DROP FOREIGN KEY fk_invoices_patient");
        echo "✓ Restricción eliminada exitosamente\n";
    } else {
        echo "Restricción 'fk_invoices_patient': NO EXISTE\n";
    }
    
    // 2. Verificar estructura actual de la tabla
    echo "\n=== ESTRUCTURA ACTUAL DE INVOICES ===\n";
    $stmt = $db->query("DESCRIBE invoices");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
             ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
    }
    
    // 3. Verificar restricciones restantes
    echo "\n=== RESTRICCIONES ACTUALES ===\n";
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'invoices'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $stmt->fetchAll();
    if (empty($constraints)) {
        echo "No hay restricciones de clave foránea\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "- {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
    }
    
    // 4. Asegurar que patient_id puede ser NULL
    echo "\n=== VERIFICANDO COLUMNA PATIENT_ID ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM invoices WHERE Field = 'patient_id'");
    $patientIdColumn = $stmt->fetch();
    
    if ($patientIdColumn) {
        echo "Columna patient_id: {$patientIdColumn['Type']} " . 
             ($patientIdColumn['Null'] === 'YES' ? 'PERMITE NULL' : 'NO PERMITE NULL') . "\n";
        
        if ($patientIdColumn['Null'] === 'NO') {
            echo "Modificando columna para permitir NULL...\n";
            $db->exec("ALTER TABLE invoices MODIFY COLUMN patient_id VARCHAR(36) DEFAULT NULL");
            echo "✓ Columna modificada para permitir NULL\n";
        }
    }
    
    // 5. Test de inserción
    echo "\n=== TEST DE INSERCIÓN ===\n";
    try {
        $testInvoiceNumber = 'TEST-' . time();
        $stmt = $db->prepare("
            INSERT INTO invoices (
                invoice_number, clinic_id, patient_id, subtotal, tax_amount, total_amount, invoice_date, status
            ) VALUES (?, ?, NULL, 1000.00, 210.00, 1210.00, CURDATE(), 'draft')
        ");
        
        // Use a valid user_id from user_profiles
        $stmt2 = $db->query("SELECT user_id FROM user_profiles WHERE role = 'user' LIMIT 1");
        $testUser = $stmt2->fetch();
        
        if ($testUser) {
            $stmt->execute([$testInvoiceNumber, $testUser['user_id']]);
            echo "✓ Test de inserción: EXITOSO\n";
            
            // Limpiar test
            $db->prepare("DELETE FROM invoices WHERE invoice_number = ?")->execute([$testInvoiceNumber]);
            echo "✓ Test limpiado\n";
        } else {
            echo "⚠ No hay usuarios disponibles para test\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Test de inserción falló: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== COMPLETADO ===\n";
    echo "La tabla invoices está lista para crear facturas de suscripción\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>