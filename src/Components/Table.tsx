import React, { ChangeEvent, Component, useId } from "react";
import { DataGrid, GridColDef, GridValueGetterParams, skSK, GridSortModel, GridFilterModel } from '@mui/x-data-grid';
import { ProgressBar } from 'primereact/progressbar';

import Modal, { ModalProps } from "./Modal";
import Form, { FormProps, FormColumns } from "./Form";
import { dateToEUFormat, datetimeToEUFormat } from "./Inputs/DateTime";

import { adiosError, deepObjectMerge } from "./Helper";
import request from "./Request";

interface TableProps {
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: FormColumns,
  formId?: number,
  formEndpoint?: string,
  formModal?: ModalProps,
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

interface TableState {
  endpoint: string,
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: Array<GridColDef>,
  data?: TableData,
  filterBy?: GridFilterModel,
  formId?: number,
  formEndpoint?: string,
  formParams?: FormProps,
  orderBy?: GridSortModel,
  page: number,
  pageLength: number,
  search?: string,
  showHeader?: boolean,
  title?: string,
}

export default class Table extends Component<TableProps> {
  state: TableState;

  constructor(props: TableProps) {
    super(props);

    this.state = {
      endpoint: props.endpoint ? props.endpoint : 'components/table',
      canCreate: props.canCreate ?? true,
      canDelete: props.canDelete ?? true,
      canRead: props.canRead ?? true,
      canUpdate: props.canUpdate ?? true,
      formId: props.formId ? props.formId : 0,
      formEndpoint: props.formEndpoint ? props.formEndpoint : 'components/form',
      formParams: {
        model: props.model,
        uid: props.uid,
      },
      page: 1,
      pageLength: 15,
      showHeader: props.showHeader ?? true,
    };
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

  _commonCellRenderer(column, content): JSX.Element {
    return <div className={column.cssClass}>{content}</div>
  }

  loadParams() {
    let propsColumns = this.props.columns ?? {};

    request.get(
      this.state.endpoint,
      {
        returnParams: '1',
        model: this.props.model,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        tag: this.props.tag,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        let params: any = deepObjectMerge(data.params, this.props);
        let columns: Array<any> = [];
console.log('table params', data.params.title, this.props.title, params.title);
        if (params.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);

        for (let columnName in params.columns) {
          let adiosColumnDef = {...params.columns[columnName], ...(propsColumns[columnName] ?? {})};
          let newColumn = {
            adiosColumnDef: adiosColumnDef,
            field: columnName,
            headerName: adiosColumnDef['title'],
            flex: 1,
            renderCell: (params: any) => {
              let column = params.api.getColumn(params.field);

              switch (column.adiosColumnDef['type']) {
                case 'color': {
                  return this._commonCellRenderer(
                    column.adiosColumnDef,
                    <div 
                      style={{ width: '20px', height: '20px', background: params.value }} 
                      className="rounded" 
                    />
                  );
                }
                case 'image': {
                  if (!params.value) {
                    return this._commonCellRenderer(
                      column.adiosColumnDef,
                      <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
                    );
                  }

                  return this._commonCellRenderer(
                    column.adiosColumnDef,
                    <img 
                      style={{ width: '30px', height: '30px' }}
                      src={params.folderUrl + "/" + params.value}
                      className="rounded"
                    />
                  );
                }
                case 'lookup': { 
                  return this._commonCellRenderer(
                    column.adiosColumnDef,
                    <span style={{
                      color: '#2d4a8a'
                    }}>{params.value?.lookupSqlValue}</span>
                  );
                }
                case 'enum': { 
                  return this._commonCellRenderer(column.adiosColumnDef, column.adiosColumnDef['enumValues'][params.value]);
                }
                case 'bool':
                case 'boolean': { 
                  if (params.value) {
                    return this._commonCellRenderer(
                      column.adiosColumnDef,
                      <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>
                    );
                  } else {
                    return this._commonCellRenderer(
                      column.adiosColumnDef,
                      <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>
                    );
                  }
                }
                case 'date': { 
                  return this._commonCellRenderer(column.adiosColumnDef, dateToEUFormat(params.value));
                }
                case 'time': { 
                  return this._commonCellRenderer(column.adiosColumnDef, params.value);
                }
                case 'datetime': {
                  return this._commonCellRenderer(column.adiosColumnDef, datetimeToEUFormat(params.value));
                }
                case 'tags': {
                  let key = 0;
                  return <div>
                    {params.value.map((value) => {
                      return <span className="badge badge-info mx-1" key={key++}>{value[column.adiosColumnDef.dataKey]}</span>;
                    })}
                  </div>
                }
                default: {
                  return this._commonCellRenderer(column.adiosColumnDef, params.value);
                }
              }
            }
          };

          columns.push(newColumn);
        };

        this.setState({
          addButtonText: this.props.addButtonText ?? params.addButtonText,
          canCreate: params.canCreate ?? true,
          canDelete: params.canDelete ?? true,
          canRead: params.canRead ?? true,
          canUpdate: params.canUpdate ?? true,
          columns: columns,
          showHeader: params.showHeader ?? true,
          title: this.props.title ?? params.title,
        });
      }
    );
  }

  loadData(page: number = 1) {
    this.setState({
      page: page
    });

    request.get(
      this.state.endpoint,
      {
        returnData: '1',
        filterBy: this.state.filterBy,
        model: this.props.model,
        orderBy: this.state.orderBy,
        page: page,
        pageLength: this.state.pageLength,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        search: this.state.search,
        tag: this.props.tag,
        where: this.props.where,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        this.setState({
          data: data.data
        });
      }
    );
  }

  openForm(id: number) {
    this.setState(
      { formId: id },
      () => {
        //@ts-ignore
        ADIOS.modalToggle(this.props.uid);
      }
    )
  }

  onAddClick() {
    this.openForm(0);
  }

  onRowClick(id: number) {
    this.openForm(id);
  }

  onFilterChange(data: GridFilterModel) {
    this.setState({
      filterBy: data
    }, () => this.loadData());
  }

  onOrderByChange(data: GridSortModel) {
    this.setState({
      orderBy: data[0]
    }, () => this.loadData());
  }

  onSearchChange(search: string) {
    this.setState({
      search: search
    }, () => this.loadData());
  }

  render() {
    // console.log('table render', this.props.model, this.state.formParams?.model);

    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '30px' }}></ProgressBar>;
    }

    let params = {...this.props.formParams};
    params.defaultValues = params.defaultValues ?? {};
    params.columns = {...params.columns};

    if (this.props.parentFormId != undefined) {
      const lastSlashIndex = this.props.parentFormModel?.lastIndexOf("/") ?? 0;
      const modelString = this.props.parentFormModel?.substring(lastSlashIndex + 1) ?? '';
      const targetColumn = 'id_' + modelString.toLowerCase(); /* TODO: Nemusi vzdy fungovat? Treba asi lepsie vyriesit... */

      params.defaultValues[targetColumn] = this.props.parentFormId;
      params.columns[targetColumn] = params.columns[targetColumn] ?? {};
      params.columns[targetColumn].readonly = true;
    }

    return (
      <>
        <Modal 
          uid={this.props.uid}
          model={this.props.model}
          {...this.props.modal}
          hideHeader={true}
          isOpen={this.props.formParams?.id ? true : false}
        >
          <Form
            uid={this.props.uid}
            model={this.props.model}
            tag={this.props.tag}
            id={this.state.formId ?? 0}
            endpoint={this.state.formEndpoint ?? ''}
            showInModal={true}
            onSaveCallback={() => {
              this.loadData();
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid);
            }}
            onDeleteCallback={() => {
              this.loadData();
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid);
            }}
            {...params}
          />
        </Modal>

        <div
          id={"adios-table-" + this.props.uid}
          className="adios-react-ui table"
        >
          <div className="card border-0">
            {this.state.showHeader ?
              <div className="card-header mb-2">
                <div className="row m-0">

                  <div className="col-lg-12 p-0 m-0">
                    <h3 className="card-title m-0">{this.state.title}</h3>
                  </div>

                  <div className="col-lg-6 m-0 p-0">
                    {this.state.canCreate ?
                      <button
                        className="btn btn-primary btn-icon-split"
                        onClick={() => this.onAddClick()}
                      >
                        <span className="icon">
                          <i className="fas fa-plus"/>
                        </span>
                        <span className="text">
                          {this.state.addButtonText}
                        </span>
                      </button>
                    : ""}
                  </div>

                  <div className="col-lg-6 m-0 p-0">
                    <div className="d-flex flex-row-reverse">
                      <div className="dropdown no-arrow">
                        <button 
                          className="btn btn-light dropdown-toggle" 
                          type="button"
                          data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v"/>
                        </button>
                        <div className="dropdown-menu">
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-file-export mr-2"/> Exportovať do CSV
                          </button>
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-print mr-2"/> Tlačiť
                          </button>
                        </div>
                      </div>

                      <input 
                        className="mr-2 form-control border-end-0 border rounded-pill"
                        style={{maxWidth: '250px'}}
                        type="search"
                        placeholder="Start typing to search..."
                        value={this.state.search}
                        onChange={(event: ChangeEvent<HTMLInputElement>) => this.onSearchChange(event.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
            : ''}
           
            <DataGrid
              localeText={skSK.components.MuiDataGrid.defaultProps.localeText}
              autoHeight={true}
              rows={this.state.data.data}
              columns={this.state.columns}
              initialState={{
                pagination: {
                  paginationModel: {
                    page: (this.state.page - 1), 
                    pageSize: this.state.pageLength
                  },
                },
              }}
              paginationMode="server"
              onPaginationModelChange={(pagination) => this.loadData(pagination.page + 1)}
              sortingMode="server"
              onSortModelChange={(data: GridSortModel) => this.onOrderByChange(data)}
              filterMode="server"
              onFilterModelChange={(data: GridFilterModel) => this.onFilterChange(data)}
              rowCount={this.state.data.total}
              onRowClick={(item) => this.onRowClick(item.id as number)}
              rowHeight={this.props.rowHeight ? this.props.rowHeight : 30}

              // stripped rows
              getRowClassName={ (params) => params.indexRelativeToCurrentPage % 2 === 0 ? '' : 'bg-light' }


              // disableColumnFilter
              // disableColumnSelector
              // disableDensitySelector
              sx={{
                '.MuiDataGrid-cell:focus': {
                  outline: 'none'
                },
                '& .MuiDataGrid-row:hover': {
                  cursor: 'pointer'
                }
              }}
              //loading={false}
              pageSizeOptions={[5, 10, 15, 30, 50, 100]}
              //checkboxSelection
            />
          </div>
        </div>
      </>
    );
  }
}
