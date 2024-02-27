import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

export default class Varchar extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'varchar',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <input
        type="text"
        value={this.state.value}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange(this.props.columnName, e.currentTarget.value)}
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
