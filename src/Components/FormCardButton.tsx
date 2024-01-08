
import React, { Component } from "react";
import Modal, { ModalProps } from "./Modal";
import Form, { FormProps } from "./Form";

interface FormCardButtonProps {
  uid: string,
  model: string,
  onClick?: string,
  href?: string,
  text: string,
  icon: string,
  subtitle?: string,
  css?: string,
  formId?: number,
  form?: FormProps
}

interface FormCardButtonState {
  css: string,
  icon: string,
}

export default class FormCardButton extends Component<FormCardButtonProps> {
  state: FormCardButtonState;

  constructor(props: FormCardButtonProps) {
    super(props);

    this.state = {
      css: props.css ?? 'btn-primary',
      icon: props.icon ?? 'fas fa-check',
    }

    console.log(this.props.form);
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
            model={this.props.model}
            showInModal={true}
            id={this.props.formId}
            {...this.props.form}
          />
        </Modal>

        <button
          id={"adios-card-button-" + this.props.uid}
          onClick={() => window.adiosModalToggle(this.props.uid)}
          className={"btn " + this.state.css + " shadow-sm mb-1 p-4"}
          style={{width: '14em'}}
        >
          <i 
            className={this.state.icon} 
            style={{fontSize: '4em'}}
          ></i>

          <div className="text-center pt-4 mt-4 h5">{ this.props.text }</div>
          { this.props.subtitle ? (
            <div className="text-center small">{ this.props.subtitle }</div>
          ) : ''}
        </button>
      </>
    );
  }
}

