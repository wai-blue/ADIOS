import Swal, { SweetAlertOptions } from "sweetalert2";

export interface ModalParams {
  url: string,
  title?: string|null
}

export function ModalPageLarge(
  params: ModalParams,
  onClose?: () => void
): void {
  const iframeContent = `
    <iframe 
      src="` + _APP_URL + params.url + `" 
      width="100%" 
      height="800px" 
      frameborder="0"
    ></iframe>
  `;

  Swal.fire({
    title: params.title,
    html: iframeContent,
    width: '80%',
    showCloseButton: true,
    showConfirmButton: false,
    showCancelButton: false,
    focusConfirm: false,
    customClass: {
      container: 'iframe-popup-container',
    },
    willClose: () => {
      if (onClose) onClose();
    }
  } as SweetAlertOptions);
}

/*export function ModalHtmlLarge(
  params: ModalParams,
  onClose?: () => void
): void {
  const iframeContent = `
    <iframe 
      src="` + config.serverUrl + params.url + `" 
      width="100%" 
      height="800px" 
      frameborder="0"
    ></iframe>
  `;

  Swal.fire({
    title: params.title,
    html: iframeContent,
    width: '80%',
    showCloseButton: true,
    showConfirmButton: false,
    showCancelButton: false,
    focusConfirm: false,
    customClass: {
      container: 'iframe-popup-container',
    },
    willClose: () => {
      if (onClose) onClose();
    }
  } as SweetAlertOptions);
}*/
