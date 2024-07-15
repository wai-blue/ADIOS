import React, { ChangeEvent, createRef } from 'react';
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

import Table, { OrderBy, TableState, TableProps } from './../Table';
import ExportButton from '../ExportButton';
import { dateToEUFormat, datetimeToEUFormat } from "../Inputs/DateTime";

export interface PrimeTableProps extends TableProps {
  selectionMode?: 'single' | 'multiple' | undefined,
}

/**
 * [Description PrimeTableState]
 */
export interface PrimeTableState extends TableState {
  selection: any,
}

export default class PrimeTable<P, S> extends Table<PrimeTableProps, PrimeTableState> {
  state: PrimeTableState;
  props: PrimeTableProps;

  dt = createRef<DataTable<any[]>>();

  constructor(props: TableProps) {
    super(props);

    this.state = {
      ...this.state,
      selection: [],
    };
  }

  getTableProps(): Object {
    const sortOrders = {'asc': 1, 'desc': -1};

    return {
      ref: this.dt,
      value: this.state.data?.data,
      // editMode: 'row',
      compareSelectionBy: 'equals',
      dataKey: "id",
      first: (this.state.page - 1) * this.state.itemsPerPage,
      paginator: true,
      lazy: true,
      rows: this.state.itemsPerPage,
      totalRecords: this.state.data?.total,
      rowsPerPageOptions: [15, 30, 50, 100],
      paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
      currentPageReportTemplate: "{first}-{last} / {totalRecords}",
      onRowClick: (data: DataTableRowClickEvent) => this.onRowClick(data.data.id as number),
      onRowSelect: (event: DataTableSelectEvent) => this.onRowSelect(event),
      onRowUnselect: (event: DataTableUnselectEvent) => this.onRowUnselect(event),
      onPage: (event: DataTablePageEvent) => this.onPaginationChangeCustom(event),
      onSort: (event: DataTableSortEvent) => this.onOrderByChangeCustom(event),
      sortOrder: sortOrders[this.state.orderBy?.direction ?? 'asc'],
      sortField: this.state.orderBy?.field,
      rowClassName: this.rowClassName,
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
          {selection: event.value} as PrimeTableState,
          function() {
            this.onSelectionChange(event);
          }
        )
      }
    };
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

    console.log(event, orderBy);

    this.onOrderByChange(orderBy);
  }

  onRowSelect(event: DataTableSelectEvent) {
    // to be overriden
  }

  onRowUnselect(event: DataTableUnselectEvent) {
    // to be overriden
  }

  /*
   * Render body for Column (PrimeReact column)
   */
  renderCell(columnName: string, column: any, data: any, options: any) {
    const columnValue: any = data[columnName];
    const enumValues = column.enumValues;

    if (enumValues) return <span style={{fontSize: '10px'}}>{enumValues[columnValue]}</span>;

    if (columnValue === null) {
      return <div className="text-right"><small style={{color: 'var(--gray-700)'}}>N/A</small></div>;
    } else {
      switch (column.type) {
        case 'int':
          return <div className="text-right">
            {columnValue}
            {column.unit ? ' ' + column.unit : ''}
          </div>;
        case 'float':
          return <div className="text-right">
            {columnValue}
            {column.unit ? ' ' + column.unit : ''}
          </div>;
        case 'color':
          return <div
            style={{ width: '20px', height: '20px', background: columnValue }} 
            className="rounded"
          />;
        case 'image':
          if (!columnValue) return <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
          return <img 
            style={{ width: '30px', height: '30px' }}
            src={this.state.folderUrl + "/" + columnValue}
            className="rounded"
          />;
        case 'lookup':
          return <span style={{
            color: '#2d4a8a'
          }}>{columnValue?.lookupSqlValue}</span>;
        case 'enum':
          const enumValues = column.enumValues;
          if (!enumValues) return;
          return enumValues[columnValue];
        case 'bool':
        case 'boolean':
          if (columnValue) return <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>
          return <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>
        case 'date': return dateToEUFormat(columnValue);
        case 'datetime': return datetimeToEUFormat(columnValue);
        case 'tags': {
          return <>
            {columnValue.map((item: any) => {
              if (!column.dataKey) return <></>;
              return <span className="badge badge-info mx-1" key={item.id}>{item[column.dataKey]}</span>;
            })}
          </>
        }
        default: return columnValue;
      }
    }
  }

  renderRows(): JSX.Element[] {
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

    return columns;
  }

  cellClassName(columnName: string, rowData: any) {
    return ''; // rowData.id % 2 === 0 ? '' : 'bg-light';
  }

  rowClassName(rowData: any) {
    return ''; // rowData.id % 2 === 0 ? '' : 'bg-light';
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    return (
      <>
        {this.renderFormModal()}

        <div
          id={"adios-table-prime-" + this.props.uid}
          className={"adios-react-ui table " + (this.state.loadingInProgress ? "loading" : "")}
        >
          <div className="card border-0">
            {this.state.showHeader ?
              <div className="card-header mb-2">
                <div className="row m-0">

                  <div className="col-lg-12 p-0 m-0">
                    <h3 className="card-title m-0 text-primary mb-2">{this.state.title}</h3>
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
                      {/* <div className="dropdown no-arrow">
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
                          <ExportButton
                            uid={this.props.uid}
                            exportType="image"
                            exportElementId={'adios-table-prime-body-' + this.props.uid}
                            exportFileName={this.state.title}
                            text="Save as image"
                            icon="fas fa-file-export mr-2"
                            customCssClass="dropdown-item"
                          />
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-file-export mr-2"/> Exportovať do CSV
                          </button>
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-print mr-2"/> Tlačiť
                          </button>
                        </div>
                      </div> */}

                      <input 
                        className="mr-2 form-control border-end-0 border"
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

            <div id={"adios-table-prime-body-" + this.props.uid}>
              {/* <DataTable {...this.getTableProps()} {...globalThis.app.primeReactTailwindTheme.getPropsFor('DataTable')}>
                {this.renderRows()}
              </DataTable> */}
              <DataTable {...this.getTableProps()}>
                {this.renderRows()}
              </DataTable>
            </div>
          </div>
        </div>
      </>
    );
  }
}

