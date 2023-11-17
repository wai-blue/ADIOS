import React, { Component } from "react";
import { DataGrid, GridColDef, GridValueGetterParams } from '@mui/x-data-grid';
import axios from "axios";
import { ModalPageLarge } from "./Modal";

interface TableProps {
  // Required
  model: string,
  
  // Additional
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

interface TableColumns {
  [key: string]: string;
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
  page: number,
  pageLength: number,
  columns?: Array<GridColDef>,
  data?: TableData
}

export default class Table extends Component {
  state: TableState;
  params: TableParams = {
    model: "" ,
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

  _testColumns: GridColDef[] = [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'firstName', headerName: 'First name', width: 130 },
    { field: 'lastName', headerName: 'Last name', width: 130 },
    {
      field: 'age',
      headerName: 'Age',
      type: 'number',
      width: 90,
    },
    {
      field: 'fullName',
      headerName: 'Full name',
      description: 'This column has a value getter and is not sortable.',
      sortable: false, width: 160,
      valueGetter: (params: GridValueGetterParams) => `${params.row.firstName || ''} ${params.row.lastName || ''}`,
    },
  ];

  _testData = [
    { id: 1, lastName: 'Snow', firstName: 'Jon', age: 35 },
    { id: 2, lastName: 'Lannister', firstName: 'Cersei', age: 42 },
    { id: 3, lastName: 'Lannister', firstName: 'Jaime', age: 45 },
    { id: 4, lastName: 'Stark', firstName: 'Arya', age: 16 },
    { id: 5, lastName: 'Targaryen', firstName: 'Daenerys', age: null },
    { id: 6, lastName: 'Melisandre', firstName: null, age: 150 },
    { id: 7, lastName: 'Clifford', firstName: 'Ferrara', age: 44 },
    { id: 8, lastName: 'Frances', firstName: 'Rossini', age: 36 },
    { id: 9, lastName: 'Roxie', firstName: 'Harvey', age: 65 },
  ];

  constructor(props: TableProps) {
    super(props);

    this.state = {
      columns: undefined,
      data: undefined,
      page: 1,
      pageLength: 15,

      //columns: this._testColumns,
      //data: this._testData
    };

    this.params = {...this.params, ...this.props};
    this.params.title = props.title ? props.title : this.params.model;
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
        model: this.params.model
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns,
        data: data.data
      });
    });
  }

  add() {
    ModalPageLarge({url: '/sandbox/react/FormAdd'}, () => this.loadData);
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <small>Loading</small>;
    }

    return (
      <div className="card">
        <div className="card-header">
          <div className="row">

            {this.params.showTitle ? (
              <div className="col-lg-12">
                <h3 className="card-title">{this.params.title}</h3>
              </div>
            ) : ''}

            <div className="col-lg-12">
              <button
                className="btn btn-primary"
                onClick={() => this.add()} 
              >Add</button>
            </div>
          </div>
        </div>
        
        <DataGrid
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
          rowCount={this.state.data.total} 
          //loading={false}
          //pageSizeOptions={[5, 10]}
          //checkboxSelection
        />
      </div>
    );
  }
}
