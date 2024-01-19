import React, { Component } from 'react'

interface BooleanInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Boolean extends Component<BooleanInputProps> {
  constructor(props: BooleanInputProps) {
    super(props);
  }

  render() {
    return (
      <div className="form-check mb-4">
        <input 
          type="checkbox" 
          value={this.props.parentForm.state.inputs[this.props.columnName]}
          onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
          className={`form-check-input ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
          disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
          checked={this.props.parentForm.state.inputs[this.props.columnName]}
        />
      </div>
    );
  } 
}
