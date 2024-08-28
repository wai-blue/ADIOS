import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';

export interface ModalProps {
  onClose?: () => void;
  uid: string,
  type?: string,
  children?: any;
  // isActive?: boolean;
  title?: string;
  hideHeader?: boolean;
  isOpen?: boolean;
  // model?: string;
}

interface ModalState {
  uid: string,
  type: string,
  isOpen: boolean;
  title?: string;
}

export default class Modal extends Component<ModalProps> {
  // private modalRoot: HTMLDivElement;
  state: ModalState;

  constructor(props: ModalProps) {
    super(props);

    if (this.props.uid) {
      globalThis.app.reactElements[this.props.uid] = this;
    }

    this.state = this.getStateFromProps(props);
  }

  getStateFromProps(props: ModalProps) {
    return {
      uid: props.uid ?? uuid.v4(),
      type: props.type ?? "right",
      isOpen: props.isOpen ?? false,
      title: props.title
    };
  };

  /**
   * This function trigger if something change, for Form id of record
   */
  componentDidUpdate(prevProps: any) {
    if (
      prevProps.title != this.props.title
      || prevProps.isOpen != this.props.isOpen
    ) {
      this.setState(this.getStateFromProps(this.props));
    }
  }

  render(): JSX.Element {
    return ReactDOM.createPortal(
      <div
        id={'adios-modal-' + this.props.uid} 
        className={"adios component modal " + this.state.type + " fade"}
        style={{"display": this.state.isOpen ? "block" : "none"}}
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
                          style={{whiteSpace: "nowrap"}}
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
                        onClick={this.props.onClose}
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
      document.getElementsByTagName('body')[0]
    );
  } 
}
