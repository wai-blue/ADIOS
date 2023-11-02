import React, { Component } from "react";

import axios from "axios";

import { Notyf } from "notyf";
import 'notyf/notyf.min.css';

import ReactQuill, { Value } from 'react-quill';
import 'react-quill/dist/quill.snow.css';

/** Components */
import InputLookup from "./Inputs/Lookup";
import InputVarchar from "./Inputs/Varchar";
import InputTextarea from "./Inputs/Textarea";
import InputInt from "./Inputs/Int";
import InputBoolean from "./Inputs/Boolean";

interface FormProps {
  model: string,
  content?: Object
}

interface FormState {
  model: string,
  content?: Object,
  columns?: FormColumns,
  inputs?: FormInputs,
  emptyRequiredInputs: Object
}

export interface FormColumnParams {
  title: string,
  type: string,
  length?: number,
  required?: boolean,
  description?: string,
  disabled?: boolean,
  model?: string
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

    console.log(props);

    this.state = {
      model: props.model,
      content: props.content,
      emptyRequiredInputs: {},
      inputs: {},
      columns: { 
        "name": {
          "title": "Name",
          "type": "string",
          "length": 150,
          "required": true,
        },
        "company_name": {
          "title": "Company Name",
          "type": "string",
          "length": 150,
          "required": true,
          "disabled": true
        },
        "lookup_test": {
          "title": "Lookup test",
          "type": "lookup",
          "model": "App/Widgets/Bookkeeping/Books/Models/AccountingPeriod",
          "length": 1,
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
      }
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
    let notyf = new Notyf();

    //@ts-ignore
    axios.post(_APP_URL + '/UI/Form/OnCreate', {
      model: this.state.model,
      inputs: this.state.inputs 
    }).then((res: any) => {
      notyf.success("Success");
      this.initInputs(this.state.columns);
    }).catch((res) => {
      notyf.error(res.response.data.message);

      if (res.response.status == 422) {
        this.setState({
          emptyRequiredInputs: res.response.data.emptyRequiredInputs 
        });
      }
    });
  }

  /**
  * Input onChange with event parameter 
  */
  inputOnChange(columnName: string, event: React.FormEvent<HTMLInputElement>) {
    let inputValue: string|number = event.currentTarget.value;

    this.inputOnChangeRaw(columnName, inputValue);
  }

  /**
  * Input onChange with raw input value, change inputs (React state)
  */
  inputOnChangeRaw(columnName: string, inputValue: any) {
    let changedInput: any = {};
    changedInput[columnName] = inputValue;

    this.setState({
      inputs: {...this.state.inputs, ...changedInput}
    });
  }

  /**
  * Dynamically initialize inputs (React state) from model columns
  */
  initInputs(columns: FormColumns) {
    let inputs: any = {};

    Object.keys(columns).map((columnName: string) => {
      if (columnName != 'id') inputs[columnName] = null;
    });

    this.setState({
      inputs: inputs
    });
  }

  /**
  * Render different input types
  */
  _renderInput(columnName: string): JSX.Element {
    switch (this.state.columns[columnName].type) {
      case 'text':
        return <InputTextarea 
          parentForm={this}
          columnName={columnName}
        />;
      case 'float':
      case 'int':
        return <InputInt 
          parentForm={this}
          columnName={columnName}
        />;
      case 'boolean':
        return <InputBoolean 
          parentForm={this}
          columnName={columnName}
        />;
      case 'lookup':
        return <InputLookup 
          parentForm={this}
          {...this.state.columns[columnName]}
          columnName={columnName}
        />;
      case 'editor':
        return (
          <div className={'h-100 form-control ' + `${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
            <ReactQuill 
              theme="snow" 
              value={this.inputs[columnName] as Value} 
              onChange={(value) => this.inputOnChangeRaw(columnName, value)}
              className="w-100" 
            />
          </div>
        );

      default:
        return <InputVarchar
          parentForm={this}
          columnName={columnName}
        />
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
              Object.keys(this.state.columns).map((columnName: string) => {
                return columnName != 'id' ? (
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
                ) : '';
              })
            ) : ''}

            {this.state.content != null ? (
              Object.keys(this.state.content).map((componentName: string) => {
                return window.getComponent(componentName, this.state.content[componentName]);
              })
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
