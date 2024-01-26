import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';

import './Css/Modal.css';

export interface ModalProps {
  //onClose?: () => void;
  uid: string,
  type?: string,
  children?: any;
  isActive?: boolean;
  title?: string;
  hideHeader?: boolean;
  isOpen?: boolean;
}

interface ModalState {
  uid: string,
  type: string,
  isActive: boolean;
  title?: string;
}

export default class Modal extends Component<ModalProps> {
  private modalRoot: HTMLDivElement;
  state: ModalState;

  constructor(props: ModalProps) {
    super(props);

    this.state = {
      uid: this.props.uid ?? uuid.v4(),
      type: this.props.type ?? "right",
      isActive: true,
      title: props.title
    };

    this.modalRoot = document.createElement('div');
    document.body.appendChild(this.modalRoot);
  };

  componentWillUnmount() {
    document.body.removeChild(this.modalRoot);
  }

  componentDidMount() {
    if (this.props.isOpen === true) {
      ADIOS.modalToggle(this.state.uid);
    }
  }

  /**
   * This function trigger if something change, for Form id of record
   */
  componentDidUpdate(prevProps: any) {
    if (prevProps.title != this.props.title) {
      this.setState({
        title: this.props.title
      });
    }
  }

  render() {
    return ReactDOM.createPortal(
      <div
        id={'adios-modal-' + this.state.uid} 
        className={"modal " + this.state.type + " fade"}
        role="dialog"
      >
        <div className="modal-dialog" role="document">
          <div className="modal-content">
            {this.props.hideHeader ? (
              <div 
                id={'adios-modal-body-' + this.state.uid}
              >
                {this.props.children}
              </div>
            ) : (
              <>
                <div className="modal-header text-left">
                  <div className="row w-100 p-0 m-0 d-flex align-items-center justify-content-center">
                    <div className="col-lg-8 text-left">
                      {this.state.title ? (
                        <h3
                          id={'adios-modal-title-' + this.props.uid}
                          className="m-0 p-0"
                        >
                          {this.state.title}
                        </h3>
                      ) : ''}
                    </div>

                    <div className="col-lg-4 d-flex flex-row-reverse">
                      <button 
                        className="btn btn-light"
                        type="button" 
                        data-dismiss="modal" 
                        aria-label="Close"
                      ><span>&times;</span></button>
                    </div>
                  </div>
                </div>

                <div 
                  id={'adios-modal-body-' + this.state.uid}
                  className="modal-body"
                >
                  {this.props.children}
                </div>
              </>
            )}
          </div>
        </div>
      </div>,
      this.modalRoot
    );
  } 
}
