import React from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';

export default class Textarea extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'textarea',
    id: uuid.v4(),
  }

  renderInputElement() {
    return (
      <textarea
        value={this.state.value}
        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => this.onChange(e.currentTarget.value)}
        aria-describedby="passwordHelpInline"
        rows={5}
        placeholder={this.props.params?.placeholder}
        className={
          "form-control"
          + " " + (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
    );
  }
}
