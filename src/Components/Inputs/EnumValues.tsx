import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface EnumValuesInputProps extends InputProps {
  enumValues?: {};
  enumCssClasses?: {};
  uiStyle?: 'select' | 'buttons';
}

export default class EnumValues extends Input<EnumValuesInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'enumValues',
    id: uuid.v4(),
    uiStyle: 'select',
  }

  _renderOption(key: string|number): JSX.Element {
    if (this.props.enumValues == undefined) return <></>;
    return <option key={key} value={key}>{this.props.enumValues[key] ?? ''}</option>
  }

  renderValueElement() {
    let value = this.props.enumValues ? this.props.enumValues[this.state.value] : null;
    let cssClass = this.props.enumCssClasses ? this.props.enumCssClasses[this.state.value] : null;

    if (!value) {
      if (this.props.enumValues) value = this.props.enumValues[Object.keys(this.props.enumValues)[0]];
      else value = '-';
    }

    return <>
      <div className={"badge " + cssClass ?? ''}>
        {value}
      </div>
    </>;
  }

  renderInputElement() {
    if (!this.props.enumValues) return <></>;

    if (this.props.uiStyle == 'select') {
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
          {Object.keys(this.props.enumValues).map((key: string|number) => this._renderOption(key))}
        </select>
      );
    } else if (this.props.uiStyle == 'buttons') {
      return <div className="btn-group">{Object.keys(this.props.enumValues).map((key: string|number) => {
        const enumValue = this.props.enumValues ? (this.props.enumValues[key] ?? '') : '';
        const enumCssClass = this.props.enumCssClasses ? (this.props.enumCssClasses[key] ?? '') : '';
        return <>
          <button
            className={"btn " + (this.state.readonly ? "btn-disabled" : "") + " " + (this.state.value == key ? "btn-primary" : "btn-light") + " " + enumCssClass}
            onClick={() => { if (!this.state.readonly) this.onChange((this.state.value == key ? null : key)); }}
          >
            <span className="text">{enumValue}</span>
          </button>
        </>;
      })}</div>;
    } else {
      return <></>;
    }
  }
}
