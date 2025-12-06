<?php
/**
 * Debug script - Enable error display
 */

// Habilitar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('API_ACCESS', true);

echo "<h1>Testing Settings Module Load</h1>";

try {
    echo "<p>1. Loading config...</p>";
    require_once __DIR__ . '/config.php';

    echo "<p>2. Loading Database...</p>";
    require_once __DIR__ . '/core/Database.php';

    echo "<p>3. Getting DB instance...</p>";
    $db = Database::getInstance();

    echo "<p>4. Checking if module file exists...</p>";
    $modulePath = __DIR__ . '/admin/modules/settings/index.php';
    if (file_exists($modulePath)) {
        echo "<p style='color: green;'>✅ Module file exists</p>";
    } else {
        die("<p style='color: red;'>❌ Module file NOT found at: $modulePath</p>");
    }

    echo "<p>5. Checking file permissions...</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($modulePath)), -4) . "</p>";

    echo "<p>6. Attempting to include module...</p>";
    ob_start();

    // Simular que NO es POST para evitar procesamiento
    $_SERVER['REQUEST_METHOD'] = 'GET';

    include $modulePath;

    $output = ob_get_clean();

    echo "<p style='color: green;'>✅ Module loaded successfully</p>";
    echo "<p>Output length: " . strlen($output) . " bytes</p>";

    if (strlen($output) > 0) {
        echo "<hr>";
        echo "<h2>Module Output Preview (first 1000 chars):</h2>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 1000)) . "</pre>";
    } else {
        echo "<p style='color: red;'>⚠️ Module produced NO output</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>If you see this message, PHP is working correctly.</strong></p>";
echo "<p>Now test the actual admin panel: <a href='admin/?module=settings'>Go to Settings</a></p>";
?>
