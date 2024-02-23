import  { Component } from "react";
import { GridColDef, GridSortModel, GridFilterModel } from '@mui/x-data-grid';

import  { ModalProps } from "./Modal";
import  { FormProps, FormColumns } from "./Form";

import { adiosError, deepObjectMerge } from "./Helper";
import request from "./Request";

export interface SortBy {
  field: string,
  sort?: string | null
}

export interface TableProps {
  addButtonText?: string,
  canCreate?: boolean,
  canDelete?: boolean,
  canRead?: boolean,
  canUpdate?: boolean,
  columns?: FormColumns,
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
  sortBy?: SortBy,
  page: number,
  itemsPerPage: number,
  search?: string,
  showHeader?: boolean,
  title?: string,
}

export default class Table<T extends TableState = TableState> extends Component<TableProps> {
  state: T;

  constructor(props: TableProps) {
    super(props);

    this.state = {
      endpoint: props.endpoint ? props.endpoint : 'components/table',
      canCreate: props.canCreate ?? true,
      canDelete: props.canDelete ?? true,
      canRead: props.canRead ?? true,
      canUpdate: props.canUpdate ?? true,
      formId: props.formId ? props.formId : 0,
      formEndpoint: props.formEndpoint ? props.formEndpoint : 'components/form',
      formParams: {
        model: props.model,
        uid: props.uid,
      },
      page: 1,
      itemsPerPage: 15,
      showHeader: props.showHeader ?? true,
    } as T;
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

  // TODO: TOTO VYLEPSIT
  loadParams(successCallback?: (params: any) => void) {
    let propsColumns = this.props.columns ?? {};

    request.get(
      this.state.endpoint,
      {
        returnParams: '1',
        model: this.props.model,
        parentFormId: this.props.parentFormId ? this.props.parentFormId : 0,
        parentFormModel: this.props.parentFormModel ? this.props.parentFormModel : '',
        tag: this.props.tag,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        let params: any = deepObjectMerge(data.params, this.props);
        if (params.columns.length == 0) adiosError(`No columns to show in table for '${this.props.model}'.`);
        if (successCallback) successCallback(params);

        this.setState({
          addButtonText: this.props.addButtonText ?? params.addButtonText,
          canCreate: params.canCreate ?? true,
          canDelete: params.canDelete ?? true,
          canRead: params.canRead ?? true,
          canUpdate: params.canUpdate ?? true,
          columns: params.columns,
          showHeader: params.showHeader ?? true,
          title: this.props.title ?? params.title,
        });
      }
    );
  }

  loadData(page: number = 1, itemsPerPage = 15) {
    request.get(
      this.state.endpoint,
      {
        returnData: '1',
        filterBy: this.state.filterBy,
        model: this.props.model,
        sortBy: this.state.sortBy,
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
          data: data.data,
          page: page,
          itemsPerPage: itemsPerPage
        });
      }
    );
  }

  openForm(id: number) {
    this.setState(
      { formId: id },
      () => {
        //@ts-ignore
        ADIOS.modalToggle(this.props.uid);
      }
    )
  }

  onAddClick() {
    this.openForm(0);
  }

  onRowClick(id: number) {
    this.openForm(id);
  }

  onPaginationChange(page: number, itemsPerPage: number) {
    this.loadData(page, itemsPerPage);
  }

  onFilterChange(data: GridFilterModel) {
    this.setState({
      filterBy: data
    }, () => this.loadData());
  }

  onSortByChange(sortBy?: SortBy, stateParams?: any) {
    console.log(stateParams);
    this.setState({
      ...stateParams,
      sortBy: sortBy,
    }, () => this.loadData());
  }

  onSearchChange(search: string) {
    this.setState({
      search: search
    }, () => this.loadData());
  }
}
