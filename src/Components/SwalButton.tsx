import React, { Component } from "react";
import Swal, { SweetAlertOptions } from "sweetalert2";
import Notification from "./Notification";
import axios from "axios";

interface SwalButtonProps {
  uid: string,
  confirmUrl: string,
  confirmParams?: Object,
  onConfirmCallback?: (data: any) => void,
  swal: SweetAlertOptions,
  type?: string,
  onclick?: string,
  href?: string,
  text?: string,
  icon?: string,
  css?: string,
  successMessage?: string
}

interface SwalButtonState {
  icon: string,
  css: string
}

export default class SwalButton extends Component<SwalButtonProps> {
  state: SwalButtonState;
  swal: SweetAlertOptions;

  constructor(props: SwalButtonProps) {
    super(props);

    this.state = {
      css: props.css ?? 'btn-primary',
      icon: props.icon ?? 'fas fa-check',
    };

    this.swal = {
      title: this.props.swal.title ?? 'Title',
      html: this.props.swal.html ?? 'body',
      icon: this.props.swal.icon ?? 'info',
      showCancelButton: this.props.swal.showCancelButton ?? true,
      cancelButtonText: this.props.swal.cancelButtonText ?? 'No',
      confirmButtonText: this.props.swal.confirmButtonText ?? 'Yes',
      confirmButtonColor: this.props.swal.confirmButtonColor ?? '#dc4c64'
    }

    switch (this.props.type) {
      case 'save':
        this.state = {
          icon: 'fas fa-check',
          css: 'btn-success'
        }
      break;
      case 'delete':
        this.state = {
          icon: 'fas fa-check',
          css: 'btn-danger'
        }
      break;
      case 'close':
        this.state = {
          icon: 'fas fa-times',
          css: 'btn-light'
        }
      break;
    }
  }

  onClick() {
    //@ts-ignore
    Swal.fire(this.swal as SweetAlertOptions).then((result) => {
      if (result.isConfirmed) {
        //@ts-ignore
        axios.post(
          _APP_URL + '/' + this.props.confirmUrl,
          {...this.props.confirmParams, __IS_AJAX__: '1'}
        ).then((res) => {
          Notification.success(this.props.successMessage ?? 'Confirmed');
          if (this.props.onConfirmCallback) this.props.onConfirmCallback(res.data);
        }).catch((res) => {
          Notification.error(res.response.data.message);

          if (res.response.status == 422) {
            this.setState({
              invalidInputs: res.response.data.invalidInputs 
            });
          }
        });
      }
    })
  }

  render() {
    return (
      <div
        id={"adios-button-" + this.props.uid}
        className="adios react ui button"
      >
        <button
          className={"adios ui Button btn " + this.state.css + " btn-icon-split"}
          onClick={() => this.onClick()}
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

