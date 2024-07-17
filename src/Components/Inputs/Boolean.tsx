import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
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
      isChecked: this.props.value == '1' || this.props.value == 'Y' || this.props.value > 0 || this.props.value == 'true',
    };
  }

  renderInputElement() {
    return (
      <input 
        type="checkbox"
        value={this.state.value ? 'false' : 'true'}
        onChange={(e) => {
          this.setState({isChecked: !this.state.isChecked});
          this.onChange(!this.state.isChecked);
        }}
        disabled={this.state.readonly}
        checked={this.state.isChecked}
      />
    );
  } 
}
