import React, { Component } from 'react'
import AsyncSelect from 'react-select/async'
import { FormColumnParams } from '../Form' 
import { Input, InputProps, InputState } from '../Input'
import request from '../Request'

interface LookupInputProps {
  placeholder?: string,
  model?: string,
}

interface LookupInputState {
  data: Array<any>,
}

export default class Lookup extends Input<InputProps & LookupInputProps, InputState & LookupInputState> {
  props: InputProps & LookupInputProps;
  state: InputState & LookupInputState;

  constructor(props: InputProps & LookupInputProps) {
    super(props);

    this.state.data = [];
  }

  componentDidMount() {
    this.loadData();
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    request.get(
      'components/inputs/lookup',
      {
        model: this.props.model,
        search: inputValue,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        this.setState({
          //@ts-ignore
          data: data.data
        });

        if (callback) callback(Object.values(data.data ?? {}));
      }
    );
  }

  render() {
  console.log('lookup render', this.state.value, this.state.data);
    return (
      <>
        ==={this.state.value}===
        <AsyncSelect
          loadOptions={(inputValue: string, callback: any) => this.loadData(inputValue, callback)}
          defaultOptions={Object.values(this.state.data ?? {})}
          value={this.state.value}
          getOptionLabel={(option: any) => { return option.text }}
          getOptionValue={(option: any) => { return option.id }}
          onChange={(item: any) => this.onChange(this.props.columnName, item.id)}
          isDisabled={this.state.readonly}
          placeholder={this.props.placeholder}
          className={
            "w-100"
            + " " + (this.state.isInvalid ? 'is-invalid' : '')
            + " " + (this.props.params?.cssClass ?? "")
            + " " + (this.state.readonly ? "bg-muted" : "")
          }
        />
      </>
    )
  } 
}
