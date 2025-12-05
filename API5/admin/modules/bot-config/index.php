<?php
/**
 * Configuración del Chatbot BOT
 *
 * Permite configurar los parámetros del modelo de IA del chatbot
 * Guarda en archivo JSON para evitar uso de base de datos
 */

$success = '';
$error = '';

// Ruta del archivo de configuración
$settingsFile = API_BASE_DIR . '/bot/bot-settings.json';

// Cargar configuración actual
function loadBotSettings($file) {
    if (!file_exists($file)) {
        return [
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'tone' => 'profesional',
            'max_history' => 10
        ];
    }

    $json = file_get_contents($file);
    $settings = json_decode($json, true);

    if (!$settings) {
        return [
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'tone' => 'profesional',
            'max_history' => 10
        ];
    }

    return $settings;
}

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bot_config'])) {
    try {
        $settings = [
            'model' => $_POST['model'] ?? 'gpt-4o',
            'temperature' => floatval($_POST['temperature'] ?? 0.7),
            'max_tokens' => intval($_POST['max_tokens'] ?? 1000),
            'tone' => sanitize_text_field($_POST['tone'] ?? 'profesional'),
            'max_history' => intval($_POST['max_history'] ?? 10),
            'updated_at' => date('c'),
            'updated_by' => $_SESSION['username'] ?? 'admin'
        ];

        // Validaciones
        if ($settings['temperature'] < 0) $settings['temperature'] = 0;
        if ($settings['temperature'] > 2) $settings['temperature'] = 2;
        if ($settings['max_tokens'] < 100) $settings['max_tokens'] = 100;
        if ($settings['max_tokens'] > 4000) $settings['max_tokens'] = 4000;
        if ($settings['max_history'] < 1) $settings['max_history'] = 1;
        if ($settings['max_history'] > 50) $settings['max_history'] = 50;

        // Guardar en archivo JSON
        $json = json_encode($settings, JSON_PRETTY_PRINT);
        if (file_put_contents($settingsFile, $json) !== false) {
            $success = '✅ Configuración del chatbot guardada correctamente.';
        } else {
            $error = '❌ Error: No se pudo escribir el archivo de configuración.';
        }
    } catch (Exception $e) {
        $error = '❌ Error: ' . $e->getMessage();
    }
}

// Cargar settings actuales
$settings = loadBotSettings($settingsFile);

// Helper function para sanitización
function sanitize_text_field($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Modelos disponibles
$availableModels = [
    'gpt-4o' => 'GPT-4o - Multimodal equilibrado, calidad alta',
    'gpt-4o-mini' => 'GPT-4o Mini - Muy barato y veloz, multimodal',
    'gpt-4.1' => 'GPT-4.1 - Texto de alta calidad, razonamiento sólido',
    'gpt-4.1-mini' => 'GPT-4.1 Mini - Rápido y barato, buena calidad',
    'gpt-5' => 'GPT-5 - Máxima calidad de razonamiento (costoso)',
    'gpt-5-mini' => 'GPT-5 Mini - Rápido y económico, buen razonamiento'
];
?>

<style>
.bot-config-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.bot-config-card h3 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.info-box {
    background: #e7f3ff;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin-bottom: 20px;
}
.warning-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin-bottom: 20px;
    font-size: 14px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}
.form-group select,
.form-group input[type="number"],
.form-group input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.form-group small {
    color: #6c757d;
    font-size: 13px;
    display: block;
    margin-top: 5px;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
.btn {
    padding: 12px 24px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-primary:hover {
    background: #0056b3;
}
.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.config-meta {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    font-size: 13px;
    color: #6c757d;
}
</style>

<h2>⚙️ Configuración del Chatbot</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="warning-box">
    <strong>⚠️ Importante:</strong> Estos parámetros afectan directamente al costo de las peticiones y la calidad de las respuestas.
    Los modelos más avanzados (GPT-5, GPT-4.1) son más costosos pero ofrecen mejor calidad.
    Los modelos "mini" son más económicos y rápidos pero pueden tener menor calidad en respuestas complejas.
</div>

<form method="POST" action="">
    <div class="bot-config-card">
        <h3>Modelo de IA</h3>

        <div class="form-group">
            <label for="model">Modelo</label>
            <select name="model" id="model">
                <?php foreach ($availableModels as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($settings['model'] === $value) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>El modelo determina la calidad y costo de las respuestas. Afecta directamente los precios de tu licenciamiento.</small>
        </div>
    </div>

    <div class="bot-config-card">
        <h3>Parámetros del Modelo</h3>

        <div class="form-grid">
            <div class="form-group">
                <label for="temperature">Temperatura (Creatividad)</label>
                <input type="number"
                       name="temperature"
                       id="temperature"
                       step="0.1"
                       min="0"
                       max="2"
                       value="<?php echo htmlspecialchars($settings['temperature']); ?>">
                <small>Entre 0 (más preciso) y 2 (más creativo). Recomendado: 0.7</small>
            </div>

            <div class="form-group">
                <label for="max_tokens">Máximo de Tokens por Respuesta</label>
                <input type="number"
                       name="max_tokens"
                       id="max_tokens"
                       step="50"
                       min="100"
                       max="4000"
                       value="<?php echo htmlspecialchars($settings['max_tokens']); ?>">
                <small>Límite de tokens para cada respuesta. Afecta costo y longitud. Recomendado: 1000</small>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="tone">Tono de las Respuestas</label>
                <input type="text"
                       name="tone"
                       id="tone"
                       value="<?php echo htmlspecialchars($settings['tone']); ?>"
                       placeholder="profesional">
                <small>Ej: profesional, amigable, técnico, casual</small>
            </div>

            <div class="form-group">
                <label for="max_history">Mensajes de Historial</label>
                <input type="number"
                       name="max_history"
                       id="max_history"
                       min="1"
                       max="50"
                       value="<?php echo htmlspecialchars($settings['max_history']); ?>">
                <small>Número de mensajes previos a incluir en el contexto. Afecta costo. Recomendado: 10</small>
            </div>
        </div>
    </div>

    <div class="bot-config-card">
        <button type="submit" name="save_bot_config" class="btn btn-primary">💾 Guardar Configuración</button>

        <?php if (isset($settings['updated_at'])): ?>
            <div class="config-meta">
                Última actualización: <?php echo htmlspecialchars($settings['updated_at']); ?>
                <?php if (isset($settings['updated_by'])): ?>
                    por <?php echo htmlspecialchars($settings['updated_by']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</form>

<div class="info-box">
    <strong>ℹ️ Nota:</strong> Estos ajustes se aplican automáticamente a todas las peticiones del chatbot en todos los sitios con licencia BOT.
    Los cambios son inmediatos. El archivo de configuración se encuentra en <code>/bot/bot-settings.json</code>.
</div>
