import { listen } from './lib/dom'
import notify, { confirm } from './lib/notify'
import { request } from './lib/shared'

(() => {
  listen('[data-confirm]', 'click', async event => {
    const config = {
      url: event.target.dataset.confirm,
      method: event.target.dataset.method || 'POST',
    }

    if (!config.url.match(/:/)) {
      config.url = `${appx.baseURL}${config.url}`
    }

    const { isConfirmed, value: { success, message, data } = {} } = await confirm(() => request(config))

    if (isConfirmed && success) {
      notify(message, true)

      if (data?.redirect) {
        setTimeout(() => window.location.assign(data.redirect), 1200)
      } else if (data?.refresh) {
        setTimeout(() => window.location.reload(), 1200)
      }
    }
  })
})()