<?php
// PHSBOT – chat/chat-core.php
// Núcleo: constantes, helpers, i18n, OpenAI (Chat/Responses) y AJAX handler.
if (!defined('ABSPATH')) exit;

/* ===== Constantes + helpers ===== */
if (!defined('PHSBOT_CHAT_OPT'))   define('PHSBOT_CHAT_OPT',   'phsbot_chat_settings');
if (!defined('PHSBOT_CHAT_GROUP')) define('PHSBOT_CHAT_GROUP', 'phsbot_chat_group');
if (!defined('PHSBOT_KB_DOC_OPT')) define('PHSBOT_KB_DOC_OPT', 'phsbot_kb_document');

/* ===== LENGUAJE: detector externo ===== */
require_once PHSBOT_DIR . 'lang/lang.php';
/* ===== FIN LENGUAJE ===== */

/* -- Devuelve un setting del core con fallback -- */
if (!function_exists('phsbot_setting')) {
  function phsbot_setting($key, $default=null){
    $opt = get_option('phsbot_settings', array());
    return (is_array($opt) && array_key_exists($key,$opt)) ? $opt[$key] : $default;
  }
}

/* -- Defaults del chat -- */
function phsbot_chat_defaults(){
  return array(
    'model'            => 'gpt-4.1-mini',
    'temperature'      => 0.5,
    'tone'             => 'profesional',
    'welcome'          => 'Hola, soy PHSBot. ¿En qué puedo ayudarte?',
    'allow_html'       => 1,
    'allow_elementor'  => 1,
    'allow_live_fetch' => 1,
    'max_history'      => 10,
    'max_tokens'       => 1400,
    'max_height_vh'    => 70,
    'anchor_paragraph' => 1,
  );
}

/* -- Genera y cachea traducciones del saludo (welcome_i18n) -- */
if (!function_exists('phsbot_chat_build_welcome_i18n')) {
function phsbot_chat_build_welcome_i18n($text){
  $text = trim(wp_strip_all_tags((string)$text));
  if ($text === '') return array();
  $langs = apply_filters('phsbot_chat_welcome_langs', array('es','en','fr','de','it','pt'));
  $base  = substr(get_locale(),0,2); if (!$base) $base = 'es';
  $out   = array($base => $text);

  $api_key = (string) phsbot_setting('openai_api_key', '');
  if (!$api_key) return $out;

  $prompt = "Traduce el saludo entre <<< y >>> a estos idiomas: ".implode(',', $langs).".\n".
            "Devuelve SOLO un objeto JSON {\"es\":\"...\",\"en\":\"...\"} sin texto extra.\n".
            "<<<".$text.">>>";

  $body = array(
    'model' => 'gpt-4o-mini',
    'temperature' => 0.2,
    'messages' => array(
      array('role'=>'system','content'=>'Eres un traductor profesional. Devuelves exclusivamente JSON válido.'),
      array('role'=>'user','content'=>$prompt),
    ),
    'max_tokens' => 300,
  );

  $res = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
    'timeout' => 25,
    'headers' => array(
      'Authorization' => 'Bearer '.$api_key,
      'Content-Type'  => 'application/json',
    ),
    'body' => wp_json_encode($body),
  ));
  if (is_wp_error($res)) return $out;
  $code = wp_remote_retrieve_response_code($res);
  if ($code !== 200) return $out;

  $json = json_decode(wp_remote_retrieve_body($res), true);
  $txt  = (string)($json['choices'][0]['message']['content'] ?? '');
  $txt  = trim($txt);
  $start = strpos($txt,'{'); $end = strrpos($txt,'}');
  if ($start!==false && $end!==false) $txt = substr($txt,$start,$end-$start+1);
  $map = json_decode($txt, true);
  if (!is_array($map)) return $out;

  foreach ($map as $k=>$v){
    $k2  = strtolower(substr((string)$k,0,2));
    $val = trim(wp_strip_all_tags((string)$v));
    if ($k2 && $val!=='') $out[$k2] = $val;
  }
  return $out;
}
}

/* -- Traducción runtime del saludo con hash anti-stale -- */
if (!function_exists('phsbot_chat_translate_welcome_runtime')) {
function phsbot_chat_translate_welcome_runtime($lang){
  $lang = strtolower(substr((string)$lang, 0, 2));
  $opt  = phsbot_chat_get_settings();
  $base = trim((string)($opt['welcome'] ?? ''));
  $map  = (array)($opt['welcome_i18n'] ?? array());
  if ($lang === '') return $base;
  if ($base === '') return '';

  $hash_current = md5(wp_strip_all_tags($base));
  $hash_stored  = isset($opt['welcome_hash']) ? (string)$opt['welcome_hash'] : '';
  if ($hash_stored !== $hash_current) {
    $map = array();
  }

  if (!empty($map[$lang])) return (string)$map[$lang];

  $api = (string) phsbot_setting('openai_api_key', '');
  if (!$api) return $base;

  $prompt = "Translate the following greeting to the target language (ISO-639-1): ".$lang.".\\n".
            "Preserve emojis and tone. Return ONLY JSON: {\"t\":\"...\"}.\\n<<<".$base.">>>";

  $body = array(
    'model' => 'gpt-4o-mini',
    'temperature' => 0.2,
    'messages' => array(
      array('role'=>'system','content'=>'You are a precise translator. Output strictly valid JSON only.'),
      array('role'=>'user','content'=>$prompt),
    ),
    'max_tokens' => 200,
  );

  $res = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
    'timeout' => 20,
    'headers' => array(
      'Authorization' => 'Bearer '.$api,
      'Content-Type'  => 'application/json',
    ),
    'body' => wp_json_encode($body),
  ));
  if (is_wp_error($res)) return $base;
  if (wp_remote_retrieve_response_code($res) !== 200) return $base;

  $json = json_decode(wp_remote_retrieve_body($res), true);
  $txt  = trim((string)($json['choices'][0]['message']['content'] ?? ''));
  $start = strpos($txt,'{'); $end = strrpos($txt,'}');
  if ($start!==false && $end!==false) $txt = substr($txt, $start, $end-$start+1);
  $obj = json_decode($txt, true);
  $t   = isset($obj['t']) ? trim(wp_strip_all_tags((string)$obj['t'])) : '';

  if ($t === '') return $base;

  $map[$lang] = $t;
  $opt['welcome_i18n'] = $map;
  $opt['welcome_hash'] = $hash_current;
  update_option(PHSBOT_CHAT_OPT, $opt);

  return $t;
}
}

/* -- ¿Usa API /responses? (GPT-5*) -- */
function phsbot_model_uses_responses_api($model){
  return (bool) preg_match('/^gpt-?5/i', (string)$model);
}

/* -- Convierte messages[] a input[] (Responses) -- */
function phsbot_messages_to_responses_input($messages){
  $out = array();
  foreach ((array)$messages as $m){
    $role = isset($m['role']) && in_array($m['role'], array('system','user','assistant'), true) ? $m['role'] : 'user';
    $text = (string)($m['content'] ?? '');
    $out[] = array(
      'role'    => $role,
      'content' => array(array('type'=>'text', 'text'=>$text)),
    );
  }
  return $out;
}

/* -- Lee opciones fusionadas con defaults -- */
function phsbot_chat_get_settings(){
  $opt = get_option(PHSBOT_CHAT_OPT, array());
  if (!is_array($opt)) $opt = array();
  return array_merge(phsbot_chat_defaults(), $opt);
}

/* -- Acceso a una clave anidada -- */
function phsbot_chat_opt($keys, $def=null){
  $opt = phsbot_chat_get_settings();
  if (!is_array($keys)) $keys = array($keys);
  $cur = $opt;
  foreach ($keys as $k){
    if (!is_array($cur) || !array_key_exists($k, $cur)) return $def;
    $cur = $cur[$k];
  }
  return $cur;
}

/* ===== AJAX hooks ===== */
add_action('wp_ajax_phsbot_chat','phsbot_ajax_chat');
add_action('wp_ajax_nopriv_phsbot_chat','phsbot_ajax_chat');

/* -- Handler AJAX principal -- */
function phsbot_ajax_chat(){
  if (!check_ajax_referer('phsbot_chat','_ajax_nonce', false)) {
    wp_send_json(array('ok'=>false,'error'=>'Nonce inválido'));
  }

  $chat = phsbot_chat_get_settings();
  $api_key = (string) phsbot_setting('openai_api_key', '');
  if (!$api_key) wp_send_json(array('ok'=>false,'error'=>'Falta API key'));

  $q     = sanitize_text_field($_POST['q'] ?? '');
  $cid   = sanitize_text_field($_POST['cid'] ?? '');
  $url   = esc_url_raw($_POST['url'] ?? '');
  $hist  = json_decode(stripslashes($_POST['history'] ?? '[]'), true);
  if (!is_array($hist)) $hist = array();
  $hist  = array_slice($hist, -max(1,intval($chat['max_history'] ?? 10))*2);

  $ctx_raw = isset($_POST['ctx']) ? wp_unslash($_POST['ctx']) : '';
  $ctx = array();
  if ($ctx_raw) { $tmp = json_decode($ctx_raw, true); if (is_array($tmp)) $ctx = $tmp; }
  $lim = function($s,$n){ $s=(string)$s; $s=wp_strip_all_tags($s); return (mb_strlen($s)>$n)?mb_substr($s,0,$n):$s; };
  $ctx_url   = $ctx && !empty($ctx['url']) ? esc_url_raw($ctx['url']) : '';
  $ctx_h1    = $lim($ctx['h1'] ?? '', 160);
  $ctx_title = $lim($ctx['title'] ?? '', 160);
  $ctx_topic = $lim($ctx['topic'] ?? '', 160);
  $ctx_mdesc = $lim($ctx['meta_description'] ?? '', 300);
  $ctx_ogt   = $lim($ctx['og_title'] ?? '', 160);
  $ctx_bc    = $lim($ctx['breadcrumbs'] ?? '', 220);
  $ctx_lang  = sanitize_text_field($ctx['lang'] ?? '');
  $ctx_sel   = $lim($ctx['selection'] ?? '', 400);
  $ctx_main  = $lim($ctx['main_excerpt'] ?? '', 1200);

  $model = (string) ($chat['model'] ?? 'gpt-4o-mini');
  $temp  = floatval($chat['temperature'] ?? 0.5);
  $tone  = (string)  ($chat['tone'] ?? 'profesional');
  $sys_p = (string)  ($chat['system_prompt'] ?? '');
  $max_t = intval($chat['max_tokens'] ?? 1400);
  if ($max_t < 200) $max_t = 200;

  $kb = (string) get_option(PHSBOT_KB_DOC_OPT, '');
  if ($kb !== '') $kb = wp_strip_all_tags($kb);

  $live = '';
  if (!empty($chat['allow_live_fetch']) && !empty($url)){
    $allowed = phsbot_setting('allowed_domains', array());
    if (is_string($allowed)) {
      $allowed = preg_split('/[\s,]+/', $allowed);
    }
    if (!is_array($allowed)) $allowed = array();
    $host = parse_url($url, PHP_URL_HOST);
    if ($host){
      $domain = strtolower($host);
      $ok = false;
      foreach ($allowed as $ad){
        $ad = strtolower(trim((string)$ad));
        if (!$ad) continue;
        if (substr('.'.$domain, -strlen('.'.$ad)) === '.'.$ad || $domain===$ad) { $ok=true; break; }
      }
      if ($ok){
        $res = wp_remote_get($url, array('timeout'=>8));
        if (!is_wp_error($res) && wp_remote_retrieve_response_code($res)===200){
          $live = wp_strip_all_tags(wp_remote_retrieve_body($res));
          $live = preg_replace('/\s+/',' ', $live);
          $live = wp_trim_words($live, 1200, '…');
        }
      }
    }
  }

  $__phs_reply_lang = function_exists('phsbot_reply_language') ? phsbot_reply_language($q) : 'es';
  $__phs_lang_directive = "LANGUAGE: Always reply in [{$__phs_reply_lang}] and keep that language consistently. Switch only if the user's latest message is clearly in another language.";

  $system = ($sys_p !== '') ? $sys_p : 'Responde de forma precisa y honesta. Si no sabes, dilo. Responde en el idioma del usuario.';
  $system .= "\n".$__phs_lang_directive;
  if ($tone !== '') $system .= "\nTono: ".$tone.".";
  if (!empty(phsbot_chat_opt(array('allow_html')))) {
    $system .= "\nFORMATO DE SALIDA: Devuelve SIEMPRE HTML válido, usando <p>, <ul>, <ol>, <li>, <strong>, <em>, <pre>, <code>, <br>. No uses <script>, <style>, iframes ni backticks. No envuelvas con <html> ni <body>.";
  }

  $messages = array(array('role'=>'system','content'=>$system));
  if ($kb)   $messages[] = array('role'=>'system','content'=>"KB:\n".$kb);
  if ($live) $messages[] = array('role'=>'system','content'=>"Contenido de la URL (resumen):\n".$live);

  $ctx_lines = array();
  if ($ctx_url)   $ctx_lines[] = '- URL: '.$ctx_url;
  if ($ctx_h1)    $ctx_lines[] = '- H1: '.$ctx_h1;
  if ($ctx_title) $ctx_lines[] = '- Título: '.$ctx_title;
  if ($ctx_topic) $ctx_lines[] = '- Tema: '.$ctx_topic;
  if ($ctx_mdesc) $ctx_lines[] = '- Meta: '.$ctx_mdesc;
  if ($ctx_ogt)   $ctx_lines[] = '- OG: '.$ctx_ogt;
  if ($ctx_bc)    $ctx_lines[] = '- Migas: '.$ctx_bc;
  if ($ctx_lang)  $ctx_lines[] = '- HTML lang: '.$ctx_lang;
  if ($ctx_sel)   $ctx_lines[] = '- Selección: '.$ctx_sel;
  if ($ctx_main)  $ctx_lines[] = "- Extracto:\n".$ctx_main;
  if (!empty($ctx_lines)){
    $messages[] = array('role'=>'system','content'=>"Contexto actual de página:\n".implode("\n", $ctx_lines));
  }

  foreach ($hist as $h){
    $content = isset($h['content']) ? (string)$h['content'] : ( isset($h['html']) ? wp_strip_all_tags((string)$h['html']) : '' );
    $messages[] = array('role'=>($h['role']==='assistant'?'assistant':'user'), 'content'=>$content);
  }
  if ($q) $messages[] = array('role'=>'user','content'=>$q);

  $use_responses = phsbot_model_uses_responses_api($model);
  $endpoint = $use_responses ? 'https://api.openai.com/v1/responses' : 'https://api.openai.com/v1/chat/completions';
  $payload  = $use_responses
    ? array(
        'model'             => $model,
        'input'             => phsbot_messages_to_responses_input($messages),
        'temperature'       => $temp,
        'max_output_tokens' => $max_t,
        'metadata'          => array('cid'=>$cid, 'source'=>'phsbot'),
      )
    : array(
        'model'       => $model,
        'temperature' => $temp,
        'max_tokens'  => $max_t,
        'messages'    => $messages,
      );

  $res = wp_remote_post($endpoint, array(
    'timeout' => 30,
    'headers' => array(
      'Authorization' => 'Bearer '.$api_key,
      'Content-Type'  => 'application/json',
    ),
    'body' => wp_json_encode($payload),
  ));
  if (is_wp_error($res)) wp_send_json(array('ok'=>false,'error'=>$res->get_error_message()));

  $code = wp_remote_retrieve_response_code($res);
  if ($code !== 200 && defined('WP_DEBUG') && WP_DEBUG) {
    error_log('PHSBOT endpoint='.$endpoint.' model='.$model.' code='.$code);
    error_log('PHSBOT body='.substr(wp_remote_retrieve_body($res),0,400));
  }
  $body = json_decode(wp_remote_retrieve_body($res), true);
  if ($code !== 200 || !is_array($body)) wp_send_json(array('ok'=>false,'error'=>'Error '.$code));

  $txt = '';
  if ($use_responses){
    if (isset($body['output_text']) && is_string($body['output_text'])) {
      $txt = trim($body['output_text']);
    } elseif (!empty($body['output']) && is_array($body['output'])) {
      $buf = '';
      foreach ($body['output'] as $item){
        if (!empty($item['content']) && is_array($item['content'])){
          foreach ($item['content'] as $c){
            if (isset($c['text'])) $buf .= (string)$c['text'];
          }
        }
      }
      $txt = trim($buf);
    } else {
      $txt = '';
    }
  } else {
    $txt = trim((string)($body['choices'][0]['message']['content'] ?? ''));
  }

  $allow_html = !empty($chat['allow_html']) || !empty(phsbot_chat_opt(array('allow_html')));
  $html       = $allow_html ? wp_kses_post($txt) : esc_html($txt);

  wp_send_json(array(
    'ok'   => true,
    'text' => $allow_html ? '' : $txt,
    'html' => $allow_html ? $html : '',
  ));
}
