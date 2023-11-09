import React, { Component, JSXElementConstructor } from "react";

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

interface Content {
  [key: string]: ContentCard|any;
}

interface ContentCard {
  title: string
}

interface FormProps {
  model: string,
  id?: number,
  title?: string,
  readonly?: boolean,
  content?: Content,
  layout?: Array<Array<string>>
}

interface FormState {
  model: string,
  content?: Object,
  columns?: FormColumns,
  inputs?: FormInputs,
  isEdit: boolean,
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

export default class Form extends Component<FormProps> {
  model: String;
  state: FormState;
  layout: string;

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
      isEdit: false,
      emptyRequiredInputs: {},
      inputs: {},
      columns: undefined 
    };


    //@ts-ignore
    this.layout = this.props.layout?.map(row => `"${row.join(' ')}"`).join('\n');
  }

  //_testColumns = { 
  //  "name": {
  //    "title": "Name",
  //    "type": "string",
  //    "length": 150,
  //    "required": true,
  //  },
  //  "company_name": {
  //    "title": "Company Name",
  //    "type": "string",
  //    "length": 150,
  //    "required": true,
  //    "disabled": true
  //  },
  //  "lookup_test": {
  //    "title": "Lookup test",
  //    "type": "lookup",
  //    "model": "App/Widgets/Bookkeeping/Books/Models/AccountingPeriod",
  //    "length": 1,
  //    "required": true
  //  },
  //  "is_active":{
  //    "title": "Is active?",
  //    "type": "boolean"
  //  },
  //  "text":{
  //    "title": "Text",
  //    "type": "editor"
  //  }
  //};

  componentDidMount() {
    if (this.props.id) this.state.isEdit = true;

    this.loadData();
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Form/OnLoadData', {
      params: {
        model: this.state.model,
        id: this.props.id
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns
      });

      if (Object.keys(data.inputs).length > 0) {
        this.setState({
          inputs: data.inputs
        });
      } else {
        this.initInputs(data.columns);
      }
    });
  }

  create() {
    let notyf = new Notyf();

    //@ts-ignore
    axios.post(_APP_URL + '/Components/Form/OnCreate', {
      model: this.state.model,
      inputs: this.state.inputs 
    }).then((res: any) => {
      notyf.success("Success");
      if (this.state.columns != null) this.initInputs(this.state.columns);
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
  * Render content item 
  */
  _renderContentItem(contentItemArea: string, contentItemParams: undefined|string|Object): JSX.Element {
    if (contentItemParams == undefined) return <b style={{color: 'red'}}>Content item params are not defined</b>;

    let contentItemKeys = Object.keys(contentItemParams);
    if (contentItemKeys.length == 0) return <b style={{color: 'red'}}>Bad content item definition</b>;

    let contentItemName = contentItemKeys[0];

    let contentItem: JSX.Element;

    switch (contentItemName) {
      case 'item': 
        contentItem = this._renderInput(contentItemParams['item'] as string);
      break;
      case 'html': 
        contentItem = (<div dangerouslySetInnerHTML={{ __html: contentItemParams['html'] }} />);
      break;
      default: 
        contentItem = window.getComponent(contentItemName, contentItemParams[contentItemName]);
    }
    
    return (
      <div style={{gridArea: contentItemArea}}>
        {contentItem}
      </div>
    );
  }

  /**
  * Render different input types
  */
  _renderInput(columnName: string): JSX.Element {
    if (this.state.columns == null) return <></>;

    let inputToRender: JSX.Element;

    switch (this.state.columns[columnName].type) {
      case 'text':
        inputToRender = <InputTextarea 
          parentForm={this}
          columnName={columnName}
        />;
        break;
      case 'float':
      case 'int':
        inputToRender = <InputInt 
          parentForm={this}
          columnName={columnName}
        />;
        break;
      case 'boolean':
        inputToRender = <InputBoolean 
          parentForm={this}
          columnName={columnName}
        />;
        break;
      case 'lookup':
        inputToRender = <InputLookup 
          parentForm={this}
          {...this.state.columns[columnName]}
          columnName={columnName}
        />;
        break;
      case 'editor':
        inputToRender = (
          <div className={'h-100 form-control ' + `${this.state.emptyRequiredInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
            <ReactQuill 
              theme="snow" 
              value={this.inputs[columnName] as Value} 
              onChange={(value) => this.inputOnChangeRaw(columnName, value)}
              className="w-100" 
            />
          </div>
        );
        break;
      default:
        inputToRender = <InputVarchar
          parentForm={this}
          columnName={columnName}
        />
        break;
    }

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
        {inputToRender}
        <div className="col-auto">
          <span id="passwordHelpInline" className="form-text">
            {this.state.columns[columnName].description}
          </span>
        </div>
      </div>
    ) : <></>;
  }

  render() {
    return (
      <div className="m-3">
        <div className="card w-100">
          <div className="card-header">
            <div className="row">
              <div className="col-lg-12">
                <h3 className="card-title fw-semibold">{ this.props.title ? this.props.title : this.state.model } -  Nový záznam</h3>
              </div>
              <div className="col-lg-12 text-end">
                {this.state.isEdit ? <button 
                  onClick={() => alert()}
                  className="btn btn-danger btn-sm"
                ><i className="fas fa-trash"></i> Vymazať</button> : ''}
              </div>

              {this.props.content?.cards ? (
                <div className="card text-center bg-light mt-2"> 
                  <ul className="nav nav-tabs card-header-tabs">
                    {Object.keys(this.props.content.cards).map((cardKey: string) => {
                      return (
                        <li className="nav-item"> 
                          <a 
                            className="nav-link active" 
                            href="https://practice.geeksforgeeks.org/courses"
                          >{ this.props.content?.cards[cardKey].title}</a> 
                        </li>
                      );
                    })}
                  </ul>
                </div>
              ) : ''}
            </div>
          </div>

          <div className="card-body">
            <div 
              style={{
                display: 'grid', 
                gridTemplateRows: 'auto', 
                gridTemplateAreas: this.layout, 
                gridGap: '10px'
              }}
            >
              {this.props.content != null ? 
                Object.keys(this.props.content).map((contentArea: string) => {
                  return this._renderContentItem(contentArea, this.props.content[contentArea]); 
                })
              : this.state.columns != null ? (
                Object.keys(this.state.columns).map((columnName: string) => {
                  if (
                    this.state.columns == null 
                    || this.state.columns[columnName] == null
                  ) return <strong style={{color: 'red'}}>Not defined params for {columnName}</strong>;

                  return this._renderInput(columnName)
                })
              ) : ''}
            </div>

            {this.state.isEdit == true ? (
              <button 
                onClick={() => alert()}
                className="btn btn-secondary"
              ><i className="fas fa-save"></i> Uložiť zmeny</button>
            ) : (
              <button 
                onClick={() => this.create()}
                className="btn btn-primary"
              ><i className="fas fa-plus"></i> Vytvoriť</button>
            )}
          </div>
        </div>
      </div>
    );
  }
}
