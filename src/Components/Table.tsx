import React, { Component } from "react";
import { DataGrid, GridColDef, GridValueGetterParams } from '@mui/x-data-grid';
import axios from "axios";
import { ModalPageLarge } from "./Modal";

interface TableProps {
  title?: string,
  model: string
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
  model: string;
  title: string;

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
      sortable: false,
      width: 160,
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

    this.model = props.model;
    this.title = props.title ? props.title : this.model;
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
        model: this.model
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns,
        data: data.data
      });
    });
  }

  add() {
    ModalPageLarge({url: '/sandbox/react/Form'}, this.loadData);
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <small>Loading</small>;
    }

    return (
      <div className="card">
        <div className="card-header">
          <div className="row">
            <div className="col-lg-12">
              <h3 className="card-title">{this.title}</h3>
            </div>
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
