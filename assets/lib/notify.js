import Swal from 'sweetalert2'
import { getColor } from './dom'

export default notify

export const confirm = (action, {
  title = 'Are you sure?',
  ...options
} = {}) => Swal.fire({
  title,
  showCancelButton: true,
  reverseButtons: true,
  confirmButtonText: 'Yes',
  cancelButtonText: 'No',
  icon: 'warning',
  confirmButtonColor: getColor('bs-danger'),
  showLoaderOnConfirm: true,
  allowOutsideClick: false,
  preConfirm: action || (() => ({ message: 'OK', success: true })),
  ...options
})

export const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  didOpen: toast => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  },
})

function notify(text, success, options = {}) {
  Toast.fire({
    text,
    icon: success ? 'success' : 'error',
    titleText: success ? 'Success' : 'Failure',
    ...options,
  })
}