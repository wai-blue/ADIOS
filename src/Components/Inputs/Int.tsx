import React, { Component } from 'react'
import { FormColumnParams } from '../Form'

interface IntInputProps {
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean
}

interface IntInputState {
  readonly?: boolean,
}

export default class Int extends Component<IntInputProps> {
  state: IntInputState;

  constructor(props: IntInputProps) {
    super(props);

    let parentForm = props.parentForm;
    let pfState = parentForm.state;
    let pfProps = parentForm.props;
    let columnName = props.columnName;

    this.state = {
      readonly:
        (props.params.readonly ?? false)
        || (pfProps?.readonly ?? false)
        || (pfState.columns[columnName].disabled ?? false)
        || (pfState.columns[columnName].readonly ?? false)
    }
  }

  render() {
    let column: FormColumnParams = this.props.parentForm.state.columns[this.props.columnName];

    let parentForm = this.props.parentForm;
    let pfState = parentForm.state;
    let columnName = this.props.columnName;

    return (
      <input 
        type="number" 
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        step={column.step ?? 1}
        min={column.min ?? 0}
        className={
          "form-control"
          + " " + (pfState.invalidInputs[columnName] ? 'is-invalid' : '')
          + " " + (this.props.params?.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
    );
  } 
}
