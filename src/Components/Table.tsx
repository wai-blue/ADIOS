import React, { Component } from "react";
import { DataGrid, GridColDef, GridValueGetterParams } from '@mui/x-data-grid';
import axios from "axios";

interface TableProps {
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
  model: string,
  page: number,
  columns: TableColumns,
  data?: TableData
}

export default class Table extends Component {
  state: TableState;

  columns: GridColDef[] = [
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

  rows = [
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
      model: props.model,
      columns: {},
      data: undefined,
      page: 0
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Table/OnLoadData', {
      params: {
        page: this.state.page,
        model: this.state.model
      }
    }).then(({data}: any) => {
      this.setState({
        columns: data.columns,
        data: data.data
      });
    });
  }

  render() {
    if (!this.state.data) {
      return <small>Loading</small>;
    }

    return (
      <div style={{ height: 400, width: '100%' }}>
        <DataGrid
          rows={this.state.data.data}
          columns={this.columns}
          initialState={{
            pagination: {
              paginationModel: { page: 0, pageSize: 5 },
            },
          }}
          pageSizeOptions={[5, 10]}
          checkboxSelection
        />
      </div>
    );
  }
}
