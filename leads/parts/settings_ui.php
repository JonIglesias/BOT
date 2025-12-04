<?php
if (!defined('ABSPATH')) exit;

/**
 * Render del panel de Ajustes dentro de la pestaña "Configuración".
 */
if (!function_exists('phsbot_leads_render_settings_panel')) {
    function phsbot_leads_render_settings_panel() {
        if (!current_user_can('manage_options')) return;
        $s = phsbot_leads_settings();
        $action = esc_url(admin_url('admin-post.php'));
        ?>
        <div class="phsbot-card" style="background:#fff;border:1px solid #e2e2e2;border-radius:8px;padding:16px;margin:10px 0 18px;">
            <form method="post" action="<?php echo $action; ?>">
                <h2 style="margin:0 0 10px;"><?php echo esc_html__('Ajustes de Leads', 'phsbot'); ?></h2>
                <p style="margin:0 0 12px;color:#555;"><?php echo esc_html__('Configura cómo se guardan, resumen y valoran los leads, y cuándo se notifican por Telegram/email.', 'phsbot'); ?></p>
                <?php wp_nonce_field('phsbot_leads_save_settings', 'phsbot_leads_nonce'); ?>
                <input type="hidden" name="action" value="phsbot_leads_save_settings" />
                <input type="hidden" name="return_tab" value="settings" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Guardar conversaciones', 'phsbot'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_store" value="1" <?php checked(1, (int)$s['enable_store']); ?> />
                                <?php echo esc_html__('Activar (desde Chat.php). Desmárcalo para pruebas sin persistencia.', 'phsbot'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Umbral de Telegram', 'phsbot'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="10" name="telegram_threshold" value="<?php echo esc_attr($s['telegram_threshold']); ?>" />
                            <p class="description"><?php echo esc_html__('Se enviará a Telegram si score ≥ umbral (y siempre si hay teléfono).', 'phsbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Email de notificación', 'phsbot'); ?></th>
                        <td>
                            <input type="email" name="notify_email" value="<?php echo esc_attr($s['notify_email']); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Si lo dejas vacío usará el email del administrador.', 'phsbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Email para digest diario', 'phsbot'); ?></th>
                        <td>
                            <input type="email" name="digest_email" value="<?php echo esc_attr($s['digest_email']); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Resumen diario de nuevos, abiertos y cerrados.', 'phsbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Prompt de resumen', 'phsbot'); ?></th>
                        <td>
                            <textarea name="summary_prompt" rows="3" class="large-text code"><?php echo esc_textarea($s['summary_prompt']); ?></textarea>
                            <p style="margin-top:6px;">
  <button type="button" class="button" id="phsbot-reset-summary">
    Restaurar “Prompt de resumen”
  </button>
</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Prompt de scoring', 'phsbot'); ?></th>
                        <td>
                            <textarea name="scoring_prompt" rows="3" class="large-text code"><?php echo esc_textarea($s['scoring_prompt']); ?></textarea>
                            <p style="margin-top:6px;">
  <button type="button" class="button" id="phsbot-reset-scoring">
    Restaurar “Prompt de scoring”
  </button>
</p>
                        </td>
                    </tr>
                </table>
<?php
/* ======== BOTONES RESTAURAR POR DEFECTO (Prompts) ======== */
$__phs_defaults = function_exists('phsbot_leads_settings_defaults') ? phsbot_leads_settings_defaults() : array();
$__def_summary  = isset($__phs_defaults['summary_prompt']) ? $__phs_defaults['summary_prompt'] : '';
$__def_scoring  = isset($__phs_defaults['scoring_prompt']) ? $__phs_defaults['scoring_prompt'] : '';
?>


<script>
(function(){
  const defSummary = <?php echo wp_json_encode($__def_summary); ?>;
  const defScoring = <?php echo wp_json_encode($__def_scoring); ?>;

  const $$ = (sel) => document.querySelector(sel);

  const btnSum = document.getElementById('phsbot-reset-summary');
  if (btnSum) btnSum.addEventListener('click', function(){
    const ta = $$('textarea[name="summary_prompt"]');
    if (ta) { ta.value = defSummary; ta.dispatchEvent(new Event('input', {bubbles:true})); }
  });

  const btnSco = document.getElementById('phsbot-reset-scoring');
  if (btnSco) btnSco.addEventListener('click', function(){
    const ta = $$('textarea[name="scoring_prompt"]');
    if (ta) { ta.value = defScoring; ta.dispatchEvent(new Event('input', {bubbles:true})); }
  });
})();
</script>


                <p style="margin-top:10px;">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar ajustes','phsbot'); ?></button>
                    <a class="button" id="phsbot-leads-reset-browser" href="#" onclick="return false;"><?php echo esc_html__('Reset chat (navegador)','phsbot'); ?></a>
                    <span style="margin-left:8px;color:#666;">
                        <?php
                        $v = (int) get_option(PHSBOT_CLIENT_RESET_OPT, 0);
                        echo sprintf( esc_html__('Versión de cliente: %d (al reset sube +1)', 'phsbot'), $v );
                        ?>
                    </span>
                </p>
            </form>
        </div>
        <?php
    }
}

/** Guardado de ajustes: vuelve a la pestaña "Configuración" */
add_action('admin_post_phsbot_leads_save_settings', function(){
    if (!current_user_can('manage_options')) wp_die('forbidden', 403);
    check_admin_referer('phsbot_leads_save_settings', 'phsbot_leads_nonce');

    $s = phsbot_leads_settings(); // arr base
    $s['enable_store']       = isset($_POST['enable_store']) ? 1 : 0;
    $s['telegram_threshold'] = isset($_POST['telegram_threshold']) ? floatval($_POST['telegram_threshold']) : $s['telegram_threshold'];
    $s['notify_email']       = isset($_POST['notify_email']) ? sanitize_email(wp_unslash($_POST['notify_email'])) : $s['notify_email'];
    $s['digest_email']       = isset($_POST['digest_email']) ? sanitize_email(wp_unslash($_POST['digest_email'])) : $s['digest_email'];
    $s['summary_prompt']     = isset($_POST['summary_prompt']) ? wp_kses_post(wp_unslash($_POST['summary_prompt'])) : $s['summary_prompt'];
    $s['scoring_prompt']     = isset($_POST['scoring_prompt']) ? wp_kses_post(wp_unslash($_POST['scoring_prompt'])) : $s['scoring_prompt'];

    update_option(PHSBOT_LEADS_SETTINGS_OPT, $s, false);

    // Redirige a la pestaña 'settings' tras guardar
    $url = add_query_arg(array('page'=>'phsbot-leads','tab'=>'settings','phsbot_saved'=>1), admin_url('admin.php'));
    wp_safe_redirect($url);
    exit;
});