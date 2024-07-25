import React, {Component} from "react";
import * as uuid from 'uuid';

import Notification from "./Notification";
import { ProgressBar } from 'primereact/progressbar';
import { Tooltip } from 'primereact/tooltip';
import request from "./Request";

import Swal, {SweetAlertOptions} from "sweetalert2";

import { adiosError, deepObjectMerge } from "./Helper";

import Table from "./Table";
import { InputProps } from "./Input";
import { InputFactory } from "./InputFactory";
import { TableProps } from "@mui/material";

interface Content {
  [key: string]: ContentCard | any;
}

interface ContentCard {
  title: string
}

export interface FormProps {
  parentTable?: any,
  isInitialized?: boolean,
  uid?: string,
  model: string,
  id?: number,
  prevId?: number,
  nextId?: number,
  readonly?: boolean,
  content?: Content,
  layout?: Array<Array<string>>,
  onClose?: () => void;
  onSaveCallback?: (inlineEditing: boolean) => void,
  onDeleteCallback?: () => void,
  hideOverlay?: boolean,
  showInModal?: boolean,
  showInModalSimple?: boolean,
  isInlineEditing?: boolean,
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
  prevId?: number,
  nextId?: number,
  readonly?: boolean,
  canCreate?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  canDelete?: boolean,
  content?: Content,
  columns?: FormColumns,
  data?: FormInputs,
  isEdit: boolean,
  isInlineEditing: boolean,
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
  [key: string]: any;
}

export default class Form<P, S> extends Component<FormProps, FormState> {
  static defaultProps = {
    uid: '_form_' + uuid.v4().replace('-', '_'),
  }

  newState: any;

  model: String;
  components: Array<React.JSX.Element> = [];

  jsxContentRendered: boolean = false;
  jsxContent: JSX.Element;

  constructor(props: FormProps) {
    super(props);

    if (this.props.uid) {
      globalThis.app.reactElements[this.props.uid] = this;
    }

    this.state = {
      isInitialized: false,
      endpoint: props.endpoint ? props.endpoint : (globalThis.app.config.defaultFormEndpoint ?? 'components/form'),
      id: props.id,
      prevId: props.prevId,
      nextId: props.nextId,
      readonly: props.readonly,
      canCreate: props.readonly,
      canRead: props.readonly,
      canUpdate: props.readonly,
      canDelete: props.readonly,
      content: props.content,
      layout: this.convertLayoutToString(props.layout),
      isEdit: props.id ? props.id > 0 : false,
      isInlineEditing: props.isInlineEditing ? props.isInlineEditing : false,
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
        let newState: any = deepObjectMerge({ ...data }, this.props);
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
    if (this.state.id && this.state.id > 0) {
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

  saveRecord(inlineEditing: boolean) {
    this.setState({
      invalidInputs: {}
    });

    let formattedInputs = JSON.parse(JSON.stringify(this.state.data));

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
        if (this.props.onSaveCallback) this.props.onSaveCallback(inlineEditing);
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

  // deleteRecord(id: number) {
  //   //@ts-ignore
  //   Swal.fire({
  //     title: 'Ste si istý?',
  //     html: 'Ste si istý, že chcete vymazať tento záznam?',
  //     icon: 'question',
  //     showCancelButton: true,
  //     cancelButtonText: 'Nie',
  //     confirmButtonText: 'Áno',
  //     confirmButtonColor: '#dc4c64',
  //     reverseButtons: false,
  //   } as SweetAlertOptions).then((result) => {
  //     if (result.isConfirmed) {
  //       request.delete(
  //         'components/form/ondelete',
  //         {
  //           model: this.props.model,
  //           id: id,
  //           __IS_AJAX__: '1',
  //         },
  //         () => {
  //           Notification.success("Záznam zmazaný");
  //           if (this.props.onDeleteCallback) this.props.onDeleteCallback();
  //         }
  //       );
  //     }
  //   })
  // }

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
  renderContent(): JSX.Element {
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
        contentItem = globalThis.app.getComponent(
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
  input(columnName: string, customInputParams?: any, onChange?: any): JSX.Element {
    let inputToRender: JSX.Element = <></>;

    // if (!colDef) {
    //   return adiosError(`Column '${columnName}' is not available for the Form component. Check definiton of columns in the model '${this.props.model}'.`);
    // }

    if (!onChange) onChange = (value: any) => this.inputOnChangeRaw(columnName, value);

    const inputParams = this.buildInputParams(columnName, customInputParams);
    const data = this.state.data ?? {};
    const columns = this.state.columns ?? {};
    const inputProps: InputProps = {
      uid: this.props.uid + '_' + columnName,
      columnName: columnName,
      parentForm: this,
      context: this.props.context ? this.props.context : this.props.uid,
      params: inputParams,
      value: data[columnName] ?? '',
      invalid: this.state.invalidInputs[columnName] ?? false,
      readonly: this.props.readonly || columns[columnName]?.readonly || columns[columnName]?.disabled,
      cssClass: inputParams.cssClass ?? '',
      onChange: onChange,
      isInitialized: false,
      isInlineEditing: this.state.isInlineEditing,
      showInlineEditingButtons: !this.state.isInlineEditing,
      onInlineEditCancel: () => {
      },
      onInlineEditSave: () => {
        this.saveRecord(true);
      }
    };

    return InputFactory(inputProps);
  }

  inputWrapper(columnName: string, customInputParams?: any) {

    const inputParams = this.buildInputParams(columnName, customInputParams);

    return columnName == 'id' ? <></>: (
      <div
        id={this.props.uid + '_' + columnName}
        className={"input-wrapper" + (inputParams.required == true ? " required" : "")}
        key={columnName}
      >
        <label className="input-label" htmlFor={this.props.uid + '_' + columnName}>
          {inputParams.title}
        </label>

        <div className="input-body" key={columnName}>
          {this.input(columnName, inputParams)}

          {inputParams.description
            ? <>
              <Tooltip target={'#' + this.props.uid + '_' + columnName + ' .input-description'} />
              <i
                className="input-description fas fa-info"
                data-pr-tooltip={inputParams.description}
                data-pr-position="top"
              ></i>
            </>
            : null
          }
        </div>

      </div>
    );
  }

  renderSaveButton(): JSX.Element {
    let id = this.state.id ? this.state.id : 0;

    return (
      <button
        onClick={() => this.saveRecord(false)}
        className={
          "btn btn-success"
          + (id <= 0 && this.state.canCreate || id > 0 && this.state.canUpdate ? "d-block" : "d-none")
        }
      >
        {this.state.isEdit
          ? (
            <>
              <span className="icon"><i className="fas fa-save"></i></span>
              <span className="text"> {this.state.saveButtonText ?? globalThis.app.translate("Save")}</span>
            </>
          )
          : (
            <>
              <span className="icon"><i className="fas fa-plus"></i></span>
              <span className="text"> {this.state.addButtonText ?? globalThis.app.translate("Add")}</span>
            </>
          )
        }
      </button>
    );
  }

  renderEditButton(): JSX.Element {
    return (
      <button
        onClick={() => this.setState({ isInlineEditing: true })}
        className="btn btn-transparent"
      >
        <span className="icon"><i className="fas fa-pencil-alt"></i></span>
        <span className="text">{globalThis.app.translate('Edit')}</span>
      </button>
    );
  }

  renderCloseButton(): JSX.Element {
    return (
      <button
        className="btn btn-light"
        type="button"
        data-dismiss="modal"
        aria-label="Close"
        onClick={this.props.onClose}
      ><span className="text">&times;</span></button>
    );
  }

  renderHeaderLeft(): JSX.Element {
    return <>
      {this.props.showInModal ? this.renderCloseButton() : null}
      {this.state.isInlineEditing ? this.renderSaveButton() : this.renderEditButton()}
    </>;
  }

  renderHeaderRight(): JSX.Element {
    const prevId = this.state?.prevId ?? 0;
    const nextId = this.state?.nextId ?? 0;

    return <>
      {prevId > 0 && nextId > 0 ? <>
        <button
          onClick={() => {
            if (prevId > 0 && this.props.parentTable) {
              this.props.parentTable.openForm(prevId);
            }
          }}
          className={"btn btn-transparent" + (prevId > 0 ? "" : " btn-disabled")}
        >
          <span className="icon"><i className="fas fa-angle-left"></i></span>
        </button>
        <button
          onClick={() => {
            if (nextId > 0 && this.props.parentTable) {
              this.props.parentTable.openForm(nextId);
            }
          }}
          className={"btn btn-transparent" + (nextId > 0 ? "" : " btn-disabled")}
        >
          <span className="icon"><i className="fas fa-angle-right"></i></span>
        </button>
      </> : null}
      {/* {this.state.isEdit ?
        <button
          onClick={() => this.deleteRecord(this.state.id ? this.state.id : 0)}
          className={"btn btn-danger btn-icon-split ml-2 " + (this.state.canDelete ? "d-block" : "d-none")}
        >
          <span className="icon"><i className="fas fa-trash"></i></span>
          <span className="text">{globalThis.app.translate('Delete')}</span>
        </button>
        : ''
      } */}
    </>;
  }

  renderTitle(): JSX.Element {
    let title = 
      this.state.title ? this.state.title :
      this.state.isEdit ? this.state.params?.titleForEditing : this.state.params?.titleForInserting
    ;

    return <>
      <h2>{title}</h2>
      <small>{
        this.state.isEdit
          ? globalThis.app.translate('Editing record') + ' #' + this.state.id
          : globalThis.app.translate('Adding new record')
      }</small>
    </>
  }

  render() {

    if (!this.state.isInitialized) {
      return (
        <div className="p-4 h-full flex items-center">
          <ProgressBar mode="indeterminate" style={{ flex: 1, height: '30px' }}></ProgressBar>
        </div>
      );
    }

    let formTitle = this.renderTitle();
    let formContent = this.renderContent();

    if (this.props.showInModalSimple) {
      return <>
        <div className="modal-header">
          <div className="modal-header-inner">
            <div className="modal-header-left">
              {this.renderHeaderLeft()}
            </div>
            <div className="modal-header-title">
              {formTitle}
            </div>
            <div className="modal-header-right">
              {this.renderHeaderRight()}
            </div>
          </div>
        </div>
        <div className="modal-body">
          {formContent}
        </div>
      </>;
    } else {
      return (
        <>
          {this.props.showInModal ? (
            <div className="modal-header">
              <div className="row w-100 p-0 m-0 d-flex align-items-center justify-content-center">
                <div className="col-lg-4 p-0">
                  {this.renderHeaderLeft()}
                </div>
                <div className="col-lg-4 text-center">
                  <h3
                    id={'adios-modal-title-' + this.props.uid}
                    className="m-0 p-0"
                  >{formTitle}</h3>
                </div>
                <div className="col-lg-4 p-0 d-flex flex-row-reverse">
                  {this.renderHeaderRight()}
                </div>
              </div>
            </div>
          ) : ''}

          <div
            id={"adios-form-" + this.props.uid}
            className="adios component form"
          >
            {this.props.showInModal ? (
              <div className="modal-body">
                {formContent}
              </div>
            ) : (
              <>
                {formTitle}
                <div className="card w-100">
                  <div className="card-header">
                    <div className="row">
                      <div className={"col-lg-" + (this.state.tabs == undefined ? "6" : "3") + " m-0 p-0"}>
                        {this.renderHeaderLeft()}
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
                        {this.renderHeaderRight()}
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
}
