import React, { ChangeEvent, Component, useId } from "react";
import { DataGrid, GridColDef, GridValueGetterParams, skSK, GridSortModel, GridFilterModel } from '@mui/x-data-grid';
import axios from "axios";

import { FormProps } from "./Form";

import Modal, { ModalProps } from "./Modal";
import Form from "./Form";

import Loader from "./Loader";

interface TableProps {
  // Required
  uid: string,
  model: string,

  // Additional
  formModal?: ModalProps,

  title?: string,
  showTitle?: boolean,
  showPaging?: boolean,
  showControls?: boolean,
  showAddButton?: boolean,
  showPrintButton?: boolean,
  showSearchButton?: boolean,
  showExportCsvButton?: boolean,
  showImportCsvButton?: boolean,
  showFulltextSearch?: boolean
}

interface TableParams {
  uid: string,
  modal: ModalProps,
  model: string,
  title: string,
  showTitle: boolean,
  showPaging: boolean,
  showControls: boolean,
  showAddButton: boolean,
  showPrintButton: boolean,
  showSearchButton: boolean,
  showExportCsvButton: boolean,
  showImportCsvButton: boolean,
  showFulltextSearch: boolean,
  showCardOverlay: boolean
}

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
  title: string,
  page: number,
  pageLength: number,
  columns?: Array<GridColDef>,
  data?: TableData,
  form?: FormProps,
  orderBy?: GridSortModel,
  filterBy?: GridFilterModel,
  search?: string
}

export default class Table extends Component {
  state: TableState;

  params: TableParams = {
    // Params for Modal with Form component
    modal: {},

    uid: this.props.uid,
    model: this.props.model,
    title: "",
    showTitle:  true,
    showPaging: true,
    showControls: true,
    showAddButton: true,
    showPrintButton: true,
    showSearchButton: true,
    showExportCsvButton: true,
    showImportCsvButton: false,
    showFulltextSearch: true,
    showCardOverlay: true
  };

  //_testColumns: GridColDef[] = [
  //  { field: 'id', headerName: 'ID', width: 70 },
  //  { field: 'firstName', headerName: 'First name', width: 130 },
  //  { field: 'lastName', headerName: 'Last name', width: 130 },
  //  {
  //    field: 'age',
  //    headerName: 'Age',
  //    type: 'number',
  //    width: 90,
  //  },
  //  {
  //    field: 'fullName',
  //    headerName: 'Full name',
  //    description: 'This column has a value getter and is not sortable.',
  //    sortable: false, width: 160,
  //    valueGetter: (params: GridValueGetterParams) => `${params.row.firstName || ''} ${params.row.lastName || ''}`,
  //  },
  //];

  //_testData = [
  //  { id: 1, lastName: 'Snow', firstName: 'Jon', age: 35 },
  //  { id: 2, lastName: 'Lannister', firstName: 'Cersei', age: 42 },
  //  { id: 3, lastName: 'Lannister', firstName: 'Jaime', age: 45 },
  //  { id: 4, lastName: 'Stark', firstName: 'Arya', age: 16 },
  //  { id: 5, lastName: 'Targaryen', firstName: 'Daenerys', age: null },
  //  { id: 6, lastName: 'Melisandre', firstName: null, age: 150 },
  //  { id: 7, lastName: 'Clifford', firstName: 'Ferrara', age: 44 },
  //  { id: 8, lastName: 'Frances', firstName: 'Rossini', age: 36 },
  //  { id: 9, lastName: 'Roxie', firstName: 'Harvey', age: 65 },
  //];
  //
  constructor(props: TableProps) {
    super(props);

    this.params = {...this.params, ...this.props};
    this.params.title = props.title ? props.title : this.params.model;

    this.state = {
      title: this.params.title,
      columns: undefined,
      data: undefined,
      page: 1,
      pageLength: 15,
      form: {
        uid: props.uid,
        model: this.params.model,
        id: undefined
      }

      //columns: this._testColumns,
      //data: this._testData
    };
  }

  componentDidMount() {
    this.loadData();
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
        model: this.params.model,
        orderBy: this.state.orderBy,
        filterBy: this.state.filterBy,
        search: this.state.search
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns,
        data: data.data,
        title: data.title
      });
    });
  }

  onAddClick() {
    //@ts-ignore
    $('#adios-modal-' + this.params.uid).modal('toggle');

    this.setState({
      form: {...this.state.form, id: undefined }
    })
  }

  onRowClick(id: number) {
    //@ts-ignore
    $('#adios-modal-' + this.params.uid).modal('toggle');

    console.log(id);

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
          uid={this.params.uid}
          {...this.params.modal}
        >
          <Form 
            uid={this.params.uid}
            model={this.params.model}
            id={this.state.form?.id}
            title={this.state.title}
            refreshCallback={() => this.loadData()}
          />
        </Modal>

        <div
          id={"adios-table-" + this.params.uid}
          className="adios react ui table"
        >
          <div className="card">
            <div className="card-header">
              <div className="row m-0">

                {this.params.showTitle ? (
                  <div className="col-lg-12 p-0 m-0">
                    <h3 className="card-title m-0">{this.state.title}</h3>
                  </div>
                ) : ''}

                <div className="col-lg-6 m-0 p-0">
                  <button
                    className="btn btn-primary"
                    onClick={() => this.onAddClick()} 
                  ><i className="fas fa-plus"/> Pridať záznam</button>
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
           
            <div style={{width: '100%'}}>
              <DataGrid
                localeText={skSK.components.MuiDataGrid.defaultProps.localeText}
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
                //loading={false}
                //pageSizeOptions={[5, 10]}
                //checkboxSelection
              />
            </div>
          </div>
        </div>
      </>
    );
  }
}
