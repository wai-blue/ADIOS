import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from './Input';
import { Tooltip } from 'primereact/tooltip';

export interface FormInputProps {
  children: any,
  title?: string|JSX.Element,
  description?: string,
  required?: boolean,
}

interface FormInputState {
  uid: string,
  title?: string|JSX.Element,
  description: string,
  required: boolean,
}

export default class FormInput extends Component<FormInputProps> {
  state: FormInputState;

  constructor(props: FormInputProps) {
    super(props);

    this.state = {
      uid: uuid.v4(),
      title: this.props.title,
      description: this.props.description ?? '',
      required: this.props.required ?? false,
    };
  }

  render(): JSX.Element {
    return <>
      <div
        id={this.state.uid}
        className={"input-wrapper" + (this.state.required == true ? " required" : "")}
        key={this.state.uid}
      >
        {this.state.title ?
          <label className="input-label" htmlFor={this.state.uid}>
            {this.state.title}
          </label>
        : null}

        <div className="input-body" key={this.state.uid}>
          {this.props.children}
        </div>

        {this.state.description
          ? <>
            <Tooltip target={'#' + this.state.uid + ' .input-description'} />
            <i
              className="input-description fas fa-info"
              data-pr-tooltip={this.state.description}
              data-pr-position="top"
            ></i>
          </>
          : null
        }
      </div>
    </>;
  } 
}
