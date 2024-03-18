import React, { Component } from 'react'
import * as uuid from 'uuid';
import Form, { FormColumnParams } from './Form';

export interface InputProps {
  params: FormColumnParams,
  inputClassName?: string,
  columnName: string,
  id?: string,
  value?: any,
  onChange?: (value: any) => void | string,
  readonly?: boolean,
  invalid?: boolean,
  cssClass?: string,
  placeholder?: string,
  isInitialized?: boolean,
  context?: any,
  parentForm?: Form,
}

export interface InputState {
  readonly: boolean,
  invalid: boolean,
  value: any,
  onChange?: any,
  cssClass: string,
  isInitialized: boolean,
}

export class Input<P extends InputProps, S extends InputState> extends Component<P, S> {
  static defaultProps = {
    inputClassName: '',
    id: uuid.v4(),
  };

  state: S;

  constructor(props: P) {
    super(props);

    const isInitialized: boolean = props.isInitialized ?? false;
    const readonly: boolean = props.readonly ?? false;
    const invalid: boolean = props.invalid ?? false;
    const value: any = props.value;
    const onChange: any = props.onChange ?? null;
    const cssClass: string = props.cssClass ?? '';

    this.state = {
      isInitialized: isInitialized,
      readonly: readonly,
      invalid: invalid,
      value: value,
      onChange: onChange,
      cssClass: cssClass,
    } as S;
  }

  componentDidUpdate(prevProps: any): void {
    let newState: any = {};
    let setNewState: boolean = false;

    if (this.props.isInitialized != prevProps.isInitialized) {
      newState.isInitialized = this.props.isInitialized;
      setNewState = true;
    }

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
    if (typeof this.props.onChange == 'function') {
      this.props.onChange(value);
    }
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
          name={this.props.id}
          type="hidden"
          value={this.serialize()}
          style={{width: "100%", fontSize: "0.4em"}}
          className="value bg-light"
        ></input>
        {this.renderInputElement()}
      </div>
    );
  }
}
