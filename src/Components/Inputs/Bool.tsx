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
      isChecked: this.props.value == 'Y',
    };
  }

  renderInputElement() {
    return <>
      <InputSwitch
        disabled={this.state.readonly}
        checked={this.state.isChecked}
        onChange={(e) => {
          const newIsChecked = !this.state.isChecked;
          this.setState({isChecked: newIsChecked});
          this.onChange(newIsChecked ? 'Y' : 'N');
        }}
      />
    </>;
  }
}
