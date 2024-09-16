import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';
import BigTable, { TableDescription, TableProps, TableState } from '../Table'

interface TableInputProps extends InputProps {
  children?: any,
  onRowClick?: (table: BigTable<TableProps, TableState>, row: any) => void,
}

interface TableInputState extends InputState {
}

export default class Table extends Input<TableInputProps, TableInputState> {
  static defaultProps = {
    inputClassName: 'table',
    id: uuid.v4(),
  }

  renderInputElement() {
    const CHILDREN = React.Children.map(this.props.children, (child) => {
      return React.cloneElement(child, {
        data: {data: this.state.value},
        isUsedAsInput: true,
        isInlineEditing: this.state.isInlineEditing,
        readonly: !this.state.isInlineEditing,
        onChange: (table: BigTable<TableProps, TableState>) => {
          this.onChange(table.state.data?.data);
        },
        onDeleteSelectionChange: (table: BigTable<TableProps, TableState>) => {
          this.onChange(table.state.data?.data ?? []);
        },
        onRowClick: (table: BigTable<TableProps, TableState>, row: any) => {
          if (this.props.onRowClick) {
            this.props.onRowClick(table, row);
          }
        },
      });
    });

    return CHILDREN;
  };


  renderValueElement() { return this.renderInputElement(); }
}
