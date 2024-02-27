import React from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

interface IntInputProps extends InputProps {
  unit?: string
}

export default class Int extends Input<IntInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'int',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <div className={"max-w-250 " + (this.props.unit ? "input-group" : "")}>
        <input
          type="number"
          value={this.state.value}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange(e.currentTarget.value)}
          placeholder={this.props.params?.placeholder}
          className={
            "form-control"
            + " " + (this.state.invalid ? 'is-invalid' : '')
            + " " + (this.props.cssClass ?? "")
            + " " + (this.state.readonly ? "bg-muted" : "")
          }
          disabled={this.state.readonly}
        />
          {this.props.unit ? (
            <div className="input-group-append">
              <span className="input-group-text">{this.props.unit}</span>
            </div>
          ) : ''}
      </div>
    );
  }
}
