import React, { Component } from 'react'
import AsyncSelect from 'react-select/async'
import axios from 'axios'
import { FormColumnParams } from '../Form' 
import request from '../Request'

interface LookupInputProps extends FormColumnParams {
  parentForm: any,
  columnName: string,
  params: any
}

interface LookupInputState {
  data: Array<any>,
  readonly?: boolean
}

export default class Lookup extends Component<LookupInputProps> {
  state: LookupInputState;
  model: string;

  constructor(props: LookupInputProps) {
    super(props);

    if (props.model != undefined) this.model = props.model;

    let parentForm = props.parentForm;
    let pfState = parentForm.state;
    let pfProps = parentForm.props;
    let columnName = props.columnName;

    this.state = {
      data: [],
      readonly:
        (props.params.readonly ?? false)
        || (pfProps?.readonly ?? false)
        || (pfState.columns[columnName].disabled ?? false)
        || (pfState.columns[columnName].readonly ?? false)
  };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    request.get(
      '/Components/Inputs/Lookup/OnLoadData',
      {
        __IS_AJAX__: '1',
        model: this.model,
        search: inputValue
      },
      (data: any) => {
        this.setState({
          data: data.data
        });

        if (callback) callback(Object.values(data.data ?? {}));
      }
    );
  }

  getOptionValue(option: any) {
    return option.id;
  }

  getOptionLabel(option: any) {
    return option.lookupSqlValue;
  }

  render() {
    let input = this.props.parentForm.state.inputs[this.props.columnName];
    let data = this.state.data ?? {};
    let value = (input in data ? data[input] : 0);

    return (
      <AsyncSelect
        loadOptions={(inputValue: string, callback: any) => this.loadData(inputValue, callback)}
        defaultOptions={Object.values(this.state.data ?? {})}
        value={value}
        getOptionLabel={this.getOptionLabel}
        getOptionValue={this.getOptionValue}
        onChange={(item: any) => this.props.parentForm.inputOnChangeRaw(this.props.columnName, item.id)}
        isDisabled={this.state.readonly}
        placeholder=""
        className={
          "w-100"
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        styles={{
          control: (baseStyles, state) => ({
            ...baseStyles,
            borderColor: this.props.parentForm.state.invalidInputs[this.props.columnName] ? '#e74a3b' : '#d1d3e2',
          }),
        }}
      />
    )
  } 
}
