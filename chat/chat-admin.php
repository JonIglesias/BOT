<?php
// PHSBOT – chat/chat-admin.php
// Admin: menú y pantalla de configuración "Chat & Widget (FLOAT)".
if (!defined('ABSPATH')) exit;

/* Menús */
add_action('admin_menu', function(){
  add_menu_page('PHSBot', 'PHSBot', 'manage_options', 'phsbot', function(){
    echo '<div class="wrap"><h1>PHSBot</h1></div>';
  }, 'dashicons-format-chat', 60);

  add_submenu_page('phsbot', 'Chat & Widget (FLOAT)', 'Chat & Widget', 'manage_options', 'phsbot_chat', 'phsbot_render_chat_settings');
}, 60);

/* Render ajustes */
function phsbot_render_chat_settings(){
  if (!current_user_can('manage_options')) return;
  $saved = false;

  if ($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('phsbot_chat_save','phsbot_chat_nonce')){
    $opt = phsbot_chat_get_settings();

    $prev_welcome = (string)($opt['welcome'] ?? '');

    $opt['model']            = sanitize_text_field($_POST['model'] ?? 'gpt-4.1-mini');
    $opt['temperature']      = max(0.0, min(2.0, floatval($_POST['temperature'] ?? 0.5)));
    $opt['tone']             = sanitize_text_field($_POST['tone'] ?? 'profesional');
    $opt['welcome']          = wp_kses_post($_POST['welcome'] ?? 'Hola, soy PHSBot. ¿En qué puedo ayudarte?');

    $opt['allow_html']       = !empty($_POST['allow_html']) ? 1 : 0;
    $opt['allow_elementor']  = !empty($_POST['allow_elementor']) ? 1 : 0;
    $opt['allow_live_fetch'] = !empty($_POST['allow_live_fetch']) ? 1 : 0;

    $opt['max_history']      = max(1, intval($_POST['max_history'] ?? 10));
    $opt['max_tokens']       = max(200, intval($_POST['max_tokens'] ?? 1400));
    $opt['max_height_vh']    = max(50, min(95, intval($_POST['max_height_vh'] ?? 70)));
    $opt['anchor_paragraph'] = !empty($_POST['anchor_paragraph']) ? 1 : 0;

    $welcome_changed     = ((string)$opt['welcome'] !== $prev_welcome);
    $opt['welcome_i18n'] = array();
    $opt['welcome_hash'] = md5(wp_strip_all_tags($opt['welcome']));

    // Generar traducciones si hay licencia válida (usa API5, no OpenAI directamente)
    $bot_license = (string) phsbot_setting('bot_license_key', '');
    if ($bot_license && $opt['welcome'] !== '') {
      $opt['welcome_i18n'] = phsbot_chat_build_welcome_i18n($opt['welcome']);
    }

    update_option(PHSBOT_CHAT_OPT, $opt);
    if ($welcome_changed) {
      update_option('phsbot_client_reset_version', time());
    }

    $saved = true;
  }

  $opt = phsbot_chat_get_settings();
  ?>
  <div class="wrap">
    <h1>Chat & Widget (FLOAT)</h1>
    <?php if ($saved): ?><div class="notice notice-success"><p>Guardado ✅</p></div><?php endif; ?>
    <form method="post">
      <?php wp_nonce_field('phsbot_chat_save','phsbot_chat_nonce'); ?>
      <table class="form-table">
        <tr><th scope="row">Modelo</th><td><input type="text" name="model" value="<?php echo esc_attr($opt['model']); ?>" class="regular-text"></td></tr>
        <tr><th scope="row">Temperatura</th><td><input type="number" step="0.1" name="temperature" value="<?php echo esc_attr($opt['temperature']); ?>" class="small-text"> (0–2)</td></tr>
        <tr><th scope="row">Tono</th><td><input type="text" name="tone" value="<?php echo esc_attr($opt['tone']); ?>" class="regular-text"></td></tr>
        <tr><th scope="row">Saludo</th><td><textarea name="welcome" rows="2" class="large-text"><?php echo esc_textarea($opt['welcome']); ?></textarea></td></tr>
        <tr><th scope="row">Permitir HTML</th><td><label><input type="checkbox" name="allow_html" value="1" <?php checked($opt['allow_html'],1); ?>> Sí</label></td></tr>
        <tr><th scope="row">Integración Elementor</th><td><label><input type="checkbox" name="allow_elementor" value="1" <?php checked($opt['allow_elementor'],1); ?>> Sí</label></td></tr>
        <tr><th scope="row">Live fetch (URL actual)</th><td><label><input type="checkbox" name="allow_live_fetch" value="1" <?php checked($opt['allow_live_fetch'],1); ?>> Sí</label></td></tr>
        <tr><th scope="row">Histórico (turnos)</th><td><input type="number" name="max_history" value="<?php echo esc_attr($opt['max_history']); ?>" class="small-text"></td></tr>
        <tr><th scope="row">Máx. tokens</th><td><input type="number" name="max_tokens" value="<?php echo esc_attr($opt['max_tokens']); ?>" class="small-text"></td></tr>
        <tr><th scope="row">Altura máx. (%VH)</th><td><input type="number" name="max_height_vh" value="<?php echo esc_attr($opt['max_height_vh']); ?>" class="small-text"> (50–95)</td></tr>
        <tr><th scope="row">Anclar al 1er párrafo</th><td><label><input type="checkbox" name="anchor_paragraph" value="1" <?php checked($opt['anchor_paragraph'],1); ?>> Sí</label></td></tr>
      </table>
      <p><button class="button button-primary">Guardar cambios</button></p>
    </form>
  </div>
  <?php
}
