import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';
import Modal, { ModalProps } from "./Modal";

export default class ModalSimple extends Modal {
  render(): JSX.Element {
    return <>
      <div
        id={"adios-modal-" + this.props.uid}
        className={"modal " + this.state.type}
        style={{"display": this.props.isOpen ? "block" : "none"}}
      >
        {this.props.children}
      </div>
    </>;
  } 
}
