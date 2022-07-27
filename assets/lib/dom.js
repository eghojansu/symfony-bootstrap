import { caseKebab } from './common'

export const getColor = varName => getComputedStyle(document.documentElement).getPropertyValue(`--${varName}`)
export const createElement = (tag, props, ...children) => {
  const element = document.createElement(tag)

  if (props) {
    Object.entries(props).forEach(([key, value]) => {
      if (key.startsWith('on') && typeof value === 'function') {
        element.addEventListener(key.substring(2), value)
      } else if (key.startsWith('data-')) {
        element.setAttribute(key, value)
      } else if ('class' === key) {
        (Array.isArray(value) ? value : value.split(' ')).filter(cls => !!cls).forEach(
          cls => element.classList.add(cls)
        )
      } else if ('data' === key) {
        Object.entries(value).forEach(([name, val]) => element.setAttribute(
          `data-${caseKebab(name)}`,
          val,
        ))
      } else {
        element[key] = value
      }
    })
  }

  children.forEach(child => {
    if (Array.isArray(child)) {
      element.append(...child)

      return;
    }

    if (typeof child === 'string' || typeof child === 'number') {
      child = document.createTextNode(child)
    }

    if (child instanceof Node) {
      element.appendChild(child)
    }
  })

  return element
}
export const listen = (selector, event, fn) => document.querySelectorAll(selector).forEach(
  element => element.addEventListener(event, fn),
)
export const on = (selector, event, fn) => document.addEventListener(event, event => {
  const target = event.target.closest(selector)

  if (target) {
    fn(event, target)
  }
})