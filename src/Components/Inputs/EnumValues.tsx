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
    let parentForm: any = this.props.parentForm;
    let enumValues: any = parentForm.state.columns[this.props.columnName].enum_values ?? {};

    return (
      <select
        value={parentForm.state.inputs[this.props.columnName] ?? 0}
        onChange={(e) => parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        disabled={parentForm.props.readonly || parentForm.state.columns[this.props.columnName].disabled}
      >
        <option value=""></option>
        {Object.keys(enumValues).map((key: string|number) => (
          <option key={key} value={key}>
            {enumValues[key]}
          </option>
        ))}
      </select>
    );
  } 
}
