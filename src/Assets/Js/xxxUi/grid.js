ADIOS.views.Grid = {
  updateArea: function(uid, areaName, action, params) {
    _ajax_update(
      action,
      params,
      '#' + uid + ' .area[data-area="' + areaName + '"]'
    )
  }
}