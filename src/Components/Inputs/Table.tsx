import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';
import BigTable, { TableProps, TableState } from '../Table'

interface TableInputProps extends InputProps {
  model: string,
  children?: any,
  columns?: any,
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
    console.log(this.state);
    return (
      <BigTable
        uid={this.props.uid + '_table'}
        model={this.props.model}
        rowHeight={30}
        showHeader={false}
        isInlineEditing={this.state.isInlineEditing}
        loadParams={(table: BigTable) => {
          table.setState({
            columns: this.state.columns,
          });
        }}
        loadData={(table: BigTable) => {
          table.setState({data: {data: this.state.value}});
        }}
        onChange={(table: BigTable) => {
          this.onChange(table.state.data?.data);
        }}
      ></BigTable>
    );
  }

  renderValueElement() { return this.renderInputElement(); }
}
