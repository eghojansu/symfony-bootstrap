import { Controller } from '@hotwired/stimulus'
import { createElement } from '../lib/dom'
import { request } from '../lib/shared'
import notify from '../lib/notify'

export default class extends Controller {
  get ignores() {
    return []
  }

  get notifyTimeout() {
    return 3500
  }

  get fieldElements() {
    return this.fields.map(
      field => this.element.querySelectorAll(`[name="${field}"]`),
    ).reduce((elements, fieldElements) => [...elements, ...fieldElements], [])
  }

  get passwordElements() {
    return this.fieldElements.filter(el => 'password' === el.type || 'password' === el.dataset.toggle)
  }

  get submitElement() {
    return this.element.querySelector('[type=submit]')
  }

  get cancelElement() {
    return this.element.querySelector('[type=reset]')
  }

  get errorContainer() {
    return this.element.querySelector('[data-holder=error]')
  }

  initialize() {
    this.fields = []
    this.lastResult = null
    this.processing = false

    this.element.setAttribute('novalidate', true)
    this.element.addEventListener('submit', this.handleSubmit.bind(this))
    this.element.querySelectorAll('[name]').forEach(this.registerInput.bind(this))
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

  showInvalid(el, message) {
    if (!el) {
      return
    }

    const parent = this.getInputGroup(el)

    if (!parent) {
      return
    }

    const error = message || el.validationMessage

    this.submitElement.disabled = false
    el.classList.remove('is-invalid')
    parent.querySelector('.invalid-feedback')?.remove()

    if (error) {
      this.submitElement.disabled = true
      el.classList.add('is-invalid')
      parent.append(
        createElement('div', {
          class: ['invalid-feedback', 'd-block', parent.classList.contains('form-floating') && 'mb-3'],
        }, error),
      )

      return
    }
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

  reset() {
    console.log('resetting')
  }

  disable(disabled = true) {
    this.fieldElements.forEach(el => el.disabled = disabled)
  }

  enable() {
    this.disable(false)
  }

  progress(processing = true) {
    this.processing = processing
    this.disable(processing)

    this.submitElement.disabled = processing
    this.submitElement.classList.remove('btn-spinning')

    if (processing) {
      this.submitElement.classList.add('btn-spinning')
    }

    if (this.cancelElement) {
      this.cancelElement.disabled = processing
    }
  }

  async handleSubmit(event) {
    event.preventDefault()

    if (!event.target.checkValidity()) {
      this.fieldElements.forEach(el => this.showInvalid(el))

      return
    }

    const config = this.formBeforeArgs(event)

    this.progress()

    const result = await request(config)
    const { success, message, data, errors } = result
    const args = this.formAfterArgs(event, result)

    this.passwordElements.forEach(el => el.value = '')

    if (errors) {
      if (Array.isArray(errors)) {
        if (this.errorContainer) {
          const alert = this.errorContainer.querySelector('.alert')

          alert?.remove()
          this.errorContainer.append(
            createElement('div', {
              class: 'alert alert-warning alert-dismissible fade show',
              role: 'alert',
            }, [
              errors.join(', '),
              createElement('button', {
                type: 'button',
                class: 'btn-close',
                data: {
                  bsDismiss: 'alert',
                  ariaLabel: 'Close',
                }
              })
            ])
          )
        }
      } else {
        Object.entries(errors).forEach(([field, errors]) => this.showInvalid(
          this.element.querySelector(`[name="${field}"]`)
          || this.element.querySelector(`[name="${this.element.name || ''}[${field}]"]`),
          errors.join(', '),
        ))
      }
    }

    if (success) {
      const responseHandled = await this.afterSubmit(args)

      if (!responseHandled) {
        notify(message || 'Data has been submitted', true, {
          title: 'Successful',
          timer: this.notifyTimeout,
          willClose: async () => {
            const successHandled = await this.afterSuccess(args)

            if (!successHandled) {
              if (data?.redirect) {
                window.location.assign(data.redirect)
              } else if (data?.refresh) {
                window.location.reload()
              }
            }
          }
        })
      }
    }

    await this.afterComplete(args)

    this.lastResult = result
    this.progress(false)
  }

  formBeforeArgs(event) {
    return {
      url: event.target.action,
      method: event.target.method,
      data: new FormData(this.element),
    }
  }

  formAfterArgs(event, result) {
    return { event, ...result }
  }

  async afterSubmit(args) {
    return false
  }

  async afterComplete(args) {
    return false
  }

  async afterSuccess(args) {
    return false
  }
}