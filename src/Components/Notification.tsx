import { INotyfNotificationOptions, Notyf, NotyfNotification } from "notyf";
import 'notyf/notyf.min.css';

export interface NotificationOptions {
  type: string,
  message: string,
  dissmisible?: boolean,
  duration?: number
}

class Notification extends Notyf {

  constructor() {
    super({
      types: [
        {
          type: 'info',
          background: '#54b4d3',
          icon: {
            className: 'fas fa-info',
            tagName: 'i',
            color: '#fff'
          },
          dismissible: false
        }
      ]
    });
  }

  error(payload: string | Partial<INotyfNotificationOptions>): NotyfNotification {
    return super.error(payload);
  }

  custom(options: NotificationOptions): NotyfNotification {
    const notification = this.open({
      type: options.type,
      message: options.message,
      dismissible: options.dissmisible ?? false,
      duration: options.duration ?? 3000
    });

    return notification;
  }

}

export default new Notification();
