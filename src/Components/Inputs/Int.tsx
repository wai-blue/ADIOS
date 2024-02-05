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
    let parentForm = this.props.parentForm;
    let pfState = parentForm.state;
    let columnName = this.props.columnName;
    let column: FormColumnParams = pfState.columns[columnName];

    return (
      <>
        <div className={"max-w-250 " + (column.unit ? "input-group" : "")}>
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
          {column.unit ? (
            <div className="input-group-append">
              <span className="input-group-text">{column.unit}</span>
            </div>
          ) : ''}
        </div>
      </>
    );
  } 
}
