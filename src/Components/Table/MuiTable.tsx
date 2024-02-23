import React, { ChangeEvent, Component, useId } from "react";
import { DataGrid, GridColDef, GridValueGetterParams, skSK, GridSortModel, GridFilterModel } from '@mui/x-data-grid';
import { ProgressBar } from 'primereact/progressbar';

import Table, { SortBy } from '../Table';
import Modal from "./../Modal";
import Form, { FormColumnParams } from "./../Form";
import ExportButton from "../ExportButton";
import { dateToEUFormat, datetimeToEUFormat } from "../Inputs/DateTime";

interface MuiTableColumn extends Omit<GridColDef, 'field' | 'headerName'> {
  adiosColumnDef: FormColumnParams,
  field: string;
  headerName: string;
}

export default class MuiTable extends Table {

  _commonCellRenderer(column: FormColumnParams, content: JSX.Element): JSX.Element {
    return <div className={column.cssClass}>{content}</div>
  }

  _renderCellBody(column: MuiTableColumn, params: any): JSX.Element {
    const columnValue: any = params.value;
    const enumValues = column.adiosColumnDef.enumValues;

    if (enumValues) return <span style={{fontSize: '10px'}}>{enumValues[columnValue]}</span>;

    switch (column.adiosColumnDef.type) {
      case 'color':
        return <div 
          style={{ width: '20px', height: '20px', background: params.value }} 
          className="rounded" 
        />;
      case 'image':
        if (!columnValue) return <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
        return <img
          style={{ width: '30px', height: '30px' }}
          src={this.state.folderUrl + "/" + params.value}
          className="rounded"
        />;
      case 'lookup':
        return <span style={{
          color: '#2d4a8a'
        }}>{columnValue?.lookupSqlValue}</span>;
      case 'bool':
      case 'boolean':
        if (columnValue) return <span className="text-success" style={{fontSize: '1.2em'}}>✓</span>
        return <span className="text-danger" style={{fontSize: '1.2em'}}>✕</span>
      case 'date': return <>{dateToEUFormat(columnValue)}</>;
      case 'datetime': return <>{datetimeToEUFormat(columnValue)}</>;
      case 'tags': {
        //let key = 0;
        //return <div>
        //  {params.value.map((value: any) => {
        //    return <span className="badge badge-info mx-1" key={key++}>{value[column.adiosColumnDef.dataKey]}</span>;
        //  })}
        //</div>
      }
      default: return columnValue;
    }
  }

  getColumnAlign(columnType: string): string {
    switch (columnType) {
      case 'bool':
      case 'boolean':
      case 'color': return 'center';
      default: return 'left';
    }
  }

  loadParams() {
    super.loadParams((params: any) => {
      let newColumns: Array<GridColDef> = [];

      for (let columnName in params.columns) {
        const column: FormColumnParams = {...params.columns[columnName], ...this.props.columns};

        let newColumn = {
          adiosColumnDef: column,
          headerName: column.title,
          field: columnName,
          flex: 1,
          //align: this.getColumnAlign(column.type),
          renderCell: (params: any) => {
            const column: MuiTableColumn = params.api.getColumn(params.field);
            const cellBody: JSX.Element = this._renderCellBody(column, params);

            return this._commonCellRenderer(
              column.adiosColumnDef,
              cellBody
            );
          }
        };

        newColumns.push(newColumn);
      };

      params.columns = newColumns;
    });
  }

  onPaginationChangeCustom(event: any) {
    const page = event.page + 1;
    const itemsPerPage = event.pageSize;
    this.onPaginationChange(page, itemsPerPage);
  }

  onSortByChangeCustom(data: GridSortModel) {
    if (data.length == 0) return this.onSortByChange();

    const sortBy: SortBy = {
      field: data[0].field,
      sort: data[0].sort,
    };

    this.onSortByChange(sortBy);
  }

  render() {
    // console.log('table render', this.props.model, this.state.formParams?.model);

    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    let params = {...this.props.formParams};
    params.defaultValues = params.defaultValues ?? {};
    params.columns = {...params.columns};

    if (this.props.parentFormId != undefined && this.props.parentFormModel != undefined) {
      const lastSlashIndex = this.props.parentFormModel.lastIndexOf("/");
      const modelString = this.props.parentFormModel.substring(lastSlashIndex + 1);
      const targetColumn = 'id_' + modelString.toLowerCase(); /* TODO: Nemusi vzdy fungovat? Treba asi lepsie vyriesit... */

      params.defaultValues[targetColumn] = this.props.parentFormId;
      params.columns[targetColumn] = {readonly: true, ...params.columns[targetColumn]};
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
              globalThis.adios.modalToggle(this.props.uid);
            }}
            onDeleteCallback={() => {
              this.loadData();
              globalThis.adios.modalToggle(this.props.uid);
            }}
            {...params}
          />
        </Modal>

        <div
          id={"adios-table-mui-" + this.props.uid}
          className="adios-react-ui table"
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
                          <ExportButton
                            uid={this.props.uid}
                            exportType="image"
                            exportElementId={'adios-table-mui-body-' + this.props.uid}
                            exportFileName={this.state.title}
                            text="Save as image"
                            icon="fas fa-file-export mr-2"
                            customCssClass="dropdown-item"
                          />
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-file-export mr-2"/> Export to CSV
                          </button>
                          <button className="dropdown-item" type="button">
                            <i className="fas fa-print mr-2"/> Print
                          </button>
                        </div>
                      </div>

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
          
            <div id={"adios-table-mui-body-" + this.props.uid}>
              <DataGrid
                localeText={skSK.components.MuiDataGrid.defaultProps.localeText}
                autoHeight={true}
                rows={this.state.data.data}
                columns={this.state.columns}
                initialState={{
                  pagination: {
                    paginationModel: {
                      page: (this.state.page - 1),
                      pageSize: this.state.itemsPerPage
                    },
                  },
                }}
                paginationMode="server"
                sortingMode="server"
                filterMode="server"
                rowCount={this.state.data.total}
                rowHeight={this.props.rowHeight ?? 50}
                onPaginationModelChange={(pagination) => this.onPaginationChangeCustom(pagination)}
                onSortModelChange={(data: GridSortModel) => this.onSortByChangeCustom(data)}
                onFilterModelChange={(data: GridFilterModel) => this.onFilterChange(data)}
                onRowClick={(item) => this.onRowClick(item.id as number)}
                // stripped rows
                getRowClassName={(params) => params.indexRelativeToCurrentPage % 2 === 0 ? '' : 'bg-light' }
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
        </div>
      </>
    );
  }
}
