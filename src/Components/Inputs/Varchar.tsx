import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface VarcharInputState extends InputState {
  showPredefinedValues: boolean,
}

export default class Varchar extends Input<InputProps, VarcharInputState> {
  static defaultProps = {
    inputClassName: 'varchar',
    id: uuid.v4(),
    type: 'text',
  }

  constructor(props: InputProps) {
    super(props);

    this.state = this.getStateFromProps(props);
  }

  getStateFromProps(props: InputProps) {
    return {
      ...this.state, // Parent state
      showPredefinedValues: false,
    };
  }

  renderInputElement() {
    return <>
      <input
        type='text'
        value={this.state.value}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange(e.currentTarget.value)}
        placeholder={this.props.placeholder}
        className={
          (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
      {this.props.params?.predefinedValues ?
        this.state.showPredefinedValues ?
          <div className="mt-1">
            <select
              onChange={(e) => {
                this.setState({value: e.currentTarget.value});
              }}
            >
              {this.props.params?.predefinedValues.map((item: string) => {
                return <option value={item}>{item}</option>
              })}
            </select>
          </div>
        :
          <button className="mt-1 btn btn-small btn-transparent" onClick={() => { this.setState({showPredefinedValues: true}); }}>
            <span className="text text-xs">Choose from predefined options...</span>
          </button>
      : null}
    </>;
  }
}
