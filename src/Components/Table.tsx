import React, { ChangeEvent, Component, useId } from "react";
import { DataGrid, GridColDef, GridValueGetterParams, skSK, GridSortModel, GridFilterModel } from '@mui/x-data-grid';
import axios from "axios";

import Modal, { ModalProps } from "./Modal";
import Form, { FormProps, FormColumns } from "./Form";
import { dateToEUFormat, timeToEUFormat, datetimeToEUFormat } from "./Inputs/DateTime";

import Loader from "./Loader";
import { adiosError } from "./Helper";

interface TableProps {
  uid: string,
  model: string,
  formModal?: ModalProps,
  title?: string,
  showTitle?: boolean,
  modal?: ModalProps,
  formId?: number,
  formParams?: FormProps
  addButtonText?: string
  columns?: FormColumns
  where?: Array<any>,
  tag?: string,
  loadParamsController?: string,
  loadDataController?: string
  rowHeight: number,

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
  last_page: number,
  last_page_url: string,
  links: Array<any>,
  next_page_url: string|null,
  path: string,
  per_page: number,
  prev_page_url: string|null,
  to: number,
  total: number
}

interface TableState {
  page: number,
  pageLength: number,
  columns?: Array<GridColDef>,
  data?: TableData,
  form?: FormProps,
  orderBy?: GridSortModel,
  filterBy?: GridFilterModel,
  search?: string,
  addButtonText?: string,
  title?: string
}

export default class Table extends Component<TableProps> {
  state: TableState;

  constructor(props: TableProps) {
    super(props);
    this.state = {
      page: 1,
      pageLength: 15,
      form: {
        uid: props.uid,
        model: props.model,
        id: props.formId
      }
    };
  }

  componentDidMount() {
    this.loadParams();
    this.loadData();
  }

  _commonCellRenderer(column, content): JSX.Element {
    return <div className={column.viewParams?.Table?.cssClass}>{content}</div>
  }

  loadParams() {
    let loadParamsController = this.props.loadParamsController ? this.props.loadParamsController : 'Components/Table/OnLoadParams';

    //@ts-ignore
    axios.get(_APP_URL + '/' + loadParamsController, {
      params: {
        model: this.props.model,
        tag: this.props.tag,
        columns: this.props.columns
      }
    }).then(({data}: any) => {
        let columns: Array<any> = [];

        if (data.columns.length == 0) adiosError("Any column to show. Set showColumn param for column");

        for (let columnName in data.columns) {
          let origColumn = data.columns[columnName];
          let newColumn = {
            _adiosColumnDef: origColumn,
            field: columnName,
            headerName: origColumn['title'],
            flex: 1,
            renderCell: (params: any) => {
              let column = params.api.getColumn(params.field);

              switch (column._adiosColumnDef['type']) {
                case 'color': {
                  return this._commonCellRenderer(
                    column._adiosColumnDef,
                    <span 
                      style={{ width: '20px', height: '20px', background: params.value }} 
                      className="rounded" 
                    />
                  );
                }
                case 'image': {
                  if (!params.value) {
                    return this._commonCellRenderer(
                      column._adiosColumnDef,
                      <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
                    );
                  }

                  return this._commonCellRenderer(
                    column._adiosColumnDef,
                    <img 
                      style={{ width: '30px', height: '30px' }}
                      src={data.folderUrl + "/" + params.value}
                      className="rounded"
                    />
                  );
                }
                case 'lookup': { 
                  return this._commonCellRenderer(
                    column._adiosColumnDef,
                    <span style={{
                      color: '#2d4a8a'
                    }}>{params.value?.lookupSqlValue}</span>
                  );
                }
                case 'enum': { 
                  return this._commonCellRenderer(column._adiosColumnDef, column._adiosColumnDef['enumValues'][params.value]);
                }
                case 'bool':
                case 'boolean': { 
                  if (params.value) {
                    return this._commonCellRenderer(
                      column._adiosColumnDef,
                      <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>
                    );
                  } else {
                    return this._commonCellRenderer(
                      origColumn,
                      <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>
                    );
                  }
                }
                case 'date': { 
                  return this._commonCellRenderer(column._adiosColumnDef, dateToEUFormat(params.value));
                }
                case 'time': { 
                  return this._commonCellRenderer(column._adiosColumnDef, timeToEUFormat(params.value));
                }
                case 'datetime': {
                  return this._commonCellRenderer(column._adiosColumnDef, datetimeToEUFormat(params.value));
                }
                case 'tags': {
                  return <div>
                    {params.value.map((value) => {
                      return <span className="badge badge-info mx-1">{value[column._adiosColumnDef.dataKey]}</span>;
                    })}
                  </div>
                }
                default: {
                  return this._commonCellRenderer(column._adiosColumnDef, params.value);
                }
              }
            }
          };

          columns.push(newColumn);
        };

        this.setState({
          columns: columns,
          title: this.props.title ?? data.tableTitle,
          addButtonText: this.props.addButtonText ?? data.addButtonText
        });
      });
  }

  loadData(page: number = 1) {
    let loadDataController = this.props.loadDataController ? this.props.loadDataController : 'Components/Table/OnLoadData';

    this.setState({
      page: page
    });

    //@ts-ignore
    axios.get(_APP_URL + '/' + loadDataController, {
      params: {
        page: page,
        pageLength: this.state.pageLength,
        model: this.props.model,
        orderBy: this.state.orderBy,
        filterBy: this.state.filterBy,
        search: this.state.search,
        where: this.props.where,
        tag: this.props.tag
      }
    }).then(({data}: any) => {
        this.setState({
          data: data.data
        });
      });
  }

  onAddClick() {
    ADIOS.modalToggle(this.props.uid);
    this.setState({
      form: {...this.state.form, id: undefined }
    })
  }

  onRowClick(id: number) {
    ADIOS.modalToggle(this.props.uid);
    this.setState({
      form: {...this.state.form, id: id}
    })
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
    if (!this.state.data || !this.state.columns) {
      return <Loader />;
    }

    return (
      <>
        <Modal 
          uid={this.props.uid}
          {...this.props.modal}
          hideHeader={true}
          isOpen={this.props.formId ? true : false}
        >
          <Form 
            uid={this.props.uid}
            model={this.props.model}
            id={this.state.form?.id}
            showInModal={true}
            onSaveCallback={() => {
              this.loadData();
              ADIOS.modalToggle(this.props.uid);
            }}
            onDeleteCallback={() => {
              this.loadData();
              ADIOS.modalToggle(this.props.uid);
            }}
            {...this.props.formParams}
            columns={this.props.columns}
          />
        </Modal>

        <div
          id={"adios-table-" + this.props.uid}
          className="adios react ui table"
        >
          <div className="card border-0">
            <div className="card-header mb-2">
              <div className="row m-0">

                <div className="col-lg-12 p-0 m-0">
                  <h3 className="card-title m-0">{this.state.title}</h3>
                </div>

                <div className="col-lg-6 m-0 p-0">
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
              //pageSizeOptions={[5, 10]}
              //checkboxSelection
            />
          </div>
        </div>
      </>
    );
  }
}
