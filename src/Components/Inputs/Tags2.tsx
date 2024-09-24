import React, { Component } from 'react'
import Select from 'react-select'
import { Input, InputProps, InputState } from '../Input'
import request from '../Request'
import * as uuid from 'uuid';
import { ProgressBar } from 'primereact/progressbar';

interface Tags2InputProps extends InputProps {
  model?: string
  endpoint?: string,
  targetColumn: string,
  sourceColumn: string,
  colorColumn?: string,
}

interface Tags2InputState extends InputState {
  options: Array<any>,
  model: string
  endpoint: string,
  targetColumn: string,
  sourceColumn: string,
  colorColumn: string,
}

export default class Tags2 extends Input<Tags2InputProps, Tags2InputState> {
  static defaultProps = {
    inputClassName: 'tags',
    id: uuid.v4(),
  }

  constructor(props: Tags2InputProps) {
    super(props);

    this.state = this.getStateFromProps(props);
  }

  getStateFromProps(props: Tags2InputProps) {
    return {
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
      options: [],
      targetColumn: props.targetColumn,
      sourceColumn: props.sourceColumn,
    };
  }

  componentDidMount() {
    this.loadOptions();
  }

  getEndpointUrl() {
    return this.state.endpoint;
  }

  getEndpointParams(): object {
    let formRecord: any = null;

    if (this.props.parentForm) {
      formRecord = {...this.props.parentForm.state.record};
      (this.props.parentForm.state.record._RELATIONS ?? []).map((relName) => {
        delete formRecord[relName];
      })
    }

    return {
      model: this.state.model,
      context: this.props.context,
      formRecord: formRecord,
      __IS_AJAX__: '1',
    };
  }

  loadOptions() {
    request.post(
      this.getEndpointUrl(),
      this.getEndpointParams(),
      {},
      (data: any) => {
        let options: Array<any> = [];
        for (let i in data) {
          options[data[i].id] = {
            value: data[i].id,
            label: data[i]._LOOKUP,
            color: data[i][this.props.colorColumn ?? ''] ?? '',
          };
        }

        this.setState({
          isInitialized: true,
          options: options,
        });
      }
    );
  }

  convertValueToOptionList(value): Array<any> {
    let optionList: Array<any> = [];
    if (value) {
      optionList = value.map((item) => {
        const optionId = item.id;
        const optionValue = item[this.props.sourceColumn];
        const optionData = this.state.options[optionValue];

        return {
          id: optionId,
          value: optionValue,
          label: optionData?.label ?? '[' + optionValue + ']',
          color: optionData?.color ?? '',
        }
      });
    }

    return optionList;

  }

  renderValueElement() {
    const options: Array<any> = this.convertValueToOptionList(this.state.value);

    if (options) {
      let items: Array<any> = [];
      for (let i in options) {
        items.push(
          <button
            className="btn btn-transparent btn-small mr-1"
            style={{ borderColor: (options[i].color ? options[i].color : '') }}
          >
            <span
              className="text"
              style={{ color: (options[i].color ? options[i].color : '') }}
            >{options[i].label}</span>
          </button>
        );
      }
      return items;
    } else {
      return <span className='no-value'></span>;
    }
  }

  renderInputElement() {
    return (
      <Select
        defaultValue={this.convertValueToOptionList(this.state.value)}
        isMulti
        options={this.state.options}
        className="adios-lookup"
        onChange={(selectedOptions: any) => {
          const value: Array<any> = [];
          for (let i in selectedOptions) {
            value.push({
              id: selectedOptions[i].id ?? -1,
              [this.props.targetColumn]: {_useMasterRecordId_: true},
              [this.props.sourceColumn]: selectedOptions[i].value,
            });
          }
          this.onChange(value);
        }}
      />
    )
  }
}
