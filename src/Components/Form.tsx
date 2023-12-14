import React, { Component, JSXElementConstructor } from "react";

import axios from "axios";

import { Notyf } from "notyf";
import 'notyf/notyf.min.css';

import ReactQuill, { Value } from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import Swal, { SweetAlertOptions } from "sweetalert2";

/** Components */
import InputLookup from "./Inputs/Lookup";
import InputVarchar from "./Inputs/Varchar";
import InputTextarea from "./Inputs/Textarea";
import InputInt from "./Inputs/Int";
import InputBoolean from "./Inputs/Boolean";
import InputMapPoint from "./Inputs/MapPoint";
import InputColor from "./Inputs/Color";
import InputImage from "./Inputs/Image";
import InputTags from "./Inputs/Tags";

interface Content {
  [key: string]: ContentCard|any;
}

interface ContentCard {
  title: string
}

export interface FormProps {
  uid: string,
  model: string,
  id?: number,
  title?: string,
  readonly?: boolean,
  content?: Content,
  layout?: Array<Array<string>>,
  onSaveCallback?: () => void,
  onDeleteCallback?: () => void
}

/*interface FormParams {
  model: string,
  id: number,
  title: string,
  readonly: boolean,
  content: Content,
  layout: Array<Array<string>>
}*/

interface FormState {
  model: string,
  content?: Object,
  columns?: FormColumns,
  inputs?: FormInputs,
  isEdit: boolean,
  invalidInputs: Object,
  tabs?: any 
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
  state: FormState;

  model: String;
  layout: string;
  columns: FormColumns = {}; 
  inputs: FormInputs = {};

  constructor(props: FormProps) {
    super(props);

    this.state = {
      model: props.model,
      content: props.content,
      isEdit: false,
      invalidInputs: {},
      inputs: {},
    };

    //@ts-ignore
    this.layout = this.props.layout?.map(row => `"${row.join(' ')}"`).join('\n');
  }

  /**
   * This function trigger if something change, for Form id of record
   */
  componentDidUpdate(prevProps: any) {
    if (prevProps.id !== this.props.id) {
      this.checkIfIsEdit();
      this.loadData();

      this.setState({
        invalidInputs: {}
      });
    }
  }

  componentDidMount() {
    this.checkIfIsEdit();
    this.initTabs();
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

  saveRecord() {
    let notyf = new Notyf();

    //@ts-ignore
    axios.post(_APP_URL + '/Components/Form/OnSave', {
      model: this.state.model,
      inputs: this.state.inputs 
    }).then((res: any) => {
        notyf.success(res.data.message);
        if (this.props.onSaveCallback) this.props.onSaveCallback();
        //if (this.state.columns != null) this.initInputs(this.state.columns);
      }).catch((res) => {
        notyf.error(res.response.data.message);

        if (res.response.status == 422) {
          this.setState({
            invalidInputs: res.response.data.invalidInputs 
          });
        }
      });
  }

  deleteRecord(id: number) {
    //@ts-ignore
    Swal.fire({
      title: 'Ste si istý?',
      html: 'Ste si istý, že chcete vymazať tento záznam?',
      icon: 'danger',
      showCancelButton: true,
      cancelButtonText: 'Nie',
      confirmButtonText: 'Áno',
      confirmButtonColor: '#dc4c64',
      reverseButtons: false,
    } as SweetAlertOptions).then((result) => {
      if (result.isConfirmed) {
        let notyf = new Notyf();
        //@ts-ignore
        axios.patch(_APP_URL + '/Components/Form/OnDelete', {
          model: this.state.model,
          id: id
        }).then(() => {
            notyf.success("Záznam zmazaný");
            if (this.props.onDeleteCallback) this.props.onDeleteCallback();
            //if (this.state.columns != null) this.initInputs(this.state.columns);
          }).catch((res) => {
            notyf.error(res.response.data.message);

            if (res.response.status == 422) {
              this.setState({
                invalidInputs: res.response.data.invalidInputs 
              });
            }
          });
      }
    })
  }

  /**
   * Check if is id = undefined or id is > 0
   */
  checkIfIsEdit() {
    this.state.isEdit = this.props.id ? true : false;
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

    console.log(inputs);
    this.setState({
      inputs: inputs
    });
  }

  changeTab(changeTabName: string) {
    let tabs: any = {};

    Object.keys(this.state.tabs).map((tabName: string) => {
      tabs[tabName] = {
        active: tabName == changeTabName
      };
    });

    this.setState({
      tabs: tabs
    });
  }

  /*
    * Initialize form tabs is are defined
    */
  initTabs() {
    if (this.props.content?.tabs == undefined) return;

    let tabs: any = {};
    let firstIteration: boolean = true;

    Object.keys(this.props.content?.tabs).map((tabName: string) => {
      tabs[tabName] = {
        active: firstIteration 
      };

      firstIteration = false;
    });

    this.setState({
      tabs: tabs
    });  
  }

  /**
    * Render tab
    */
  _renderTab(): JSX.Element {
    if (this.props.content?.tabs) {
      let tabs: any = Object.keys(this.props.content.tabs).map((tabName: string) => {
        return this._renderTabContent(tabName, this.props.content?.tabs[tabName]);
      })

      return tabs;
    } else {
      return this._renderTabContent("default", this.props.content);
    } 
  }

  /*
    * Render tab content
    * If tab is not set, use default tabName else use activated one
    */
  _renderTabContent(tabName: string, content: any) {
    if (
      tabName == "default" 
        || (this.state.tabs && this.state.tabs[tabName]['active'])
    ) {
      return (
        <div 
          style={{
            display: 'grid', 
            gridTemplateRows: 'auto', 
            gridTemplateAreas: this.layout, 
            gridGap: '10px'
          }}
        >
          {this.props.content != null ? 
            Object.keys(content).map((contentArea: string) => {
              return this._renderContentItem(contentArea, content[contentArea]); 
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
      );
    } else {
      return <></>;
    }
  }

  /**
    * Render content item 
    */
  _renderContentItem(contentItemArea: string, contentItemParams: undefined|string|Object|Array<string>): JSX.Element {
    if (contentItemParams == undefined) return <b style={{color: 'red'}}>Content item params are not defined</b>;

    let contentItemKeys = Object.keys(contentItemParams);
    if (contentItemKeys.length == 0) return <b style={{color: 'red'}}>Bad content item definition</b>;

    let contentItemName = contentItemArea == "inputs" 
      ? contentItemArea : contentItemKeys[0];

    let contentItem: JSX.Element;

    switch (contentItemName) {
      case 'input': 
        contentItem = this._renderInput(contentItemParams['input'] as string);
        break;
      case 'inputs':
        contentItem = (contentItemParams as Array<string>).map((input: string) => {
          return this._renderInput(input)
        });
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
      case 'MapPoint':
        inputToRender = <InputMapPoint
          parentForm={this}
          columnName={columnName}
        />;
      break;
      case 'color':
        inputToRender = <InputColor
          parentForm={this}
          columnName={columnName}
        />;
      break;
      case 'tags':
        inputToRender = <InputTags
          parentForm={this}
          columnName={columnName}
        />;
      break;
      case 'image':
        inputToRender = <InputImage
          parentForm={this}
          columnName={columnName}
        />;
      break;
      case 'editor':
        inputToRender = (
          <div className={'h-100 form-control ' + `${this.state.invalidInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
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
    }

    return columnName != 'id' ? (
      <div 
        className="row g-1 align-items-center"
        key={columnName}
      >
        <div className="col-lg-4">
          <label className="col-form-label text-dark">
            {this.state.columns[columnName].title}
            {this.state.columns[columnName].required == true ? <b className="ms-1 text-danger">*</b> : ""}
          </label>
        </div>
        <div className="col-lg-8">
          {inputToRender}
        </div>
        <div className="col-auto">
          <span className="form-text">
            {this.state.columns[columnName].description}
          </span>
        </div>
      </div>
    ) : <></>;
  }

  render() {
    return (
      <div
        id={"adios-form-" + this.props.uid}
        className="adios react ui form"
      >
        <div className="card w-100 overflow-auto">
          <div className="card-header">
            <div className="row">
              <div className="col-lg-12 m-0 p-0">
                <h3 className="card-title p-0 m-0">{ this.props.title ? this.props.title : this.state.model } -  
                  <small className="text-secondary">
                    {this.state.isEdit ? ' Editácia záznamu' : ' Nový záznam'}
                  </small>
                </h3>
              </div>
              <div className="col-lg-12 m-0 p-0 mt-2">
                <div className="d-flex flex-row-reverse">
                  {this.state.isEdit ? <button 
                    onClick={() => this.deleteRecord(this.props.id ?? 0)}
                    className="btn btn-danger btn-sm"
                  ><i className="fas fa-trash"></i> Zmazať</button> : ''}
                </div>
              </div>

              {this.state.tabs != undefined ? (
                <ul className="nav nav-tabs card-header-tabs mt-3">
                  {Object.keys(this.state.tabs).map((tabName: string) => {
                    return (
                      <li className="nav-item"> 
                        <button 
                          className={this.state.tabs[tabName]['active'] ? 'nav-link active' : 'nav-link'}
                          onClick={() => this.changeTab(tabName)}
                        >{ tabName }</button> 
                      </li>
                    );
                  })}
                </ul>
              ) : ''}
            </div>
          </div>

          <div className="card-body">
            {this._renderTab()}

            <button 
              onClick={() => this.saveRecord()}
              className="btn btn-primary mt-2"
            >
              {this.state.isEdit == true 
                ? <span><i className="fas fa-save"></i> Uložiť záznam</span>
                : <span><i className="fas fa-plus"></i> Pridať záznam</span>
              }
            </button>
          </div>
        </div>
      </div>
    );
  }
}
