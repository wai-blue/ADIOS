import React, { Component } from 'react'
import * as uuid from 'uuid';
import Form from './Form';

export interface InputProps {
  parentForm: Form,
  params: any,
  //inputClassName: string,
  columnName: string,
  id?: string,
  readonly?: boolean,
  value?: any,
  onChange?: (value: string) => void,
  isInvalid?: boolean,
  cssClass?: string,

  // For lookup
  model?: string,
  // For datetime
  type?: string
}

export interface InputState {
  readonly: boolean,
  invalid: boolean,
  value: any,
  onChange?: any,
  cssClass: string,
}

export class Input<P, S> extends Component<InputProps, InputState> {
  static defaultProps = {
    inputClassName: '',
    id: uuid.v4(),
  };

  state: InputState;

  constructor(props: InputProps) {
    super(props);

    let readonly: boolean = props.readonly ?? false;
    let invalid: boolean = props.isInvalid ?? false;
    let value: any = props.value ?? '';
    let onChange: any = props.onChange ?? null;
    let cssClass: string = props.cssClass ?? '';

    // if (props.parentForm) {
    //   let parentForm = props.parentForm;
    //   let pfState = parentForm.state;
    //   let pfProps = parentForm.props;
    //   let columnName = props.columnName;

    //   readonly = (pfProps?.readonly ?? false)
    //     || (pfState.columns[columnName].disabled ?? false)
    //     || (pfState.columns[columnName].readonly ?? false)
    //   ;

    //   // invalid = pfState.invalidInputs[columnName] ?? false;
    //   // value = pfState.data[this.props.columnName] ?? "";
    //   // onChange = parentForm.inputOnChangeRaw();
    // } else {
    // }

    this.state = {
      readonly: readonly,
      invalid: invalid,
      value: value,
      onChange: onChange,
      cssClass: cssClass,
    }
  }

  componentDidUpdate(prevProps): void {
    let newState: any = {};
    let setNewState = false;

    if (this.props.value != prevProps.value) {
      newState.value = this.props.value;
      setNewState = true;
    }
    if (this.props.cssClass != prevProps.cssClass) {
      newState.cssClass = this.props.cssClass;
      setNewState = true;
    }
    if (this.props.readonly != prevProps.readonly) {
      newState.readonly = this.props.readonly;
      setNewState = true;
    }
    if (this.props.invalid != prevProps.invalid) {
      newState.invalid = this.props.invalid;
      setNewState = true;
    }

    if (setNewState) {
      this.setState(newState);
    }
  }

  getClassName() {
    return (
      "adios-react-ui input"
      + " " + this.props.inputClassName
      + " " + (this.state.invalid ? 'invalid' : '')
      + " " + (this.state.cssClass ?? "")
      + " " + (this.state.readonly ? "bg-muted" : "")
    );
  }

  onChange(value: any) {
    this.setState({value: value});
    this.props.onChange(value);
  }

  serialize(): string {
    return this.state.value ? this.state.value.toString() : '';
  }

  renderInputElement() {
    return <input type="text" value={this.state.value}></input>;
  }

  render() {
    return (
      <div className={this.getClassName()}>
        <input
          id={this.props.id}
          type="hidden"
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
