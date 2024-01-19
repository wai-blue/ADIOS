import React, { Component } from 'react'

interface VarcharInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Varchar extends Component<VarcharInputProps> {
  constructor(props: VarcharInputProps) {
    super(props);
  }

  render() {
    let parentForm = this.props.parentForm;
    let pfState = parentForm.state;
    let pfProps = parentForm.props;
    let columnName = this.props.columnName;

    return (
      <input 
        type="text" 
        value={pfState.inputs[this.props.columnName] ?? ""}
        onChange={(e) => parentForm.inputOnChange(columnName, e)}
        className={`form-control ${pfState.invalidInputs[columnName] ? 'is-invalid' : ''} ${this.props.params?.cssClass}`}
        disabled={pfProps.readonly || pfState.columns[columnName].disabled}
      />
    );
  } 
}
