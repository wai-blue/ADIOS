import React, { Component } from 'react'

interface TextareaInputProps {
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean
}

interface TextareaInputState {
  readonly?: boolean,
}

export default class Textarea extends Component<TextareaInputProps> {
  state: TextareaInputState;

  constructor(props: TextareaInputProps) {
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
    let parentForm = this.props.parentForm;
    let pfState = parentForm.state;
    let pfProps = parentForm.props;
    let columnName = this.props.columnName;

    return (
      <textarea
        value={this.props.parentForm.state.inputs[this.props.columnName] ?? ""}
        onChange={(e) => this.props.parentForm.inputOnChange(this.props.columnName, e)}
        aria-describedby="passwordHelpInline"
        rows={5}
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
