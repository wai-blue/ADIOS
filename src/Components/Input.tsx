import { Component } from 'react'

export interface InputProps {
  uid?: string,
  parentForm: any,
  columnName: string,
  params: any,
  readonly?: boolean,
  value?: any,
}

export interface InputState {
  readonly: boolean,
  isInvalid: boolean,
  value: any,
  onChange?: any,
}

export class Input<P> extends Component<InputProps> {
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
      onChange = parentForm.inputOnChange();
    }

    this.state = {
      readonly: readonly,
      isInvalid: isInvalid,
      value: value,
      onChange: onChange,
    }
  }
}
