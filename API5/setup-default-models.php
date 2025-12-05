<?php
/**
 * Setup script - Insertar modelos por defecto si no existen
 */

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();

    echo "<h2>Verificando tabla api_model_prices...</h2>";

    // Verificar si hay modelos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM " . DB_PREFIX . "model_prices");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'];

    echo "<p>Modelos encontrados: <strong>$total</strong></p>";

    if ($total == 0) {
        echo "<p style='color: orange;'>⚠️ No hay modelos en la base de datos. Insertando modelos por defecto...</p>";

        $defaultModels = [
            ['gpt-4o-mini', 0.00015, 0.0006, 'openai_pricing_nov2024', 'Modelo más barato y recomendado'],
            ['gpt-4o', 0.005, 0.015, 'openai_pricing_nov2024', 'Modelo equilibrado'],
            ['gpt-4-turbo', 0.01, 0.03, 'openai_pricing_nov2024', 'Modelo rápido'],
            ['gpt-4', 0.03, 0.06, 'openai_pricing_nov2024', 'Modelo premium'],
            ['gpt-3.5-turbo', 0.0005, 0.0015, 'openai_pricing_nov2024', 'Modelo legacy'],
            ['claude-3-5-sonnet', 0.003, 0.015, 'anthropic_pricing_nov2024', 'Claude Sonnet 3.5'],
            ['claude-3-opus', 0.015, 0.075, 'anthropic_pricing_nov2024', 'Claude Opus'],
            ['claude-3-sonnet', 0.003, 0.015, 'anthropic_pricing_nov2024', 'Claude Sonnet'],
            ['claude-3-haiku', 0.00025, 0.00125, 'anthropic_pricing_nov2024', 'Claude Haiku']
        ];

        foreach ($defaultModels as $modelData) {
            $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "model_prices
                (model_name, price_input_per_1k, price_output_per_1k, source, notes, is_active, updated_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute($modelData);
            echo "<p>✅ Insertado: {$modelData[0]}</p>";
        }

        echo "<p style='color: green;'><strong>✅ Modelos insertados correctamente</strong></p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla ya tiene modelos</p>";
    }

    echo "<h2>Verificando settings de AI...</h2>";

    // Verificar si hay settings de AI
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM " . DB_PREFIX . "settings WHERE setting_key LIKE '%_ai_%'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSettings = $result['total'];

    echo "<p>Settings de AI encontrados: <strong>$totalSettings</strong></p>";

    if ($totalSettings == 0) {
        echo "<p style='color: orange;'>⚠️ No hay settings de AI. Insertando configuración por defecto...</p>";

        $defaultSettings = [
            ['geowrite_ai_model', 'gpt-4o', 'string'],
            ['geowrite_ai_temperature', '0.7', 'float'],
            ['geowrite_ai_max_tokens', '2000', 'integer'],
            ['geowrite_ai_tone', 'profesional', 'string'],
            ['bot_ai_model', 'gpt-4o', 'string'],
            ['bot_ai_temperature', '0.7', 'float'],
            ['bot_ai_max_tokens', '1000', 'integer'],
            ['bot_ai_tone', 'profesional', 'string'],
            ['bot_ai_max_history', '10', 'integer']
        ];

        foreach ($defaultSettings as $settingData) {
            $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "settings
                (setting_key, setting_value, setting_type)
                VALUES (?, ?, ?)");
            $stmt->execute($settingData);
            echo "<p>✅ Insertado: {$settingData[0]} = {$settingData[1]}</p>";
        }

        echo "<p style='color: green;'><strong>✅ Settings insertados correctamente</strong></p>";
    } else {
        echo "<p style='color: green;'>✅ Ya existen settings de AI</p>";
    }

    echo "<hr><p><strong>Setup completado. Puedes cerrar esta ventana.</strong></p>";
    echo "<p><a href='admin/?module=settings'>Ir al módulo de Configuración</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
