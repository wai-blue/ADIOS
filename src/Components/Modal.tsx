import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import $ from 'jquery';
import { v4 } from 'uuid';

import './Css/Modal.css';

export interface ModalProps {
  //onClose?: () => void;
  uid: string,
  type?: string,
  children?: any;
  isActive?: boolean;
}

interface ModalParams {
  uid: string,
  type: string
}

interface ModalState {
  isActive: boolean;
}

export default class Modal extends Component<ModalProps> {
  private modalRoot: HTMLDivElement;
  state: ModalState;

  params: ModalParams = {
    uid: this.props.uid,
    type: "right"
  };

  constructor(props: ModalProps) {
    super(props);

    this.state = {
      isActive: true
    };

    console.log(props);
    this.params = {
      uid: this.props.uid ?? v4(),
      type: this.props.type ?? "right"
    }

    this.modalRoot = document.createElement('div');
    document.body.appendChild(this.modalRoot);
  };

  componentWillUnmount() {
    document.body.removeChild(this.modalRoot);
  }

  toggleModal() {
    //$('#adios-modal-' + this.params.uid).modal('toggle');
  }

  render() {
    return ReactDOM.createPortal(
      <div
        id={'adios-modal-' + this.params.uid} 
        className={"modal " + this.params.type + " fade"}
        role="dialog"
      >
        <div className="modal-dialog" role="document">
          <div className="modal-content">

            <div className="modal-header">
              <button 
                className="btn btn-light"
                type="button" 
                data-dismiss="modal" 
                aria-label="Close"
                onClick={() => this.toggleModal()}
              ><span aria-hidden="true">&times;</span></button>
            </div>

            <div className="modal-body">
              {this.props.children}
            </div>

          </div>
        </div>
      </div>,
      this.modalRoot
    );
  } 
}
