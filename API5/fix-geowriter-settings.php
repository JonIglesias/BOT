<?php
/**
 * Script para insertar settings de GeoWriter que faltan
 */

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();

    echo "<h2>Insertando Settings de GeoWriter</h2>";

    // Verificar qué settings existen
    $stmt = $db->prepare("SELECT setting_key FROM " . DB_PREFIX . "settings WHERE setting_key LIKE 'geowrite_ai_%'");
    $stmt->execute();
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Settings de GeoWriter existentes: " . count($existing) . "</p>";
    if (!empty($existing)) {
        echo "<ul>";
        foreach ($existing as $key) {
            echo "<li>$key</li>";
        }
        echo "</ul>";
    }

    // Settings que deben existir
    $requiredSettings = [
        'geowrite_ai_model' => ['value' => 'gpt-4o-mini', 'type' => 'string'],
        'geowrite_ai_temperature' => ['value' => '0.7', 'type' => 'float'],
        'geowrite_ai_max_tokens' => ['value' => '2000', 'type' => 'integer'],
        'geowrite_ai_tone' => ['value' => 'profesional', 'type' => 'string']
    ];

    echo "<h3>Insertando settings faltantes:</h3>";

    $inserted = 0;
    $updated = 0;

    foreach ($requiredSettings as $key => $data) {
        if (in_array($key, $existing)) {
            echo "<p>⚠️ $key ya existe, actualizando...</p>";
            $stmt = $db->prepare("UPDATE " . DB_PREFIX . "settings SET setting_value = ?, setting_type = ? WHERE setting_key = ?");
            $stmt->execute([$data['value'], $data['type'], $key]);
            $updated++;
        } else {
            echo "<p>✅ Insertando $key = {$data['value']}</p>";
            $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
            $stmt->execute([$key, $data['value'], $data['type']]);
            $inserted++;
        }
    }

    echo "<hr>";
    echo "<p><strong>Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>Settings insertados: $inserted</li>";
    echo "<li>Settings actualizados: $updated</li>";
    echo "</ul>";

    // Verificar resultado
    echo "<h3>Verificando settings de GeoWriter:</h3>";
    $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM " . DB_PREFIX . "settings WHERE setting_key LIKE 'geowrite_ai_%'");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Key</th><th>Value</th><th>Type</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['setting_key']}</td>";
        echo "<td><strong>{$row['setting_value']}</strong></td>";
        echo "<td>{$row['setting_type']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<p style='color: green;'><strong>✅ Completado. Ahora ve a <a href='admin/?module=settings'>Configuración</a> y prueba a guardar.</strong></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
