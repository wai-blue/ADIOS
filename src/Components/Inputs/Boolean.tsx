import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import { InputSwitch } from 'primereact/inputswitch';
import * as uuid from 'uuid';

interface BooleanInputState extends InputState {
  isChecked: boolean
}

export default class Boolean extends Input<InputProps, InputState & BooleanInputState> {
  static defaultProps = {
    inputClassName: 'boolean',
    id: uuid.v4(),
  }

  constructor(props: InputProps) {
    super(props);

    this.state = {
      ...this.state, // Parent state
      isChecked: this.props.value == '1' || this.props.value > 0 || this.props.value == 'true',
    };
  }

  renderValueElement() {
    if (this.state.isChecked) {
      return <span className="text-green-600" style={{fontSize: '1.2em'}}>✓</span>;
    } else {
      return <span className="text-red-600" style={{fontSize: '1.2em'}}>✕</span>;
    }
  }

  renderInputElement() {
    return <>
      <InputSwitch
        disabled={this.state.readonly}
        checked={this.state.isChecked}
        onChange={(e) => {
          this.setState({isChecked: !this.state.isChecked});
          this.onChange(!this.state.isChecked);
        }}
      />
    </>;
  }
}
