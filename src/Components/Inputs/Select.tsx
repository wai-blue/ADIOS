import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface SelectInputProps extends InputProps {
  options?: {};
  optionsCssClasses?: {};
}

export default class Select extends Input<SelectInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'select',
    id: uuid.v4(),
  }

  renderOption(key: string|number): JSX.Element {
    if (this.props.options == undefined) return <></>;
    return <option key={key} value={key}>{this.props.options[key] ?? ''}</option>
  }

  renderValueElement() {
    let value = this.props.options ? this.props.options[this.state.value] : null;
    let cssClass = this.props.optionsCssClasses ? this.props.optionsCssClasses[this.state.value] : null;

    if (!value) {
      if (this.props.options) value = this.props.options[Object.keys(this.props.options)[0]];
      else value = '-';
    }

    return <>
      <div className={"badge " + cssClass ?? ''}>
        {value}
      </div>
    </>;
  }

  renderInputElement() {
    if (!this.props.options) return <></>;

    return (
      <select
        value={this.state.value ?? 0}
        onChange={(e: React.ChangeEvent<HTMLSelectElement>) => this.onChange(e.target.value)}
        className={
          (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      >
        {Object.keys(this.props.options).map((key: string|number) => this.renderOption(key))}
      </select>
    );
  }
}
