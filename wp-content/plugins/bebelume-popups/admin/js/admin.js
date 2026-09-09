/* Bebelume Popups – Admin JS */
(function ($) {
  'use strict';

  /* ── MONACO INIT ── */
  var editorHTML, editorCSS;

  require.config({ paths: { vs: BPP.monaco_base } });

  require(['vs/editor/editor.main'], function () {
    if (!document.getElementById('bpp-editor-html')) return;

    const monacoTheme = {
      base: 'vs',
      inherit: true,
      rules: [],
      colors: { 'editor.background': '#fafafa' }
    };
    monaco.editor.defineTheme('bpp-light', monacoTheme);

    editorHTML = monaco.editor.create(document.getElementById('bpp-editor-html'), {
      value: window.BPP_INITIAL ? window.BPP_INITIAL.code : '',
      language: 'html',
      theme: 'bpp-light',
      minimap: { enabled: false },
      fontSize: 13,
      lineNumbers: 'on',
      scrollBeyondLastLine: false,
      automaticLayout: true,
      wordWrap: 'on',
      padding: { top: 8, bottom: 8 }
    });

    editorCSS = monaco.editor.create(document.getElementById('bpp-editor-css'), {
      value: window.BPP_INITIAL ? window.BPP_INITIAL.css : '',
      language: 'css',
      theme: 'bpp-light',
      minimap: { enabled: false },
      fontSize: 13,
      lineNumbers: 'on',
      scrollBeyondLastLine: false,
      automaticLayout: true,
      wordWrap: 'on',
      padding: { top: 8, bottom: 8 }
    });
  });

  /* ── BACKDROP TOGGLE ── */
  $(document).on('change', '#bpp-backdrop', function () {
    if ($(this).is(':checked')) {
      $('#bpp-opacity-wrap').slideDown(150);
    } else {
      $('#bpp-opacity-wrap').slideUp(150);
    }
  });

  /* ── SAVE ── */
  $(document).on('click', '#bpp-save', function () {
    var $btn      = $(this);
    var $feedback = $('#bpp-save-feedback');

    var title = $('#bpp-title').val().trim();
    if (!title) {
      $feedback.removeClass('success').addClass('error').text('O título é obrigatório.');
      return;
    }

    $btn.addClass('loading').prop('disabled', true);
    $feedback.removeClass('success error').text('');

    $.ajax({
      url: BPP.ajax_url,
      method: 'POST',
      data: {
        action: 'bpp_save_popup',
        nonce:  BPP.nonce,
        popup_id:          $('#bpp-popup-id').val(),
        popup_title:       title,
        popup_description: $('#bpp-description').val(),
        popup_code:        editorHTML ? editorHTML.getValue() : '',
        popup_css:         editorCSS  ? editorCSS.getValue()  : '',
        popup_backdrop:    $('#bpp-backdrop').is(':checked') ? 1 : 0,
        popup_opacity:     $('#bpp-opacity').val(),
        popup_position:    $('#bpp-position').val(),
        pmpro_exclude_levels: $('input[name="bpp_pmpro_exclude[]"]:checked').map(function(){ return this.value; }).get(),
      },
      success: function (res) {
        if (res.success) {
          $feedback.removeClass('error').addClass('success').text('Popup salvo com sucesso!');
          if (!$('#bpp-popup-id').val()) {
            // Update to edit mode
            $('#bpp-popup-id').val(res.data.id);
            history.replaceState(null, '', '?page=bpp-create&edit=' + res.data.id);
            $('#bpp-save .bpp-btn-label').text('Salvar alterações');
            $('h1').first().text('Editar Popup');
          }
        } else {
          $feedback.removeClass('success').addClass('error').text('Erro ao salvar. Tente novamente.');
        }
      },
      error: function () {
        $feedback.removeClass('success').addClass('error').text('Erro de conexão.');
      },
      complete: function () {
        $btn.removeClass('loading').prop('disabled', false);
      }
    });
  });

  /* ── DELETE ── */
  $(document).on('click', '.bpp-delete', function () {
    var $btn = $(this);
    var id   = $btn.data('id');
    if (!confirm('Tem certeza que deseja excluir este popup?')) return;

    $.ajax({
      url: BPP.ajax_url,
      method: 'POST',
      data: { action: 'bpp_delete_popup', nonce: BPP.nonce, popup_id: id },
      success: function (res) {
        if (res.success) {
          $btn.closest('tr').fadeOut(200, function () { $(this).remove(); });
        }
      }
    });
  });

})(jQuery);
