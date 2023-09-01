
  function ui_tabs_change_tab(uid, tab) {
    let scrollTo = $('#' + uid + '_tab_content_' + tab);
    let container = $('#' + uid + ' .tab_contents');
    let tabs = $(scrollTo).closest('.adios.ui.Tabs');

    tabs.find('.tab_title').removeClass('active');
    tabs.find('.tab_title_' + tab).addClass('active');

    container.find('.tab_content').removeClass('active');
    scrollTo.addClass('active');
  };

  function ui_tab_load_content(el_id, action, params){

    if ($(el_id).attr('data-content-loaded') != 1 && $(el_id).length){
      setTimeout(function(){
        $(el_id).attr('data-content-loaded', 1);
        _ajax_supdate(action, params, el_id);
      }, 200);
    };
  };