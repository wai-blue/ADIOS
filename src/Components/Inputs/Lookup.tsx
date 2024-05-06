import React, { Component } from 'react'
import AsyncSelect from 'react-select/async'
import { Input, InputProps, InputState } from '../Input'
import request from '../Request'
import * as uuid from 'uuid';
import { ProgressBar } from 'primereact/progressbar';

interface LookupInputProps extends InputProps {
  model?: string
  endpoint?: string,
}

interface LookupInputState extends InputState {
  data: Array<any>,
  endpoint: string,
}

export default class Lookup extends Input<LookupInputProps, LookupInputState> {
  static defaultProps = {
    inputClassName: 'lookup',
    id: uuid.v4(),
  }

  constructor(props: LookupInputProps) {
    super(props);

    this.state = {
      ...this.state, // Parent state
      endpoint: props.endpoint ? props.endpoint : (props.params.endpoint ? props.params.endpoint : 'components/inputs/lookup'),
      data: [],
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    request.get(
      this.state.endpoint,
      {
        model: this.props.params?.model,
        context: this.props.context,
        formData: this.props.parentForm?.state?.data,
        search: inputValue,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        this.setState({
          isInitialized: true,
          //@ts-ignore
          data: data.data
        });

        if (callback) callback(Object.values(data.data ?? {}));
      }
    );
  }

  renderInputElement() {
    if (!this.state.isInitialized) {
      // return <ProgressBar mode="indeterminate" style={{ height: '3px' }}></ProgressBar>;
      return (
        <AsyncSelect
          isDisabled={true}
          placeholder="..."
        />
      )
    }

    return (
      <AsyncSelect
        loadOptions={(inputValue: string, callback: any) => this.loadData(inputValue, callback)}
        defaultOptions={Object.values(this.state.data ?? {})}
        value={{
          id: this.state.value,
          text: this.state.data ? (this.state.data[this.state.value] ? this.state.data[this.state.value].text : '') : '',
        }}
        getOptionLabel={(option: any) => { return option.text }}
        getOptionValue={(option: any) => { return option.id }}
        onChange={(item: any) => { this.onChange(item.id); }}
        isDisabled={this.state.readonly}
        placeholder={this.props.params?.placeholder}
      />
    )
  }
}
