import React, { Component } from 'react'
import * as uuid from 'uuid';

export interface InputProps {
  params: any,
  inputClassName?: string,
  columnName: string,
  id?: string,
  value?: any,
  onChange?: (columnName: string, value: any) => void | string,
  readonly?: boolean,
  invalid?: boolean,
  cssClass?: string,
  placeholder?: string,
}

export interface InputState {
  readonly: boolean,
  invalid: boolean,
  value: any,
  onChange?: any,
  cssClass: string,
}

export class Input<P extends InputProps, S> extends Component<P, InputState> {
  static defaultProps = {
    inputClassName: '',
    id: uuid.v4(),
  };

  state: InputState;

  constructor(props: P) {
    super(props);

    const readonly: boolean = props.readonly ?? false;
    const invalid: boolean = props.invalid ?? false;
    const value: any = props.value;
    const onChange: any = props.onChange ?? null;
    const cssClass: string = props.cssClass ?? '';

    this.state = {
      readonly: readonly,
      invalid: invalid,
      value: value,
      onChange: onChange,
      cssClass: cssClass,
    }
  }

  componentDidUpdate(prevProps: any): void {
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

  onChange(columnName: string, value: any) {
    this.setState({value: value});
    if (typeof this.props.onChange == 'function') {
      this.props.onChange(columnName, value);
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
