import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface EnumValuesInputProps extends InputProps {
  enumValues?: {};
}

export default class EnumValues extends Input<EnumValuesInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'enumValues',
    id: uuid.v4(),
  }

  _renderOption(key: string|number): JSX.Element {
    if (this.props.enumValues == undefined) return <></>;
    return <option key={key} value={key}>{this.props.enumValues[key] ?? ''}</option>
  }

  renderInputElement() {
    if (!this.props.enumValues) return <></>;

    return (
      <select
        value={this.state.value ?? 0}
        onChange={(e: React.ChangeEvent<HTMLSelectElement>) => this.onChange(e.target.value)}
        className={
          "form-control"
          + " " + (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      >
        {Object.keys(this.props.enumValues).map((key: string|number) => this._renderOption(key))}
      </select>
    );
  }
}
