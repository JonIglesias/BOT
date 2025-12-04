<?php
/**
 * PHSBOT – KB Core (helpers, prompt, modelos, override, OpenAI, errores)
 * Archivo: /kb/kb-core.php
 */
if ( ! defined('ABSPATH') ) exit;

if (!defined('PHSBOT_CAP_SETTINGS')) {
    define('PHSBOT_CAP_SETTINGS', 'manage_options');
}

/* ====================== Helpers/Options ====================== */
function phsbot_kb_update_option_noautoload($key, $value) {
    if ( false === get_option($key, false) ) add_option($key, $value, '', 'no');
    else update_option($key, $value, 'no');
}
function phsbot_kb_get_openai_key() {
    $settings = get_option('phsbot_settings', []);
    return isset($settings['openai_api_key']) ? trim($settings['openai_api_key']) : '';
}

/* ====================== Registro de errores visibles ====================== */
function phsbot_kb_record_error($message, $data = []) {
    $err = [
        'when'    => current_time('mysql'),
        'message' => (string)$message,
        'data'    => is_array($data) ? $data : [],
    ];
    phsbot_kb_update_option_noautoload('phsbot_kb_last_error', $err);
}

/* ====================== Limpieza / preview ====================== */
function phsbot_kb_strip_fences($text) {
    $raw = (string)$text;
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    if (preg_match('/```(?:\s*html)?\s*(.+?)```/is', $raw, $m)) $raw = $m[1];
    else {
        $raw = preg_replace('/^```(?:\s*html)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
    }
    return trim($raw);
}
function phsbot_kb_preview_sanitize($html) {
    $out = (string)$html;
    $out = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $out);
    $out = preg_replace_callback('/<([a-z0-9\-]+)\b([^>]*)>/i', function($m){
        $attrs = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $m[2]);
        return '<'.$m[1].$attrs.'>';
    }, $out);
    return $out;
}
function phsbot_kb_remove_external_links($html, $main_host) {
    if (!trim($html)) return $html;
    return preg_replace_callback('~<a\b([^>]*\bhref\s*=\s*(["\'])(.*?)\2[^>]*)>(.*?)</a>~is', function($m) use ($main_host){
        $attrs = $m[1]; $href = $m[3]; $text = $m[4];
        if ($href === '' || $href[0] === '#' || strpos($href, '://') === false) return '<a'.$attrs.'>'.$text.'</a>';
        $h = wp_parse_url($href, PHP_URL_HOST);
        if (!$h || strcasecmp($h, $main_host) === 0) return '<a'.$attrs.'>'.$text.'</a>';
        return '<span class="ext-ref">'.$text.'</span>';
    }, $html);
}

/* ====================== Detección base + Override ====================== */
function phsbot_kb_detect_site_base() {
    $site_url = get_site_url();
    $p = wp_parse_url($site_url);
    $scheme = isset($p['scheme']) ? $p['scheme'] : 'https';
    $host   = isset($p['host'])   ? $p['host']   : '';
    $path   = isset($p['path'])   ? $p['path']   : '/';
    if ($path === '') $path = '/';
    if ($path !== '/' && substr($path, -1) !== '/') $path .= '/';
    return ['scheme'=>$scheme,'host'=>$host,'path'=>$path];
}
function phsbot_kb_normalize_override_for_prompt($det, $val){
    $raw = trim((string)$val);
    if ($raw === '') return $det;

    if ($raw[0] === '/') {
        $path = $raw;
        if ($path !== '/' && substr($path, -1) !== '/') $path .= '/';
        return ['scheme'=>$det['scheme'], 'host'=>$det['host'], 'path'=>$path];
    }
    if (!preg_match('~^[a-z]+://~i', $raw) && strpos($raw, '.') !== false) $raw = 'https://' . $raw;

    $p = wp_parse_url($raw);
    if (empty($p['host'])) return $det;

    $scheme = isset($p['scheme']) ? strtolower($p['scheme']) : $det['scheme'];
    $host   = strtolower($p['host']);
    $path   = isset($p['path']) ? $p['path'] : '/';
    if ($path !== '/' && substr($path, -1) !== '/') $path .= '/';
    return ['scheme'=>$scheme,'host'=>$host,'path'=>$path];
}
function phsbot_kb_get_active_site_base() {
    $det = phsbot_kb_detect_site_base();
    $on  = (bool) get_option('phsbot_kb_site_override_on', false);
    $val = trim((string) get_option('phsbot_kb_site_override', ''));
    if (!$on || $val === '') return $det;
    return phsbot_kb_normalize_override_for_prompt($det, $val);
}

/* ====================== Modelos ====================== */
function phsbot_kb_get_models($force_refresh = false) {
    $cache = get_option('phsbot_kb_models_cache', ['ts' => 0, 'list' => []]);
    $now   = time();
    $stale = ($now - intval($cache['ts'] ?? 0)) > DAY_IN_SECONDS;

    if (!$force_refresh && !$stale && !empty($cache['list'])) return $cache['list'];

    $api_key = phsbot_kb_get_openai_key();
    if ($api_key) {
        $res = wp_remote_get('https://api.openai.com/v1/models', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
        ]);
        if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
            $body = json_decode(wp_remote_retrieve_body($res), true);
            $list = [];
            if (isset($body['data']) && is_array($body['data'])) {
                foreach ($body['data'] as $m) if (!empty($m['id'])) $list[] = $m['id'];
                usort($list, function($a,$b){
                    $rank = ['gpt-5','gpt-4.1','gpt-4o','gpt-4o-mini','gpt-4','gpt-3.5'];
                    $wa=$wb=100; foreach ($rank as $i=>$n){ if(stripos($a,$n)!==false)$wa=$i; if(stripos($b,$n)!==false)$wb=$i; }
                    return $wa===$wb ? strnatcasecmp($a,$b) : $wa-$wb;
                });
                $list = array_values(array_unique($list));
                phsbot_kb_update_option_noautoload('phsbot_kb_models_cache', ['ts'=>$now,'list'=>$list]);
                return $list;
            }
        }
    }
    $fallback = ['gpt-5','gpt-4.1','gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-4','gpt-3.5-turbo'];
    phsbot_kb_update_option_noautoload('phsbot_kb_models_cache', ['ts'=>$now,'list'=>$fallback]);
    return $fallback;
}
function phsbot_kb_choose_model_with_fallback($selected, $available) {
    $available = array_values(array_unique($available));
    if (in_array($selected, $available, true)) return [$selected, null];

    $b = (stripos($selected,'gpt-5')!==false)
        ? [['gpt-5'],['gpt-4.1','gpt-4o'],['gpt-4o-mini','gpt-4-turbo','gpt-4'],['gpt-3.5-turbo']]
        : [['gpt-4.1','gpt-4o'],['gpt-4o-mini','gpt-4-turbo','gpt-4'],['gpt-3.5-turbo']];

    foreach ($b as $bucket) {
        foreach ($bucket as $cand) foreach ($available as $av) if (strcasecmp($cand,$av)===0)
            return [$av, "El modelo seleccionado \"{$selected}\" no estaba disponible; se usó \"{$av}\"."];
        foreach ($bucket as $needle) foreach ($available as $av) if (stripos($av,$needle)!==false)
            return [$av, "El modelo seleccionado \"{$selected}\" no estaba disponible; se usó \"{$av}\"."];
    }
    return !empty($available[0])
        ? [$available[0], "El modelo seleccionado \"{$selected}\" no estaba disponible; se usó \"{$available[0]}\"."]
        : [$selected, "No se pudo resolver un modelo alternativo. Se intentará \"{$selected}\"."];
}

/* ====================== Job status ====================== */
function phsbot_kb_job_set_running() { phsbot_kb_update_option_noautoload('phsbot_kb_job_status', 'running'); phsbot_kb_update_option_noautoload('phsbot_kb_job_started', current_time('mysql')); }
function phsbot_kb_job_set_idle()     { phsbot_kb_update_option_noautoload('phsbot_kb_job_status', 'idle'); }

/* ====================== Prompt por defecto (neutro) ====================== */
function phsbot_kb_get_default_prompt() {
    $base = phsbot_kb_get_active_site_base();
    $root = rtrim($base['scheme'] . '://' . $base['host'] . $base['path'], '/');

    $default = <<<EOT
Actúa como constructor de Base de Conocimiento para un Chatbot que debe responder cualquier pregunta de usuarios sobre el dominio activo. Tu es generar un Documento Maestro de Conocimiento completo, verificable y utilizable por el chatbot con toda la información del sitio {$root}.

ALCANCE Y NAVEGACIÓN (obligatorio)
Recorre toda la web: menú, submenu (hasta 3 niveles), footer, migas, categorías, etiquetas, listados/paginaciones, buscador interno, sitemap.xml y mapas de sitio secundarios si existen.
Sigue solo enlaces internos bajo {$root} (ignora externos y parámetros de tracking). Deduplica URLs canónicas y evita contenido repetido.
Extrae datos tal cual aparecen. No inventes. Si falta un dato, escribe “No especificado” e incluye la(s) URL(s) revisada(s).

FORMATO DE SALIDA 
Devuelve solo HTML mínimo, sin estilos ni scripts, y sin DOCTYPE.
Enlaces: solo internos a {$root}, con URL absoluta.


CRITERIOS DE REDACCIÓN Y CALIDAD
Idioma: español. Estilo claro, preciso y orientado a resolver preguntas reales de usuarios.
Condición estricta: cada producto/servicio debe incluir una Descripción de al menos 200 palabras. 
cita las URLs internas utilizadas.
Interlinking: solo URLs internas de {$root}.

EOT;
    return $default;
}

/* ====================== HTTP helper ====================== */
function phsbot_kb_http_get($url, $timeout = 10) {
    $args = ['timeout'=>$timeout,'headers'=>['User-Agent'=>'PHSBOT-KB/1.0 (+wordpress)']];
    $res = wp_remote_get($url, $args);
    if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) return $res;

    $p = wp_parse_url($url);
    if (!empty($p['scheme']) && strtolower($p['scheme']) === 'https') {
        $alt = preg_replace('~^https://~i', 'http://', $url);
        $res2 = wp_remote_get($alt, $args);
        if (!is_wp_error($res2) && wp_remote_retrieve_response_code($res2) === 200) return $res2;
    }
    return $res;
}

/* ====================== OpenAI ====================== */
function phsbot_kb_openai_chat($api_key, $model, $user_prompt, $max_tokens = 8000, $temperature = 0.2) {
    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $payload = [
        'model'       => $model,
        'temperature' => $temperature,
        'max_tokens'  => $max_tokens,
        'messages'    => [
            ['role' => 'system', 'content' => 'Eres un analista de contenidos web senior y redactas en español en HTML semántico válido, sin Markdown ni fences.'],
            ['role' => 'user',   'content' => $user_prompt],
        ],
    ];
    $args = [
        'timeout' => 120,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ];
    return wp_remote_post($endpoint, $args);
}