  var ADIOS_windows = {};
  var desktop_main_box_history = [];
  var desktop_keyboard_shortcuts = [];

  var adios_menu_hidden = 0;

  function window_popup(controller, params, options) {
    if (typeof options == 'undefined') options = {};

    if (options.type == 'POST') {
      let paramsObj = _ajax_params(params);
      let formHtml = '';

      formHtml = '<form action="' + _APP_URL + '/' + _controller_url(controller, {}, true) + '" method=POST target="adios_popup">';
      for (var i in paramsObj) {
       formHtml += '<input type="hidden" name="' + i + '" value="' + paramsObj[i] + '" />';
      }
      formHtml += '</form>';

      // console.log(_APP_URL + '/' + _controller_url(controller, {}, true));
      // console.log(formHtml);
      $(formHtml).appendTo('body').submit();
    } else {
      window.open(_APP_URL + '/' + _controller_url(controller, params));
    }
  }

  function window_render(controller, params, onclose, options) {
    if (typeof params == 'undefined') params = {};
    if (typeof options == 'undefined') options = {};

    params.__IS_WINDOW__ = '1';

    $('.adios.main-content .windows').addClass('update-in-progress');

    setTimeout(function() {
      _ajax_read(controller, params, function(html) {

        // if (params.windowParams && params.windowParams.uid) {
        //   $('#' + params.windowParams.uid).remove();
        // }

        $('.adios.main-content .windows').show();
        $('.adios.main-content .windows').removeClass('update-in-progress');

        $('.adios.main-content .windows .windows-content').append(html);
        // window_post_render(html, controller, params, options);

      });

    }, 0);
  }

  function window_post_render(html, controller, url, params, options) {
  // console.log(url);
  // alert('a');
    // $('.adios.main-content .windows .windows-content').append(html);

    windowId = $('.adios.ui.Window')
      .last()
      .attr('id')
    ;

    let sameWindows = $('.adios.ui.Window[id="' + windowId + '"]');

    if (sameWindows.length > 1) {
      sameWindows.eq(0).remove();
    }

    if ($('.adios.ui.Window').length == 1) {
      $('#' + windowId).addClass('inline');
    } else {
      $('#' + windowId).addClass('modal');
    }

    if (typeof options.onAfterRender == 'function') {
      options.onAfterRender(windowId);
    }

    if ($('.adios.ui.Window').length == 1 && url != '') {
      desktop_main_box_history_push(
        url,
        params,
        $('.adios.main-content').html(),
        options
      );
    }

    ADIOS_windows[windowId] = {
      'controller': controller,
      'url': url,
      'params': params,
      'onclose': onclose,
    };
  }

  function window_refresh(windowId) {
    let win = $('#' + windowId);

    if (win.length > 0) {
      win
        .attr('id', win.attr('id') + '_TO_BE_REMOVED')
      ;
      window_render(
        ADIOS_windows[windowId]['controller'],
        ADIOS_windows[windowId]['params'],
        ADIOS_windows[windowId]['onclick'],
      );

      setTimeout(function() {
        win.remove();
      }, 500);
    }
  }

  function window_close(windowId, oncloseParams) {
    if (!ADIOS_windows[windowId]) {
      // okno bolo otvarane cez URL
      window.location.href = _APP_URL;
    } else {

      if ($('.adios.main-content .adios.ui.Window').length == 1) {
        window.history.back();
      }

      $('#' + windowId).find('a.--onclose-href').trigger('click');
      $('#' + windowId).remove();

      if ($('.adios.main-content .adios.ui.Window').length == 0) {
        $('.adios.main-content .windows').hide();
      }

      if (typeof ADIOS_windows[windowId]['onclose'] == 'function') {
        ADIOS_windows[windowId]['onclose'](oncloseParams);
      }

    }

  }

  function window_close_all() {
    for (windowId in ADIOS_windows) {
      window_close(windowId);
    }
  }

  function desktop_update(controller, params, options) {
    desktop_render(controller, params, options);
  };

  function desktop_render(controller, params, options) {
    if (typeof params == 'undefined') params = {};
    if (typeof options == 'undefined') options = {};

    $('.adios.main-content').css('opacity', 0.5);

    if (options.type == 'POST') {
      let paramsObj = _ajax_params(params);
      let formHtml = '';

      formHtml = '<form action="' + _APP_URL + '/' + _controller_url(controller, {}, true) + '" method=POST>';
      for (var i in paramsObj) {
       formHtml += '<input type="hidden" name="' + i + '" value="' + paramsObj[i] + '" />';
      }
      formHtml += '</form>';

      $(formHtml).appendTo('body').submit();
    } else {
      window.location.href = _APP_URL + '/' + _controller_url(controller, params, true);
    }

  }


  function desktop_main_box_history_push(controller, params, html, options) {
    if (typeof options != 'object') options = {};

    window.history.pushState(
      {
        "html": html,
        "pageTitle": document.title,
      },
      "",
      _APP_URL + '/' + _controller_url(controller, params, true)
    );

  };

  window.onpopstate = function (e) {
    if (e.state) {
      document.title = e.state.pageTitle;
    }
  };



  function _alert(text, params) {

    if (typeof params == 'undefined') params = {};

    params.resizable = params.resizable ?? false;
    params.modal = params.modal ?? true;
    params.width = params.width ?? 450;
    params.title = (params.title ?? _TRANSLATIONS['Information']) ?? 'Information';
    params.titleClass = params.titleClass ?? '';
    params.contentClass = params.contentClass ?? '';
    params.confirmButtonText = params.confirmButtonText ?? _TRANSLATIONS['OK, I understand'];
    params.confirmButtonClass = params.confirmButtonClass ?? '';
    params.cancelButtonText = params.cancelButtonText ?? _TRANSLATIONS['Cancel'];
    params.cancelButtonClass = params.cancelButtonClass ?? '';

      
    if (params.width > $(window).width()) params.width = $(window).width() - 20;
    if (params.buttons == '' || typeof params.buttons == 'undefined') {
      params.buttons = [
        {
          'text': params.confirmButtonText,
          'fa_icon': 'fas fa-check',
          'class': 'btn-primary btn-icon-split ' + params.confirmButtonClass,
          'onclick': function() {
            if (typeof params.onConfirm == 'function') params.onConfirm();
            $(this).closest('.adios.ui.window').remove();
          }
        }
      ];

      if (typeof params.onConfirm == 'function') {
        params.buttons.push({
          'text': params.cancelButtonText,
          'fa_icon': 'fas fa-times',
          'class': 'btn-secondary btn-icon-split ' + params.cancelButtonClass,
          'onclick': function () {
            $(this).closest('.adios.ui.window').remove();
          }
        });
      }
    }

    let buttonsHtml = '';
    for (let i in params.buttons) {
      let button = params.buttons[i];
      buttonsHtml += '<button type="button" class="btn ' + button.class + '" btn-index="' + i + '">';
      buttonsHtml +=   '<span class="icon">';
      buttonsHtml +=     '<i class="' + button.fa_icon + ' mr-1"></i>';
      buttonsHtml +=   '</span>';
      buttonsHtml +=   '<span class="text">' + button.text + '</span>';
      buttonsHtml += '</button>';
    }

    let html = '<div class="adios ui window modal">';
    html += '  <div class="modal-dialog shadow" role="document">';
    html += '      <div class="modal-content ' + params.contentClass + '">';
    html += '          <div class="modal-header ' + params.titleClass + '">';
    html += '              <h5 class="modal-title">' + params.title + '</h5>';
    html += '              <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
    html += '                  <span aria-hidden="true">&times;</span>';
    html += '              </button>';
    html += '          </div>';
    html += '          <div class="modal-body">';
    html += '              <p>' + text + '</p>';
    html += '          </div>';
    html += '          <div class="modal-footer">';
    html += buttonsHtml;
    html += '          </div>';
    html += '      </div>';
    html += '  </div>';
    html += '</div>';

    var window_div = $(html)
      .prependTo('body')
    ;

    $(window_div).find("button.close").bind('click', function() {
      $(this).closest('.adios.ui.window').remove();
    });

    $(window_div).find(".btn-primary").focus();

    for (let i in params.buttons) {
      $(window_div).find("button[btn-index='" + i + "']").bind('click', params.buttons[i].onclick)
    }
  };

  function _warning(message) {
    _alert(
      message,
      {
        title: 'Warning',
        titleClass: 'bg-warning text-white',
        contentClass: 'text-warning',
        confirmButtonClass: 'btn-warning',
      }
    );
  }

  function _fatal(message) {
    _alert(
      message,
      {
        title: 'Fatal error',
        titleClass: 'bg-danger text-white',
        contentClass: 'text-danger',
        confirmButtonClass: 'btn-danger',
      }
    );
  }

  function _confirm(text, params, callback) {
    params.title = params.title ?? _TRANSLATIONS['Confirmation'];
    params.onConfirm = callback;
    _alert(text, params);
  }

  function _prompt(text, params, callback) {
    params.title = 'Prompt';
    params.onConfirm = callback;

    if (params.use_textarea) text += "<br/><textarea style='width:95%;height:70px;' id='desktop_confirm_prompt_input'></textarea><br/>";
    else text += "<br/><input type='text' style='width:95%;' id='desktop_confirm_prompt_input' /><br/><br/>";

    params.buttons = [
        {
            'text': 'OK',
            'class': 'btn-primary',
            'onclick': function() {
                if (typeof params.onConfirm == 'function') params.onConfirm($('#desktop_confirm_prompt_input').val());
                $(this).closest('.adios.ui.window').remove();
            },
        },
        {
            'text': 'Zrušiť',
            'class': 'btn-secondary',
            'onclick': function () {
                $(this).closest('.adios.ui.window').remove();
            },
        }
    ];

    _alert(text, params);
  };



  