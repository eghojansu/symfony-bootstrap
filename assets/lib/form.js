import { Controller } from '@hotwired/stimulus'
import { createElement } from './common'
import { request } from './shared'

export default class extends Controller {
  get ignores() {
    return []
  }

  get fieldElements() {
    return this.fields.map(field => this.element.querySelector(`[name="${field}"]`))
  }

  initialize() {
    this.fields = []
  }

  connect() {
    this.element.setAttribute('novalidate', true)
    this.element.addEventListener('submit', this.handleSubmit.bind(this))
    this.element.querySelectorAll('[name]').forEach(this.registerInput.bind(this))
  }

  handleSubmit(event) {
    event.preventDefault()

    if (!event.target.checkValidity()) {
      this.fieldElements.forEach(el => this.showInvalid(el))

      return
    }

    const config = {
      url: event.target.action,
      method: event.target.method,
      data: Object.fromEntries(
        this.fieldElements.map(el => [el.name, this.getFieldValue(el)]),
      ),
    }

    console.log('submit', config)
  }

  registerInput(el) {
    if (this.ignores.includes(el.name)) {
      return
    }

    const isInput = el.tagName.match(/^input$/i)
    const isText =  el.tagName.match(/^text$/i)
    const isRadio = isInput && el.type.match(/^(checkbox|radio)$/i)

    if (!isRadio || isText) {
      el.addEventListener('input', event => this.showInvalid(event.target))
      this.fields.push(el.name)

      return
    }
  }

  showInvalid(el) {
    if (!el) {
      return
    }

    const parent = this.getInputGroup(el)

    if (!parent) {
      return
    }

    if (el.validationMessage ) {
      const float = parent.classList.contains('form-floating')

      parent.appendChild(createElement('div', {
        class: ['invalid-feedback', 'd-block', float && 'mb-3'],
      }, el.validationMessage))

      return
    }

    parent.querySelector('.invalid-feedback')?.remove()
  }

  getInputGroup(el) {
    let parent = el.closest('div')

    if (parent?.classList.contains('input-group')) {
      parent = el.closest('div')
    }

    if (parent?.closest('form') !== this.element) {
      parent = null
    }

    return parent
  }
}