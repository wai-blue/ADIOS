import React, { Component } from 'react'
import Select from 'react-select'
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

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Inputs/Lookup/OnLoadData', {
      params: {
        model: this.model 
      }
    }).then(({data}: any) => {
      this.setState({
        data: data.data
      });
    });
  }

  getOptionValue(option: any) {
    return option.id;
  }

  getOptionLabel(option: any) {
    return option.lookupSqlValue;
  }

  render() {
    return (
      <Select
        className={`${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
        options={Object.values(this.state.data)}
        value={this.state.data[this.props.parentForm.state.inputs[this.props.columnName]]}
        getOptionLabel={this.getOptionLabel}
        getOptionValue={this.getOptionValue}
        onChange={(item: any) => this.props.parentForm.inputOnChangeRaw(this.props.columnName, item.id)}
        isDisabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].disabled}
      />
    )
  } 
}
