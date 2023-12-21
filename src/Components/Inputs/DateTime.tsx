import React, { Component } from 'react'

interface DateTimeInputProps {
  parentForm: any,
  columnName: string
}

export const dateToEUFormat = (dateString: string): string => {
  let d = new Date(dateString);

  return ('0' + d.getDate()).slice(-2) + "."
    + ('0' + (d.getMonth() + 1)).slice(-2)
    + "." + d.getFullYear()
    + " " + ('0' + d.getHours()).slice(-2) + ":" + ('0' + d.getMinutes()).slice(-2)
  ;
}


export default class DateTime extends Component<DateTimeInputProps> {
  constructor(props: DateTimeInputProps) {
    super(props);
  }

  render() {
    return (
      <input 
        type="datetime-local" 
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
      />
    );
  } 
}
