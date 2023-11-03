import React, { Component } from 'react'

interface IntInputProps {
  parentForm: any,
  columnName: string
}

export default class Text extends Component<IntInputProps> {
  constructor(props: IntInputProps) {
    super(props);
  }

  render() {
    return (
      <div className="col-auto">
        <input 
          type="number" 
          value={this.props.parentForm.state.inputs[this.props.columnName]}
          onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
          className={`form-control ${this.props.parentForm.state.emptyRequiredInputs[this.props.columnName] ? 'is-invalid' : ''}`}
          disabled={this.props.parentForm.state.columns[this.props.columnName].disabled}
        />
      </div>
    );
  } 
}
