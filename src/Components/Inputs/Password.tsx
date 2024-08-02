import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface PasswordInputProps extends InputProps {
}

interface PasswordInputState extends InputState {
  visible: boolean,
}

export default class Password extends Input<PasswordInputProps, PasswordInputState> {
  static defaultProps = {
    inputClassName: 'password',
    id: uuid.v4(),
    type: 'text',
  }

  refInput1;
  refInput2;

  constructor(props: PasswordInputProps) {
    super(props);

    this.state = {
      ...this.state,
      value: '',
      visible: false,
    };

    this.refInput1 = React.createRef();
    this.refInput2 = React.createRef();
  }

  onChange() {
    const val1 = this.refInput1.current.value;
    const val2 = this.refInput2.current.value;
    super.onChange([val1, val2]);
  }

  renderValueElement() {
    return '***';
  }

  renderInputElement() {
    const password1 = this.state.value[0] ?? '';
    const password2 = this.state.value[1] ?? '';

    return <>
      <div className={"block pr-2"}>
        <input
          type={this.state.visible ? 'text' : 'password'}
          value={password1}
          ref={this.refInput1}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange()}
          placeholder={globalThis.app.translate("New password")}
          className={
            (this.state.invalid ? 'is-invalid' : '')
            + " " + (this.props.cssClass ?? "")
            + " " + (this.state.readonly ? "bg-muted" : "")
            + " " + (password1 == password2 ? "" : "bg-red-100")
          }
          disabled={this.state.readonly}
        />
        <input
          type={this.state.visible ? 'text' : 'password'}
          value={password2}
          ref={this.refInput2}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange()}
          placeholder={globalThis.app.translate("Confirm new password")}
          className={
            (this.state.invalid ? 'is-invalid' : '')
            + " " + (this.props.cssClass ?? "")
            + " " + (this.state.readonly ? "bg-muted" : "")
            + " " + (password1 == password2 ? "" : "bg-red-100")
          }
          disabled={this.state.readonly}
        />
      </div>
      <span
        className="btn btn-light"
        onClick={() => { this.setState({visible: !this.state.visible}); }}
      >
        <span className="icon"><i className={"fas " + (this.state.visible ? "fa-low-vision" : "fa-eye")}></i></span>
      </span>
    </>;
  }
}
