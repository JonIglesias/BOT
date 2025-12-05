<?php
/**
 * Test manual para verificar el guardado de settings
 */

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();

    echo "<h2>Test de Guardado de Settings</h2>";

    // Simular guardado
    if (isset($_GET['test']) && $_GET['test'] == 'save') {
        $testSettings = [
            'bot_ai_model' => 'gpt-4o-TEST',
            'bot_ai_temperature' => 0.8,
            'bot_ai_max_tokens' => 1500,
            'bot_ai_tone' => 'test-tone',
            'bot_ai_max_history' => 15
        ];

        echo "<h3>Guardando settings de prueba:</h3>";
        echo "<pre>" . print_r($testSettings, true) . "</pre>";

        foreach ($testSettings as $key => $value) {
            $type = is_numeric($value) ? (is_float($value) ? 'float' : 'integer') : 'string';
            $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "settings (setting_key, setting_value, setting_type)
                                  VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?");
            $stmt->execute([$key, $value, $type, $value, $type]);
            echo "<p>✅ Guardado: $key = $value</p>";
        }

        echo "<p><a href='?test=read'>Leer valores guardados</a></p>";
    }

    // Leer valores
    else if (isset($_GET['test']) && $_GET['test'] == 'read') {
        echo "<h3>Leyendo settings desde BD:</h3>";

        $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM " . DB_PREFIX . "settings WHERE setting_key LIKE 'bot_ai_%'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            echo "<p style='color: red;'>⚠️ NO HAY SETTINGS DE BOT EN LA BD</p>";
        } else {
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
        }

        echo "<p><a href='?test=clean'>Limpiar valores de prueba</a></p>";
    }

    // Limpiar
    else if (isset($_GET['test']) && $_GET['test'] == 'clean') {
        echo "<h3>Limpiando valores de prueba:</h3>";

        // Restaurar defaults
        $defaultSettings = [
            'bot_ai_model' => 'gpt-4o',
            'bot_ai_temperature' => '0.7',
            'bot_ai_max_tokens' => '1000',
            'bot_ai_tone' => 'profesional',
            'bot_ai_max_history' => '10'
        ];

        foreach ($defaultSettings as $key => $value) {
            $type = is_numeric($value) ? (is_float($value) ? 'float' : 'integer') : 'string';
            $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "settings (setting_key, setting_value, setting_type)
                                  VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?");
            $stmt->execute([$key, $value, $type, $value, $type]);
            echo "<p>✅ Restaurado: $key = $value</p>";
        }

        echo "<p>✅ Valores restaurados a defaults</p>";
        echo "<p><a href='?test=read'>Verificar valores</a></p>";
    }

    // Menu
    else {
        echo "<p>Selecciona una acción:</p>";
        echo "<ul>";
        echo "<li><a href='?test=save'>1. Guardar settings de prueba</a></li>";
        echo "<li><a href='?test=read'>2. Leer settings actuales</a></li>";
        echo "<li><a href='?test=clean'>3. Limpiar y restaurar defaults</a></li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
