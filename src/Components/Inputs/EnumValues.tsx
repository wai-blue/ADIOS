import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface EnumValuesInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

//export default class EnumValues extends Component<EnumValuesInputProps> {
//  constructor(props: EnumValuesInputProps) {
//    super(props);
//  }
//
//  render() {
//    let parentForm: any = this.props.parentForm;
//    let enumValues: any = parentForm.state.columns[this.props.columnName].enumValues ?? {};
//
//    return (
//      <select
//        value={parentForm.state.data[this.props.columnName] ?? 0}
//        onChange={(e) => parentForm.inputOnChange(this.props.columnName, e)}
//        className={`form-control ${parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
//        disabled={parentForm.props.readonly || parentForm.state.columns[this.props.columnName].disabled}
//      >
//        <option value=""></option>
//        {Object.keys(enumValues).map((key: string|number) => (
//          <option key={key} value={key}>
//            {enumValues[key]}
//          </option>
//        ))}
//      </select>
//    );
//  } 
//}

export default class EnumValues extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'varchar',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <input
        type="text"
        value={this.state.value}
        onChange={(e) => this.onChange(e.currentTarget.value)}
        placeholder={this.props.params?.placeholder}
        className={
          "form-control"
          + " " + (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
    );
  }
}
