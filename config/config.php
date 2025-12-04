<?php
/**
 * PHSBOT – Configuración unificada (v1.3.2)
 */

if (!defined('ABSPATH')) exit;

if (!defined('PHSBOT_CONFIG_SLUG'))   define('PHSBOT_CONFIG_SLUG',   'phsbot_config');
if (!defined('PHSBOT_CHAT_OPT'))      define('PHSBOT_CHAT_OPT',      'phsbot_chat_settings');
if (!defined('PHSBOT_SETTINGS_OPT'))  define('PHSBOT_SETTINGS_OPT',  'phsbot_settings');

global $phsbot_config_pagehook;


/* ======== REGISTRO DEL SUBMENÚ ======== */
/* Registra la página de Configuración bajo el menú PHSBOT y guarda el pagehook */
function phsbot_config_register_menu(){
  if (!current_user_can('manage_options')) return;
  global $phsbot_config_pagehook;
  $phsbot_config_pagehook = add_submenu_page(
    'phsbot',
    'PHSBOT · Configuración',
    'Configuración',
    'manage_options',
    PHSBOT_CONFIG_SLUG,
    'phsbot_config_render_page'
  );
}
/* ========FIN REGISTRO DEL SUBMENÚ ===== */
add_action('admin_menu', 'phsbot_config_register_menu', 50);


/* ======== ENQUEUE DE ASSETS ======== */
/* Carga CSS/JS solo en la pantalla de Configuración (y fallback al root del plugin) */
function phsbot_config_enqueue($hook_suffix){
  global $phsbot_config_pagehook;
  $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
  $is_target = ($hook_suffix === $phsbot_config_pagehook) || ($page === 'phsbot') || ($page === PHSBOT_CONFIG_SLUG);
  if (!$is_target) return;

  wp_enqueue_style('wp-color-picker');
  wp_enqueue_script('wp-color-picker');

  $base = plugin_dir_url(__FILE__);
  wp_enqueue_style ('phsbot-config',  $base.'config.css', array(), '1.3.2', 'all');
  wp_enqueue_script('phsbot-config',  $base.'config.js',  array('jquery','wp-color-picker'), '1.3.2', true);
}
/* ========FIN ENQUEUE DE ASSETS ===== */
add_action('admin_enqueue_scripts', 'phsbot_config_enqueue');


/* ======== GUARDADO DE OPCIONES ======== */
/* Procesa el POST y persiste tanto ajustes generales como de chat */
function phsbot_config_handle_save(){
  if (!current_user_can('manage_options')) wp_die('No autorizado');
  check_admin_referer('phsbot_config_save', '_phsbot_config_nonce');

  // -------- Ajustes Generales --------
  $g = get_option(PHSBOT_SETTINGS_OPT, array()); if (!is_array($g)) $g = array();

  $g['chat_position']  = isset($_POST['chat_position']) ? sanitize_text_field($_POST['chat_position']) : ($g['chat_position'] ?? 'bottom-right');
  $g['chat_width']     = isset($_POST['chat_width'])    ? sanitize_text_field($_POST['chat_width'])    : ($g['chat_width'] ?? '360px');
  $g['chat_height']    = isset($_POST['chat_height'])   ? sanitize_text_field($_POST['chat_height'])   : ($g['chat_height'] ?? '720px');

  $g['color_primary']     = isset($_POST['color_primary'])     ? sanitize_hex_color($_POST['color_primary'])     : ($g['color_primary']     ?? '#667a3a');
  $g['color_secondary']   = isset($_POST['color_secondary'])   ? sanitize_hex_color($_POST['color_secondary'])   : ($g['color_secondary']   ?? '#4c5e27');
  $g['color_background']  = isset($_POST['color_background'])  ? sanitize_hex_color($_POST['color_background'])  : ($g['color_background']  ?? '#ffffff');
  $g['color_text']        = isset($_POST['color_text'])        ? sanitize_hex_color($_POST['color_text'])        : ($g['color_text']        ?? '#111111');
  $g['color_bot_bubble']  = isset($_POST['color_bot_bubble'])  ? sanitize_hex_color($_POST['color_bot_bubble'])  : ($g['color_bot_bubble']  ?? '#f1f1f2');
  $g['color_user_bubble'] = isset($_POST['color_user_bubble']) ? sanitize_hex_color($_POST['color_user_bubble']) : ($g['color_user_bubble'] ?? '#e6e6e7');
  $g['color_whatsapp']    = isset($_POST['color_whatsapp'])    ? sanitize_hex_color($_POST['color_whatsapp'])    : ($g['color_whatsapp']    ?? '#25D366');
  $g['color_footer']      = isset($_POST['color_footer'])      ? sanitize_hex_color($_POST['color_footer'])      : ($g['color_footer']      ?? '');

  $g['btn_height']     = isset($_POST['btn_height'])     ? max(36, min(56, intval($_POST['btn_height'])))           : ($g['btn_height']     ?? 44);
  $g['head_btn_size']  = isset($_POST['head_btn_size'])  ? max(20, min(34, intval($_POST['head_btn_size'])))        : ($g['head_btn_size']  ?? 26);
  $g['mic_stroke_w']   = isset($_POST['mic_stroke_w'])   ? max(1,  min(3,  intval($_POST['mic_stroke_w'])))         : ($g['mic_stroke_w']   ?? 1);

  $g['openai_api_key']     = isset($_POST['openai_api_key'])     ? (string) wp_unslash($_POST['openai_api_key'])     : ($g['openai_api_key']     ?? '');
  $g['telegram_bot_token'] = isset($_POST['telegram_bot_token']) ? (string) wp_unslash($_POST['telegram_bot_token']) : ($g['telegram_bot_token'] ?? '');
  $g['telegram_chat_id']   = isset($_POST['telegram_chat_id'])   ? sanitize_text_field($_POST['telegram_chat_id'])   : ($g['telegram_chat_id']   ?? '');
  $g['whatsapp_phone']     = isset($_POST['whatsapp_phone'])     ? sanitize_text_field($_POST['whatsapp_phone'])     : ($g['whatsapp_phone']     ?? '');

  // Nuevo: tamaño de fuente de las burbujas (12–22 px)
  $g['bubble_font_size'] = isset($_POST['bubble_font_size'])
    ? max(12, min(22, intval($_POST['bubble_font_size'])))
    : ($g['bubble_font_size'] ?? 15);

  // Título de cabecera (guardado seguro)
  if ( array_key_exists('chat_title', $_POST) ) {
    $raw = (string) wp_unslash($_POST['chat_title']);
    $val = trim( wp_strip_all_tags( $raw ) );
    $g['chat_title'] = ($val === '') ? 'PHSBot' : $val;
  }

  update_option(PHSBOT_SETTINGS_OPT, $g);

  // -------- Ajustes del Chat (IA) --------
  $c = get_option(PHSBOT_CHAT_OPT, array()); if (!is_array($c)) $c = array();

  $c['model']            = isset($_POST['chat_model'])         ? sanitize_text_field($_POST['chat_model'])         : ($c['model']            ?? 'gpt-4o-mini');
  $c['temperature']      = isset($_POST['chat_temperature'])   ? max(0.0, min(2.0, floatval($_POST['chat_temperature']))) : ($c['temperature'] ?? 0.5);
  $c['tone']             = isset($_POST['chat_tone'])          ? sanitize_text_field($_POST['chat_tone'])          : ($c['tone']             ?? 'profesional');
  $c['welcome']          = isset($_POST['chat_welcome'])       ? wp_kses_post($_POST['chat_welcome'])              : ($c['welcome']          ?? 'Hola, soy PHSBot. ¿En qué puedo ayudarte?');
  $c['system_prompt']    = isset($_POST['chat_system_prompt']) ? wp_kses_post($_POST['chat_system_prompt'])         : ($c['system_prompt']    ?? '');
  $c['allow_html']       = !empty($_POST['chat_allow_html'])        ? 1 : (!empty($c['allow_html'])        ? 1 : 0);
  $c['allow_elementor']  = !empty($_POST['chat_allow_elementor'])   ? 1 : (!empty($c['allow_elementor'])   ? 1 : 0);
  $c['allow_live_fetch'] = !empty($_POST['chat_allow_live_fetch'])  ? 1 : (!empty($c['allow_live_fetch'])  ? 1 : 0);
  $c['anchor_paragraph'] = !empty($_POST['chat_anchor_paragraph'])  ? 1 : (!empty($c['anchor_paragraph'])  ? 1 : 0);
  $c['max_history']      = isset($_POST['chat_max_history'])   ? max(1, intval($_POST['chat_max_history']))        : ($c['max_history']      ?? 10);
  $c['max_tokens']       = isset($_POST['chat_max_tokens'])    ? max(200, intval($_POST['chat_max_tokens']))       : ($c['max_tokens']       ?? 1400);
  $c['max_height_vh']    = isset($_POST['chat_max_height_vh']) ? max(50, intval($_POST['chat_max_height_vh']))     : ($c['max_height_vh']    ?? 70);

  update_option(PHSBOT_CHAT_OPT, $c, false);

  // Redirección OK
  $url = add_query_arg(array('page'=>PHSBOT_CONFIG_SLUG,'updated'=>'1'), admin_url('admin.php'));
  wp_safe_redirect($url); exit;
}
/* ========FIN GUARDADO DE OPCIONES ===== */
add_action('admin_post_phsbot_config_save', 'phsbot_config_handle_save');

/* ======== OPENAI: OBTENER API KEY ======== */
/* Devuelve la API key de OpenAI desde los ajustes principales */
if (!function_exists('phsbot_openai_get_api_key')) {
    function phsbot_openai_get_api_key() {
        $main = get_option(defined('PHSBOT_MAIN_SETTINGS_OPT') ? PHSBOT_MAIN_SETTINGS_OPT : 'phsbot_settings', array());
        $key  = isset($main['openai_api_key']) ? (string)$main['openai_api_key'] : '';
        return trim($key);
    }
} /* ========FIN OPENAI: OBTENER API KEY ===== */



/* ======== OPENAI: NORMALIZAR ALIAS DE MODELO ======== */
/* Colapsa snapshots/aliases fechados a su alias base (p. ej. gpt-4.1-2025-05-13 → gpt-4.1) */
if (!function_exists('phsbot_openai_collapse_model_alias')) {
    function phsbot_openai_collapse_model_alias($model_id) {
        $alias = preg_replace('/-(20\d{2}-\d{2}-\d{2}|latest)$/i', '', (string)$model_id);
        return $alias ?: (string)$model_id;
    }
} /* ========FIN OPENAI: NORMALIZAR ALIAS DE MODELO ===== */



/* ======== OPENAI: LISTAR MODELOS GPT-4+ / GPT-5* (EN VIVO + CACHE) ======== */
/* Llama a /v1/models, filtra GPT-4* y GPT-5* óptimos para chat (no embeddings/audio/tts/etc.) y cachea en transient */
if (!function_exists('phsbot_openai_list_chat_models')) {
    function phsbot_openai_list_chat_models($ttl = 12 * HOUR_IN_SECONDS) {
        $cache_key = 'phsbot_openai_models_chat_v3';
        $cached = get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) return $cached;

        $api_key = phsbot_openai_get_api_key();
        if (!$api_key) return array();

        $resp = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 8,
        ));
        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) return array();

        $data = json_decode(wp_remote_retrieve_body($resp), true);
        if (!isset($data['data']) || !is_array($data['data'])) return array();

        $ids = array();
        foreach ($data['data'] as $item) {
            if (empty($item['id'])) continue;
            $id = (string)$item['id'];

            // Incluir solo familias GPT-4* y GPT-5*
            if (!preg_match('/^gpt-(4|5)/i', $id)) continue;

            // Excluir no-chat u obsoletos
            if (preg_match('/(embed|embedding|whisper|tts|audio|realtime|vision-only|legacy|deprecated|ft:|fine|batch|vector)/i', $id)) continue;

            // Colapsar snapshots/aliases a su base
            $alias = phsbot_openai_collapse_model_alias($id);
            $ids[] = $alias;
        }

        // Únicos y ordenados por preferencia (5 > 4o > 4.1 > resto; "mini" detrás)
        $ids = array_values(array_unique($ids));
        usort($ids, function($a, $b){
            $rank = function($m){
                $w = 0;
                if (preg_match('/^gpt-5/i', $m))    $w += 500;
                if (preg_match('/^gpt-4o/i', $m))   $w += 410;
                if (preg_match('/^gpt-4\.1/i', $m)) $w += 405;
                if (preg_match('/^gpt-4/i', $m))    $w += 400;
                if (preg_match('/mini/i', $m))      $w -= 5; // mini = barato/rápido
                return $w;
            };
            $ra = $rank($a); $rb = $rank($b);
            return ($ra === $rb) ? strcmp($a,$b) : (($ra > $rb) ? -1 : 1);
        });

        set_transient($cache_key, $ids, $ttl);
        return $ids;
    }
} /* ========FIN OPENAI: LISTAR MODELOS GPT-4+ / GPT-5* (EN VIVO + CACHE) ===== */



/* ======== OPENAI: LABEL AMIGABLE PARA MODELO ======== */
/* Genera un label descriptivo; no inventa IDs, solo añade descriptor genérico por patrón */
if (!function_exists('phsbot_openai_model_label')) {
    function phsbot_openai_model_label($id) {
        $label = (string)$id;
        $id_l  = strtolower($label);

        $is5     = (strpos($id_l, 'gpt-5') === 0);
        $is4o    = (strpos($id_l, 'gpt-4o') === 0);
        $is41    = (strpos($id_l, 'gpt-4.1') === 0);
        $isMini  = (strpos($id_l, 'mini') !== false);

        if     ($is5 && $isMini)  $desc = 'rápido y económico; buen razonamiento';
        elseif ($is5)             $desc = 'máxima calidad de razonamiento; más costoso';
        elseif ($is4o && $isMini) $desc = 'muy barato y veloz; multimodal';
        elseif ($is4o)            $desc = 'multimodal equilibrado; calidad alta';
        elseif ($is41 && $isMini) $desc = 'rápido y barato; buena calidad';
        elseif ($is41)            $desc = 'texto de alta calidad; razonamiento sólido';
        else                      $desc = $isMini ? 'rápido y barato' : 'equilibrado para chat';

        return sprintf('%s — %s', $label, $desc);
    }
} /* ========FIN OPENAI: LABEL AMIGABLE PARA MODELO ===== */

/* ======== RENDER DE LA PÁGINA ======== */
/* Pinta la UI de configuración con previsualización */
function phsbot_config_render_page(){
  if (!current_user_can('manage_options')) return;

  $g = get_option(PHSBOT_SETTINGS_OPT, array()); if (!is_array($g)) $g = array();
  $c = get_option(PHSBOT_CHAT_OPT, array());     if (!is_array($c)) $c = array();

  // Conexiones
  $openai_api_key     = isset($g['openai_api_key'])     ? $g['openai_api_key']     : '';
  $telegram_bot_token = isset($g['telegram_bot_token']) ? $g['telegram_bot_token'] : '';
  $telegram_chat_id   = isset($g['telegram_chat_id'])   ? $g['telegram_chat_id']   : '';
  $whatsapp_phone     = isset($g['whatsapp_phone'])     ? $g['whatsapp_phone']     : '';

  // Apariencia
  $chat_position  = isset($g['chat_position']) ? $g['chat_position'] : 'bottom-right';
  $chat_width     = isset($g['chat_width'])    ? $g['chat_width']    : '360px';
  $chat_height    = isset($g['chat_height'])   ? $g['chat_height']   : '720px';
  $chat_title     = isset($g['chat_title'])    ? $g['chat_title']    : 'PHSBot';
  $bubble_font_size = isset($g['bubble_font_size']) ? intval($g['bubble_font_size']) : 15;

  $color_primary      = isset($g['color_primary'])      ? $g['color_primary']      : '#667a3a';
  $color_secondary    = isset($g['color_secondary'])    ? $g['color_secondary']    : '#4c5e27';
  $color_background   = isset($g['color_background'])   ? $g['color_background']   : '#ffffff';
  $color_text         = isset($g['color_text'])         ? $g['color_text']         : '#111111';
  $color_bot_bubble   = isset($g['color_bot_bubble'])   ? $g['color_bot_bubble']   : '#f1f1f2';
  $color_user_bubble  = isset($g['color_user_bubble'])  ? $g['color_user_bubble']  : '#e6e6e7';
  $color_whatsapp     = isset($g['color_whatsapp'])     ? $g['color_whatsapp']     : '#25D366';

  // Footer (preview)
  $color_footer_saved   = isset($g['color_footer']) ? $g['color_footer'] : '';
  $color_footer_preview = ($color_footer_saved !== '') ? $color_footer_saved : $color_background;

  $btn_height    = isset($g['btn_height'])    ? intval($g['btn_height'])    : 44;
  $head_btn_size = isset($g['head_btn_size']) ? intval($g['head_btn_size']) : 26;
  $mic_stroke_w  = isset($g['mic_stroke_w'])  ? intval($g['mic_stroke_w'])  : 1;

  // Chat (IA)
  $chat_model           = isset($c['model']) ? $c['model'] : 'gpt-4o-mini';
  $chat_temperature     = isset($c['temperature']) ? floatval($c['temperature']) : 0.5;
  $chat_tone            = isset($c['tone']) ? $c['tone'] : 'profesional';
  $chat_welcome         = isset($c['welcome']) ? $c['welcome'] : 'Hola, soy PHSBot. ¿En qué puedo ayudarte?';
  $chat_system_prompt   = isset($c['system_prompt']) ? $c['system_prompt'] : '';
  $chat_allow_html      = !empty($c['allow_html']);
  $chat_allow_elementor = !empty($c['allow_elementor']);
  $chat_allow_live_fetch= !empty($c['allow_live_fetch']);
  $chat_anchor_paragraph= !empty($c['anchor_paragraph']);
  $chat_max_history     = isset($c['max_history']) ? intval($c['max_history']) : 10;
  $chat_max_tokens      = isset($c['max_tokens']) ? intval($c['max_tokens']) : 1400;
  $chat_max_height_vh   = isset($c['max_height_vh']) ? intval($c['max_height_vh']) : 70;

  // Normaliza tamaños px
  $w_px = intval(preg_replace('/[^0-9]/','', $chat_width));
  $h_px = intval(preg_replace('/[^0-9]/','', $chat_height));
  if ($w_px < 260) $w_px = 360;
  if ($h_px < 400) $h_px = 720;

  /* ======== PROMPT POR DEFECTO (usa dominio activo) ======== */
  $root_url = untrailingslashit( home_url() );
  $contact_url_default = home_url( '/contacto/' );
  $default_system_prompt = <<<PHSBOT_DEF
***Rol y objetivo***
Eres el un asesor de actividades de caza del sitio  $root_url. Responde siempre en el mismo idioma que use el usuario. Tu objetivo principal es orientar al usuario y darle opciones para su viaje de caza
Eres parte de la empresa, no hables de la empresa en tercera persona.

***Estilo de respuesta***

- Breve y concisa. Máximo 200 palabras.
- Formato en HTML obligado
- No repitas la pregunta del usuario como entradilla.

***Captura de datos de forma discreta y escalonada a partir del 10º mensaje***
- Nunca pidas datos como teléfono, mail al inicio de la conversación
- Camufla la petición de teléfono o mail dentro del siguiente paso útil (1º pide correo electrónico, 2ª teléfono, 3ª Prefíjo telefónico del país).
- Si el usuario comparte datos, confirma brevemente y continúa con el siguiente paso.



Plantillas sutiles (adaptar al idioma del usuario)
- Si quieres te envío una propuesta con fechas y precios por mail
- ¿Prefieres que te llame a un teléfono y lo comentamos?


Reglas de contenido
- Usa información del sitio  $root_url (o su Base de Conocimiento).
- Cuando cites una sección existente, añade su enlace interno en HTML.
- Evita más de un enlace por mensaje salvo que sea imprescindible.
- Mantén el tono profesional y útil; nada de frases de relleno.

Si falta contexto
- Haz una única pregunta breve para avanzar (≤12 palabras).
PHSBOT_DEF;
  /* ========FIN PROMPT POR DEFECTO ===== */

  // Si no hay prompt guardado, mostrar el por defecto en el textarea
  $chat_system_prompt_display = ($chat_system_prompt !== '') ? $chat_system_prompt : $default_system_prompt;
  ?>
  <div class="wrap phsbot-config-wrap" data-phsbot-config="v1.3.2">
    <h1 class="wp-heading-inline">PHSBOT · Configuración</h1>
    <?php if (!empty($_GET['updated'])): ?>
      <div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper phsbot-config-tabs" role="tablist" aria-label="PHSBOT Config">
      <a href="#tab-aspecto"     class="nav-tab nav-tab-active" role="tab" aria-selected="true">Aspecto del chat</a>
      <a href="#tab-chat"        class="nav-tab" role="tab" aria-selected="false">Chat (IA)</a>
      <a href="#tab-conexiones"  class="nav-tab" role="tab" aria-selected="false">Conexiones</a>
    </h2>

    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="phsbot-config-form">
      <?php wp_nonce_field('phsbot_config_save','_phsbot_config_nonce'); ?>
      <input type="hidden" name="action" value="phsbot_config_save" />

      <section id="tab-aspecto" class="phsbot-config-panel" aria-hidden="false">
        <div class="phsbot-aspecto-grid">
          <div class="phsbot-aspecto-left">
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row">Posición</th>
                  <td>
                    <select name="chat_position" id="chat_position">
                      <option value="bottom-right" <?php selected($chat_position,'bottom-right');?>>Inferior derecha</option>
                      <option value="bottom-left"  <?php selected($chat_position,'bottom-left');?>>Inferior izquierda</option>
                      <option value="top-right"    <?php selected($chat_position,'top-right');?>>Superior derecha</option>
                      <option value="top-left"     <?php selected($chat_position,'top-left');?>>Superior izquierda</option>
                    </select>
                  </td>
                </tr>

                <tr>
                  <th scope="row">Título cabecera</th>
                  <td>
                    <input type="text" name="chat_title" class="regular-text"
                           value="<?php echo esc_attr($chat_title); ?>" placeholder="PHSBot">
                  </td>
                </tr>

                <tr>
                  <th scope="row">Texto de los mensajes</th>
                  <td>
                    <div class="phsbot-slider-row">
                      <label for="bubble_font_size">Tamaño de fuente</label>
                      <input type="range" id="bubble_font_size" name="bubble_font_size"
                             min="12" max="22" step="1"
                             value="<?php echo esc_attr($bubble_font_size); ?>">
                      <span id="bubble_font_size_val"><?php echo esc_html($bubble_font_size); ?> px</span>
                    </div>
                  </td>
                </tr>

                <tr>
                  <th scope="row">Tamaño</th>
                  <td>
                    <div class="phsbot-slider-row">
                      <label for="chat_width_slider">Ancho</label>
                      <input type="range" id="chat_width_slider" min="260" max="920" step="2" value="<?php echo esc_attr($w_px);?>">
                      <span id="chat_width_val"><?php echo esc_html($w_px);?> px</span>
                      <input type="hidden" id="chat_width" name="chat_width" value="<?php echo esc_attr($w_px.'px');?>">
                    </div>
                    <div class="phsbot-slider-row">
                      <label for="chat_height_slider">Alto</label>
                      <input type="range" id="chat_height_slider" min="420" max="960" step="2" value="<?php echo esc_attr($h_px);?>">
                      <span id="chat_height_val"><?php echo esc_html($h_px);?> px</span>
                      <input type="hidden" id="chat_height" name="chat_height" value="<?php echo esc_attr($h_px.'px');?>">
                    </div>
                  </td>
                </tr>

                <tr>
                  <th scope="row">Colores</th>
                  <td>
                    <fieldset class="phsbot-colors">
                      <input type="text" name="color_primary"     class="phsbot-color" value="<?php echo esc_attr($color_primary);?>"> Cabecera<br>
                      <input type="text" name="color_secondary"   class="phsbot-color" value="<?php echo esc_attr($color_secondary);?>"> Hovers<br>
                      <input type="text" name="color_background"  class="phsbot-color" value="<?php echo esc_attr($color_background);?>"> Fondo del chat<br>
                      <input type="text" name="color_text"        class="phsbot-color" value="<?php echo esc_attr($color_text);?>"> Texto general<br>
                      <input type="text" name="color_bot_bubble"  class="phsbot-color" value="<?php echo esc_attr($color_bot_bubble);?>"> Burbuja BOT<br>
                      <input type="text" name="color_user_bubble" class="phsbot-color" value="<?php echo esc_attr($color_user_bubble);?>"> Burbuja Usuario<br>
                      <input type="text" name="color_footer"      class="phsbot-color"
                             value="<?php echo esc_attr($color_footer_saved); ?>"
                             data-default-color="<?php echo esc_attr($color_footer_saved ?: $color_background); ?>">
                      Pie del panel / footer<br>
                    </fieldset>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="phsbot-aspecto-right">
            <div id="phsbot-preview"
                 data-pos="<?php echo esc_attr($chat_position); ?>"
                 style="--phsbot-width: <?php echo esc_attr(intval($w_px)); ?>px;
                        --phsbot-height: <?php echo esc_attr(intval($h_px)); ?>px;
                        --phsbot-bg: <?php echo esc_attr($color_background); ?>;
                        --phsbot-text: <?php echo esc_attr($color_text); ?>;
                        --phsbot-bot-bubble: <?php echo esc_attr($color_bot_bubble); ?>;
                        --phsbot-user-bubble: <?php echo esc_attr($color_user_bubble); ?>;
                        --phsbot-primary: <?php echo esc_attr($color_primary); ?>;
                        --phsbot-secondary: <?php echo esc_attr($color_secondary); ?>;
                        --phsbot-whatsapp: <?php echo esc_attr($color_whatsapp); ?>;
                        --phsbot-footer: <?php echo esc_attr($color_footer_preview); ?>;
                        --phsbot-btn-h: <?php echo esc_attr(intval($btn_height)); ?>px;
                        --phsbot-head-btn: <?php echo esc_attr(intval($head_btn_size)); ?>px;
                        --mic-stroke-w: <?php echo esc_attr(intval($mic_stroke_w)); ?>px;
                        --phsbot-bubble-fs: <?php echo esc_attr(intval($bubble_font_size)); ?>px;">
              <div class="phs-header">
                <div class="phs-title"><?php echo esc_html($chat_title); ?></div>
                <div class="phs-head-actions">
                  <button type="button" class="phsbot-btn phsbot-mic" style="width: 32px; height: 32px;" title="Cerrar" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                </div>
              </div>
              <!-- 👇 APLICAMOS AQUÍ LA VARIABLE PARA QUE SE VEA EL TAMAÑO EN LA PREVIEW -->
              <div class="phs-messages" style="font-size: var(--phsbot-bubble-fs, 15px);">
                <div class="phs-msg bot"><div class="phsbot-bubble"><p>¡Hola! ¿Me dices tu nombre y en que puedo ayudarte?.</p></div></div>
                <div class="phs-msg user"><div class="phsbot-bubble"><p>Aqui va la respuesta del usuario, normalmente un sin sentido...</p></div></div>
              </div>
              <div class="phs-input">
                <button class="phsbot-btn phsbot-mic" id="phsbot-mic" type="button" aria-label="<?php echo esc_attr_x('Micrófono', 'Microphone button', 'phsbot'); ?>">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                    <g fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="9" y="3" width="6" height="10" rx="3"/>
                      <path d="M5 11a7 7 0 0 0 14 0"/>
                      <line x1="12" y1="17" x2="12" y2="20"/>
                      <line x1="9"  y1="21" x2="15" y2="21"/>
                    </g>
                  </svg>
                </button>
                <textarea style="border-radius:99px;height:50px" id="phsbot-q" disabled placeholder="Escribe un mensaje…"></textarea>
                <button class="phsbot-btn phsbot-mic" id="phsbot-send" type="button">
                  <svg viewBox="0 0 24 24" role="img" focusable="false" aria-hidden="true">
                    <polygon points="12,6 18,18 6,18" fill="currentColor"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB Chat -->
      <section id="tab-chat" class="phsbot-config-panel" aria-hidden="true">
        <table class="form-table" role="presentation">
          <tbody>
            <?php
// Modelos desde OpenAI (en vivo, cacheado) o lista sugerida si no hay API/resultado
$api_models = phsbot_openai_list_chat_models();            // usa funciones de config.php
$has_api    = (phsbot_openai_get_api_key() !== '');
$fallback   = array('gpt-5','gpt-5-mini','gpt-4o','gpt-4o-mini','gpt-4.1','gpt-4.1-mini');
$models     = !empty($api_models) ? $api_models : $fallback;
?>
<tr>
  <th scope="row"><label for="chat_model">Modelo</label></th>
  <td>
    <select name="chat_model" id="chat_model" class="regular-text">
      <?php foreach ($models as $mid): ?>
        <option value="<?php echo esc_attr($mid); ?>" <?php selected($chat_model, $mid); ?>>
          <?php echo esc_html(phsbot_openai_model_label($mid)); ?>
        </option>
      <?php endforeach; ?>

      <?php if (!empty($chat_model) && !in_array($chat_model, $models, true)): ?>
        <option value="<?php echo esc_attr($chat_model); ?>" selected>
          <?php echo esc_html('Personalizado: '.$chat_model); ?>
        </option>
      <?php endif; ?>
    </select>

    <p class="description">
      <?php if (!$has_api): ?>
        Sin API key: mostrando lista sugerida de modelos recientes (GPT-4+). Añade tu API key para consultar los modelos activos en tu cuenta.
      <?php else: ?>
        Lista obtenida en vivo desde tu cuenta de OpenAI (filtrada a modelos GPT-4+ óptimos para chatbot).
      <?php endif; ?>
    </p>
  </td>
</tr>
            <tr><th scope="row">Temperatura</th>
              <td><input type="number" step="0.05" min="0" max="2" name="chat_temperature" value="<?php echo esc_attr($chat_temperature);?>" style="width:120px"></td></tr>
            <tr><th scope="row">Máx. tokens de respuesta</th>
              <td>
                <input type="number" min="200" step="50" name="chat_max_tokens" value="<?php echo esc_attr($chat_max_tokens);?>" style="width:140px">
                <p class="description">Límite de tokens para la <em>completion</em> del asistente.</p>
              </td></tr>
            <tr><th scope="row">Tono</th>
              <td><input type="text" name="chat_tone" value="<?php echo esc_attr($chat_tone);?>" class="regular-text"></td></tr>
            <tr><th scope="row">Saludo</th>
              <td><textarea name="chat_welcome" rows="2" class="large-text"><?php echo esc_textarea($chat_welcome);?></textarea></td></tr>
            <tr><th scope="row">System prompt</th>
              <td>
                <textarea name="chat_system_prompt" id="chat_system_prompt" rows="8" class="large-text"><?php echo esc_textarea($chat_system_prompt_display);?></textarea>
                <p style="margin-top:6px;">
                  <button type="button" class="button" id="phsbot-system-default-btn">Restaurar valor por defecto</button>
                  <span class="description">Rellena el prompt recomendado (ajustado a <?php echo esc_html($root_url); ?>).</span>
                </p>
                <script>
                  (function(){
                    var btn = document.getElementById('phsbot-system-default-btn');
                    var ta  = document.getElementById('chat_system_prompt');
                    if(!btn || !ta) return;
                    var DEFAULT_PROMPT = <?php echo json_encode($default_system_prompt); ?>;
                    btn.addEventListener('click', function(){
                      ta.value = DEFAULT_PROMPT;
                      ta.dispatchEvent(new Event('input', {bubbles:true}));
                    });
                  })();
                </script>
              </td></tr>
          </tbody>
        </table>
      </section>

      <!-- TAB Conexiones -->
      <section id="tab-conexiones" class="phsbot-config-panel" aria-hidden="true">
        <table class="form-table" role="presentation">
          <tbody>
            <tr><th scope="row">Token OpenAI (ChatGPT)</th>
              <td><input type="text" name="openai_api_key" class="regular-text" value="<?php echo esc_attr($openai_api_key);?>"></td></tr>
            <tr><th scope="row">Token de Telegram</th>
              <td><input type="text" name="telegram_bot_token" class="regular-text" value="<?php echo esc_attr($telegram_bot_token);?>"></td></tr>
            <tr><th scope="row">ID de Telegram</th>
              <td><input type="text" name="telegram_chat_id" class="regular-text" value="<?php echo esc_attr($telegram_chat_id);?>"></td></tr>
            <tr><th scope="row">Teléfono de WhatsApp (E.164)</th>
              <td><input type="text" name="whatsapp_phone" class="regular-text" placeholder="+34123456789" value="<?php echo esc_attr($whatsapp_phone);?>"></td></tr>
          </tbody>
        </table>
      </section>

      <?php submit_button('Guardar configuración'); ?>
    </form>
  </div>
<?php
}
/* ========FIN RENDER DE LA PÁGINA ===== */
