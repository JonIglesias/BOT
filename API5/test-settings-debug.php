<?php
/**
 * Debug script para verificar settings de AI
 */

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();

    echo "<h2>1. Modelos en api_model_prices:</h2>";
    $stmt = $db->prepare("SELECT id, model_name, is_active FROM " . DB_PREFIX . "model_prices ORDER BY is_active DESC, model_name");
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($models)) {
        echo "<p style='color: red;'>⚠️ NO HAY MODELOS EN LA TABLA api_model_prices</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Modelo</th><th>Activo</th></tr>";
        foreach ($models as $model) {
            $color = $model['is_active'] ? 'green' : 'gray';
            echo "<tr>";
            echo "<td>{$model['id']}</td>";
            echo "<td>{$model['model_name']}</td>";
            echo "<td style='color: $color;'>" . ($model['is_active'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2>2. Settings de AI en api_settings:</h2>";
    $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM " . DB_PREFIX . "settings WHERE setting_key LIKE '%_ai_%' ORDER BY setting_key");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($settings)) {
        echo "<p style='color: red;'>⚠️ NO HAY SETTINGS DE AI EN LA TABLA api_settings</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Key</th><th>Value</th><th>Type</th></tr>";
        foreach ($settings as $setting) {
            echo "<tr>";
            echo "<td>{$setting['setting_key']}</td>";
            echo "<td><strong>{$setting['setting_value']}</strong></td>";
            echo "<td>{$setting['setting_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2>3. Modelos activos disponibles para selectores:</h2>";
    $stmt = $db->prepare("SELECT DISTINCT model_name FROM " . DB_PREFIX . "model_prices WHERE is_active = 1 ORDER BY model_name");
    $stmt->execute();
    $availableModels = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($availableModels)) {
        echo "<p style='color: red;'>⚠️ NO HAY MODELOS ACTIVOS - Los selectores estarán vacíos</p>";
    } else {
        echo "<ul>";
        foreach ($availableModels as $model) {
            echo "<li>$model</li>";
        }
        echo "</ul>";
    }

    echo "<h2>4. Valores actuales de CONSTANTS:</h2>";
    echo "<ul>";
    echo "<li><strong>BOT_DEFAULT_MODEL:</strong> " . (defined('BOT_DEFAULT_MODEL') ? BOT_DEFAULT_MODEL : 'NO DEFINIDA') . "</li>";
    echo "<li><strong>OPENAI_MODEL:</strong> " . (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'NO DEFINIDA') . "</li>";
    echo "<li><strong>OPENAI_MAX_TOKENS:</strong> " . (defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 'NO DEFINIDA') . "</li>";
    echo "<li><strong>OPENAI_TEMPERATURE:</strong> " . (defined('OPENAI_TEMPERATURE') ? OPENAI_TEMPERATURE : 'NO DEFINIDA') . "</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
