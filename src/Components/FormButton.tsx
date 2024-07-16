import React, { Component } from "react";
import Modal, { ModalProps } from "./Modal";
import Form, { FormProps } from "./Form";

interface FormButtonProps {
  uid: string,
  css?: string,
  icon?: string,
  text: string,
  formParams?: FormProps
}

interface FormButtonState {
  css: string,
  icon: string,
}

export default class FormButton extends Component<FormButtonProps> {
  state: FormButtonState;

  constructor(props: FormButtonProps) {
    super(props);

    this.state = {
      css: props.css ?? 'btn-primary',
      icon: props.icon ?? 'fas fa-check',
    }
  }

  render() {
    return (
      <>
        <Modal 
          uid={this.props.uid}
          //{...this.props.modal}
          hideHeader={true}
        >
          <Form 
            uid={this.props.uid}
            showInModal={true}
            {...this.props.formParams}
          />
        </Modal>
        <div
          id={"adios-button-" + this.props.uid}
          className="adios component button"
        >
          <button
            onClick={() => ADIOS.modalToggle(this.props.uid)}
            className={"adios ui Button btn " + this.state.css + " btn-icon-split"}
          >
            <span className="icon">
              <i className={this.state.icon}></i>
            </span>
            <span className="text">{this.props.text}</span>
          </button>
        </div>
      </>
    );
  }
}

