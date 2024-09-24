import React, { Component, ChangeEvent, createRef } from 'react';

import Modal, { ModalProps } from "./Modal";
import ModalSimple from "./ModalSimple";
import Form, { FormEndpoint, FormProps, FormState } from "./Form";
import Notification from "./Notification";
import Swal from "sweetalert2";

import {
  DataTable,
  DataTableRowClickEvent,
  DataTableSelectEvent,
  DataTableUnselectEvent,
  DataTablePageEvent,
  DataTableSortEvent,
  SortOrder,
} from 'primereact/datatable';
import { Column } from 'primereact/column';
import { ProgressBar } from 'primereact/progressbar';
import { OverlayPanel } from 'primereact/overlaypanel';
import { InputFactory } from "./InputFactory";
import { dateToEUFormat, datetimeToEUFormat } from "./Inputs/DateTime";


import { adiosError, deepObjectMerge } from "./Helper";
import request from "./Request";

export interface TableEndpoint {
  describeTable: string,
  getRecords: string,
  deleteRecord: string,
}

export interface TableOrderBy {
  field: string,
  direction?: string | null
}

export interface TableColumns {
  [key: string]: any;
}

export interface TablePermissions {
  canCreate?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  canDelete?: boolean,
}

export interface TableUi {
  title?: string,
  subTitle?: string,
  addButtonText?: string,
  showHeader?: boolean,
  showFooter?: boolean,
  showFilter?: boolean,
  //showPaging?: boolean,
  //showControls?: boolean,
  //showAddButton?: boolean,
  //showPrintButton?: boolean,
  //showSearchButton?: boolean,
  //showExportCsvButton?: boolean,
  //showImportCsvButton?: boolean,
  //showFulltextSearch?: boolean
}

export interface TableDescription {
  columns: TableColumns,
  permissions?: TablePermissions,
  ui?: TableUi,
}

export interface ExternalCallbacks {
  openForm?: string,
  onAddClick?: string,
  onRowClick?: string,
  onDeleteRecord?: string,
}

export interface TableProps {
  uid: string,
  description?: TableDescription,
  descriptionSource?: 'props' | 'request' | 'both',
  recordId?: any,
  formEndpoint?: FormEndpoint,
  formModal?: ModalProps,
  formProps?: FormProps,
  formReactComponent?: string,
  formCustomProps?: any,
  endpoint?: TableEndpoint,
  customEndpointParams?: any,
  model: string,
  parentRecordId?: any,
  parentForm?: Form<FormProps, FormState>,
  parentFormModel?: string,
  tag?: string,
  context?: string,
  where?: Array<any>,
  params?: any,
  externalCallbacks?: ExternalCallbacks,
  itemsPerPage: number,
  orderBy?: TableOrderBy,
  inlineEditingEnabled?: boolean,
  isInlineEditing?: boolean,
  isUsedAsInput?: boolean,
  selectionMode?: 'single' | 'multiple' | undefined,
  onChange?: (table: Table<TableProps, TableState>) => void,
  onRowClick?: (table: Table<TableProps, TableState>, row: any) => void,
  onDeleteRecord?: (table: Table<TableProps, TableState>) => void,
  onDeleteSelectionChange?: (table: Table<TableProps, TableState>) => void,
  data?: TableData,
  async?: boolean,
  readonly?: boolean,
  closeFormAfterSave?: boolean,
  className?: string,
}

// Laravel pagination
interface TableData {
  current_page?: number,
  data: Array<any>,
  first_page_url?: string,
  from?: number,
  last_page_url?: string,
  last_page?: number,
  links?: Array<any>,
  next_page_url?: string|null,
  path?: string,
  per_page?: number,
  prev_page_url?: string|null,
  to?: number,
  total?: number
}

export interface TableState {
  endpoint: TableEndpoint,
  description?: TableDescription,
  data?: TableData | null,
  filterBy?: any,
  recordId?: any,
  recordPrevId?: any,
  recordNextId?: any,
  formEndpoint?: FormEndpoint,
  formProps?: FormProps,
  orderBy?: TableOrderBy,
  page: number,
  itemsPerPage: number,
  search?: string,
  inlineEditingEnabled: boolean,
  isInlineEditing: boolean,
  isUsedAsInput: boolean,
  selection: any,
  async: boolean,
  readonly: boolean,
  customEndpointParams: any,
}

export default class Table<P, S> extends Component<TableProps, TableState> {
  static defaultProps = {
    itemsPerPage: 100,
    descriptionSource: 'both',
  }

  state: TableState;

  dt = createRef<DataTable<any[]>>();

  constructor(props: TableProps) {
    super(props);

    globalThis.app.reactElements[this.props.uid] = this;

    this.state = this.getStateFromProps(props);
  }

  getStateFromProps(props: TableProps): TableState {
    return {
      endpoint: props.endpoint ? props.endpoint : (globalThis.app.config.defaultTableEndpoint ?? {
        describeTable: 'api/table/describe',
        getRecords: 'api/record/get-list',
        deleteRecord: 'api/record/delete',
      }),
      description: props.description,
      recordId: props.recordId,
      formEndpoint: props.formEndpoint ? props.formEndpoint : (globalThis.app.config.defaultFormEndpoint ?? null),
      formProps: {
        model: props.model,
        uid: props.uid,
      },
      page: 1,
      itemsPerPage: this.props.itemsPerPage,
      orderBy: this.props.orderBy,
      inlineEditingEnabled: props.inlineEditingEnabled ? props.inlineEditingEnabled : false,
      isInlineEditing: props.isInlineEditing ? props.isInlineEditing : false,
      isUsedAsInput: props.isUsedAsInput ? props.isUsedAsInput : false,
      selection: [],
      // idsToDelete: [],
      data: props.data ? props.data : null,
      async: props.async ?? true,
      readonly: props.readonly ?? false,
      customEndpointParams: this.props.customEndpointParams ?? {},
    };
  }

  componentDidMount() {
    if (this.state.async) {
      this.loadTableDescription();
      this.loadData();
    }
  }

  componentDidUpdate(prevProps: TableProps) {
    if (
      (prevProps.formProps?.id != this.props.formProps?.id)
      || (prevProps.parentRecordId != this.props.parentRecordId)
    ) {
      this.state.formProps = this.props.formProps;
      if (this.state.async) {
        this.loadTableDescription();
        this.loadData();
      }
    }

    if (
      prevProps.data != this.props.data
      || prevProps.description != this.props.description
    ) {
      this.setState(this.getStateFromProps(this.props))
    }
  }

  onAfterLoadTableDescription(params: any): any {
    return params;
  }

  // getEndpointUrl(): string {
  //   return this.state.endpoint;
  // }

  getEndpointUrl(action: string) {
    return this.state.endpoint[action] ?? '';
  }

  getEndpointParams(): any {
    return {
      model: this.props.model,
      parentRecordId: this.props.parentRecordId ? this.props.parentRecordId : 0,
      parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
      tag: this.props.tag,
      context: this.props.context,
      __IS_AJAX__: '1',
      ...this.props.customEndpointParams,
    }
  }

  getTableProps(): Object {
    const sortOrders = {'asc': 1, 'desc': -1};
    const totalRecords = this.state.data?.total ?? 0;

    let tableProps: any = {
      ref: this.dt,
      value: this.state.data?.data,
      // editMode: 'row',
      compareSelectionBy: 'equals',
      dataKey: "id",
      first: (this.state.page - 1) * this.state.itemsPerPage,
      paginator: totalRecords > this.state.itemsPerPage,
      lazy: true,
      rows: this.state.itemsPerPage,
      totalRecords: totalRecords,
      rowsPerPageOptions: [5, 15, 30, 50, 100, 200, 300, 500, 750, 1000, 1500, 2000],
      paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
      currentPageReportTemplate: "{first}-{last} / {totalRecords}",
      onRowClick: (data: DataTableRowClickEvent) => this.onRowClick(data.data),
      onRowSelect: (event: DataTableSelectEvent) => this.onRowSelect(event),
      onRowUnselect: (event: DataTableUnselectEvent) => this.onRowUnselect(event),
      onPage: (event: DataTablePageEvent) => this.onPaginationChangeCustom(event),
      onSort: (event: DataTableSortEvent) => this.onOrderByChangeCustom(event),
      sortOrder: sortOrders[this.state.orderBy?.direction ?? 'asc'],
      sortField: this.state.orderBy?.field,
      rowClassName: (rowData: any) => this.rowClassName(rowData),
      stripedRows: true,
      //globalFilter={globalFilter}
      //header={header}
      emptyMessage: globalThis.app.dictionary['PrimeTable/emptyMessage'] ?? 'No data.',
      dragSelection: true,
      selectAll: true,
      metaKeySelection: true,
      selection: this.state.selection,
      selectionMode: (this.props.selectionMode == 'single' ? 'radiobutton': (this.props.selectionMode == 'multiple' ? 'checkbox' : null)),
      onSelectionChange: (event: any) => {
        this.setState(
          {selection: event.value} as TableState,
          function() {
            this.onSelectionChange(event);
          }
        )
      },
    };

    if (this.state.description?.ui?.showFooter) tableProps.footer = this.renderFooter();

    return tableProps;
  }

  loadTableDescription(successCallback?: (params: any) => void) {

    if (this.props.descriptionSource == 'props') return;

    // if (this.props.description) {
    //   this.setState({description: this.props.description});
    // } else {
      request.get(
        this.getEndpointUrl('describeTable'),
        {
          ...this.getEndpointParams(),
        },
        (description: any) => {
          try {
            // if (description.status == 'error') throw new Error('Error while loading table description: ' + description.message);
console.log(description, this.props.description, this.props.descriptionSource);
            if (this.props.description && this.props.descriptionSource == 'both') description = deepObjectMerge(description, this.props.description);
console.log(description);
            // let description: any = data; //deepObjectMerge(data, this.props.description ?? {});
            if (description.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);
            if (successCallback) successCallback(description);

            description = this.onAfterLoadTableDescription(description);

            this.setState({description: description});
          } catch (err) {
            Notification.error(err.message);
          }
        }
      );
    // }
  }

  loadData() {
    if (this.props.data) {
      this.setState({data: this.props.data});
    } else {
      request.get(
        this.getEndpointUrl('getRecords'),
        {
          ...this.getEndpointParams(),
          filterBy: this.state.filterBy,
          model: this.props.model,
          orderBy: this.state.orderBy,
          page: this.state.page ?? 0,
          itemsPerPage: this.state.itemsPerPage ?? 15,
          parentRecordId: this.props.parentRecordId ? this.props.parentRecordId : 0,
          parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
          search: this.state.search,
          tag: this.props.tag,
          context: this.props.context,
          where: this.props.where,
          __IS_AJAX__: '1',
        },
        (data: any) => {
          this.setState({
            data: data,
          });
        }
      );
    }
  }

  getFormProps(): FormProps {
    return {
      parentTable: this,
      uid: this.props.uid + '_form',
      model: this.props.model,
      tag: this.props.tag,
      context: this.props.context,
      id: this.state.recordId ?? null,
      prevId: this.state?.recordPrevId ?? 0,
      nextId: this.state?.recordNextId ?? 0,
      endpoint: this.state.formEndpoint,
      isInlineEditing: (this.state.recordId ?? null) === -1,
      showInModal: true,
      description: this.props.formProps?.description,
      ...this.props.formCustomProps ?? {},
      customEndpointParams: this.state.customEndpointParams ?? {},
      onClose: () => {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('recordId');
        urlParams.delete('recordTitle');
        if (Array.from(urlParams).length == 0) {
          window.history.pushState({}, '', window.location.protocol + "//" + window.location.host + window.location.pathname);
        } else {
          window.history.pushState({}, "", '?' + urlParams.toString());
        }

        this.setState({ recordId: null });
      },
      onSaveCallback: (form: Form<FormProps, FormState>, saveResponse: any) => {
        this.loadData();
        if (this.props.closeFormAfterSave ?? false) {
          this.setState({ recordId: null });
        } else {
          if (saveResponse && saveResponse.savedRecord.id) {
            this.openForm(saveResponse.savedRecord.id);
          }
        }
      },
      onCopyCallback: (form: Form<FormProps, FormState>, saveResponse: any) => {
        this.loadData();
        this.openForm(saveResponse.savedRecord.id);
      },
      onDeleteCallback: () => {
        this.loadData();
        this.setState({ recordId: null });
      },
      isInitialized: false,
    }
  }

  getFormModalProps(): any {
    return {
      uid: this.props.uid + '_form',
      type: this.state.recordId == -1 ? 'centered' : 'right',
      hideHeader: true,
      isOpen: this.state.recordId !== null,
      ...this.props.formModal
    }
  }

  cellClassName(columnName: string, column: any, rowData: any) {
    let cellClassName = '';

    if (column.enumValues) {
      cellClassName = 'badge ' + (column.enumCssClasses ? (column.enumCssClasses[rowData[columnName]] ?? '') : '');
    } else {
      switch (column.type) {
        case 'int':
        case 'float':
          cellClassName = 'text-right font-semibold';
        break;
        case 'date':
        case 'datetime':
          cellClassName = 'text-right';
        break;
        case 'lookup':
          cellClassName = 'text-primary';
        break;
      }
    }

    return cellClassName;
  }

  rowClassName(rowData: any): string {
    return rowData.id === this.state.recordId ? 'highlighted' : '';
  }

  renderAddButton(): JSX.Element {
    return (
      <button
        className="btn btn-primary"
        onClick={() => this.onAddClick()}
      >
        <span className="icon"><i className="fas fa-plus"/></span>
        {this.state.description?.ui?.addButtonText ? <span className="text">{this.state.description?.ui?.addButtonText}</span> : null}
      </button>
    );
  }

  renderHeaderButtons(): Array<JSX.Element> {
    let buttons: Array<JSX.Element> = [];
    if (!this.state.readonly && this.state.description?.permissions?.canCreate) buttons.push(this.renderAddButton());
    return buttons;
  }

  renderHeader(): JSX.Element {
    return <div className="table-header">
      {this.state.description?.ui?.title ? <div className="table-header-title">{this.state.description?.ui?.title}</div> : null}

      <div className="table-header-left">
        {this.renderHeaderButtons()}
      </div>

      <div className="table-header-right">
        <input
          className="table-header-search"
          type="search"
          placeholder="Start typing to search..."
          value={this.state.search}
          onChange={(event: ChangeEvent<HTMLInputElement>) => this.onSearchChange(event.target.value)}
        />
      </div>
    </div>
  }

  renderFilter(): JSX.Element {
    return <></>;
  }

  renderFooter(): JSX.Element {
    return <></>;
  }

  findRecordById(id: number): any {
    let data: any = {};

    for (let i in this.state.data?.data) {
      if (this.state.data?.data[i].id == id) {
        data = this.state.data.data[i];
      }
    }

    return data;
  }

  deleteRecord() {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onDeleteRecord) {
      window[this.props.externalCallbacks.onDeleteRecord](this);
    } if (this.props.onDeleteRecord) {
      this.props.onDeleteRecord(this);
    } else {

      let recordToDelete: any = null;

      for (let i in this.state.data?.data) {
        if (this.state.data?.data[i]._toBeDeleted_) {
          recordToDelete = this.state.data?.data[i];
          break;
        }
      }

      // this.findRecordById(this.state.idsToDelete[0]);

      if (recordToDelete) {
        request.get(
          this.getEndpointUrl('deleteRecord'),
          {
            model: this.props.model,
            id: recordToDelete.id ?? 0,
            hash: recordToDelete._idHash_ ?? '',
          },
          (response: any) => {
            if (response.errorHtml) {
              Swal.fire({
                title: '<div style="text-align:left">🥴 Ooops</div>',
                html: response.errorHtml,
                width: '80vw',
                padding: '1em',
                color: "#ad372a",
                background: "white",
                backdrop: `rgba(123,12,0,0.2)`
              });
              // Notification.error(response.error);
            } else {
              this.loadData();
            }
          }
        );
      }
    }
  }

  renderDeleteConfirmModal(): JSX.Element {
    let hasRecordsToDelete: boolean = false;
    for (let i in this.state.data?.data) {
      if (this.state.data?.data[i]._toBeDeleted_) {
        hasRecordsToDelete = true;
        break;
      }
    }

    if (hasRecordsToDelete) {
      return (
        <ModalSimple
          uid={this.props.uid + '_delete_confirm'}
          isOpen={true} type='centered tiny'
        >
          <div className='modal-header'>
            <div>
              <div>{globalThis.app.translate('Delete record')}</div>
            </div>
          </div>
          <div className='modal-body'>
            {globalThis.app.translate('You are about to delete the record. Press OK to confirm.')}
          </div>
          <div className='modal-footer'>
            <div className='flex justify-between'>
              <button
                className='btn btn-primary'
                onClick={() => {
                  this.deleteRecord();
                }}
              >
                <span className='icon'><i className='fas fa-check'></i></span>
                <span className='text'>{globalThis.app.translate('Yes, delete')}</span>
              </button>
              <button
                className='btn btn-cancel'
                onClick={() => {
                  if (this.state.data) {
                    let newData: TableData = this.state.data;
                    for (let i in newData.data) delete newData.data[i]._toBeDeleted_;
                    this.setState({data: newData});
                  }
                }}
              >
                <span className='icon'><i className='fas fa-times'></i></span>
                <span className='text'>{globalThis.app.translate('No, do not delete')}</span>
              </button>
            </div>
          </div>
        </ModalSimple>
      );
    } else {
      return <></>;
    }
  }

  renderFormModal(): JSX.Element {
    if (this.state.recordId) {
      return <ModalSimple {...this.getFormModalProps()}>{this.renderForm()}</ModalSimple>;
    } else {
      return <></>;
    }
  }

  renderForm(): JSX.Element {
    if (this.props.formReactComponent) {
      return globalThis.app.renderReactElement(this.props.formReactComponent, this.getFormProps()) ?? <></>;
    } else {
      return <Form {...this.getFormProps()} />;
    }
  }

  // getColumnValue(columnName: string, column: any, data: any) {
  //   if (column['type'] == 'lookup') {
  //     return data['_LOOKUP[' + columnName + ']'] ?? '';
  //   } else {
  //     return data[columnName];
  //   }
  // }

  /*
   * Render body for Column (PrimeReact column)
   */
  renderCell(columnName: string, column: any, data: any, options: any) {
    const columnValue: any = data[columnName]; // this.getColumnValue(columnName, column, data);
    const enumValues = column.enumValues;
    const inputProps = {
      uid: this.props.uid + '_' + columnName,
      columnName: columnName,
      params: column,
      value: columnValue,
      showInlineEditingButtons: true,
    };
    const rowIndex = options.rowIndex;
    const cellContent = enumValues ? enumValues[columnValue] : columnValue;

    if (typeof column.cellRenderer == 'function') {
      return column.cellRenderer(this, data, options);
    } else {

      let cellValueElement: JSX.Element|null = null;

      if (cellContent === null) {
        cellValueElement = null;
      } else {
        switch (column.type) {
          case 'int':
            cellValueElement = <>
              {cellContent}
              {column.unit ? ' ' + column.unit : ''}
            </>;
          break;
          case 'float':
            cellValueElement = <>
              {cellContent ? cellContent.toFixed(column.decimals ?? 2) : null}
              {column.unit ? ' ' + column.unit : ''}
            </>;
          break;
          case 'color':
            cellValueElement = <div
              style={{ width: '20px', height: '20px', background: cellContent }}
              className="rounded"
            />;
          break;
          // case 'image':
          //   if (!cellContent) cellValueElement = <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
          //   else {
          //     cellValueElement = <img
          //       style={{ width: '30px', height: '30px' }}
          //       src={this.state.folderUrl + "/" + cellContent}
          //       className="rounded"
          //     />;
          //   }
          break;
          case 'lookup':
            cellValueElement = data['_LOOKUP[' + columnName + ']'] ?? '';
          break;
          case 'enum':
            const enumValues = column.enumValues;
            if (enumValues) cellValueElement = enumValues[cellContent];
          break;
          case 'boolean':
            if (cellContent) cellValueElement = <span className="text-green-600" style={{fontSize: '1.2em'}}>✓</span>
            else cellValueElement = <span className="text-red-600" style={{fontSize: '1.2em'}}>✕</span>
          break;
          case 'date':
            cellValueElement = <>{cellContent == '0000-00-00' ? '' : dateToEUFormat(cellContent)}</>;
          break;
          case 'datetime':
            cellValueElement = <>{cellContent == '0000-00-00' ? '' : datetimeToEUFormat(cellContent)}</>;
          break;
          case 'tags':
            cellValueElement = <>
              {cellContent.map((item: any) => {
                if (!column.dataKey) return <></>;
                return <span className="badge badge-info mx-1" key={item.id}>{item[column.dataKey]}</span>;
              })}
            </>
          break;
          default:
            cellValueElement = cellContent;
          break;
        }

        if (cellValueElement === <></>) {
          cellValueElement = cellContent;
        }
      }

      let op = createRef<OverlayPanel>();

      if (this.props.isInlineEditing) {
        return InputFactory({
          ...inputProps,
          isInlineEditing: this.props.isInlineEditing,
          showInlineEditingButtons: false,
          onInlineEditCancel: () => { op.current?.hide(); },
          onChange: (value: any) => {
            if (this.state.data) {
              let data: TableData = this.state.data;
              data.data[rowIndex][columnName] = value;
              this.setState({data: data});
              if (this.props.onChange) {
                this.props.onChange(this);
              }
            }
          }
        });
      } else {
        return cellValueElement;
      }
    }
  }

  renderColumns(): JSX.Element[] {
    let columns: JSX.Element[] = [];

    if (this.props.selectionMode) {
      columns.push(<Column selectionMode={this.props.selectionMode}></Column>);
    }

    Object.keys(this.state.description?.columns ?? {}).map((columnName: string) => {
      const column: any = this.state.description?.columns[columnName] ?? {};
      columns.push(<Column
        key={columnName}
        field={columnName}
        header={column.title}
        body={(data: any, options: any) => {
          return (
            <div
              className={
                (column.cssClass ?? '')
                + (data._toBeDeleted_ ? ' to-be-deleted' : '')
                + ' '
                + this.cellClassName(columnName, column, data)
              }
              style={column.cssStyle}
            >
              {this.renderCell(columnName, column, data, options)}
            </div>
          );
        }}
        style={{ width: 'auto' }}
        sortable
      ></Column>);
    });

    columns.push(<Column
      key='__actions'
      field='__actions'
      header=''
      body={(data: any, options: any) => {
        return <>
          {!this.state.readonly && this.state.description?.permissions?.canDelete ?
            data._toBeDeleted_
            ? <button
              className="btn btn-list-item btn-cancel"
              onClick={(e) => {
                e.preventDefault();
                delete this.findRecordById(data.id)._toBeDeleted_;
                this.setState({data: this.state.data}, () => {
                  if (this.props.onDeleteSelectionChange) {
                    this.props.onDeleteSelectionChange(this);
                  }
                });
              }}
            >
              <span className="icon"><i className="fas fa-times"></i></span>
            </button>
            : <button
              className="btn btn-list-item btn-danger"
              title={globalThis.app.translate('Delete')}
              onClick={(e) => {
                e.preventDefault();
                this.findRecordById(data.id)._toBeDeleted_ = true;
                this.setState({data: this.state.data}, () => {
                  if (this.props.onDeleteSelectionChange) {
                    this.props.onDeleteSelectionChange(this);
                  }
                });
              }}
            >
              <span className="icon"><i className="fas fa-trash-alt"></i></span>
            </button>
          : null}
        </>;
      }}
      style={{ width: 'auto' }}
    ></Column>);

    return columns;
  }

  render() {
    if (!this.state.data || !this.state.description?.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    return (
      <>
        {this.renderFormModal()}
        {this.state.isUsedAsInput ? null : this.renderDeleteConfirmModal()}

        <div
          id={"adios-table-" + this.props.uid}
          className={"adios component table" + (this.props.className ? " " + this.props.className : "")}
        >
          {this.state.description?.ui?.showHeader ? this.renderHeader() : null}
          {this.state.description?.ui?.showFilter ? this.renderFilter() : null}

          <div className="table-body" id={"adios-table-body-" + this.props.uid}>
            <DataTable {...this.getTableProps()}>
              {this.renderColumns()}
            </DataTable>
          </div>
        </div>
      </>
    );
  }

  onSelectionChange(event: any) {
    // to be overriden
  }

  onPaginationChangeCustom(event: DataTablePageEvent) {
    const page: number = (event.page ?? 0) + 1;
    const itemsPerPage: number = event.rows;
    this.onPaginationChange(page, itemsPerPage);
  }

  onOrderByChangeCustom(event: DataTableSortEvent) {
    let orderBy: TableOrderBy | null = null;

    // Icons in PrimeTable changing
    // 1 == ASC
    // -1 == DESC
    // null == neutral icons
    if (event.sortField == this.state.orderBy?.field) {
      orderBy = {
        field: event.sortField,
        direction: event.sortOrder === 1 ? 'asc' : 'desc',
      };
    } else {
      orderBy = {
        field: event.sortField,
        direction: 'asc',
      };
    }

    this.onOrderByChange(orderBy);
  }

  onRowSelect(event: DataTableSelectEvent) {
    // to be overriden
  }

  onRowUnselect(event: DataTableUnselectEvent) {
    // to be overriden
  }

  openForm(id: any) {
    let prevId: any = null;
    let nextId: any = null;
    let prevRow: any = {};
    let saveNextId: boolean = false;

    for (let i in this.state.data?.data) {
      const row = this.state.data?.data[i];
      if (row && row.id) {
        if (saveNextId) {
          nextId = row.id;
          saveNextId = false;
        } else if (row.id == id) {
          prevId = prevRow.id ?? null;
          saveNextId = true;
        }
      }
      prevRow = row;
    }

    if (this.props.externalCallbacks && this.props.externalCallbacks.openForm) {
      window[this.props.externalCallbacks.openForm](this, id);
    } else {
      if (!this.props.parentForm) {
        const urlParams = new URLSearchParams(window.location.search);
        const recordTitle = this.findRecordById(id)._LOOKUP ?? null;
        urlParams.set('recordId', id);
        if (recordTitle) urlParams.set('recordTitle', recordTitle);
        window.history.pushState({}, "", '?' + urlParams.toString());
      }

      this.setState({ recordId: null }, () => {
        this.setState({ recordId: id, recordPrevId: prevId, recordNextId: nextId });
      });
    }
  }

  onAddClick() {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onAddClick) {
      window[this.props.externalCallbacks.onAddClick](this);
    } else {
      this.openForm(-1);
    }
  }

  onRowClick(row: any) {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onRowClick) {
      window[this.props.externalCallbacks.onRowClick](this, row.id ?? 0);
    } if (this.props.onRowClick) {
      this.props.onRowClick(this, row);
    } else {
      this.openForm(row.id ?? 0);
    }
  }

  onPaginationChange(page: number, itemsPerPage: number) {
    this.setState({page: page, itemsPerPage: itemsPerPage}, () => {
      this.loadData();
    });
  }

  onFilterChange(data: any) {
    this.setState({
      filterBy: data
    }, () => this.loadData());
  }

  onOrderByChange(orderBy?: TableOrderBy | null, stateParams?: any) {
    this.setState({
      ...stateParams,
      orderBy: orderBy,
    }, () => this.loadData());
  }

  onSearchChange(search: string) {
    this.setState({
      search: search
    }, () => this.loadData());
  }
}
