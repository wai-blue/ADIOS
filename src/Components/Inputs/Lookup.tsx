import React, { Component } from 'react'
import AsyncSelect from 'react-select/async'
import axios from 'axios'
import { FormColumnParams } from '../Form' 

interface LookupInputProps extends FormColumnParams {
  parentForm: any,
  columnName: string
}

interface LookupInputState {
  data: Array<any> 
}

export default class Lookup extends Component<LookupInputProps> {
  state: LookupInputState;
  model: string;

  constructor(props: LookupInputProps) {
    super(props);

    if (props.model != undefined) this.model = props.model;

    this.state = {
      data: [] 
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData(inputValue: string|null = null, callback: ((option: Array<any>) => void)|null = null) {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Inputs/Lookup/OnLoadData', {
      params: {
        model: this.model,
        search: inputValue
      }
    }).then(({data}: any) => {
      this.setState({
        data: data.data
      });

      if (callback) callback(Object.values(data.data ?? {}));
    });
  }

  getOptionValue(option: any) {
    return option.id;
  }

  getOptionLabel(option: any) {
    return option.lookupSqlValue;
  }

  render() {
  // console.log(input, this.state.data);
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
        isDisabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].readonly}
        placeholder=""
        className="w-100"
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
