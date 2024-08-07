import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';
import Modal, { ModalProps } from "./Modal";

export default class ModalSimple extends Modal {
  render(): JSX.Element {
    if (this.state.isOpen) {
      return <>
        <div
          id={"adios-modal-" + this.props.uid}
          className={"modal " + this.state.type}
        >
          {this.props.children}
        </div>
      </>;
    } else {
      return <></>;
    }
  } 
}
