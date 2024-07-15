ADIOS.views.Form = {

  change: function(uid, column) {
    let params = {};
    // let formInputs = $('#' + uid).find('input,select,textarea').not('adios-do-not-serialize');
    params.model = $('#' + uid).attr('data-model');
    params.column = column;
    params.formUid = uid;
    params.formData = ADIOS.views.Form.get_values(uid);
    // params.formInputUids = {};

    // console.log(params.formData);

    // formInputs.each(function() {
    //   let inputId = $(this).attr('id');
    //   let column = '';

    //   if (inputId && inputId.indexOf(uid + '_') != -1) {
    //     column = inputId.substring((uid + '_').length, inputId.length);
    //     params.formInputUids[column] = inputId;
    //   }

    // });

    _ajax_read(
      'UI/Form/OnChange',
      params,
      function(res) {
        try {
          let form = document.getElementById(uid);
          console.log(res);

          for (column in res) {
            if (res[column].hasOwnProperty('value')) {
              $(form).find('#' + uid + '_' + column).val(res[column].value);
            }
            
            if (res[column].hasOwnProperty('inputHtml')) {
            // console.log($(form).find('#' + uid + '_' + column).closest('.input-content'), res[column].inputHtml);
              $(form).find('#' + uid + '_' + column).closest('.input-content').html(res[column].inputHtml);
            }
            
            if (res[column].hasOwnProperty('inputCssClass')) {
              $(form).find('#' + uid + '_' + column).closest('.input-content')
                .removeClass()
                .addClass('input-content')
                .addClass(res[column].inputCssClass)
              ;
            }
            
            if (res[column].hasOwnProperty('alert')) {
              _alert(res[column].alert);
            }
            
            if (res[column].hasOwnProperty('warning')) {
              _warning(res[column].warning);
            }
            
            if (res[column].hasOwnProperty('fatal')) {
              _fatal(res[column].fatal);
            }
          }
        } catch (ex) {
          console.log(ex);
        }
      }
    )
  },


  save: function(uid, params, btn) {
    if (typeof params === 'undefined') { params = {}; }

    var data = {};
    data.id = $('#'+uid).attr('data-id');
    data.table = $('#'+uid).attr('data-table');
    data.model = $('#' + uid).attr('data-model');
    data.modelUrlBase = $('#' + uid).attr('data-model-url-base');
    data.values = ADIOS.views.Form.get_values(uid);

    var allowed = true;

    let tmpBtnText = $(btn).find('.text').text();
    $(btn).find('.text').text('Saving...');
    setTimeout(function() {
      $(btn).find('.text').text(tmpBtnText);
    }, 300);

    $('.' + uid + '_button').attr('disabled', 'disabled');

    if (typeof window[uid + '_onbeforesave'] == 'function') {
      var c_res = window[uid + '_onbeforesave'](uid, data, {});
      data = c_res['data'];
      allowed = c_res['allowed'];
    };

    $('#' + uid + ' .save_error_info').hide();
    $('#' + uid + ' .item').removeClass('save_error');
    $('#' + uid + ' .item.has_pattern').each(function () {
      let tmp_input = $(this).find('.input-content input');
      let tmp_select = $(this).find('.input-content select');
      let tmp_textarea = $(this).find('.input-content textarea');

      if (
        (tmp_input.length != 0 && !tmp_input.get(0).checkValidity())
        || (tmp_select.length != 0 && !tmp_select.get(0).checkValidity())
        || (tmp_textarea.length != 0 && !tmp_textarea.get(0).checkValidity())
      ) {
        $('#' + uid + ' .save_error_info').fadeIn();
        $(this).addClass('save_error');
        allowed = false;
      }
    });

    $('#' + uid + ' .item.required').each(function() {
      let tmp_input = $(this).find('.input-content input[data-is-adios-input="1"]');
      let tmp_select = $(this).find('.input-content select[data-is-adios-input="1"]');
      let tmp_textarea = $(this).find('.input-content textarea[data-is-adios-input="1"]');

      if (
        (tmp_input.length != 0 && tmp_input.val() == '')
        || (tmp_select.val() == '' || tmp_select.val() == 0 || tmp_select.val() === null)
        || (tmp_textarea.length != 0 && tmp_textarea.val() == '')
      ) {
        $('#' + uid + ' .save_error_info').fadeIn();
        // setTimeout(function() {
        //   $('#' + uid + ' .save_error_info').fadeOut();
        // }, 1000);
        $(this).addClass('save_error');
        allowed = false;
      }
    });

    if (allowed) {
      var controller = $('#'+uid).attr('data-save-controller');
      var reopen_after_save = $('#'+uid).attr('data-reopen-after-save');

      _ajax_read(controller, data, function(saved_id) {

        $('.'+uid+'_button').removeAttr('disabled');

        if (isNaN(saved_id)) _alert(saved_id); else {

          if (data.id < 0) data.inserted_id = saved_id;
          else data.inserted_id = 0;

          data.id = saved_id;

          if (typeof window[uid + '_onaftersave'] == 'function') {
            window[uid + '_onaftersave'](uid, data, {});
          }

          ADIOS.views.Form.close(uid);

          if (reopen_after_save) {
            ADIOS.renderWindow(data.modelUrlBase + '/' + data.id + '/edit');
          }

          if (typeof params.aftersave_callback === 'function') {
            params.aftersave_callback(uid, data);
          } else if(typeof window[params.aftersave_callback] === 'function') {
            window[params.aftersave_callback](uid, data);
          }
        }
      });
    } else {
      $('.'+uid+'_button').removeAttr('disabled');
    };

  },

  print: function(uid) {
    let modelUrlBase = $('#' + uid).attr('data-model-url-base');
    let id = $('#'+uid).attr('data-id');

    window_popup(modelUrlBase + '/' + id + '/print');
  },

  delete: function(uid) {

    var data = {};
    data.model = $('#'+uid).attr('data-model');
    data.id = $('#' + uid).attr('data-id');

    var controller = $('#'+uid).attr('data-delete-controller');

    $('.' + uid + '_button').attr('disabled', 'disabled');

    _ajax_read(controller, data, function(_saved_id){
      $('.'+uid+'_button').removeAttr('disabled');

      if (isNaN(_saved_id)) {
        _alert(_saved_id);
      } else {

        var func_name = uid+'_onafterdelete';
        if (typeof window[func_name] == 'function') {
          window[func_name](uid, data, {});
        }

        ADIOS.views.Form.close(uid);

      }
    });

  },

  copy: function(uid, params){
    if(typeof params === 'undefined'){ params = {}; }

    var data = {};
    $('.'+uid+'_button').attr('disabled', 'disabled');
    data.id = $('#'+uid).attr('data-id');
    data.table = $('#'+uid).attr('data-table');
    var allowed = true;

    var func_name = uid+'_onbeforecopy';
    if (typeof window[func_name] == 'function') {
      var c_res = window[func_name](uid, data, {});
      data = c_res['data'];
      allowed = c_res['allowed'];
    };

    if (allowed){
      var controller = $('#'+uid).attr('data-copy-controller');

      _ajax_read(controller, data, function(_saved_id) {
        $('.'+uid+'_button').removeAttr('disabled');
        if (isNaN(_saved_id)) _alert(_saved_id); else {

          var func_name = uid+'_onaftercopy';
          data.inserted_id = _saved_id;
          if (typeof window[func_name] == 'function') {
            window[func_name](uid, data, {});
          }

          if ($('#'+uid).attr('data-form-type') == 'desktop'){
            desktop_render('UI/Form', {formType: 'desktop', table: data.table, id: _saved_id});
          }else{
            ADIOS.renderWindow('UI/Form', {table: data.table, id: _saved_id});
          };

          if(typeof params.aftercopy_callback === 'function'){
            params.aftercopy_callback(uid, data);
          }else if(typeof window[params.aftercopy_callback] === 'function'){
            window[params.aftercopy_callback](uid, data);
          }
        }
      });
    }else{
      $('.'+uid+'_button').removeAttr('disabled');
    };

  },

  close: function(uid) {
    let is_in_window = $('#' + uid).attr('data-is-in-window') == '1';

    var data = {};
    data.id = $('#' + uid).attr('data-id');
    data.table = $('#' + uid).attr('data-table');
    data.model = $('#' + uid).attr('data-model');
    data.values = ADIOS.views.Form.get_values(uid);

    var allowed = true;

    if (typeof window[uid + '_onbeforeclose'] == 'function') {
      var c_res = window[uid + '_onbeforeclose'](uid, data, {});
      data = c_res['data'];
      allowed = c_res['allowed'];
    }

    if (allowed) {
      if (is_in_window) {
        $('.' + uid + '_button').attr('disabled', 'disabled');

        window_close(
          $('#' + uid).attr('data-window-uid'),
          {'uid': uid, 'data': data}
        );

        ui_table_refresh_by_model(data.model);

        if (typeof window[uid + '_onafterclose'] == 'function') {
          window[uid + '_onafterclose'](uid, data, {});
        }

        $('.' + uid + '_button').removeAttr('disabled');
      } else {
        window.location.href = _APP_URL;
      }
    }
  },

  get_values: function(uid, input_id_prefix) {
    if (typeof input_id_prefix == 'undefined') input_id_prefix = uid + '_';

    var div = document.getElementById(uid);

    var result = {
      'id': $('#' + uid).attr('data-id')
    };

    var inputs = div.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++) if (inputs[i].id != '' && $(inputs[i]).attr('adios-do-not-serialize') != '1') {
      var _value = inputs[i].value;
      if (inputs[i].type == 'checkbox') _value = inputs[i].checked ? '1' : '0';

      if (inputs[i].id.indexOf(input_id_prefix) == -1) input_id = inputs[i].id;
      else input_id = inputs[i].id.substring(input_id_prefix.length, inputs[i].id.length);
      if (typeof _value != 'undefined') result[input_id] = _value;
    };

    var inputs = div.getElementsByTagName('select');
    for (var i = 0; i < inputs.length; i++) if (inputs[i].id != '' && $(inputs[i]).attr('adios-do-not-serialize') != '1') {
      if (inputs[i].id.indexOf(input_id_prefix) == -1) input_id = inputs[i].id;
      else input_id = inputs[i].id.substring(input_id_prefix.length, inputs[i].id.length);

      if (typeof inputs[i].value != 'undefined') {
        var val = null;
        if (inputs[i].multiple) {
          val = [];
          for (var j = inputs[i].options.length-1; j >= 0; j--) {
            if (inputs[i].options[j].selected) {
              val.push(inputs[i].options[j].value);
            };
          };
        } else {
          val = inputs[i].value;
        };
        result[input_id] = val;
      };
    };

    var inputs = div.getElementsByTagName('textarea');
    for (var i = 0; i < inputs.length; i++) if (inputs[i].id != '' && $(inputs[i]).attr('adios-do-not-serialize') != '1') {
      if (inputs[i].id.indexOf(input_id_prefix) == -1) input_id = inputs[i].id;
      else input_id = inputs[i].id.substring(input_id_prefix.length, inputs[i].id.length);
      if (typeof inputs[i].value != 'undefined') result[input_id] = inputs[i].value;
    };

    return result;

  }
}