import React, {Component} from "react";

import Notification from "./Notification";
import { ProgressBar } from 'primereact/progressbar';
import request from "./Request";

import ReactQuill, {Value} from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import Swal, {SweetAlertOptions} from "sweetalert2";

import { adios } from "./Loader";
import { adiosError, deepObjectMerge } from "./Helper";

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
import { InputProps } from "./Input";

interface Content {
  [key: string]: ContentCard | any;
}

interface ContentCard {
  title: string
}

export interface FormProps {
  isInitialized?: boolean,
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
  title?: string,
  titleForInserting?: string,
  titleForEditing?: string,
  saveButtonText?: string,
  addButtonText?: string,
  defaultValues?: Object,
  endpoint?: string,
  tag?: string,
  context?: any,
}

export interface FormState {
  isInitialized: boolean,
  id?: number,
  readonly?: boolean,
  canCreate?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  canDelete?: boolean,
  content?: Content,
  columns?: FormColumns,
  data?: FormInputs,
  isEdit: boolean,
  invalidInputs: Object,
  tabs?: any,
  folderUrl?: string,
  addButtonText?: string,
  saveButtonText?: string,
  title?: string,
  titleForEditing?: string,
  titleForInserting?: string,
  layout?: string,
  endpoint: string,
  params: any,
}

export interface FormColumns {
  [key: string]: any;
}

interface FormInputs {
  [key: string]: string | number;
}

export default class Form<P, S> extends Component<FormProps, FormState> {
  newState: any;

  model: String;
  components: Array<React.JSX.Element> = [];

  jsxContentRendered: boolean = false;
  jsxContent: JSX.Element;

  constructor(props: FormProps) {
    super(props);

    globalThis.adios.reactElements[this.props.uid] = this;

    this.state = {
      isInitialized: false,
      endpoint: props.endpoint ? props.endpoint : 'components/form',
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
      data: {},
      params: null,
    };
  }


  /**
   * This function trigger if something change, for Form id of record
   */
  componentDidUpdate(prevProps: FormProps, prevState: FormState) {
    let newState: any = {};
    let setNewState: boolean = false;

    if (this.props.isInitialized != prevProps.isInitialized) {
      newState.isInitialized = this.props.isInitialized;
      setNewState = true;
    }

    if (prevProps.id !== this.props.id) {
      this.checkIfIsEdit();
      this.loadParams();
      
      newState.invalidInputs = {};
      newState.isEdit = this.props.id ? this.props.id > 0 : false;
      setNewState = true;
    }

    if (!this.state.isEdit && prevProps.defaultValues != this.props.defaultValues) {
      newState.data = this.getDataState(this.state.columns ?? {}, this.props.defaultValues);
      newState.data = this.onAfterDataLoaded(newState.data);
      setNewState = true;
    }

    if (setNewState) {
      this.setState(newState);
    }
  }

  componentDidMount() {
    this.checkIfIsEdit();
    this.initTabs();

    this.loadParams();
  }

  loadParams() {
    //@ts-ignore
    request.get(
      this.state.endpoint,
      { action: 'getParams', ...this.getEndpointParams() },
      (data: any) => {
        let newState: any = deepObjectMerge(data, this.props);
        newState.params = { ...data };
        if (newState.layout) {
          newState.layout = this.convertLayoutToString(newState.layout);
        }

        this.setState(newState, () => {
          this.loadRecord();
        });
      }
    );
  }

  getEndpointParams(): object {
    return {
      model: this.props.model,
      id: this.state.id ? this.state.id : 0,
      tag: this.props.tag,
      __IS_AJAX__: '1',
    };
  }

  loadRecord() {
    if (this.state.id) {
      request.get(
        this.state.endpoint,
        { action: 'loadRecord', ...this.getEndpointParams() },
        (data: any) => {
          let newData = this.getDataState(this.state.columns ?? {}, data);
          newData = this.onAfterDataLoaded(newData);
          this.setState({isInitialized: true, data: newData}, () => {
            this.onAfterFormInitialized();
          });
        }
      );
    } else {
      let newData = this.getDataState(this.state.columns ?? {}, {});
      newData = this.onAfterDataLoaded(newData);
      this.setState({isInitialized: true, data: newData}, () => {
        this.onAfterFormInitialized();
      });
    }
  }

  saveRecord() {
    this.setState({
      invalidInputs: {}
    });

    let formattedInputs = JSON.parse(JSON.stringify(this.state.data));

    //Object.entries(this.state.columns ?? {}).forEach(([key, value]) => {
    //  if (value['relationship'] != undefined) {
    //    Object.entries(formattedInputs[key]['values']).forEach(([i, role]) => {
    //      formattedInputs[key]['values'][i] = role.id;
    //    })
    //    formattedInputs[key] = formattedInputs[key]['values'];
    //  }
    //});

    //@ts-ignore
    request.post(
      this.state.endpoint,
      {
        action: 'saveRecord',
        ...this.getEndpointParams(),
        data: { ...formattedInputs, id: this.state.id }
      },
      {
        model: this.props.model,
        __IS_AJAX__: '1',
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
      icon: 'question',
      showCancelButton: true,
      cancelButtonText: 'Nie',
      confirmButtonText: 'Áno',
      confirmButtonColor: '#dc4c64',
      reverseButtons: false,
    } as SweetAlertOptions).then((result) => {
      if (result.isConfirmed) {
        request.delete(
          'components/form/ondelete',
          {
            model: this.props.model,
            id: id,
            __IS_AJAX__: '1',
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
      isEdit: this.state.id && this.state.id > 0 ? true : false,
    });
  }

  /**
   * Input onChange with event parameter
   */
  inputOnChange(columnName: string, event: React.FormEvent<HTMLInputElement>) {
    let inputValue: string | number = event?.currentTarget?.value ?? null;

    this.inputOnChangeRaw(columnName, inputValue);
  }

  /**
   * Input onChange with raw input value, change inputs (React state)
   */
  inputOnChangeRaw(columnName: string, inputValue: any) {
    let changedInput: any = {};
    changedInput[columnName] = inputValue;

    this.setState((prevState: FormState) => {
      return {
        data: {
          ...prevState.data,
          [columnName]: inputValue
        }
      }
    });
  }

  /**
   * Dynamically initialize inputs (React state) from model columns
   */
  getDataState(columns: FormColumns, inputsValues: Object = {}) {
    let data: any = {};

    // If is new form and defaultValues props is set
    if (Object.keys(inputsValues).length == 0 && this.props.defaultValues) {
      inputsValues = this.props.defaultValues;
    }

    Object.keys(inputsValues).map((columnName: string) => {
      if (!columns[columnName]) {
        data[columnName] = inputsValues[columnName];
      } else {
        switch (columns[columnName]['type']) {
          case 'image':
            data[columnName] = {
              fileName: inputsValues[columnName] ?? inputsValues[columnName],
              fileData: inputsValues[columnName] != undefined && inputsValues[columnName] != ""
                ? this.state.folderUrl + '/' + inputsValues[columnName]
                : null
            };
          break;
          case 'bool':
          case 'boolean':
            data[columnName] = inputsValues[columnName] ?? this.getDefaultValueForInput(columnName, 0);
          break;
          default:
            data[columnName] = inputsValues[columnName] ?? this.getDefaultValueForInput(columnName, null);
        }
      }
    });

    return data;
  }

  onAfterDataLoaded(data: any) {
    return data;
  }

  onAfterFormInitialized() {
  }


  fetchColumnData(columnName: string) {
    request.get(
      this.state.endpoint,
      { action: 'loadRecord', ...this.getEndpointParams() },
      (data: any) => {
        const input = data.data[columnName];
        this.inputOnChangeRaw(columnName, input);
      }
    );
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
  renderFormContent(): JSX.Element {
    if (this.state.columns == null) {
      return adiosError(`No columns specified for ${this.props.model}. Did the controller return definition of columns?`);
    }

    if (this.state.content?.tabs) {
      let tabs: any = Object.keys(this.state.content.tabs).map((tabName: string) => {
        return this.renderTabs(tabName, this.state.content?.tabs[tabName]);
      })

      return tabs;
    } else {
      return this.renderTabs("default", this.state.content);
    }
  }

  /*
    * Render tab content
    * If tab is not set, use default tabName else use activated one
    */
  renderTabs(tabName: string, content: any) {
    if (
      tabName == "default"
      || (this.state.tabs && this.state.tabs[tabName]['active'])
    ) {

      let key = 0;

      return (
        <div
          key={tabName}
          style={{
            display: 'grid',
            gridTemplateRows: 'auto',
            gridTemplateAreas: this.state.layout,
            gridGap: '15px'
          }}
        >
          {this.state.content != null ?
            Object.keys(content).map((contentArea: string) => {
              return this._renderContentItem(key++, contentArea, content[contentArea]);
            })
            : this.state.data != null ? (
              Object.keys(this.state.data).map((columnName: string) => {
                if (
                  this.state.columns == null
                  || this.state.columns[columnName] == null
                ) return <strong style={{color: 'red'}}>Not defined params for column `{columnName}`</strong>;

                return this.inputWrapper(columnName);
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
  _renderContentItem(key: number, contentItemArea: string, contentItemParams: undefined | string | Object | Array<string>): JSX.Element {
    if (contentItemParams == undefined) return <b style={{color: 'red'}}>Content item params are not defined</b>;

    let contentItemKeys = Object.keys(contentItemParams);
    if (contentItemKeys.length == 0) return <b style={{color: 'red'}}>Bad content item definition</b>;

    let contentItemName = contentItemArea == "inputs"
      ? contentItemArea : contentItemKeys[0];

    let contentItem: JSX.Element | null;

    switch (contentItemName) {
      case 'input':
        contentItem = this.inputWrapper(contentItemParams['input'] as string);
        break;
      case 'inputs':
        //@ts-ignore
        contentItem = (contentItemParams['inputs'] as Array<string>).map((columnName: string) => {
          return this.inputWrapper(columnName)
        });
        break;
      case 'html':
        contentItem = (<div dangerouslySetInnerHTML={{__html: contentItemParams['html']}}/>);
        break;
      default:
        contentItem = adios.getComponent(
          contentItemName,
          {
            ...contentItemParams[contentItemName],
            ...{
              parentFormId: this.state.id,
              parentFormModel: this.props.model,
            }
          }
        );

        if (contentItem !== null) {
          this.components.push(contentItem);
        }

        break;
    }

    return (
      <div key={key} style={{gridArea: contentItemArea}}>
        {contentItem}
      </div>
    );
  }

  buildInputParams(columnName: string, customInputParams?: any) {
    if (!customInputParams) customInputParams = {};
    let stateColDef = (this.state.columns ? this.state.columns[columnName] ?? {} : {});
    customInputParams = {...stateColDef, ...customInputParams};

    return {...customInputParams, ...{readonly: this.state.readonly}};
  }

  /**
   * Render different input types
   */
  input(columnName: string, customInputParams?: any): JSX.Element {
    let inputToRender: JSX.Element = <></>;

    // if (!colDef) {
    //   return adiosError(`Column '${columnName}' is not available for the Form component. Check definiton of columns in the model '${this.props.model}'.`);
    // }

    const inputParams = this.buildInputParams(columnName, customInputParams);
    const data = this.state.data ?? {};
    const columns = this.state.columns ?? {};
    const inputProps: InputProps = {
      uid: this.props.uid + '_' + columnName,
      id: this.props.uid + '_' + columnName,
      parentForm: this,
      context: this.props.context ? this.props.context : this.props.uid,
      columnName: columnName,
      params: inputParams,
      value: data[columnName] ?? '',
      invalid: this.state.invalidInputs[columnName] ?? false,
      readonly: this.props.readonly || columns[columnName]?.readonly || columns[columnName]?.disabled,
      cssClass: inputParams.cssClass ?? '',
      onChange: (value: any) => this.inputOnChangeRaw(columnName, value),
      isInitialized: false,
    };

    if (inputParams.enumValues) {
      inputToRender = <InputEnumValues {...inputProps} enumValues={inputParams.enumValues}/>
    } else {
      if (typeof inputParams.inputJSX === 'string' && inputParams.inputJSX !== '') {
        inputToRender = adios.getComponent(inputParams.inputJSX, inputProps) ?? <></>;
      } else {

        switch (inputParams.type) {
          case 'text':
            inputToRender = <InputTextarea {...inputProps} />;
            break;
          case 'float':
          case 'int':
            inputToRender = <InputInt {...inputProps} />;
            break;
          case 'boolean':
            inputToRender = <InputBoolean {...inputProps} />;
            break;
          case 'lookup':
            inputToRender = <InputLookup {...inputProps} />;
            break;
          case 'color':
            inputToRender = <InputColor {...inputProps} />;
            break;
          case 'tags':
            inputToRender = <InputTags {...inputProps} model={this.props.model} formId={this.state.id} />;
            break;
          case 'image':
            inputToRender = <InputImage {...inputProps} />;
            break;
          case 'datetime':
          case 'date':
          case 'time':
            inputToRender = <InputDateTime {...inputProps} type={inputParams.type} />;
            break;
          //case 'MapPoint':
          //  inputToRender = <InputMapPoint {...inputProps} />;
          //  break;
          case 'editor':
            if (this.state.data) {
              inputToRender = (
                <div
                  className={'h-100 form-control ' + `${this.state.invalidInputs[columnName] ? 'is-invalid' : 'border-0'}`}>
                  <ReactQuill
                    theme="snow"
                    value={this.state.data[columnName] as Value}
                    onChange={(value) => this.inputOnChangeRaw(columnName, value)}
                    className="w-100"
                  />
                </div>
              );
            }
            break;
          default: inputToRender = <InputVarchar {...inputProps} />;
        }
      }
    }

    return inputToRender ?? <></>;
  }

  inputWrapper(columnName: string, customInputParams?: any) {

    const inputParams = this.buildInputParams(columnName, customInputParams);

    return columnName == 'id' ? <></>: (
      <div className="input-wrapper" key={columnName}>
        <div className="input-label">
          {inputParams.title}
          {inputParams.required == true ? <b className="text-danger"> *</b> : ""}
        </div>

        <div className="input-body" key={columnName}>
          {this.input(columnName, inputParams)}
        </div>

        {inputParams.description ?
          <div className="input-description">{inputParams.description}</div>
          : null
        }
      </div>
    );
  }

  _renderButtonsLeft(): JSX.Element {
    let id = this.state.id ? this.state.id : 0;
    return (
      <div className="d-flex">
        {this.props.showInModal ?
          <button
            className="btn btn-light mr-2"
            type="button"
            data-dismiss="modal"
            aria-label="Close"
            onClick={this.props.onClose}
          ><span>&times;</span></button>
        : ''}

        <button
          onClick={() => this.saveRecord()}
          className={
            "btn btn-success btn-icon-split mr-2"
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
        {this.state.isEdit ?
          <button
            onClick={() => this.deleteRecord(this.state.id ? this.state.id : 0)}
            className={"btn btn-danger btn-icon-split ml-2 " + (this.state.canDelete ? "d-block" : "d-none")}
          >
            <span className="icon"><i className="fas fa-trash"></i></span>
            <span className="text">Delete</span>
          </button>
          : ''
        }
      </div>
    );
  }


  render() {

    if (!this.state.isInitialized) {
      return (
        <div className="p-4">
          <ProgressBar mode="indeterminate" style={{ height: '30px' }}></ProgressBar>
        </div>
      );
    }

    let formContent = this.renderFormContent();
    let title = 
      this.state.title ? this.state.title :
      this.state.isEdit ? this.state.titleForEditing : this.state.titleForInserting
    ;

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
                >{title}</h3>
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
              {formContent}
            </div>
          ) : (
            <>
              {title ? (
                <div className="py-4">
                  <h1>{title}</h1>
                </div>
              ) : null}
              <div className="card w-100">
                <div className="card-header">
                  <div className="row">
                    <div className={"col-lg-" + (this.state.tabs == undefined ? "6" : "3") + " m-0 p-0"}>
                      {this._renderButtonsLeft()}
                    </div>

                    {this.state.tabs != undefined ? (
                      <div className={"col-lg-6 m-0 p-0"}>
                        <ul className="nav nav-tabs card-header-tabs mt-3">
                          {Object.keys(this.state.tabs).map((tabName: string) => {
                            return (
                              <li className="nav-item" key={tabName}>
                                <button
                                  className={this.state.tabs[tabName]['active'] ? 'nav-link active' : 'nav-link'}
                                  onClick={() => this.changeTab(tabName)}
                                >{tabName}</button>
                              </li>
                            );
                          })}
                        </ul>
                      </div>
                    ) : ''}

                    <div className={"col-lg-" + (this.state.tabs == undefined ? "6" : "3") + " m-0 p-0 text-right"}>
                      {this._renderButtonsRight()}
                    </div>
                  </div>
                </div>

                <div className="card-body">
                  {formContent}
                </div>
              </div>
            </>
          )}
        </div>
      </>
    );
  }
}
