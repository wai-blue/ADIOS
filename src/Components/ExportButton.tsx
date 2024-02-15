import React, { Component } from "react";
import Notification, { NotificationOptions } from "./Notification";
import * as uuid from 'uuid';
import { jsPDF } from "jspdf";

import html2canvas from 'html2canvas';
import { NotyfNotification } from "notyf";

interface ExportButtonProps {
  uid: string,
  type?: string,
  href?: string,
  text?: string,
  icon?: string,
  cssClass?: string
  cssStyle?: object,
  exportType: string,
  exportElementId: string,
  exportFileName?: string
}

interface ExportButtonState {
  icon: string,
  cssClass: string
  cssStyle?: object
}

export default class ExportButton extends Component<ExportButtonProps> {
  state: ExportButtonState;

  constructor(props: ExportButtonProps) {
    super(props);

    this.state = {
      cssClass: props.cssClass ?? 'btn-primary',
      cssStyle: props.cssStyle,
      icon: props.icon ?? 'fas fa-check',
    };

    switch (this.props.type) {
      case 'save':
        this.state = {
          icon: 'fas fa-check',
          cssClass: 'btn-success'
        }
      break;
      case 'delete':
        this.state = {
          icon: 'fas fa-check',
          cssClass: 'btn-danger'
        }
      break;
      case 'close':
        this.state = {
          icon: 'fas fa-times',
          cssClass: 'btn-light'
        }
      break;
    }
  }

  export() {
    if (!this.props.exportElementId) {
      Notification.error('export-element-id not inicialized');
      return;
    }

    const imgElement = document.getElementById(this.props.exportElementId);
    if (!imgElement) {
      alert("Error");
      return;
    }

    let infoNotification: NotyfNotification = Notification.custom({
      type: "info",
      message: "Exporting file",
      duration: 0
    } as NotificationOptions);

    setTimeout(() => {
      switch (this.props.exportType) {
        case 'image':
          html2canvas(imgElement).then((canvas: any) => {
            const imageDataURL = canvas.toDataURL("image/png");
            const a = document.createElement("a");
            a.href = imageDataURL;
            a.download = this.props.exportFileName ?? uuid.v4();
            a.click();
          });
        break;
        case 'pdf':
          html2canvas(imgElement).then((canvas: any) => {
            const imageDataURL = canvas.toDataURL("image/png");
            const doc = new jsPDF('landscape');
            doc.addImage(imageDataURL, 'PNG', 10, 10, 200, 200);
            doc.save('sample-file.pdf');
          });
        break;
        default:
          Notification.error('export-type must be pdf or image');
      }

      Notification.dismiss(infoNotification);
    }, 500);
  }

  render() {
    return (
      <div
        id={"adios-export-button-" + this.props.uid}
        className="adios-react-ui button"
      >
        <button
          className={"adios ui Button btn " + this.state.cssClass + " btn-icon-split"}
          style={this.state.cssStyle}
          onClick={() => this.export()}
        >
          <span className="icon">
            <i className={this.state.icon}></i>
          </span>
          <span className="text">{this.props.text}</span>
        </button>
      </div>
    );
  }
}

