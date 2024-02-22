import React, { Component } from 'react'
import * as uuid from 'uuid';

export interface InputProps {
  inputClassName: string,
  id?: string,
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean,
  value?: any,
  onChange?: any,
  isInvalid: boolean,
}

export interface InputState {
  readonly: boolean,
  isInvalid: boolean,
  value: any,
  onChange?: any,
}

export class Input<P, S> extends Component<InputProps, InputState> {
  static defaultProps = {
    inputClassName: '',
    id: uuid.v4(),
  };

  state: InputState;

  constructor(props: InputProps) {

    super(props);

    let readonly: boolean = false;
    let isInvalid: boolean = false;
    let value: string = '';
    let onChange: any = null;

    if (props.parentForm) {
      let parentForm = props.parentForm;
      let pfState = parentForm.state;
      let pfProps = parentForm.props;
      let columnName = props.columnName;

      readonly = (props.params.readonly ?? false)
        || (pfProps?.readonly ?? false)
        || (pfState.columns[columnName].disabled ?? false)
        || (pfState.columns[columnName].readonly ?? false)
      ;

      isInvalid = pfState.invalidInputs[columnName];
      value = pfState.inputs[this.props.columnName] ?? "";
      onChange = parentForm.inputOnChangeRaw();
    } else {
      value = props.value;
      onChange = props.onChange;
      isInvalid = props.isInvalid;
    }

    this.state = {
      readonly: readonly,
      isInvalid: isInvalid,
      value: value,
      onChange: onChange,
    }
  }

  getClassName() {
    return (
      "adios-react-ui input"
      + " " + this.props.inputClassName
      + " " + (this.state.isInvalid ? 'is-invalid' : '')
      + " " + (this.props.params?.cssClass ?? "")
      + " " + (this.state.readonly ? "bg-muted" : "")
    );
  }

  onChange(columnName: string, value: any) {
    this.setState({value: value});

    if (typeof this.state.onChange === 'function') {
      this.state.onChange(columnName, value);
    } else if (typeof this.state.onChange === 'string') {
      let func = new Function(this.state.onChange);
      func.apply(this);
    }
  }

  serialize(): string {
    return this.state.value.toString();
  }

  renderInputElement() {
    return <input type="text" value={this.state.value}></input>;
  }

  render() {
    return (
      <div className={this.getClassName()}>
        <input
          id={this.props.id}
          type="text"
          value={this.serialize()}
          style={{width: "100%", fontSize: "0.4em"}}
          className="value bg-light"
          disabled
        ></input>
        {this.renderInputElement()}
      </div>
    );
  }
}
