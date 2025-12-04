/* v1.3.2 external JS (config.js) */
(function($){
  $(function(){
    // --- Tabs ---
    var $tabs = $('.phsbot-config-tabs .nav-tab'),
        $panels = $('.phsbot-config-panel');

    function showTab(sel){
      if(!sel || !$(sel).length) sel = '#tab-aspecto';
      $tabs.removeClass('nav-tab-active').attr('aria-selected','false');
      $panels.attr('aria-hidden','true');
      $tabs.filter('[href="'+sel+'"]').addClass('nav-tab-active').attr('aria-selected','true');
      $(sel).attr('aria-hidden','false');
    }
    $tabs.on('click', function(e){
      e.preventDefault();
      var sel = $(this).attr('href');
      if(history && history.replaceState) history.replaceState(null, '', sel);
      showTab(sel);
    });
    showTab(location.hash && $(location.hash).length ? location.hash : '#tab-aspecto');

    // --- Preview helpers ---
    var $pv = $('#phsbot-preview');
    var $pvMessages = $('#phsbot-preview .phs-messages');

    function setVar(name, value){
      if($pv.length) $pv[0].style.setProperty(name, value);
    }

    // --- Color bind helper ---
    function bindColor(name, varName){
      var $i = $('input[name="'+name+'"]');
      if(!$i.length) return;

      // inicial
      setVar(varName, $i.val() || '');
      if ($.fn.wpColorPicker){
        $i.wpColorPicker({
          change: function(e, ui){ setVar(varName, ui.color.toString()); },
          clear:  function(){ setVar(varName, ''); }
        });
      } else {
        $i.on('input change', function(){ setVar(varName, $i.val() || ''); });
      }
    }

    // --- Colors ---
    bindColor('color_primary',     '--phsbot-primary');
    bindColor('color_secondary',   '--phsbot-secondary');
    bindColor('color_background',  '--phsbot-bg');
    bindColor('color_text',        '--phsbot-text');
    bindColor('color_bot_bubble',  '--phsbot-bot-bubble');
    bindColor('color_user_bubble', '--phsbot-user-bubble');
    bindColor('color_whatsapp',    '--phsbot-whatsapp');
    bindColor('color_footer',      '--phsbot-footer');

    // --- Slider bind helper (con hook onUpdate opcional) ---
    function bindSlider(id, cssVar, unit, hiddenId, onUpdate){
      var $el = $('#'+id), $label = $('#'+id+'_val');
      if(!$el.length) return;

      function upd(){
        var v = parseInt($el.val(), 10);
        if (isNaN(v)) v = 0;

        // límites si es el de fuente
        if(id === 'bubble_font_size'){
          if(v < 12) v = 12;
          if(v > 22) v = 22;
          $el.val(v); // clamp en el propio control
        }

        var vv = v + (unit || '');
        if($label.length) $label.text(v + ' ' + (unit || ''));

        // Actualiza variable CSS
        if(cssVar) setVar(cssVar, vv);

        // Persistencia si hay hidden
        if(hiddenId){
          var $h = $('#'+hiddenId);
          if($h.length) $h.val(vv);
        }

        // Hook adicional
        if(typeof onUpdate === 'function'){ onUpdate(v, vv); }
      }

      $el.on('input change', upd);
      upd(); // inicial
    }

    // Width/height sliders (actualizan hidden para persistir)
    bindSlider('chat_width_slider',  '--phsbot-width',  'px', 'chat_width');
    bindSlider('chat_height_slider', '--phsbot-height', 'px', 'chat_height');

    // Tamaño de fuente de los globos: SIN hidden (el range ya tiene name)
    // Además, aplicamos directamente a la vista previa por si alguna regla antigua pisa la var.
    bindSlider('bubble_font_size', '--phsbot-bubble-fs', 'px', null, function(v, vv){
      if ($pvMessages.length) $pvMessages[0].style.fontSize = vv; // inline > css
    });

    // Otros (si existen en la UI)
    bindSlider('btn_height',     '--phsbot-btn-h',    'px');
    bindSlider('head_btn_size',  '--phsbot-head-btn', 'px');
    bindSlider('mic_stroke_w',   '--mic-stroke-w',    'px');
  });
})(jQuery);

// Fallback: inicializa cualquier .phsbot-color suelto
jQuery(function($){
  if ($.fn.wpColorPicker) $('.phsbot-color').wpColorPicker();
});
