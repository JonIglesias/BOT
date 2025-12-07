<?php
// PHSBOT – chat/chat-admin.php
// Admin: menú principal (configuración movida a /config/config.php)
if (!defined('ABSPATH')) exit;

/* Menú principal */
add_action('admin_menu', function(){
  add_menu_page('PHSBot', 'PHSBot', 'manage_options', 'phsbot', function(){
    echo '<div class="wrap"><h1>PHSBot</h1></div>';
  }, 'dashicons-format-chat', 60);
}, 60);
