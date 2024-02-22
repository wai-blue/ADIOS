import React, { Component } from 'react'
import Block from '@uiw/react-color-block';

interface BooleanInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Color extends Component<BooleanInputProps> {
  constructor(props: BooleanInputProps) {
    super(props);
  }

  render() {
    return (
      <>
        <Block
          color={this.props.parentForm.state.data[this.props.columnName] ?? "#fff"}
          onChange={(color) => this.props.parentForm.inputOnChangeRaw(this.props.columnName, color.hex)}
        />
      </>
    );
  } 
}
