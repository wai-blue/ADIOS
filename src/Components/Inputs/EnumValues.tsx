import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

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


interface EnumValuesInputProps extends InputProps {
  enumValues?: {};
}

export default class EnumValues extends Input<EnumValuesInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'enumValues',
    id: uuid.v4(),
  }

  _renderOption(key: string|number): JSX.Element {
    if (this.props.enumValues == undefined) return <></>;
    return <option key={key} value={key}>{this.props.enumValues[key] ?? ''}</option>
  }

  renderInputElement() {
    if (!this.props.enumValues) return <></>;

    return (
      <select
        value={this.state.value ?? 0}
        onChange={(e: React.ChangeEvent<HTMLSelectElement>) => this.onChange(this.props.columnName, e.target.value)}
        className={
          "form-control"
          + " " + (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      >
        <option value=""></option>
        {Object.keys(this.props.enumValues).map((key: string|number) => this._renderOption(key))}
      </select>
    );
  }
}
