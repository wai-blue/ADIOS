import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

export default class Boolean extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'boolean',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <div className="form-check mb-4">
        <input 
          type="checkbox"
          value={this.state.value ?? false}
          onChange={(e) => {
            let currValue = e.currentTarget.value == 'false';
            this.onChange(currValue ? true : false);
          }}
          disabled={this.state.readonly}
          checked={this.state.value}
        />
      </div>
    );
  } 
}
