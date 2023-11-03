import React, { Component } from 'react'

interface VarcharInputProps {
  parentForm: any,
  columnName: string
}

export default class Varchar extends Component<VarcharInputProps> {
  constructor(props: VarcharInputProps) {
    super(props);
  }

  render() {
    return (
      <div className="col-auto">
        <input 
          type="text" 
          value={this.props.parentForm.state.inputs[this.props.columnName]}
          onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
          className={`form-control ${this.props.parentForm.state.emptyRequiredInputs[this.props.columnName] ? 'is-invalid' : ''}`}
          disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
        />
      </div>
    );
  } 
}
