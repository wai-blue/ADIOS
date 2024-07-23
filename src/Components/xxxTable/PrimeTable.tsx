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
import { OverlayPanel } from 'primereact/overlaypanel';
import { InputProps } from "../Input";
import { InputFactory } from "../InputFactory";

import Table, { OrderBy, TableState, TableProps } from './../Table';
import ExportButton from '../ExportButton';
import { dateToEUFormat, datetimeToEUFormat } from "../Inputs/DateTime";

export interface PrimeTableProps extends TableProps {
}

/**
 * [Description PrimeTableState]
 */
export interface PrimeTableState extends TableState {
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


  render() {
    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
    }

    return (
      <>
        {this.renderFormModal()}

        <div
          id={"adios-table-prime-" + this.props.uid}
          className={"adios component table " + (this.state.loadingInProgress ? "loading" : "")}
        >
          {this.state.showHeader ? this.renderHeader() : ''}

          <div className="table-body" id={"adios-table-prime-body-" + this.props.uid}>
            <DataTable {...this.getTableProps()}>
              {this.renderColumns()}
            </DataTable>
          </div>
        </div>
      </>
    );
  }
}

