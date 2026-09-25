export interface ConfirmDangerOptions {
  title: string
  text: string
  confirmText: string
}

export async function confirmDanger(options: ConfirmDangerOptions): Promise<boolean> {
  const { default: Swal } = await import('sweetalert2')
  const result = await Swal.fire({
    titleText: options.title,
    text: options.text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: options.confirmText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusCancel: true,
    allowOutsideClick: false,
    buttonsStyling: false,
    customClass: {
      popup: 'winx-confirm-popup',
      confirmButton: 'winx-confirm-accept',
      cancelButton: 'winx-confirm-cancel'
    }
  })

  return result.isConfirmed
}

export async function showActionError(title: string, text: string): Promise<void> {
  const { default: Swal } = await import('sweetalert2')
  await Swal.fire({
    titleText: title,
    text,
    icon: 'error',
    confirmButtonText: 'Fechar',
    buttonsStyling: false,
    customClass: {
      popup: 'winx-confirm-popup',
      confirmButton: 'winx-confirm-primary'
    }
  })
}

export async function showSuccessToast(message: string): Promise<void> {
  const { default: Swal } = await import('sweetalert2')
  await Swal.fire({
    toast: true,
    position: 'bottom-left',
    icon: 'success',
    titleText: message,
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    customClass: {
      popup: 'winx-success-toast'
    }
  })
}
