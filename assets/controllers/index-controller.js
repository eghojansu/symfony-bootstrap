import { Controller } from '@hotwired/stimulus'
import '../lib/datatable'

export default class extends Controller {
  initialize() {
    if (window.tableOptions) {
      $(this.element).DataTable(window.tableOptions)
      // this.table = new DataTable(this.element, window.tableOptions)
      // console.log(window.tableOptions)
    }
  }
}