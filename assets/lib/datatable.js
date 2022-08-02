import 'datatables.net-bs5'
import 'datatables.net-buttons-bs5'

$.fn.dataTable.ext.buttons.link = {
  text: '<i class="bi-house"></i>',
  action: function (e, dt, node, config) {
    console.log(config)
  },
}