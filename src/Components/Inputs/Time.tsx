import React, { Component } from 'react'

interface TimeInputProps {
  parentForm: any,
  columnName: string
}

export default class Time extends Component<TimeInputProps> {
  constructor(props: TimeInputProps) {
    super(props);
  }

  render() {
    return (
      <input 
        type="time"
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
      />
    );
  } 
}
