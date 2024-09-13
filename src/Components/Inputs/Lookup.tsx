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
  customEndpointParams?: any,
}

interface LookupInputState extends InputState {
  data: Array<any>,
  model: string
  endpoint: string,
  customEndpointParams: any,
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
            : (globalThis.app.config.defaultLookupEndpoint ?? 'api/record/lookup')
          )
      ,
      model: props.model ? props.model : (props.params && props.params.model ? props.params.model : ''),
      data: [],
      customEndpointParams: this.props.customEndpointParams ?? {},
    };
  }

  componentDidMount() {
    this.loadData();
  }

  componentDidUpdate(prevProps: LookupInputProps) {
    super.componentDidUpdate(prevProps);

    if (
      JSON.stringify(this.props.customEndpointParams) !== JSON.stringify(prevProps.customEndpointParams)
      || this.props.model !== prevProps.model
      || this.props.context !== prevProps.context
    ) {
      this.loadData();
    }
  }

  getEndpointUrl() {
    return this.state.endpoint;
  }

  getEndpointParams(): object {
    return {
      model: this.state.model,
      context: this.props.context,
      formRecord: this.props.parentForm?.state?.record,
      __IS_AJAX__: '1',
      ...this.props.customEndpointParams,
      ...this.props.parentForm?.state.customEndpointParams ?? {},
    };
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    request.post(
      this.getEndpointUrl(),
      {...this.getEndpointParams(), search: inputValue},
      {},
      (data: any) => {
        this.setState({
          isInitialized: true,
          data: data
        });

        if (callback) callback(Object.values(data ?? {}));
      }
    );
  }

  renderValueElement() {
    if (this.state.data && this.state.data[this.state.value]?._LOOKUP) {
      return <>
        <a
          role="button"
          className="text-primary"
          data-pr-tooltip={JSON.stringify(this.state.data[this.state.value] ?? {})}
          data-pr-position="bottom"
        >
          {this.state.data[this.state.value]?._LOOKUP}
        </a>
      </>;
    } else {
      return <span className='no-value'></span>;
    }
    // return this.state.value;
  }

  renderInputElement() {
    return (
      <AsyncCreatable
        value={{
          id: this.state.data[this.state.value]?.id ?? 0,
          _LOOKUP: this.state.data[this.state.value]?._LOOKUP ?? '',
        }}
        isClearable={true}
        isDisabled={this.state.readonly || !this.state.isInitialized}
        loadOptions={(inputValue: string, callback: any) => this.loadData(inputValue, callback)}
        defaultOptions={Object.values(this.state.data ?? {})}
        getOptionLabel={(option: any) => { return option._LOOKUP }}
        getOptionValue={(option: any) => { return option.id }}
        onChange={(item: any) => { this.onChange(item?.id ?? 0); }}
        placeholder={this.props.params?.placeholder}
        classNamePrefix="adios-lookup"
        allowCreateWhileLoading={true}
        formatCreateLabel={(inputValue: string) => <span className="create-new">{globalThis.app.translate('Create') + ': ' + inputValue}</span>}
        getNewOptionData={(value, label) => { return { id: {_isNew_: true, _LOOKUP: label}, _LOOKUP: label }; }}
        styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999 }) }}
        menuPosition="fixed"
        menuPortalTarget={document.body}
      />
    )
  }
}
