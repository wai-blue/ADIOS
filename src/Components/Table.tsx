import React, { Component, ChangeEvent, createRef } from 'react';

import Modal, { ModalProps } from "./Modal";
import ModalSimple from "./ModalSimple";
import Form, { FormEndpoint, FormProps, FormState, FormColumns } from "./Form";
import Notification from "./Notification";

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

export interface OrderBy {
  field: string,
  direction?: string | null
}

export interface ExternalCallbacks {
  openForm?: string,
  onAddClick?: string,
  onRowClick?: string,
}

export interface TableProps {
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: FormColumns,
  renderForm?: boolean,
  recordId?: any,
  formEndpoint?: FormEndpoint,
  formModal?: ModalProps,
  formUseModalSimple?: boolean,
  formParams?: FormProps,
  endpoint?: TableEndpoint,
  modal?: ModalProps,
  model: string,
  parentRecordId?: any,
  parentForm?: Form<FormProps, FormState>,
  parentFormModel?: string,
  showHeader?: boolean,
  tag?: string,
  title?: string,
  uid: string,
  where?: Array<any>,
  params?: any,
  externalCallbacks?: ExternalCallbacks,
  itemsPerPage: number,
  orderBy?: OrderBy,
  inlineEditingEnabled?: boolean,
  isInlineEditing?: boolean,
  selectionMode?: 'single' | 'multiple' | undefined,
  onChange?: (table: Table) => void,
  onRowClick?: (table: Table, row: any) => void,
  data?: TableData,
  async?: boolean,
  readonly?: boolean,
  closeFormAfterSave?: boolean,

  //TODO
  //showPaging?: boolean,
  //showControls?: boolean,
  //showAddButton?: boolean,
  //showPrintButton?: boolean,
  //showSearchButton?: boolean,
  //showExportCsvButton?: boolean,
  //showImportCsvButton?: boolean,
  //showFulltextSearch?: boolean
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
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: any, //Array<GridColDef>,
  data?: TableData | null,
  filterBy?: any,
  recordId?: any,
  recordPrevId?: any,
  recordNextId?: any,
  formEndpoint?: FormEndpoint,
  formParams?: FormProps,
  orderBy?: OrderBy,
  page: number,
  itemsPerPage: number,
  search?: string,
  showHeader?: boolean,
  title?: string,
  renderForm?: boolean,
  inlineEditingEnabled: boolean,
  isInlineEditing: boolean,
  selection: any,
  idToDelete: number,
  async: boolean,
  readonly: boolean,
}

export default class Table extends Component<TableProps, TableState> {
  static defaultProps = {
    itemsPerPage: 100,
    formUseModalSimple: true,
  }

  state: TableState;

  dt = createRef<DataTable<any[]>>();

  constructor(props: TableProps) {
    super(props);

    globalThis.app.reactElements[this.props.uid] = this;

    this.state = this.getStateFromProps(props);
  }

  getStateFromProps(props: TableProps) {
    return {
      endpoint: props.endpoint ? props.endpoint : (globalThis.app.config.defaultFormEndpoint ?? {
        describeTable: 'api/table/describe',
        getRecords: 'api/record/get-list',
        deleteRecord: 'api/record/delete',
      }),
      canCreate: props.canCreate ?? true,
      canDelete: props.canDelete ?? true,
      canRead: props.canRead ?? true,
      canUpdate: props.canUpdate ?? true,
      recordId: props.recordId,
      formEndpoint: props.formEndpoint ? props.formEndpoint : (globalThis.app.config.defaultFormEndpoint ?? null),
      formParams: {
        model: props.model,
        uid: props.uid,
      },
      renderForm: props.renderForm ?? true,
      page: 1,
      itemsPerPage: this.props.itemsPerPage,
      showHeader: props.showHeader ?? true,
      orderBy: this.props.orderBy,
      inlineEditingEnabled: props.inlineEditingEnabled ? props.inlineEditingEnabled : false,
      isInlineEditing: props.isInlineEditing ? props.isInlineEditing : false,
      selection: [],
      idToDelete: 0,
      data: props.data ? props.data : null,
      columns: props.columns ? props.columns : null,
      async: props.async ?? true,
      readonly: props.readonly ?? false,
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
      (prevProps.formParams?.id != this.props.formParams?.id)
      || (prevProps.parentRecordId != this.props.parentRecordId)
    ) {
      this.state.formParams = this.props.formParams;
      if (this.state.async) {
        this.loadTableDescription();
        this.loadData();
      }
    }

    if (
      prevProps.data != this.props.data
      || prevProps.columns != this.props.columns
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
      __IS_AJAX__: '1',
    }
  }

  getTableProps(): Object {
    const sortOrders = {'asc': 1, 'desc': -1};
    const totalRecords = this.state.data?.total ?? 0;

    return {
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
      rowsPerPageOptions: [5, 15, 30, 50, 100, 200, 300],
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
      }
    };
  }

  loadTableDescription(successCallback?: (params: any) => void) {
    request.get(
      this.getEndpointUrl('describeTable'),
      {
        ...this.getEndpointParams(),
      },
      (data: any) => {
        try {
          if (data.status == 'error') throw new Error('Error while loading table params: ' + data.message);

          let params: any = deepObjectMerge(data, this.props);
          if (params.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);
          if (successCallback) successCallback(params);

          params = this.onAfterLoadTableDescription(params);

          this.setState({
            addButtonText: this.props.addButtonText ?? params.addButtonText,
            canCreate: params.canCreate ?? true,
            canDelete: params.canDelete ?? true,
            canRead: params.canRead ?? true,
            canUpdate: params.canUpdate ?? true,
            columns: this.getColumns(params.columns),
            showHeader: params.showHeader ?? true,
            title: this.props.title ?? params.title,
          });
        } catch (err) {
          Notification.error(err.message);
        }
      }
    );
  }

  loadData() {
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

  getColumns(columnsFromEndpoint: any): any {
    return columnsFromEndpoint;
  }

  getFormParams(): any {
    return {
      parentTable: this,
      uid: this.props.uid + '_form',
      model: this.props.model,
      tag: this.props.tag,
      id: this.state.recordId ?? null,
      prevId: this.state?.recordPrevId ?? 0,
      nextId: this.state?.recordNextId ?? 0,
      endpoint: this.state.formEndpoint ?? '',
      isInlineEditing: (this.state.recordId ?? null) === -1,
      showInModal: true,
      showInModalSimple: this.props.formUseModalSimple,
      columns: this.props.formParams?.columns ?? {},
      titleForInserting: this.props.formParams?.titleForInserting,
      titleForEditing: this.props.formParams?.titleForEditing,
      saveButtonText: this.props.formParams?.saveButtonText,
      addButtonText: this.props.formParams?.addButtonText,
      canCreate: this.state.canCreate,
      canDelete: this.state.canDelete,
      canRead: this.state.canRead,
      canUpdate: this.state.canUpdate,
      onClose: () => {
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
      onDeleteCallback: () => {
        this.loadData();
        this.setState({ recordId: null });
      },
      isInitialized: false,
    }
  }

  getFormModalParams(): any {
    return {
      uid: this.props.uid + '_form',
      type: this.state.recordId == -1 ? 'centered' : 'right',
      // model: this.props.model,
      hideHeader: true,
      isOpen: this.state.recordId !== null,
      ...this.props.modal
    }
  }

  cellClassName(columnName: string, rowData: any) {
    return ''; // rowData.id % 2 === 0 ? '' : 'bg-light';
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
        {this.state.addButtonText ? <span className="text">{this.state.addButtonText}</span> : null}
      </button>
    );
  }

  renderHeaderButtons(): JSX.Element {
    return !this.state.readonly && this.state.canCreate ? this.renderAddButton() : <></>;
  }

  renderHeader(): JSX.Element {
    return <div className="table-header">
      {this.state.title ? <div className="table-header-title">{this.state.title}</div> : null}

      <div className="table-header-left">
        {this.renderHeaderButtons()}
      </div>

      <div className="table-header-right">
        {(this.state.data?.total ?? 0) <= this.state.itemsPerPage ? null :
          <input
            className="table-header-search"
            type="search"
            placeholder="Start typing to search..."
            value={this.state.search}
            onChange={(event: ChangeEvent<HTMLInputElement>) => this.onSearchChange(event.target.value)}
          />
        }
      </div>
    </div>
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
    const recordToDelete = this.findRecordById(this.state.idToDelete);
    request.get(
      this.getEndpointUrl('deleteRecord'),
      {
        model: this.props.model,
        id: recordToDelete.id,
        hash: recordToDelete._idHash_,
      },
      (response: any) => {
        if (response.error) {
          Notification.error(response.error);
        } else {
          this.setState({idToDelete: 0}, () => {
            this.loadData();
          });
        }
      }
    );
  }

  renderDeleteConfirmModal(): JSX.Element {
    if (this.state.idToDelete > 0) {
      const data: any = this.findRecordById(this.state.idToDelete);
      return (
        <ModalSimple
          uid={this.props.uid + '_delete_confirm'}
          isOpen={true} type='centered tiny'
        >
          <div className='modal-header'>
            <div>
              <div>{globalThis.app.translate('Delete record') + ' #' + this.state.idToDelete}</div>
              <small>{data?._lookupText_ ?? ''}</small>
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
                  this.setState({idToDelete: 0});
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
    if (this.state.renderForm && this.state.recordId) {
      if (this.props.formUseModalSimple) {
        return <ModalSimple {...this.getFormModalParams()}>{this.renderForm()}</ModalSimple>;
      } else {
        return <Modal {...this.getFormModalParams()}>{this.renderForm()}</Modal>;
      }
    } else {
      return <></>;
    }
  }

  renderForm(): JSX.Element {
    if (this.state.renderForm) {
      return <Form {...this.getFormParams()} />;
    } else {
      return <></>;
    }
  }

  /*
   * Render body for Column (PrimeReact column)
   */
  renderCell(columnName: string, column: any, data: any, options: any) {
    const columnValue: any = data[columnName];
    const enumValues = column.enumValues;
    const inputProps = {
      uid: this.props.uid + '_' + columnName,
      columnName: columnName,
      params: column,
      value: columnValue,
      showInlineEditingButtons: true,
    };
    const rowIndex = options.rowIndex;

    if (enumValues) return <span style={{fontSize: '10px'}}>{enumValues[columnValue]}</span>;

    let cellValueElement: JSX.Element|null = null;

    if (columnValue === null) {
      cellValueElement = null;
    } else {
      switch (column.type) {
        case 'int':
          cellValueElement = <div className="text-right">
            {columnValue}
            {column.unit ? ' ' + column.unit : ''}
          </div>;
        break;
        case 'float':
          cellValueElement = <div className="text-right">
            {columnValue}
            {column.unit ? ' ' + column.unit : ''}
          </div>;
        break;
        case 'color':
          cellValueElement = <div
            style={{ width: '20px', height: '20px', background: columnValue }}
            className="rounded"
          />;
        break;
        // case 'image':
        //   if (!columnValue) cellValueElement = <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
        //   else {
        //     cellValueElement = <img
        //       style={{ width: '30px', height: '30px' }}
        //       src={this.state.folderUrl + "/" + columnValue}
        //       className="rounded"
        //     />;
        //   }
        break;
        case 'lookup':
          cellValueElement = <span style={{
            color: '#2d4a8a'
          }}>{columnValue?.lookupSqlValue}</span>;
        break;
        case 'enum':
          const enumValues = column.enumValues;
          if (enumValues) cellValueElement = enumValues[columnValue];
        break;
        case 'boolean':
          if (columnValue) cellValueElement = <span className="text-green-600" style={{fontSize: '1.2em'}}>✓</span>
          else cellValueElement = <span className="text-red-600" style={{fontSize: '1.2em'}}>✕</span>
        break;
        case 'date':
          cellValueElement = <span>{dateToEUFormat(columnValue)}</span>;
        break;
        case 'datetime':
          cellValueElement = <span>{datetimeToEUFormat(columnValue)}</span>;
        break;
        case 'tags':
          cellValueElement = <>
            {columnValue.map((item: any) => {
              if (!column.dataKey) return <></>;
              return <span className="badge badge-info mx-1" key={item.id}>{item[column.dataKey]}</span>;
            })}
          </>
        break;
        default:
          cellValueElement = columnValue;
        break;
      }

      if (cellValueElement === <></>) {
        cellValueElement = columnValue;
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

    // let cellEditorElement: JSX.Element = InputFactory({
    //   ...inputProps,
    //   isInlineEditing: true,
    //   onInlineEditCancel: () => { op.current?.hide(); }
    // });

    // return <>
    //   {cellValueElement}
    //   {this.state.inlineEditingEnabled ? <>
    //     <i
    //       className="inline-edit-icon fas fa-pencil-alt text-xs"
    //       onClick={(e) => { e.stopPropagation(); op.current?.toggle(e); }}
    //     ></i>
    //     <OverlayPanel ref={op} onClick={(e) => { e.stopPropagation(); }}>
    //       {cellEditorElement}
    //     </OverlayPanel>
    //   </> : null}
    // </>;
  }

  renderColumns(): JSX.Element[] {
    let columns: JSX.Element[] = [];

    if (this.props.selectionMode) {
      columns.push(<Column selectionMode={this.props.selectionMode}></Column>);
    }

    Object.keys(this.state.columns).map((columnName: string) => {
      const column: any = this.state.columns[columnName];
      columns.push(<Column
        key={columnName}
        field={columnName}
        header={column.title}
        body={(data: any, options: any) => {
          return (
            <div
              className={(column.cssClass ?? '') + (data.id == this.state.idToDelete ? ' bg-red-50' : '') + ' ' + this.cellClassName(columnName, data)}
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
          {!this.state.readonly && this.state.canDelete ? <button
            className="btn btn-list-item btn-danger"
            title={globalThis.app.translate('Delete')}
            onClick={(e) => {
              e.preventDefault();
              this.setState({idToDelete: data.id});
            }}
          >
            <span className="icon"><i className="fas fa-trash-alt"></i></span>
          </button> : null}
        </>;
      }}
      style={{ width: 'auto' }}
    ></Column>);

    return columns;
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    return (
      <>
        {this.renderFormModal()}
        {this.renderDeleteConfirmModal()}

        <div
          id={"adios-table-" + this.props.uid}
          className={"adios component table"}
        >
          {this.state.showHeader ? this.renderHeader() : ''}

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
    let orderBy: OrderBy | null = null;

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
        const recordTitle = this.findRecordById(id)._lookupText_ ?? null;
        urlParams.set('recordId', id);
        if (recordTitle) urlParams.set('recordTitle', recordTitle);
        window.history.pushState({},"", '?' + urlParams.toString());
      }
      
      this.setState({
        recordId: id,
        recordPrevId: prevId,
        recordNextId: nextId,
      })
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

  onOrderByChange(orderBy?: OrderBy | null, stateParams?: any) {
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
