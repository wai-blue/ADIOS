import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import { InputSwitch } from 'primereact/inputswitch';
import * as uuid from 'uuid';

export default class Boolean extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'boolean',
    id: uuid.v4(),
  }

  constructor(props: InputProps) {
    super(props);
  }

  toggleValue(value: any): any {
    if (value == '1') return '0';
    else if (value == 'Y') return 'N';
    else if (value == 'true') return 'false';
    else if (value === true) return false;
    else if (value == '0') return '1';
    else if (value == 'N') return 'Y';
    else if (value == 'false') return 'true';
    else if (value === false) return true;
    else if (value == null) return true;
    else if (value == '') return true;
  }

  isChecked(value: any): boolean {
    return (
      this.props.value == '1'
      || this.props.value == 'Y'
      || this.props.value > 0
      || this.props.value == 'true'
      || this.props.value === true
    );
  }

  renderValueElement() {
    if (this.isChecked(this.state.value)) {
      return <span className="text-green-600" style={{fontSize: '1.2em'}}>✓</span>;
    } else {
      return <span className="text-red-600" style={{fontSize: '1.2em'}}>✕</span>;
    }
  }

  renderInputElement() {
    return <>
      <InputSwitch
        disabled={this.state.readonly}
        checked={this.isChecked(this.state.value)}
        onChange={(e) => {
          this.onChange(this.toggleValue(this.state.value));
        }}
      />
    </>;
  }
}
