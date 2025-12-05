<?php
/**
 * Test POST handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('API_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

echo "<h1>Test Settings POST</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Received</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    try {
        $db = Database::getInstance();

        echo "<p>Saving bot_ai_model...</p>";
        $model = $_POST['bot_ai_model'] ?? 'test-model';

        $stmt = $db->prepare("INSERT INTO " . DB_PREFIX . "settings (setting_key, setting_value, setting_type)
                              VALUES ('bot_ai_model', ?, 'string')
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$model, $model]);

        echo "<p style='color: green;'>✅ Saved successfully</p>";

        // Read back
        $stmt = $db->prepare("SELECT setting_value FROM " . DB_PREFIX . "settings WHERE setting_key = 'bot_ai_model'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Value in DB: <strong>" . htmlspecialchars($result['setting_value']) . "</strong></p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }

} else {
    echo "<p>Send a test POST:</p>";
    ?>
    <form method="POST">
        <label>Model:</label>
        <select name="bot_ai_model">
            <option value="gpt-4o">gpt-4o</option>
            <option value="gpt-4o-mini">gpt-4o-mini</option>
            <option value="gpt-4-turbo">gpt-4-turbo</option>
        </select>
        <button type="submit">Test Save</button>
    </form>
    <?php
}

echo "<hr>";
echo "<p>If this page is working, the issue is somewhere else.</p>";
?>
