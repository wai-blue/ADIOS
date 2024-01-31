import React, { Component } from 'react'
import { FormColumnParams } from '../Form'

interface IntInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Int extends Component<IntInputProps> {
  constructor(props: IntInputProps) {
    super(props);
  }

  render() {
    //let parentForm: Form = this.props.parentForm;
    let column: FormColumnParams = this.props.parentForm.state.columns[this.props.columnName];

    return (
      <input 
        type="number" 
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        disabled={this.props.parentForm.props.readonly || column.disabled}
        step={column.step ?? 1}
        min="0"
      />
    );
  } 
}
