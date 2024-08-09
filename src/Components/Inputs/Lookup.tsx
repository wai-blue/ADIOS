import React, { Component } from 'react'
import AsyncSelect from 'react-select/async'
import AsyncCreatable from 'react-select/async-creatable'
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
  model: string
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
      endpoint:
        props.endpoint
          ? props.endpoint
          : (props.params && props.params.endpoint
            ? props.params.endpoint
            : (globalThis.app.config.defaultLookupEndpoint ?? 'components/inputs/lookup')
          )
      ,
      model: props.model ? props.model : (props.params && props.params.model ? props.params.model : ''),
      data: [],
    };
  }

  componentDidMount() {
    this.loadData();
  }

  getEndpointParams(): object {
    return {
      model: this.state.model,
      context: this.props.context,
      formData: this.props.parentForm?.state?.data,
      __IS_AJAX__: '1',
    };
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    request.get(
      this.state.endpoint,
      {...this.getEndpointParams(), search: inputValue},
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

  renderValueElement() {
    return this.state.data[this.state.value]?._lookupText_ ?? <span className='no-value'></span>;
    // return this.state.value;
  }

  renderInputElement() {
    return (
      <AsyncCreatable
        isClearable={true}
        isDisabled={this.state.readonly || !this.state.isInitialized}
        loadOptions={(inputValue: string, callback: any) => this.loadData(inputValue, callback)}
        defaultOptions={Object.values(this.state.data ?? {})}
        getOptionLabel={(option: any) => { return option._lookupText_ }}
        getOptionValue={(option: any) => { return option.id }}
        onChange={(item: any) => { this.onChange(item?.id ?? 0); }}
        placeholder={this.props.params?.placeholder}
        classNamePrefix="adios-lookup"
        allowCreateWhileLoading={true}
        formatCreateLabel={(inputValue: string) => <span className="create-new">{globalThis.app.translate('Create') + ': ' + inputValue}</span>}
        getNewOptionData={(value, label) => { console.log(value, label); return { id: {_isNew_: true, _lookupText_: label}, _lookupText_: label }; }}
      />
    )
  }
}
