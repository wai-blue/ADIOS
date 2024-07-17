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
          (this.props.cssClass ?? "")
          + " " + (this.state.invalid ? 'invalid' : '')
          + " " + (this.state.readonly ? "readonly" : "")
        }
        disabled={this.state.readonly}
      />
    );
  }
}
