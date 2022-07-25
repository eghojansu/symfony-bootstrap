import { Controller } from '@hotwired/stimulus'
import { createElement } from './common'
import { request } from './shared'
import notify from './notify'

export default class extends Controller {
  get ignores() {
    return []
  }

  get fieldElements() {
    return this.fields.map(
      field => this.element.querySelectorAll(`[name="${field}"]`),
    ).reduce((elements, fieldElements) => [...elements, ...fieldElements], [])
  }

  get passwordElements() {
    return this.fieldElements.filter(el => 'password' === el.type || 'password' === el.dataset.toggle)
  }

  get errorContainer() {
    return this.element.querySelector('[data-holder=error]')
  }

  initialize() {
    this.fields = []
    this.processing = false
    this.lastResult = null
  }

  connect() {
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

  showInvalid(el) {
    if (!el) {
      return
    }

    const parent = this.getInputGroup(el)

    if (!parent) {
      return
    }

    if (el.validationMessage) {
      const float = parent.classList.contains('form-floating')

      parent.append(
        createElement('div', {
          class: ['invalid-feedback', 'd-block', float && 'mb-3'],
        }, el.validationMessage),
      )

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

  reset() {
    console.log('resetting')
  }

  async handleSubmit(event) {
    event.preventDefault()

    if (!event.target.checkValidity()) {
      this.fieldElements.forEach(el => this.showInvalid(el))

      return
    }

    const submitHandled = await this.onSubmit(event)

    if (submitHandled) {
      return
    }

    this.processing = true
    const result = await request(this.formBeforeArgs(event))
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
        console.log(errors)
      }
    }

    if (success) {
      const responded = await this.afterSubmit(args)

      if (!responded) {
        notify(message || 'Data has been submitted', true, {
          title: 'Successful'
        })

        const successful = await this.afterSuccess(args)

        if (!successful) {
          if (data?.redirect) {
            setTimeout(() => {
              window.location.assign(data.redirect)
            }, 1200)
          } else if (data?.refresh) {
            setTimeout(() => window.location.reload(), 1200)
          }
        }
      }
    }

    await this.afterComplete(args)

    this.processing = false
    this.lastResult = result
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

  async onSubmit(event) {
    return false
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