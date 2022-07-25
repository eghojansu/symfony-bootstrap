export const split = (val, by = ',') => val ? (Array.isArray(val) ? val : val.split(by)) : []
export const clsa = val => val ? (Array.isArray(val) ? val : [val]) : []
export const clsr = (...args) => args.reduce((args, val) => {
  if (!val) {
    return args
  }

  if ('object' === typeof val) {
    return [
      ...args, ...(
        Array.isArray(val) ? val : Object.entries(val).map(([key, val]) => val ? key : null)
      ).map(val => clsr(val)).reduce((args, val) => [...args, ...clsa(val)]),
    ]
  }

  return [...args, ...val.split(' ')]
}, []).filter((val, i, all) => i === all.indexOf(val))
export const clsx = (...args) => clsr(...args).join(' ') || null
export const range = (size, start = 1, step = 1) => [...Array(size)].map((...args) => (args[1] * step) + start)
export const caseJoin = (str, join = '', lowerFirst = false) => str.replace(
  /(?:^\w|[A-Z]|\b\w)/g,
  (c, i) => i === 0 && lowerFirst ? c.toLowerCase() : (
    c === c.toUpperCase() ? `_${c}` : c.toUpperCase()
  ),
).replace(/[\W_]+/g, join)
export const caseTitle = str => caseJoin(str, ' ')
export const caseCamel = str => caseJoin(str, '', true)
export const caseKebab = str => caseJoin(str, '-').toLowerCase()
export const caseSnake = str => caseJoin(str, '_').toLowerCase()
export const random = (len = 8) => {
  const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~!@-#$'

  return Array.from(crypto.getRandomValues(new Uint32Array(len))).map(r => chars[r % chars.length]).join('')
}
export const filterUnique = (compare = 'id') => (item, i, items) => (
  i === 0 || i === items.findIndex(it => it[compare] === item[compare])
)
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