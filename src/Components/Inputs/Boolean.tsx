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
console.log(this.props.value);
    this.state = {
      ...this.state, // Parent state
      isChecked: this.props.value == '1' || this.props.value > 0 || this.props.value == 'true' ? true : false,
    };
  }

  renderInputElement() {
  console.log(this.state.value, this.state.isChecked);
    return (
      <div className="form-check mb-4">
        <input 
          type="checkbox"
          value={this.state.value ? 'false' : 'true'}
          onChange={(e) => {
          // console.log(e.currentTarget.value);
          //   const currValue: boolean = e.currentTarget.value == 'false';
          //   console.log(currValue);
          //   this.onChange(currValue ? true : false);
            
            this.setState({isChecked: !this.state.isChecked});
            this.onChange(!this.state.isChecked);
          }}
          disabled={this.state.readonly}
          checked={this.state.isChecked}
        />
      </div>
    );
  } 
}
