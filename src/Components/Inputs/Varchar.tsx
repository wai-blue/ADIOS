import React, { Component } from 'react'

interface VarcharInputProps {
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean
}

interface VarcharInputState {
  readonly?: boolean,
}

export default class Varchar extends Component<VarcharInputProps> {
  state: VarcharInputState;

  constructor(props: VarcharInputProps) {
    super(props);

    let parentForm = props.parentForm;
    let pfState = parentForm.state;
    let pfProps = parentForm.props;
    let columnName = props.columnName;

    this.state = {
      readonly: props.params.readonly ?? (pfProps?.readonly ?? (pfState.columns[columnName].disabled ?? false))
    }
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
