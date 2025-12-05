<?php
/**
 * Instalador de Planes del Chatbot
 *
 * Script para crear/actualizar planes del chatbot en la base de datos
 * Ejecutar una vez para inicializar los planes
 *
 * @version 1.0
 */

define('API_ACCESS', true);

require_once __DIR__ . '/../config.php';

echo "===========================================\n";
echo "Instalador de Planes del Chatbot\n";
echo "===========================================\n\n";

try {
    // Conexión PDO directa
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $plans = [
        [
            'id' => 'bot_starter',
            'name' => 'Chatbot Starter',
            'tokens_per_month' => 50000,
            'billing_cycle' => 'monthly',
            'price' => 29.00,
            'currency' => 'EUR',
            'description' => 'Plan básico para sitios pequeños - 50,000 tokens/mes'
        ],
        [
            'id' => 'bot_pro',
            'name' => 'Chatbot Pro',
            'tokens_per_month' => 150000,
            'billing_cycle' => 'monthly',
            'price' => 79.00,
            'currency' => 'EUR',
            'description' => 'Plan profesional para sitios medianos - 150,000 tokens/mes'
        ],
        [
            'id' => 'bot_enterprise',
            'name' => 'Chatbot Enterprise',
            'tokens_per_month' => 500000,
            'billing_cycle' => 'monthly',
            'price' => 199.00,
            'currency' => 'EUR',
            'description' => 'Plan empresarial para sitios grandes - 500,000 tokens/mes'
        ]
    ];

    foreach ($plans as $plan) {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM " . DB_PREFIX . "plans WHERE id = ?");
        $stmt->execute([$plan['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Actualizar plan existente
            $stmt = $pdo->prepare("
                UPDATE " . DB_PREFIX . "plans
                SET name = ?,
                    tokens_per_month = ?,
                    billing_cycle = ?,
                    price = ?,
                    currency = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $plan['name'],
                $plan['tokens_per_month'],
                $plan['billing_cycle'],
                $plan['price'],
                $plan['currency'],
                $plan['id']
            ]);

            echo "✓ Plan actualizado: {$plan['id']} - {$plan['name']}\n";
        } else {
            // Crear nuevo plan
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "plans
                (id, name, tokens_per_month, billing_cycle, price, currency, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $plan['id'],
                $plan['name'],
                $plan['tokens_per_month'],
                $plan['billing_cycle'],
                $plan['price'],
                $plan['currency']
            ]);

            echo "✓ Plan creado: {$plan['id']} - {$plan['name']}\n";
        }
    }

    echo "\n===========================================\n";
    echo "Instalación completada exitosamente\n";
    echo "===========================================\n\n";

    echo "Planes instalados:\n";
    $stmt = $pdo->query("SELECT id, name, tokens_per_month, price FROM " . DB_PREFIX . "plans WHERE id LIKE 'bot%' ORDER BY tokens_per_month ASC");
    $installedPlans = $stmt->fetchAll();

    foreach ($installedPlans as $p) {
        echo "  • {$p['name']} ({$p['id']}): " . number_format($p['tokens_per_month']) . " tokens - €{$p['price']}/mes\n";
    }

    echo "\nPróximo paso:\n";
    echo "1. Crea productos en WooCommerce con estos planes\n";
    echo "2. Asegúrate que el nombre del producto contiene 'bot' o 'chatbot'\n";
    echo "3. Asocia el woo_product_id con cada plan en la tabla api_plans\n\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
