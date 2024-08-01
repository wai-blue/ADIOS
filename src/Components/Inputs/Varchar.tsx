import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';


interface VarcharInputProps extends InputProps {
  type?: string,
}

interface VarcharInputState extends InputState {
  type: string,
}

export default class Varchar extends Input<VarcharInputProps, VarcharInputState> {
  static defaultProps = {
    inputClassName: 'varchar',
    id: uuid.v4(),
    type: 'text',
  }

  constructor(props: VarcharInputProps) {
    super(props);

    this.state = {
      ...this.state,
      type: this.props.type ?? 'text',
    };
  }

  renderValueElement() {
    if (this.state.type == 'password') {
      return '***';
    } else {
      return super.renderValueElement();
    }
  }

  renderInputElement() {
    return (
      <input
        type={this.state.type}
        value={this.state.value}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange(e.currentTarget.value)}
        placeholder={this.props.params?.placeholder}
        className={
          "form-control"
          + " " + (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
    );
  }
}
