import React, { Component } from "react";
import { GridColDef, GridSortModel, GridFilterModel } from '@mui/x-data-grid';

import Modal, { ModalProps } from "./Modal";
import Form, { FormProps, FormColumns } from "./Form";
import Notification from "./Notification";

import { adiosError, deepObjectMerge } from "./Helper";
import request from "./Request";

export interface OrderBy {
  field: string,
  direction?: string | null
}

export interface ExternalCallbacks {
  openForm?: string,
  onAddClick?: string,
  onRowClick?: string,
}

export interface TableProps {
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: FormColumns,
  renderForm?: boolean,
  formId?: number,
  formEndpoint?: string,
  formModal?: ModalProps,
  formParams?: FormProps,
  endpoint?: string
  modal?: ModalProps,
  model: string,
  parentFormId?: number,
  parentFormModel?: string,
  rowHeight: number,
  showHeader?: boolean,
  tag?: string,
  title?: string,
  uid: string,
  where?: Array<any>,
  params?: any,
  externalCallbacks?: ExternalCallbacks,
  itemsPerPage: number,
  orderBy?: OrderBy,

  //TODO
  //showPaging?: boolean,
  //showControls?: boolean,
  //showAddButton?: boolean,
  //showPrintButton?: boolean,
  //showSearchButton?: boolean,
  //showExportCsvButton?: boolean,
  //showImportCsvButton?: boolean,
  //showFulltextSearch?: boolean
}

// Laravel pagination
interface TableData {
  current_page: number,
  data: Array<any>,
  first_page_url: string,
  from: number,
  last_page_url: string,
  last_page: number,
  links: Array<any>,
  next_page_url: string|null,
  path: string,
  per_page: number,
  prev_page_url: string|null,
  to: number,
  total: number
}

export interface TableState {
  endpoint: string,
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: any, //Array<GridColDef>,
  data?: TableData,
  filterBy?: GridFilterModel,
  formId?: number,
  formEndpoint?: string,
  formParams?: FormProps,
  orderBy?: OrderBy,
  page: number,
  itemsPerPage: number,
  search?: string,
  showHeader?: boolean,
  title?: string,
  folderUrl?: string,
  loadingInProgress: boolean,
  renderForm?: boolean,
}

export default class Table<P, S extends TableState = TableState> extends Component<TableProps, TableState> {
  state: S;

  constructor(props: TableProps) {
    super(props);

    globalThis.adios.reactElements[this.props.uid] = this;

    this.state = {
      endpoint: props.endpoint ? props.endpoint : 'components/table',
      canCreate: props.canCreate ?? true,
      canDelete: props.canDelete ?? true,
      canRead: props.canRead ?? true,
      canUpdate: props.canUpdate ?? true,
      formId: props.formId,
      formEndpoint: props.formEndpoint ? props.formEndpoint : 'components/form',
      formParams: {
        model: props.model,
        uid: props.uid,
      },
      renderForm: props.renderForm ?? true,
      page: 1,
      itemsPerPage: this.props.itemsPerPage,
      showHeader: props.showHeader ?? true,
      loadingInProgress: false,
      orderBy: this.props.orderBy,
    } as S;
  }

  componentDidMount() {
    this.loadParams();
    this.loadData();
  }

  componentDidUpdate(prevProps: TableProps, prevState: TableState) {

    if (
      (prevProps.formParams?.id != this.props.formParams?.id)
      || (prevProps.parentFormId != this.props.parentFormId)
    ) {
      this.state.formParams = this.props.formParams;
      this.loadParams();
      this.loadData();
    }
  }

  onAfterLoadParams(params: any): any {
    return params;
  }

  getEndpointUrl(): string {
    return this.state.endpoint;
  }

  getCustomEndpointParams(): any {
    /* to be overriden */
    return {};
  }

  loadParams(successCallback?: (params: any) => void) {
    let propsColumns = this.props.columns ?? {};

    request.get(
      this.getEndpointUrl(),
      {
        ...this.getCustomEndpointParams(),
        action: 'getParams',
        model: this.props.model,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        tag: this.props.tag,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        try {
          if (data.status == 'error') throw new Error('Error while loading table params: ' + data.message);
        
          let params: any = deepObjectMerge(data, this.props);
          if (params.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);
          if (successCallback) successCallback(params);

          params = this.onAfterLoadParams(params);

          this.setState({
            addButtonText: this.props.addButtonText ?? params.addButtonText,
            canCreate: params.canCreate ?? true,
            canDelete: params.canDelete ?? true,
            canRead: params.canRead ?? true,
            canUpdate: params.canUpdate ?? true,
            columns: params.columns,
            showHeader: params.showHeader ?? true,
            title: this.props.title ?? params.title,
            folderUrl: params.folderUrl,
          });
        } catch (err) {
          Notification.error(err.message);
        }
      }
    );
  }

  loadData(page: number = 1, itemsPerPage = 15) {
    this.setState({loadingInProgress: true});
    request.get(
      this.getEndpointUrl(),
      {
        ...this.getCustomEndpointParams(),
        action: 'loadData',
        filterBy: this.state.filterBy,
        model: this.props.model,
        orderBy: this.state.orderBy,
        page: page,
        itemsPerPage: itemsPerPage,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        search: this.state.search,
        tag: this.props.tag,
        where: this.props.where,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        this.setState({
          loadingInProgress: false,
          data: data,
          page: page,
          itemsPerPage: itemsPerPage
        });
      }
    );
  }

  getFormParams(): any {
    return {
      uid: this.props.uid + '_form',
      model: this.props.model,
      tag: this.props.tag, 
      id: this.state.formId ?? 0, 
      endpoint: this.state.formEndpoint ?? '',
      showInModal: true,
      columns: this.props.formParams?.columns ?? {},
      titleForInserting: this.props.formParams?.titleForInserting,
      titleForEditing: this.props.formParams?.titleForEditing,
      saveButtonText: this.props.formParams?.saveButtonText,
      addButtonText: this.props.formParams?.addButtonText,
      onSaveCallback: () => {
        this.loadData();
        //@ts-ignore
        ADIOS.modalToggle(this.props.uid);
      },
      onDeleteCallback: () => {
        this.loadData();
        //@ts-ignore
        ADIOS.modalToggle(this.props.uid);
      },
      isInitialized: false,
    }
  }

  getFormModalParams(): any {
    return {
      uid: this.props.uid + '_form',
      model: this.props.model,
      hideHeader: true,
      isOpen: this.props.formParams?.id ? true : false,
      ...this.props.modal
    }
  }

  renderFormModal(): JSX.Element {
    if (this.state.renderForm) {
      return <Modal {...this.getFormModalParams()}>{this.renderForm()}</Modal>;
    } else {
      return <></>;
    }
  }

  renderForm(): JSX.Element {
    if (this.state.renderForm) {
      return <Form {...this.getFormParams()} />;
    } else {
      return <></>;
    }
  }

  openForm(id: number) {
    if (this.props.externalCallbacks && this.props.externalCallbacks.openForm) {
      window[this.props.externalCallbacks.openForm](this, id);
    } else {
      this.setState(
        { formId: id },
        () => {
          let _this = this;
          setTimeout(function() {
            //@ts-ignore
            ADIOS.modalToggle(_this.props.uid + '_form');
          }, 280);
        }
      )
    }
  }

  onAddClick() {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onAddClick) {
      window[this.props.externalCallbacks.onAddClick](this);
    } else {
      this.openForm(0);
    }
  }

  onRowClick(id: number) {
    if (this.props.externalCallbacks && this.props.externalCallbacks.onRowClick) {
      window[this.props.externalCallbacks.onRowClick](this, id);
    } else {
      this.openForm(id);
    }
  }

  onPaginationChange(page: number, itemsPerPage: number) {
    this.loadData(page, itemsPerPage);
  }

  onFilterChange(data: GridFilterModel) {
    this.setState({
      filterBy: data
    }, () => this.loadData());
  }

  onOrderByChange(orderBy?: OrderBy | null, stateParams?: any) {
    this.setState({
      ...stateParams,
      orderBy: orderBy,
    }, () => this.loadData());
  }

  onSearchChange(search: string) {
    this.setState({
      search: search
    }, () => this.loadData());
  }
}
