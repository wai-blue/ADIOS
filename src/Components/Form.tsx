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
import InputDateTime from "./Inputs/DateTime";
import InputEnumValues from "./Inputs/EnumValues";

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
  readonly?: boolean,
  content?: Content,
  layout?: Array<Array<string>>,
  onSaveCallback?: () => void,
  onDeleteCallback?: () => void,
  hideOverlay?: boolean,
  showInModal?: boolean
}

interface FormState {
  content?: Object,
  columns?: FormColumns,
  inputs?: FormInputs,
  isEdit: boolean,
  invalidInputs: Object,
  tabs?: any,
  folderUrl?: string,
  formAddButtonText?: string,
  formSaveButtonText?: string,
  formTitleForEditing?: string,
  formTitleForInserting?: string
}

export interface FormColumnParams {
  title: string,
  type: string,
  length?: number,
  required?: boolean,
  description?: string,
  disabled?: boolean,
  model?: string,
  enum_values?: Array<string|number>
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
  inputs: FormInputs = {};

  constructor(props: FormProps) {
    super(props);

    this.state = {
      content: props.content,
      isEdit: false,
      invalidInputs: {},
      inputs: {}
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
        invalidInputs: {},
        isEdit: this.props.id ? this.props.id > 0 : false
      });
    }
  }

  componentDidMount() {
    this.checkIfIsEdit();
    this.initTabs();

    this.loadParams();
  }

  loadParams() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Form/OnLoadParams', {
      params: {
        model: this.props.model
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns,
        folderUrl: data.folderUrl,
        formAddButtonText: data.formAddButtonText,
        formSaveButtonText: data.formSaveButtonText,
        formTitleForEditing: data.formTitleForEditing,
        formTitleForInserting: data.formTitleForInserting,
      }, () => this.loadData());
    });
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Form/OnLoadData', {
      params: {
        model: this.props.model,
        id: this.props.id
      }
    }).then(({data}: any) => {
      this.initInputs(this.state.columns, data.inputs);
    });
  }

  saveRecord() {
    let notyf = new Notyf();

    //@ts-ignore
    axios.post(_APP_URL + '/Components/Form/OnSave', {
      model: this.props.model,
      inputs: this.state.inputs 
    }).then((res: any) => {
      notyf.success(res.data.message);
      if (this.props.onSaveCallback) this.props.onSaveCallback();
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
          model: this.props.model,
          id: id
        }).then(() => {
            notyf.success("Záznam zmazaný");
            if (this.props.onDeleteCallback) this.props.onDeleteCallback();
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
    this.state.isEdit = this.props.id && this.props.id > 1 ? true : false;
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
  initInputs(columns?: FormColumns, inputsValues?: Array<any>) {
    let inputs: any = {};

    if (!columns) return;

    Object.keys(columns).map((columnName: string) => {
      switch (columns[columnName]['type']) {
        case 'image':
          inputs[columnName] = {
            fileName: inputsValues[columnName] ?? inputsValues[columnName],
            fileData: inputsValues[columnName] != undefined && inputsValues[columnName] != ""
              ? this.state.folderUrl + '/' + inputsValues[columnName]
              : null
          };
          break;
        case 'bool':
        case 'boolean':
          inputs[columnName] = inputsValues[columnName] ?? 0;
          break;
        default:
          inputs[columnName] = inputsValues[columnName] ?? null;
      }
    });

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
            : this.state.inputs != null ? (
              Object.keys(this.state.inputs).map((inputName: string) => {
                if (
                  this.state.columns == null 
                    || this.state.columns[inputName] == null
                ) return <strong style={{color: 'red'}}>Not defined params for {inputName}</strong>;

                return this._renderInput(inputName)
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

    if (this.state.columns[columnName].enum_values) {
      inputToRender = <InputEnumValues
        parentForm={this}
        columnName={columnName}
      />
    } else {
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
        case 'datetime':
          inputToRender = <InputDateTime
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
    }

    return columnName != 'id' ? (
      <div 
        className="form-group mb-3"
        key={columnName}
      >
        <label className="text-dark">
          {this.state.columns[columnName].title}
          {this.state.columns[columnName].required == true ? <b className="text-danger"> *</b> : ""}
        </label>

        {inputToRender}

        <small className="form-text text-muted">{this.state.columns[columnName].description}</small>
      </div>
    ) : <></>;
  }

  _renderActionButtons(): JSX.Element {
    return (
      <div className="row">
        <div className="col-lg-12 m-0 p-0 mt-2">
          <div className="d-flex flex-row">
            <button 
              onClick={() => this.saveRecord()}
              className="btn btn-sm btn-primary"
            >
              {this.state.isEdit == true 
                ? <span><i className="fas fa-save"></i> {this.state.formSaveButtonText}</span>
                : <span><i className="fas fa-plus"></i> {this.state.formAddButtonText}</span>
              }
            </button>

            {this.state.isEdit ? <button 
              onClick={() => this.deleteRecord(this.props.id ?? 0)}
              className="ml-2 btn btn-danger btn-sm"
            ><i className="fas fa-trash"></i> Zmazať</button> : ''}
          </div>
        </div>
      </div>
    );
  }

  render() {
    return (
      <>
        {this.props.showInModal ? (
          <div className="modal-header text-left">
            <button 
              className="btn btn-light"
              type="button" 
              data-dismiss="modal" 
              aria-label="Close"
            ><span>&times;</span></button>
            <h3
              id={'adios-modal-title-' + this.props.uid}
              className="m-0 p-0"
            >
              {this.state.isEdit ? this.state.formTitleForEditing : this.state.formTitleForInserting}
            </h3>
            {this._renderActionButtons()}
          </div>
        ) : ''}

        <div
          id={"adios-form-" + this.props.uid}
          className="adios react ui form"
        >
            {this.props.showInModal ? (
              <div className="modal-body">
                {this._renderTab()}
              </div>
            ) : (
              <div className="card w-100">
                <div className="card-header">
                  <div className="row">
                    <div className="col-lg-12 m-0 p-0">
                      <h3 className="card-title p-0 m-0">
                        {this.state.isEdit ? this.state.formTitleForEditing : this.state.formTitleForInserting}
                      </h3>
                    </div>

                    {this._renderActionButtons()}

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
                </div>

              </div>
            )}
        </div>
      </>
    );
  }
}
