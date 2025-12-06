<?php
/**
 * Script para aplicar migración 011 - Agregar columnas de pricing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

echo "<h1>Aplicando Migración 011: Pricing en usage_tracking</h1>\n\n";

try {
    $db = Database::getInstance();

    echo "<h2>1. Verificando columnas actuales...</h2>\n";

    // Verificar columnas existentes
    $stmt = $db->prepare("SHOW COLUMNS FROM " . DB_PREFIX . "usage_tracking");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $existingColumns = array_column($columns, 'Field');

    echo "<p>Columnas actuales: " . implode(', ', $existingColumns) . "</p>\n";

    $newColumns = ['model', 'tokens_input', 'tokens_output', 'cost_input', 'cost_output', 'cost_total', 'campaign_id', 'campaign_name', 'batch_id', 'batch_type'];
    $missingColumns = array_diff($newColumns, $existingColumns);

    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ Todas las columnas ya existen. No se necesita migración.</p>\n";
        exit;
    }

    echo "<p style='color: orange;'>⚠️ Columnas faltantes: " . implode(', ', $missingColumns) . "</p>\n";

    echo "<h2>2. Aplicando migración...</h2>\n";

    // Leer y ejecutar la migración
    $migrationSQL = file_get_contents(__DIR__ . '/migrations/011_alter_usage_tracking_add_pricing.sql');

    // Dividir por ; pero mantener los que están dentro de UPDATE
    $statements = preg_split('/;\s*(?=(?:[^\'"]|[\'"][^\'"]*[\'"])*$)/', $migrationSQL, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            echo "<p>Ejecutando: " . substr($statement, 0, 100) . "...</p>\n";
            $db->exec($statement);
            echo "<p style='color: green;'>✅ OK</p>\n";
        } catch (Exception $e) {
            // Si la columna ya existe, continuar
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color: blue;'>ℹ️ Columna ya existe, saltando...</p>\n";
            } else {
                throw $e;
            }
        }
    }

    echo "<h2>3. Verificando resultado...</h2>\n";

    // Verificar columnas nuevamente
    $stmt = $db->prepare("SHOW COLUMNS FROM " . DB_PREFIX . "usage_tracking");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>\n";
    foreach ($columns as $col) {
        $isNew = in_array($col['Field'], $newColumns);
        $style = $isNew ? "background: lightgreen;" : "";
        echo "<tr style='$style'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>4. Probando cálculo de costos...</h2>\n";

    // Obtener algunos registros y mostrar costos
    $stmt = $db->prepare("SELECT * FROM " . DB_PREFIX . "usage_tracking ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($records)) {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Operation</th><th>Model</th><th>Tokens In</th><th>Tokens Out</th><th>Cost Total</th><th>Created</th></tr>\n";
        foreach ($records as $rec) {
            echo "<tr>";
            echo "<td>{$rec['id']}</td>";
            echo "<td>{$rec['operation_type']}</td>";
            echo "<td>{$rec['model']}</td>";
            echo "<td>{$rec['tokens_input']}</td>";
            echo "<td>{$rec['tokens_output']}</td>";
            echo "<td>\$" . number_format($rec['cost_total'], 6) . "</td>";
            echo "<td>{$rec['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No hay registros aún.</p>\n";
    }

    echo "<hr>\n";
    echo "<h2 style='color: green;'>✅ Migración completada exitosamente!</h2>\n";
    echo "<p>La tabla <code>api_usage_tracking</code> ahora tiene todas las columnas necesarias para calcular costos reales.</p>\n";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
