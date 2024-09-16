import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';
import BigTable, { TableDescription, TableProps, TableState } from '../Table'

interface TableInputProps extends InputProps {
  model: string,
  children?: any,
  description?: TableDescription,
  onRowClick?: (table: BigTable<TableProps, TableState>, row: any) => void,
}

interface TableInputState extends InputState {
  model: string,
  description?: TableDescription,
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
      description: props.description,
    };
  }

  renderInputElement() {
    return (
      <BigTable
        async={false}
        uid={this.props.uid + '_table'}
        context={this.props.context}
        model={this.props.model}
        description={this.state.description}
        data={{data: this.state.value}}
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
