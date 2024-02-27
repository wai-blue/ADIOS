import React, { Component } from 'react'
import { FormProps, FormState, FormColumnParams } from './Form'

interface FormInputProps {
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean
}

interface FormInputState {
  readonly?: boolean,
}

export default class FormInput extends Component<FormInputProps> {
  state: FormInputState;

  parentForm: any;//Component<FormProps>;
  pfState: FormState;
  pfProps: FormProps;
  columnName: string;
  column?: FormColumnParams;

  constructor(props: FormInputProps) {
    super(props);

    this.parentForm = props.parentForm;
    this.pfState = this.parentForm.state;
    this.pfProps = this.parentForm.props;
    this.columnName = props.columnName;
    // this.column = (this.pfState?.columns?[this.columnName] ? this.pfState.columns[this.columnName] : null);

    this.state = {
      readonly:
        (props.params.readonly ?? false)
        || (this.pfProps?.readonly ?? false)
        || (this.column?.disabled ?? false)
        || (this.column?.readonly ?? false)
    }
  }
}
