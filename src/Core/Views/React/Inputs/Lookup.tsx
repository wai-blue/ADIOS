import React, { Component } from 'react'
import Select from 'react-select'
import axios from 'axios'

interface LookupInputProps {
  model: string,
  onChange: (e: any) => void,
  parentForm: any
}

interface LookupInputState {
  data: Array<any> 
}

export default class Lookup extends Component<LookupInputProps> {
  state: LookupInputState;
  model: string;

  constructor(props: LookupInputProps) {
    super(props);

    this.model = props.model;
    this.state = {
      data: [] 
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/UI/Inputs/LookupInput/OnLoadData', {
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
    return option.name;
  }

  render() {
    return (
      <Select
        className='w-50'
        options={this.state.data}
        getOptionLabel={this.getOptionLabel}
        getOptionValue={this.getOptionValue}
        onChange={this.props.onChange}
        //onChange={this.props.parentForm.inputOnChange}
      />
    )
  } 
}
