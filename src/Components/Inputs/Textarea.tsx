import React, { Component } from 'react'

interface TextareaInputProps {
  parentForm: any,
  columnName: string
}

export default class Textarea extends Component<TextareaInputProps> {
  constructor(props: TextareaInputProps) {
    super(props);
  }

  render() {
    return (
      <textarea 
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        aria-describedby="passwordHelpInline"
        disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
        rows={5}
      />
    );
  } 
}
