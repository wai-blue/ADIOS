import React, { Component } from "react";
import { Dialog as PrimereactDialog, DialogProps as PrimereactDialogProps, DialogState as PrimereactDialogState } from 'primereact/dialog';
import { AdiosComponentProps } from './Component';

export default class Dialog extends Component {
  props: PrimereactDialogProps & AdiosComponentProps;
  state: PrimereactDialogState;

  constructor(props) {
    super(props);

    if (this.props.uid) {
      globalThis.app.reactElements[this.props.uid] = this;
    }
  }

  show() {
    this.setState({containerVisible: true});
  }

  hide() {
    this.setState({containerVisible: false});
  }

  render() {
    const props = {...this.props};
    props.resizable = false;
    props.headerClassName = 'dialog-handle';
    props.contentClassName = 'p-0';
    props.onHide = () => { this.hide(); };

    if (this.state) props.visible = this.state.containerVisible;
    return <PrimereactDialog {...props}></PrimereactDialog>
  }
}
