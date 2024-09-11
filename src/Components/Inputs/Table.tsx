import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';
import BigTable, { TableProps, TableState } from '../Table'

interface TableInputProps extends InputProps {
  model: string,
  children?: any,
  columns?: any,
  onRowClick?: (table: BigTable<TableProps, TableState>, row: any) => void,
}

interface TableInputState extends InputState {
  model: string,
  columns: any,
}

export default class Table extends Input<TableInputProps, TableInputState> {
  static defaultProps = {
    inputClassName: 'table',
    id: uuid.v4(),
  }

  constructor(props: TableInputProps) {
    super(props);

    this.state = {
      ...this.state, // Parent state
      model: props.model,
      columns: props.columns ? props.columns : {},
    };
  }

  renderInputElement() {
    return (
      <BigTable
        async={false}
        uid={this.props.uid + '_table'}
        model={this.props.model}
        showHeader={false}
        data={{data: this.state.value}}
        columns={this.state.columns}
        isUsedAsInput={true}
        isInlineEditing={this.state.isInlineEditing}
        readonly={!this.state.isInlineEditing}
        onChange={(table: BigTable<TableProps, TableState>) => {
          this.onChange(table.state.data?.data);
        }}
        onDeleteSelectionChange={(table: BigTable<TableProps, TableState>) => {
          this.onChange(table.state.data?.data ?? []);
        }}
        onRowClick={(table: BigTable<TableProps, TableState>, row: any) => {
          if (this.props.onRowClick) {
            this.props.onRowClick(table, row);
          }
        }}
      ></BigTable>
    );
  }

  renderValueElement() { return this.renderInputElement(); }
}
