import React, { ChangeEvent, Component, useId } from "react";
import { DataGrid, GridColDef, GridValueGetterParams, skSK, GridSortModel, GridFilterModel } from '@mui/x-data-grid';
import axios from "axios";

import { FormProps } from "./Form";

import Modal, { ModalProps } from "./Modal";
import Form from "./Form";
import { dateToEUFormat } from "./Inputs/DateTime";

import Loader from "./Loader";

interface TableProps {
  uid: string,
  model: string,
  formModal?: ModalProps,
  title?: string,
  showTitle?: boolean,
  modal?: ModalProps,

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
  tableTitle?: string
}

export default class Table extends Component<TableProps> {
  state: TableState;

  constructor(props: TableProps) {
    super(props);

    this.state = {
      tableTitle:  props.title ?? props.model,
      page: 1,
      pageLength: 15,
      form: {
        uid: props.uid,
        model: props.model
      }
    };
  }

  componentDidMount() {
    this.loadParams();
    this.loadData();
  }

  loadParams() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Table/OnLoadParams', {
      params: {
        model: this.props.model
      }
    }).then(({data}: any) => {
      let columns: Array<any> = [];

      columns = data.columns.map((column: any) => {
        switch (column['columnType']) {
          case 'color': return { 
            ...column, 
            renderCell: (params: any) => {
              return <span 
                style={{ width: '20px', height: '20px', background: params.value }} 
                className="rounded" 
              />
            }
          }
          case 'image': return { 
            ...column, 
            renderCell: (params: any) => {
              if (!params.value) return <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>

              return <img 
                style={{ width: '30px', height: '30px' }}
                src={data.folderUrl + "/" + params.value}
                className="rounded"
              />
            }
          }
          case 'lookup': return { 
            ...column, 
            renderCell: (params: any) => {
              return <span style={{
                color: '#2d4a8a'
              }}>{params.value?.lookupSqlValue}</span>
            }
          }
          case 'enum': return { 
            ...column, 
            renderCell: (params: any) => {
              return column['enumValues'][params.value];
            }
          }
          case 'bool':
          case 'boolean': return { 
            ...column, 
            renderCell: (params: any) => {
                if (params.value) return <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>;
                else return <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>;
            }
          }
          case 'date':
          case 'datetime':
          case 'datetime': return { 
            ...column, 
            renderCell: (params: any) => {
              return dateToEUFormat(params.value);
            }
          }
          default: return column;
        }
      });

      this.setState({
        columns: columns,
        tableTitle: data.tableTitle,
        addButtonText: data.addButtonText
      });
    });
  }

  loadData(page: number = 1) {
    this.setState({
      page: page
    });

    //@ts-ignore
    axios.get(_APP_URL + '/Components/Table/OnLoadData', {
      params: {
        page: page,
        pageLength: this.state.pageLength,
        model: this.props.model,
        orderBy: this.state.orderBy,
        filterBy: this.state.filterBy,
        search: this.state.search
      }
    }).then(({data}: any) => {
      this.setState({
        data: data.data
      });
    });
  }

  toggleModal() {
    //@ts-ignore
    $('#adios-modal-' + this.props.uid).modal('toggle');
  }

  onAddClick() {
    this.toggleModal();
    this.setState({
      form: {...this.state.form, id: undefined }
    })
  }

  onRowClick(id: number) {
    this.toggleModal();
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
        >
          <Form 
            uid={this.props.uid}
            model={this.props.model}
            id={this.state.form?.id}
            title={this.state.tableTitle}
            showInModal={true}
            onSaveCallback={() => {
              this.loadData();
              this.toggleModal();
            }}
            onDeleteCallback={() => {
              this.loadData();
              this.toggleModal();
            }}
          />
        </Modal>

        <div
          id={"adios-table-" + this.props.uid}
          className="adios react ui table"
        >
          <div className="card">
            <div className="card-header">
              <div className="row m-0">

                <div className="col-lg-12 p-0 m-0">
                  <h3 className="card-title m-0">{this.state.tableTitle}</h3>
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
                      placeholder="Hľadať"
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
              disableColumnFilter
              disableColumnSelector
              disableDensitySelector
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
