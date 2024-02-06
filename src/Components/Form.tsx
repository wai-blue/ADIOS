import React, {Component} from "react";

import Notification from "./Notification";
import request from "./Request";
import * as uuid from 'uuid';

import ReactQuill, {Value} from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import Swal, {SweetAlertOptions} from "sweetalert2";

import {adiosError, deepObjectMerge} from "./Helper";

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
  [key: string]: ContentCard | any;
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
  onClose?: () => void;
  onSaveCallback?: () => void,
  onDeleteCallback?: () => void,
  hideOverlay?: boolean,
  showInModal?: boolean,
  columns?: FormColumns,
  titleForInserting?: string,
  titleForEditing?: string,
  saveButtonText?: string,
  addButtonText?: string,
  loadParamsController?: string,
  loadDataController?: string,
  defaultValues?: Object
}

export interface FormState {
  id?: number,
  readonly?: boolean,
  canCreate?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  canDelete?: boolean,
  content?: Content,
  columns?: FormColumns,
  inputs?: FormInputs,
  isEdit: boolean,
  invalidInputs: Object,
  tabs?: any,
  folderUrl?: string,
  addButtonText?: string,
  saveButtonText?: string,
  titleForEditing?: string,
  titleForInserting?: string,
  layout?: string
}

export interface FormColumnParams {
  title: string,
  type: string,
  length?: number,
  required?: boolean,
  description?: string,
  disabled?: boolean,
  model?: string,
  enumValues?: Array<string | number>,
  unit?: string,
  step?: number,
  defaultValue?: any,
  viewParams: any,
  min?: number,
  readonly?: boolean,
}

export interface FormColumns {
  [key: string]: FormColumnParams;
}

interface FormInputs {
  [key: string]: string | number;
}

export default class Form extends Component<FormProps> {
  state: FormState;
  newState: any;

  model: String;
  inputs: FormInputs = {};
  components: Array<React.JSX.Element> = [];

  jsxContentRendered: boolean = false;
  jsxContent: JSX.Element;

  constructor(props: FormProps) {
    super(props);

    this.state = {
      id: props.id,
      readonly: props.readonly,
      canCreate: props.readonly,
      canRead: props.readonly,
      canUpdate: props.readonly,
      canDelete: props.readonly,
      content: props.content,
      layout: this.convertLayoutToString(props.layout),
      isEdit: props.id ? props.id > 0 : false,
      invalidInputs: {},
      inputs: {}
    };
  }


  // shouldComponentUpdate(nextProps: FormProps, nextState: FormState) {
  //   console.log('form should update', this.props.model, this.props.id, nextProps.id, this.state.id, nextState.id);
  //   return this.props.id != nextProps.id;
  // }

  /**
   * This function trigger if something change, for Form id of record
   */
  componentDidUpdate(prevProps: FormProps, prevState: FormState) {
    //console.log('form did update', this.props.model, this.props.id, prevProps.id);
    if (prevProps.id !== this.props.id) {
      this.checkIfIsEdit();
      this.loadParams();
      
      //this.loadData();
      this.setState({
        invalidInputs: {},
        isEdit: this.props.id ? this.props.id > 0 : false
      });

    }

    if (!this.state.isEdit && prevProps.defaultValues != this.props.defaultValues) {
      this.initInputs(this.state.columns ?? {}, this.props.defaultValues);
    }
  }

  componentDidMount() {
    //console.log('form did mount', this.props.model);
    this.checkIfIsEdit();
    this.initTabs();

    this.loadParams();
  }

  loadParams() {
    let loadParamsController = this.props.loadParamsController ? this.props.loadParamsController : 'Components/Form/OnLoadParams';
    //console.log('form load params', this.props.model);

    //@ts-ignore
    request.get(
      loadParamsController,
      {
        __IS_AJAX__: '1',
        model: this.props.model,
        columns: this.props.columns,
        id: this.props.id
      },
      (data: any) => {
        data = deepObjectMerge(data, this.props);
        data.layout = this.convertLayoutToString(data.layout);
        // this.newState = {
        //   columns: data.columns,
        //   folderUrl: data.folderUrl,
        //   ...data
        // };

        this.setState({
          columns: data.columns,
          folderUrl: data.folderUrl,
          ...data
        }, () => {
          this.loadData();
        });
      }
    );
  }

  loadData() {
    let loadDataController = this.props.loadDataController ? this.props.loadDataController : 'Components/Form/OnLoadData';
    let id = this.state.id ? this.state.id : 0;

    //console.log('form load data', this.props.model, id);

    if (id > 0) {
      request.get(
        loadDataController,
        {
          __IS_AJAX__: '1',
          model: this.props.model,
          id: id
        },
        (data: any) => {
          this.initInputs(this.state.columns ?? {}, data.inputs);
          // this.setState({id: id});
          // this.newState.id = id;
          // this._updateState();
        }
      );
    } else {
      this.initInputs(this.state.columns ?? {}, {});
      // this.setState({id: id});
      // this.newState.id = id;
      // this._updateState();
    }
  }

  // _updateState() {
  //   console.log('form update state', this.props.model, this.newState);
  //   if (this.newState) {
  //     this.setState(this.newState, () => {
  //       this.newState = null;
  //     });
  //   }
  // }

  saveRecord() {
    this.setState({
      invalidInputs: {}
    });

    //@ts-ignore
    request.post(
      'Components/Form/OnSave',
      {
        inputs: {...this.state.inputs, id: this.state.id}
      },
      {
        __IS_AJAX__: '1',
        model: this.props.model,
      },
      () => {
        if (this.props.onSaveCallback) this.props.onSaveCallback();
      },
      (err: any) => {
        if (err.status == 422) {
          this.setState({
            invalidInputs: err.data.invalidInputs
          });
        }
      }
    );
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
        request.delete(
          'Components/Form/OnDelete',
          {
            __IS_AJAX__: '1',
            model: this.props.model,
            id: id
          },
          () => {
            Notification.success("Záznam zmazaný");
            if (this.props.onDeleteCallback) this.props.onDeleteCallback();
          }
        );
      }
    })
  }

  /**
   * Check if is id = undefined or id is > 0
   */
  checkIfIsEdit() {
    this.setState({
      isEdit: this.state.id && this.state.id > 0
    });
  }

  /**
   * Input onChange with event parameter
   */
  inputOnChange(columnName: string, event: React.FormEvent<HTMLInputElement>) {
    let inputValue: string | number = event.currentTarget.value;

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
  initInputs(columns: FormColumns, inputsValues: Object = {}) {
    let inputs: any = {};

    // If is new form and defaultValues props is set
    if (Object.keys(inputsValues).length == 0 && this.props.defaultValues) {
      inputsValues = this.props.defaultValues;
    }

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
          inputs[columnName] = inputsValues[columnName] ?? this.getDefaultValueForInput(columnName, 0);
        break;
        case 'tags':  // Testing
          inputs[columnName] = inputsValues[columnName] ?? this.getDefaultValueForInput(columnName, [
            {id: 'Thailand', text: 'Thailand'},
            {id: 'India', text: 'India'},
            {id: 'Vietnam', text: 'Vietnam'},
            {id: 'Turkey', text: 'Turkey', className: 'red'}
          ]);
        break;
        default:
          inputs[columnName] = inputsValues[columnName] ?? this.getDefaultValueForInput(columnName, null);
      }
    });

    this.setState({
      inputs: inputs
    });
  }

  /**
   * Get default value form Model definition
   */
  getDefaultValueForInput(columnName: string, otherValue: any): any {
    if (!this.state.columns) return;
    return this.state.columns[columnName].defaultValue ?? otherValue
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
    if (this.state.content?.tabs == undefined) return;

    let tabs: any = {};
    let firstIteration: boolean = true;

    Object.keys(this.state.content?.tabs).map((tabName: string) => {
      tabs[tabName] = {
        active: firstIteration
      };

      firstIteration = false;
    });

    this.setState({
      tabs: tabs
    });
  }

  convertLayoutToString(layout?: Array<Array<string>>): string {
    //@ts-ignore
    return layout?.map(row => `"${row}"`).join('\n');
  }

  /**
   * Render tab
   */
  _renderTab(): JSX.Element {
    if (this.state.content?.tabs) {
      let tabs: any = Object.keys(this.state.content.tabs).map((tabName: string) => {
        return this._renderTabContent(tabName, this.state.content?.tabs[tabName]);
      })

      return tabs;
    } else {
      return this._renderTabContent("default", this.state.content);
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
            gridTemplateAreas: this.state.layout,
            gridGap: '15px'
          }}
        >
          {this.state.content != null ?
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
  _renderContentItem(contentItemArea: string, contentItemParams: undefined | string | Object | Array<string>): JSX.Element {
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
        contentItem = (contentItemParams['inputs'] as Array<string>).map((input: string) => {
          return this._renderInput(input)
        });
        break;
      case 'html':
        contentItem = (<div dangerouslySetInnerHTML={{__html: contentItemParams['html']}}/>);
        break;
      default:
        //console.log('window.getComponent', contentItemName, this.props.uid, this.props.model, contentItemParams[contentItemName]);

        contentItem = window.getComponent(
          contentItemName,
          // contentItemParams[contentItemName]
          {
            ...contentItemParams[contentItemName],
            ...{
              parentFormId: this.state.id,
              parentFormModel: this.props.model,
            }
          }
        );

        //console.log('rendered component uid ' + contentItem.props.uid);

        this.components.push(contentItem);

        break;
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
    if (!this.state.columns[columnName]) return adiosError(`Column: <b>${columnName}</b> doesn't exist in model or is not shown`);

    let inputToRender: JSX.Element;
    if (this.state.columns[columnName].enumValues) {

      inputToRender = <InputEnumValues
        parentForm={this}
        columnName={columnName}
      />
    } else {
      if (this.state.columns[columnName].viewParams?.Form?.inputJSX) {
        let inputJSX = this.state.columns[columnName].viewParams.Form.inputJSX;
        inputToRender = React.createElement(
          window['App']['reactElements'][inputJSX],
          {
            parentForm: this,
            columnName: columnName
          }
        );
      } else {
        let inputParams = {...this.state.columns[columnName].viewParams?.Form, ...{readonly: this.state.readonly}};

        switch (this.state.columns[columnName].type) {
          case 'text':
            inputToRender = <InputTextarea
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'float':
          case 'int':
            inputToRender = <InputInt
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'boolean':
            inputToRender = <InputBoolean
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'lookup':
            inputToRender = <InputLookup
              parentForm={this}
              {...this.state.columns[columnName]}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'MapPoint':
            inputToRender = <InputMapPoint
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'color':
            inputToRender = <InputColor
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'tags':
            inputToRender = <InputTags
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'image':
            inputToRender = <InputImage
              parentForm={this}
              columnName={columnName}
              params={inputParams}
            />;
            break;
          case 'datetime':
          case 'date':
          case 'time':
            inputToRender = <InputDateTime
              parentForm={this}
              columnName={columnName}
              type={this.state.columns[columnName].type}
              params={inputParams}
            />;
            break;
          case 'editor':
            inputToRender = (
              <div
                className={'h-100 form-control ' + `${this.state.invalidInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
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
              params={inputParams}
            />
        }
      }
    }

    return columnName != 'id' ? (
      <div
        className="input-form mb-3"
        key={columnName}
      >
        <label className="text-dark">
          {this.state.columns[columnName].title}
          {this.state.columns[columnName].required == true ? <b className="text-danger"> *</b> : ""}
        </label>

        <div key={columnName}>
          {inputToRender}
        </div>

        <small className="form-text text-muted">{this.state.columns[columnName].description}</small>
      </div>
    ) : <></>;
  }

  _renderButtonsLeft(): JSX.Element {
    let id = this.state.id ? this.state.id : 0;
    return (
      <div className="d-flex">
        <button
          className="btn btn-sm btn-light mr-2"
          type="button"
          data-dismiss="modal"
          aria-label="Close"
          onClick={this.props.onClose}
        ><span>&times;</span></button>

        <button
          onClick={() => this.saveRecord()}
          className={
            "btn btn-sm btn-success btn-icon-split "
            + (id <= 0 && this.state.canCreate || id > 0 && this.state.canUpdate ? "d-block" : "d-none")
          }
        >
          {this.state.isEdit
            ? (
              <>
                <span className="icon"><i className="fas fa-save"></i></span>
                <span className="text"> {this.state.saveButtonText ?? "Save"}</span>
              </>
            )
            : (
              <>
                <span className="icon"><i className="fas fa-plus"></i></span>
                <span className="text"> {this.state.addButtonText ?? "Add"}</span>
              </>
            )
          }
        </button>
      </div>
    );
  }

  _renderButtonsRight(): JSX.Element {
    return (
      <div className="d-flex">
        {this.state.isEdit ? <button
          onClick={() => this.deleteRecord(this.state.id ? this.state.id : 0)}
          className={"btn btn-sm btn-danger btn-icon-split " + (this.state.canDelete ? "d-block" : "d-none")}
        >
          <span className="icon"><i className="fas fa-trash"></i></span>
          <span className="text">Delete</span>
        </button> : ''}

      </div>
    );
  }


  render() {
    //console.log('form render', this.props.uid, this.props.model);

    return (
      <>
        {this.props.showInModal ? (
          <div className="modal-header">
            <div className="row w-100 p-0 m-0 d-flex align-items-center justify-content-center">
              <div className="col-lg-4 p-0">
                {this._renderButtonsLeft()}
              </div>
              <div className="col-lg-4 text-center">
                <h3
                  id={'adios-modal-title-' + this.props.uid}
                  className="m-0 p-0"
                >
                  {this.state.isEdit ? this.state.titleForEditing : this.state.titleForInserting}
                </h3>
              </div>
              <div className="col-lg-4 p-0 d-flex flex-row-reverse">
                {this._renderButtonsRight()}
              </div>
            </div>
          </div>
        ) : ''}

        <div
          id={"adios-form-" + this.props.uid}
          className="adios-react-ui form"
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
                    <h3 className="card-title">
                      {this.state.isEdit ? this.state.titleForEditing : this.state.titleForInserting}
                    </h3>
                  </div>

                  {this._renderButtonsLeft()}

                  {this.state.tabs != undefined ? (
                    <ul className="nav nav-tabs card-header-tabs mt-3">
                      {Object.keys(this.state.tabs).map((tabName: string) => {
                        return (
                          <li className="nav-item">
                            <button
                              className={this.state.tabs[tabName]['active'] ? 'nav-link active' : 'nav-link'}
                              onClick={() => this.changeTab(tabName)}
                            >{tabName}</button>
                          </li>
                        );
                      })}
                    </ul>
                  ) : ''}

                  {this._renderButtonsRight()}
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
