import React, { Component, ChangeEvent, createRef } from "react";

import Modal, { ModalProps } from "./Modal";
import ModalSimple from "./ModalSimple";
import Form, { FormProps, FormColumns } from "./Form";
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
import { InputProps } from "./Input";
import { InputFactory } from "./InputFactory";
import { dateToEUFormat, datetimeToEUFormat } from "./Inputs/DateTime";


import { adiosError, deepObjectMerge } from "./Helper";
import request from "./Request";
import { setDefaultHighWaterMark } from "stream";

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
  formId?: number|null,
  formEndpoint?: string,
  formModal?: ModalProps,
  formUseModalSimple?: boolean,
  formParams?: FormProps,
  endpoint?: string
  modal?: ModalProps,
  model: string,
  parentFormId?: number,
  parentFormModel?: string,
  rowHeight: number,
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
  selectionMode?: 'single' | 'multiple' | undefined,

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
  current_page: number,
  data: Array<any>,
  first_page_url: string,
  from: number,
  last_page_url: string,
  last_page: number,
  links: Array<any>,
  next_page_url: string|null,
  path: string,
  per_page: number,
  prev_page_url: string|null,
  to: number,
  total: number
}

export interface TableState {
  endpoint: string,
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: any, //Array<GridColDef>,
  data?: TableData,
  filterBy?: any,
  formId?: number|null,
  formPrevId?: number|null,
  formNextId?: number|null,
  formEndpoint?: string,
  formParams?: FormProps,
  orderBy?: OrderBy,
  page: number,
  itemsPerPage: number,
  search?: string,
  showHeader?: boolean,
  title?: string,
  folderUrl?: string,
  loadingInProgress: boolean,
  renderForm?: boolean,
  inlineEditingEnabled: boolean,
  selection: any,
}

export default class Table<P, S extends TableState = TableState> extends Component<TableProps, TableState> {
  static defaultProps = {
    itemsPerPage: 100,
    formUseModalSimple: true,
  }

  state: S;

  dt = createRef<DataTable<any[]>>();

  constructor(props: TableProps) {
    super(props);

    globalThis.app.reactElements[this.props.uid] = this;

    this.state = {
      endpoint: props.endpoint ? props.endpoint : (globalThis.app.config.defaultTableEndpoint ?? 'components/table'),
      canCreate: props.canCreate ?? true,
      canDelete: props.canDelete ?? true,
      canRead: props.canRead ?? true,
      canUpdate: props.canUpdate ?? true,
      formId: props.formId,
      formEndpoint: props.formEndpoint ? props.formEndpoint : (globalThis.app.config.defaultFormEndpoint ?? 'components/form'),
      formParams: {
        model: props.model,
        uid: props.uid,
      },
      renderForm: props.renderForm ?? true,
      page: 1,
      itemsPerPage: this.props.itemsPerPage,
      showHeader: props.showHeader ?? true,
      loadingInProgress: false,
      orderBy: this.props.orderBy,
      inlineEditingEnabled: props.inlineEditingEnabled ? props.inlineEditingEnabled : false,
      selection: [],
    } as S;
  }

  componentDidMount() {
    this.loadParams();
    this.loadData();
  }

  componentDidUpdate(prevProps: TableProps, prevState: TableState) {

    if (
      (prevProps.formParams?.id != this.props.formParams?.id)
      || (prevProps.parentFormId != this.props.parentFormId)
    ) {
      this.state.formParams = this.props.formParams;
      this.loadParams();
      this.loadData();
    }
  }

  onAfterLoadParams(params: any): any {
    return params;
  }

  getEndpointUrl(): string {
    return this.state.endpoint;
  }

  getEndpointParams(): any {
    return {
      model: this.props.model,
      parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
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
      onRowClick: (data: DataTableRowClickEvent) => this.onRowClick(data.data.id as number),
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

  loadParams(successCallback?: (params: any) => void) {
    let propsColumns = this.props.columns ?? {};

    request.get(
      this.getEndpointUrl(),
      {
        ...this.getEndpointParams(),
        action: 'getParams',
      },
      (data: any) => {
        try {
          if (data.status == 'error') throw new Error('Error while loading table params: ' + data.message);
        
          let params: any = deepObjectMerge(data, this.props);
          if (params.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);
          if (successCallback) successCallback(params);

          params = this.onAfterLoadParams(params);

          this.setState({
            addButtonText: this.props.addButtonText ?? params.addButtonText,
            canCreate: params.canCreate ?? true,
            canDelete: params.canDelete ?? true,
            canRead: params.canRead ?? true,
            canUpdate: params.canUpdate ?? true,
            columns: params.columns,
            showHeader: params.showHeader ?? true,
            title: this.props.title ?? params.title,
            folderUrl: params.folderUrl,
          });
        } catch (err) {
          Notification.error(err.message);
        }
      }
    );
  }

  loadData(page: number = 1, itemsPerPage = 100) {
    this.setState({loadingInProgress: true});
    request.get(
      this.getEndpointUrl(),
      {
        ...this.getEndpointParams(),
        action: 'loadData',
        filterBy: this.state.filterBy,
        model: this.props.model,
        orderBy: this.state.orderBy,
        page: page,
        itemsPerPage: itemsPerPage,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        search: this.state.search,
        tag: this.props.tag,
        where: this.props.where,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        this.setState({
          loadingInProgress: false,
          data: data,
          page: page,
          itemsPerPage: itemsPerPage
        });
      }
    );
  }

  getFormParams(): any {
    return {
      parentTable: this,
      uid: this.props.uid + '_form',
      model: this.props.model,
      tag: this.props.tag,
      id: this.state.formId ?? 0,
      prevId: this.state?.formPrevId ?? 0,
      nextId: this.state?.formNextId ?? 0,
      endpoint: this.state.formEndpoint ?? '',
      isInlineEditing: (this.state.formId ?? 0) == -1,
      showInModal: true,
      showInModalSimple: this.props.formUseModalSimple,
      columns: this.props.formParams?.columns ?? {},
      titleForInserting: this.props.formParams?.titleForInserting,
      titleForEditing: this.props.formParams?.titleForEditing,
      saveButtonText: this.props.formParams?.saveButtonText,
      addButtonText: this.props.formParams?.addButtonText,
      onClose: () => {
        this.setState({ formId: null });
      },
      onSaveCallback: (inlineEditing: boolean) => {
        this.loadData();
        if (!inlineEditing) {
          this.setState({ formId: null });
        }
        // //@ts-ignore
        // ADIOS.modalToggle(this.props.uid);
      },
      onDeleteCallback: () => {
        this.loadData();
        this.setState({ formId: null });
        // //@ts-ignore
        // ADIOS.modalToggle(this.props.uid);
      },
      isInitialized: false,
    }
  }

  getFormModalParams(): any {
    return {
      uid: this.props.uid + '_form',
      // model: this.props.model,
      hideHeader: true,
      isOpen: Number.isInteger(this.state.formId),
      ...this.props.modal
    }
  }

  cellClassName(columnName: string, rowData: any) {
    return ''; // rowData.id % 2 === 0 ? '' : 'bg-light';
  }

  rowClassName(rowData: any): string {
    return rowData.id === this.state.formId ? 'highlighted' : '';
  }

  renderAddButton(): JSX.Element {
    return (
      <button
        className="btn btn-primary"
        onClick={() => this.onAddClick()}
      >
        <span className="icon"><i className="fas fa-plus"/></span>
        <span className="text">{this.state.addButtonText}</span>
      </button>
    );
  }

  renderHeaderButtons(): JSX.Element {
    return this.state.canCreate ? this.renderAddButton() : <></>;
  }

  renderHeader(): JSX.Element {
    return <div className="table-header">
      <div className="table-header-left">
        {this.renderHeaderButtons()}
      </div>

      {this.state.title ? <div className="table-header-title">{this.state.title}</div> : null}

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

  renderFormModal(): JSX.Element {
    if (this.state.renderForm && Number.isInteger(this.state.formId)) {
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
        case 'image':
          if (!columnValue) cellValueElement = <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
          else {
            cellValueElement = <img 
              style={{ width: '30px', height: '30px' }}
              src={this.state.folderUrl + "/" + columnValue}
              className="rounded"
            />;
          }
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
        case 'bool':
        case 'boolean':
          if (columnValue) cellValueElement = <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>
          else cellValueElement = <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>
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

    let cellEditorElement: JSX.Element = InputFactory({
      ...inputProps,
      isInlineEditing: true,
      onInlineEditCancel: () => { op.current?.hide(); }
    });

    return <>
      {cellValueElement}
      {this.state.inlineEditingEnabled ? <>
        <i
          className="inline-edit-icon fas fa-pencil-alt text-xs"
          onClick={(e) => { e.stopPropagation(); op.current?.toggle(e); }}
        ></i>
        <OverlayPanel ref={op} onClick={(e) => { e.stopPropagation(); }}>
          {cellEditorElement}
        </OverlayPanel>
      </> : null}
    </>;
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
            <div className={(column.cssClass ?? '') + ' ' + this.cellClassName(columnName, data)} style={column.cssStyle}>
              {this.renderCell(columnName, column, data, options)}
            </div>
          );
        }}
        style={{ width: 'auto' }}
        sortable
      ></Column>);
    });

    // columns.push(<Column
    //   key='__actions'
    //   field='__actions'
    //   header=''
    //   body={(data: any, options: any) => {
    //     return (
    //       <button className="btn btn-small btn-transparent">
    //         <span className="icon"><i className="fas fa-ellipsis-h"></i></span>
    //       </button>
    //     );
    //   }}
    //   style={{ width: 'auto' }}
    // ></Column>);

    return columns;
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    return (
      <>
        {this.renderFormModal()}

        <div
          id={"adios-table-" + this.props.uid}
          className={"adios component table " + (this.state.loadingInProgress ? "loading" : "")}
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

  openForm(id: number) {
    let prevId: number = 0;
    let nextId: number = 0;
    let prevRow: any = {};
    let saveNextId: boolean = false;

    for (let i in this.state.data?.data) {
      const row = this.state.data?.data[i];
      if (row && row.id) {
        if (saveNextId) {
          nextId = row.id;
          saveNextId = false;
        } else if (row.id == id) {
          prevId = prevRow.id ?? 0;
          saveNextId = true;
        }
      }
      prevRow = row;
    }

    if (this.props.externalCallbacks && this.props.externalCallbacks.openForm) {
      window[this.props.externalCallbacks.openForm](this, id);
    } else {
      this.setState({
        formId: id,
        formPrevId: prevId,
        formNextId: nextId,
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

  onRowClick(id: number, prevId?: number, nextId?: number) {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onRowClick) {
      window[this.props.externalCallbacks.onRowClick](this, id);
    } else {
      this.openForm(id);
    }
  }

  onPaginationChange(page: number, itemsPerPage: number) {
    this.loadData(page, itemsPerPage);
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
