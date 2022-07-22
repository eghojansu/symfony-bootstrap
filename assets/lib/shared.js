import axios from 'axios'

export const request = (() => {
  const req = axios.create()

  return req
})()