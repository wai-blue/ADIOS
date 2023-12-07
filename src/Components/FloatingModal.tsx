import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import $ from 'jquery';

import './Css/FloatingModal.css';

interface FloatingModalProps {
  //onClose?: () => void;
  uid?: string,
  children?: any;
  isActive?: boolean;
}

interface FloatingModalParams {
  uid: string,
}

interface FloatingModalState {
  isActive: boolean;
}

export default class FloatingModal extends Component<FloatingModalProps> {
  private modalRoot: HTMLDivElement;
  state: FloatingModalState;

  params: FloatingModalParams = {
    uid: ""
  };

  constructor(props: FloatingModalProps) {
    super(props);

    this.state = {
      isActive: true
    };

    this.params = {
      uid: this.props.uid ? this.props.uid : "random"
    }

    this.modalRoot = document.createElement('div');
    document.body.appendChild(this.modalRoot);
  };

  componentWillUnmount() {
    document.body.removeChild(this.modalRoot);
  }

  toggleModal() {
    $('#adios-modal-' + this.params.uid).modal('toggle');
  }

  render() {
    console.log("Modal rendered");

    return ReactDOM.createPortal(
      <div 
        className="modal right fade"
        id={'adios-modal-' + this.params.uid} 
        role="dialog"
        aria-labelledby="myModalLabel2"
      >
        <div className="modal-dialog" role="document">
          <div className="modal-content">

            <div className="modal-header">
              <button 
                type="button" 
                className="close" 
                data-dismiss="modal" 
                aria-label="Close"
                onClick={() => this.toggleModal()}
              ><span aria-hidden="true">&times;</span></button>
              <h4 className="modal-title" id="myModalLabel2">Right Sidebar</h4>
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
