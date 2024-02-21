import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface VarcharInputProps {
  placeholder?: string,
}

export default class Varchar extends Input<InputProps & VarcharInputProps, InputState> {
  props: InputProps & VarcharInputProps;

  render() {
    return (
      <input
        type="text"
        id={this.props.uid ?? uuid.v4()}
        value={this.state.value}
        onChange={(e) => this.onChange(this.props.columnName, e.currentTarget.value)}
        placeholder={this.props.placeholder}
        className={
          "form-control"
          + " " + (this.state.isInvalid ? 'is-invalid' : '')
          + " " + (this.props.params?.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
    );
  }
}
