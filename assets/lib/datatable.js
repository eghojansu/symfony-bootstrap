import 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'

$.fn.dataTable.ext.buttons.link = {
  text: 'Link',
  action: function (e, dt, node, config) {
    if (config.url) {
      window.location = config.url
    }
  },
}