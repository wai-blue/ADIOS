import React from 'react'
import Block from '@uiw/react-color-block';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'

export default class Color extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'color',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <Block
        color={this.state.value}
        onChange={(color: any) => this.onChange(color.hex)}
      />
    );
  } 
}
