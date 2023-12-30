import React, { Component } from 'react'

interface EnumValuesInputProps {
  parentForm: any,
  columnName: string
}

export default class EnumValues extends Component<EnumValuesInputProps> {
  constructor(props: EnumValuesInputProps) {
    super(props);
  }

  render() {
    return (
      <select
        value={this.props.parentForm.state.inputs[this.props.columnName]}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
      >
        <option value=""></option>
        {this.props.parentForm.state.columns[this.props.columnName].enum_values.map((item: string|number, index: number) => (
          <option key={index} value={index}>
            {item}
          </option>
        ))}
      </select>
    );
  } 
}
