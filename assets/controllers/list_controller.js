import { Controller } from '@hotwired/stimulus'
import DataTable from 'datatables.net-bs5'

export default class extends Controller {
  initialize() {
    if (window.tableOptions) {
      this.table = new DataTable(this.element, window.tableOptions)
    }
  }
}