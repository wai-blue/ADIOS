import Swal, { SweetAlertOptions } from "sweetalert2";

export interface SwalModalParams {
  url: string,
  title?: string|null
}

export function SwalModalPageLarge(
  params: SwalModalParams,
  onClose?: () => void
): void {
  const iframeContent = `
    <iframe 
      src="` + globalThis.adios._APP_URL + params.url + `" 
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
