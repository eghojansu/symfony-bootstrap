import axios from 'axios'
import notify from './notify'

export const request = (() => {
  const req = axios.create({
    headers: {
      'Accept': 'application/json',
    },
    notify: true,
  })
  const collectData = data => 'data' in data ? data.data : (
    ('message' in data) || ('detail' in data) || ('errors' in data) ? null : data
  )
  req.interceptors.response.use(
    origin => {
      const data = 'object' === typeof origin?.data ? origin.data : {}

      return {
        success: 'success' in data ? data.success : true,
        title: data.title || origin?.statusText,
        message: data.detail || data.message || 'Request successful',
        data: collectData(data),
        errors: data.errors || null,
        origin,
      }
    },
    origin => {
      const data = 'object' === typeof origin.response?.data ? origin.response.data : {}
      const response = {
        success: false,
        title: data.title || origin.response?.statusText,
        message: data.detail || data.message || 'Unknown error',
        data: collectData(data),
        errors: data.errors || null,
        origin,
      }

      if (origin.config?.notify) {
        notify(response.message, false, { titleText: response.title })
      }

      return Promise.resolve(response)
    },
  )

  return req
})()