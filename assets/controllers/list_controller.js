import { Controller } from '@hotwired/stimulus'
import DataTable from 'datatables.net-bs5'

export default class extends Controller {
  connect() {
    this.table = new DataTable(this.element)
  }
}