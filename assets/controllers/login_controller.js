import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static targets = ['userid', 'password', 'remember', 'csrf_token']

  connect() {
    this.element.addEventListener('submit', event => {
      event.preventDefault()

      console.log(event.target)
    })
  }
}