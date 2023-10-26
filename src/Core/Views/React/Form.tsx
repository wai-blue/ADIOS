import React, { Component } from "react";

import axios from "axios";

import { Notyf } from "notyf";
import 'notyf/notyf.min.css';

import ReactQuill, { Value } from 'react-quill';
import 'react-quill/dist/quill.snow.css';

interface FormProps {
  model: string,
}

interface FormState {
  model: string,
  columns?: FormColumns,
  inputs?: FormInputs,
  emptyRequiredInputs: Object
}

interface FormColumnParams {
  title: string,
  type: string,
  length?: number,
  required?: boolean,
  description?: string
}

interface FormColumns {
  [key: string]: FormColumnParams;
}

interface FormInputs {
  [key: string]: string|number;
}

export default class Form extends Component {
  isEdit: boolean = false;
  model: String;
  state: FormState;

  columns: FormColumns = { 
    "name": {
      "title": "Name",
      "type": "string",
      "length": 150,
      "required": true
    },
    "is_active":{
      "title": "Is active?",
      "type": "boolean"
    },
    "text":{
      "title": "Text",
      "type": "editor"
    }
  };

  inputs: FormInputs = {};
  
  constructor(props: FormProps) {
    super(props);

    this.state = {
      model: props.model,
      emptyRequiredInputs: {},
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/UI/Form/OnLoadData', {
      params: {
        model: this.state.model 
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns
      });

      this.initInputs(data.columns);
    });
  }

  create() {
    //@ts-ignore
    axios.post(_APP_URL + '/UI/Form/OnCreate', {
      inputs: this.state.inputs 
    }).then((res: any) => {
      console.log(res);
    }).catch((res) => {
      this.setState({
        emptyRequiredInputs: res.response.data.emptyRequiredInputs 
      });

      var notyf = new Notyf();

      notyf.error('You must fill out the form before moving forward');
    });
  }

  inputOnChange(columnName: string, event: any) {
    let changedInput: any = {};
    changedInput[columnName] = event.target.value;

    this.setState({
      inputs: {...this.inputs, ...changedInput}
    });
  }

  initInputs(columns: Object) {
    let inputs: any = {};

    Object.keys(columns).map((columName: string) => {
      inputs[columName] = '';
    });

    this.setState({
      inputs: inputs
    });
  }

  _renderInput(columnName: string): JSX.Element {
    switch (this.state.columns[columnName].type) {
      case 'text':
        return (
          <textarea
            className={`form-control ${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : ''}`}
            value={this.inputs[columnName]}
            onChange={(e) => this.inputOnChange(columnName, e)}
            id="exampleFormControlTextarea4"
          />
        );
      case 'editor':
        return (
          <div className={'h-100 form-control ' + `${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
            <ReactQuill 
              theme="snow" 
              value={this.inputs[columnName] as Value} 
              onChange={(e) => this.inputOnChange(columnName, e)}
              className="w-100" 
            />
          </div>
        );
      case 'float':
      case 'int':
        return (
          <div className="col-auto">
            <input 
              type="number" 
              value={this.inputs[columnName]}
              onChange={(e) => this.inputOnChange(columnName, e)}
              id="inputPassword6" 
              className={`form-control ${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : ''}`}
              aria-describedby="passwordHelpInline"
            />
          </div>
        );
      case 'boolean':
        return (
          <div className="form-check">
            <input 
              value={this.inputs[columnName]}
              type="checkbox" 
              id="flexCheckDefault"
              className={`form-check-input ${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : ''}`}
            />
          </div>
        )
      default:
        return (
          <div className="col-auto">
            <input 
              type="text" 
              value={this.inputs[columnName]}
              onChange={(e) => this.inputOnChange(columnName, e)}
              id="inputPassword6" 
            className={`form-control ${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : ''}`}
            aria-describedby="passwordHelpInline"
          />
        </div>
      );
    }
  }
  
  render() {
    return (
      <div className="m-3">
        <div className="card w-100">
          <div className="card-header">
            <div className="row">
              <div className="col-lg-6">
                <h5 className="card-title fw-semibold mb-4">Form - { this.state.model }</h5>
              </div>
              <div className="col-lg-6 text-end">
                {this.isEdit ? <button 
                  onClick={() => alert()}
                  className="btn btn-danger btn-sm"
                ><i className="fa-solid fa-trash-can"></i> Vymazať</button> : ''}
              </div>
            </div>
          </div>
          <div className="card-body">
            {this.state.columns != null ? (
              Object.keys(this.state.columns).map((columnName: string) => (
                <div 
                  className="row g-3 align-items-center mb-3"
                  key={columnName}
                >
                  <div className="col-auto">
                    <label htmlFor="inputPassword6" className="col-form-label">
                      {this.state.columns[columnName].title}
                      {this.state.columns[columnName].required == true ? <b className="text-danger">*</b> : ""}
                    </label>
                  </div>
                  {this._renderInput(columnName)}
                  <div className="col-auto">
                    <span id="passwordHelpInline" className="form-text">
                      {this.state.columns[columnName].description}
                    </span>
                  </div>
                </div>
              ))
            ) : ''}

            {this.isEdit == true ? (
              <button 
                onClick={() => alert()}
                className="btn btn-primary"
              >Uložiť zmeny</button>
            ) : (
                <button 
                  onClick={() => this.create()}
                  className="btn btn-success"
                >Vytvoriť</button>
              )}
          </div>
        </div>
      </div>
    );
  }
}
