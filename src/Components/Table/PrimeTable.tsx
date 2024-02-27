import React, { ChangeEvent, createRef, useRef } from 'react';
import { classNames } from 'primereact/utils';
import { DataTable, DataTableRowClickEvent, DataTablePageEvent, DataTableSortEvent, SortOrder, } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { ProductService } from './test';
import { Toast } from 'primereact/toast';
import { Button } from 'primereact/button';
import { FileUpload } from 'primereact/fileupload';
import { Rating } from 'primereact/rating';
import { Toolbar } from 'primereact/toolbar';
import { InputTextarea } from 'primereact/inputtextarea';
import { RadioButton } from 'primereact/radiobutton';
import { InputNumber } from 'primereact/inputnumber';
import { Dialog } from 'primereact/dialog';
import { InputText } from 'primereact/inputtext';
import { Tag } from 'primereact/tag';
import { ProgressBar } from 'primereact/progressbar';

import Table, { SortBy, TableState, TableProps } from './../Table';
import Modal from '../Modal';
import Form, { FormColumnParams } from '../Form';
import ExportButton from '../ExportButton';
import { dateToEUFormat, datetimeToEUFormat } from "../Inputs/DateTime";

interface PrimeTableState extends TableState {
  sortOrder: SortOrder,
  sortField?: string
}

export default class PrimeTable extends Table<PrimeTableState> {
  dt = createRef<DataTable<any[]>>();

  constructor(props: TableProps) {
    super(props);

    this.state = {
      ...this.state,
      sortOrder: null,
    };
  }

  //const openNew = () => {
  //  setProduct(emptyProduct);
  //  setSubmitted(false);
  //  setProductDialog(true);
  //};
  //
  //const hideDialog = () => {
  //  setSubmitted(false);
  //  setProductDialog(false);
  //};
  //
  //const hideDeleteProductDialog = () => {
  //  setDeleteProductDialog(false);
  //};
  //
  //const hideDeleteProductsDialog = () => {
  //  setDeleteProductsDialog(false);
  //};
  //
  //const saveProduct = () => {
  //  setSubmitted(true);
  //  
  //  if (product.name.trim()) {
  //    let _products = [...products];
  //    let _product = { ...product };
  //    
  //    if (product.id) {
  //      const index = findIndexById(product.id);
  //      
  //      _products[index] = _product;
  //      toast.current.show({ severity: 'success', summary: 'Successful', detail: 'Product Updated', life: 3000 });
  //    } else {
  //      _product.id = createId();
  //      _product.image = 'product-placeholder.svg';
  //      _products.push(_product);
  //      toast.current.show({ severity: 'success', summary: 'Successful', detail: 'Product Created', life: 3000 });
  //    }
  //    
  //    setProducts(_products);
  //    setProductDialog(false);
  //    setProduct(emptyProduct);
  //  }
  //};
  //
  //const editProduct = (product) => {
  //  setProduct({ ...product });
  //  setProductDialog(true);
  //};
  //
  //const confirmDeleteProduct = (product) => {
  //  setProduct(product);
  //  setDeleteProductDialog(true);
  //};
  //
  //const deleteProduct = () => {
  //  let _products = products.filter((val) => val.id !== product.id);
  //  
  //  setProducts(_products);
  //  setDeleteProductDialog(false);
  //  setProduct(emptyProduct);
  //  toast.current.show({ severity: 'success', summary: 'Successful', detail: 'Product Deleted', life: 3000 });
  //};
  //
  //const findIndexById = (id) => {
  //  let index = -1;
  //  
  //  for (let i = 0; i < products.length; i++) {
  //    if (products[i].id === id) {
  //      index = i;
  //      break;
  //    }
  //  }
  //  
  //  return index;
  //};
  //
  //const createId = () => {
  //  let id = '';
  //  let chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  //  
  //  for (let i = 0; i < 5; i++) {
  //    id += chars.charAt(Math.floor(Math.random() * chars.length));
  //  }
  //  
  //  return id;
  //};
  //
  //const exportCSV = () => {
  //  dt.current.exportCSV();
  //};
  //
  //const confirmDeleteSelected = () => {
  //  setDeleteProductsDialog(true);
  //};
  //
  //const deleteSelectedProducts = () => {
  //  let _products = products.filter((val) => !selectedProducts.includes(val));
  //  
  //  setProducts(_products);
  //  setDeleteProductsDialog(false);
  //  setSelectedProducts(null);
  //  toast.current.show({ severity: 'success', summary: 'Successful', detail: 'Products Deleted', life: 3000 });
  //};
  //
  //const onCategoryChange = (e) => {
  //  let _product = { ...product };
  //  
  //  _product['category'] = e.value;
  //  setProduct(_product);
  //};
  //
  //const onInputChange = (e, name) => {
  //  const val = (e.target && e.target.value) || '';
  //  let _product = { ...product };
  //  
  //  _product[`${name}`] = val;
  //  
  //  setProduct(_product);
  //};
  //
  //const onInputNumberChange = (e, name) => {
  //  const val = e.value || 0;
  //  let _product = { ...product };
  //  
  //  _product[`${name}`] = val;
  //  
  //  setProduct(_product);
  //};
  //
  //const leftToolbarTemplate = () => {
  //  return (
  //    <div className="flex flex-wrap gap-2">
  //      <Button label="New" icon="pi pi-plus" severity="success" onClick={openNew} />
  //      <Button label="Delete" icon="pi pi-trash" severity="danger" onClick={confirmDeleteSelected} disabled={!selectedProducts || !selectedProducts.length} />
  //    </div>
  //  );
  //};
  //
  //const rightToolbarTemplate = () => {
  //  return <Button label="Export" icon="pi pi-upload" className="p-button-help" onClick={exportCSV} />;
  //};
  //
  //
  //const getSeverity = (product) => {
  //  switch (product.inventoryStatus) {
  //    case 'INSTOCK':
  //      return 'success';
  //    
  //    case 'LOWSTOCK':
  //      return 'warning';
  //    
  //    case 'OUTOFSTOCK':
  //      return 'danger';
  //    
  //    default:
  //      return null;
  //  }
  //};

  _renderHeader() {
    return (
      <div className="flex flex-wrap gap-2 align-items-center justify-content-between">
        <h4 className="m-0">Manage Products</h4>
        <span className="p-input-icon-left">
          <i className="pi pi-search" />
          <InputText type="search" onInput={(e) => setGlobalFilter(e.target.value)} placeholder="Search..." />
        </span>
      </div>
    );
  }

  //_renderProductDialogFooter() = (
  //  <React.Fragment>
  //    <Button label="Cancel" icon="pi pi-times" outlined onClick={hideDialog} />
  //    <Button label="Save" icon="pi pi-check" onClick={saveProduct} />
  //  </React.Fragment>
  //);

  //const deleteProductDialogFooter = (
  //  <React.Fragment>
  //    <Button label="No" icon="pi pi-times" outlined onClick={hideDeleteProductDialog} />
  //    <Button label="Yes" icon="pi pi-check" severity="danger" onClick={deleteProduct} />
  //  </React.Fragment>
  //);
  //const deleteProductsDialogFooter = (
  //  <React.Fragment>
  //    <Button label="No" icon="pi pi-times" outlined onClick={hideDeleteProductsDialog} />
  //    <Button label="Yes" icon="pi pi-check" severity="danger" onClick={deleteSelectedProducts} />
  //  </React.Fragment>
  //);
  //

  _renderDialog() {
    //return (
    //  <Dialog visible={productDialog} style={{ width: '32rem' }} breakpoints={{ '960px': '75vw', '641px': '90vw' }} header="Product Details" modal className="p-fluid" footer={productDialogFooter} onHide={hideDialog}>
    //    {product.image && <img src={`https://primefaces.org/cdn/primereact/images/product/${product.image}`} alt={product.image} className="product-image block m-auto pb-3" />}
    //    <div className="field">
    //      <label htmlFor="name" className="font-bold">
    //        Name
    //      </label>
    //      <InputText id="name" value={product.name} onChange={(e) => onInputChange(e, 'name')} required autoFocus className={classNames({ 'p-invalid': submitted && !product.name })} />
    //      {submitted && !product.name && <small className="p-error">Name is required.</small>}
    //    </div>
    //    <div className="field">
    //      <label htmlFor="description" className="font-bold">
    //        Description
    //      </label>
    //      <InputTextarea id="description" value={product.description} onChange={(e) => onInputChange(e, 'description')} required rows={3} cols={20} />
    //    </div>

    //    <div className="field">
    //      <label className="mb-3 font-bold">Category</label>
    //      <div className="formgrid grid">
    //        <div className="field-radiobutton col-6">
    //          <RadioButton inputId="category1" name="category" value="Accessories" onChange={onCategoryChange} checked={product.category === 'Accessories'} />
    //          <label htmlFor="category1">Accessories</label>
    //        </div>
    //        <div className="field-radiobutton col-6">
    //          <RadioButton inputId="category2" name="category" value="Clothing" onChange={onCategoryChange} checked={product.category === 'Clothing'} />
    //          <label htmlFor="category2">Clothing</label>
    //        </div>
    //        <div className="field-radiobutton col-6">
    //          <RadioButton inputId="category3" name="category" value="Electronics" onChange={onCategoryChange} checked={product.category === 'Electronics'} />
    //          <label htmlFor="category3">Electronics</label>
    //        </div>
    //        <div className="field-radiobutton col-6">
    //          <RadioButton inputId="category4" name="category" value="Fitness" onChange={onCategoryChange} checked={product.category === 'Fitness'} />
    //          <label htmlFor="category4">Fitness</label>
    //        </div>
    //      </div>
    //    </div>

    //    <div className="formgrid grid">
    //      <div className="field col">
    //        <label htmlFor="price" className="font-bold">
    //          Price
    //        </label>
    //        <InputNumber id="price" value={product.price} onValueChange={(e) => onInputNumberChange(e, 'price')} mode="currency" currency="USD" locale="en-US" />
    //      </div>
    //      <div className="field col">
    //        <label htmlFor="quantity" className="font-bold">
    //          Quantity
    //        </label>
    //        <InputNumber id="quantity" value={product.quantity} onValueChange={(e) => onInputNumberChange(e, 'quantity')} />
    //      </div>
    //    </div>
    //  </Dialog>

    //  <Dialog visible={deleteProductDialog} style={{ width: '32rem' }} breakpoints={{ '960px': '75vw', '641px': '90vw' }} header="Confirm" modal footer={deleteProductDialogFooter} onHide={hideDeleteProductDialog}>
    //    <div className="confirmation-content">
    //      <i className="pi pi-exclamation-triangle mr-3" style={{ fontSize: '2rem' }} />
    //      {product && (
    //        <span>
    //          Are you sure you want to delete <b>{product.name}</b>?
    //        </span>
    //      )}
    //    </div>
    //  </Dialog>

    //  <Dialog visible={deleteProductsDialog} style={{ width: '32rem' }} breakpoints={{ '960px': '75vw', '641px': '90vw' }} header="Confirm" modal footer={deleteProductsDialogFooter} onHide={hideDeleteProductsDialog}>
    //    <div className="confirmation-content">
    //      <i className="pi pi-exclamation-triangle mr-3" style={{ fontSize: '2rem' }} />
    //      {product && <span>Are you sure you want to delete the selected products?</span>}
    //    </div>
    //  </Dialog>
    //</div>
    //);
  }

  //<Column selectionMode="multiple" exportable={false}></Column>
  //<Column field="code" header="Code" sortable style={{ minWidth: '12rem' }}></Column>
  //<Column field="name" header="Name" sortable style={{ minWidth: '16rem' }}></Column>
  //<Column field="image" header="Image" body={this._renderImageBodyTemplate}></Column>
  //<Column field="price" header="Price" body={this._renderPriceBodyTemplate} sortable style={{ minWidth: '8rem' }}></Column>
  //<Column field="category" header="Category" sortable style={{ minWidth: '10rem' }}></Column>
  //<Column field="rating" header="Reviews" body={this._renderRatingBodyTemplate} sortable style={{ minWidth: '12rem' }}></Column>
  //<Column field="inventoryStatus" header="Status" body={this._renderStatusBodyTemplate} sortable style={{ minWidth: '12rem' }}></Column>
  //<Column body={this._renderActionBodyTemplate} exportable={false} style={{ minWidth: '12rem' }}></Column>

  onPaginationChangeCustom(event: DataTablePageEvent) {
    const page: number = (event.page ?? 0) + 1;
    const itemsPerPage: number = event.rows;
    this.onPaginationChange(page, itemsPerPage);
  }

  onSortByChangeCustom(event: DataTableSortEvent) {
    let sortOrder: number | null = 1;

    // Icons in PrimeTable changing
    // 1 == ASC
    // -1 == DESC
    // null == neutral icons
    if (event.sortField == this.state.sortField) {
      sortOrder = (this.state.sortOrder === null ? 1 : (this.state.sortOrder === 1 ? -1 : null));
    }

    const sortBy: SortBy = {
      field: event.sortField,
      sort: event.sortOrder === 1 ? 'asc' : 'desc'
    };

    this.onSortByChange(
      (sortOrder == null ? undefined : sortBy),
      {
        sortOrder: sortOrder,
        sortField: sortOrder === null ? undefined : event.sortField
      }
    );
  }

  _renderColumnBodyImage(columnValue?: any): JSX.Element {
    if (!columnValue) return <i className="fas fa-image" style={{color: '#e3e6f0'}}></i>
    return <img 
      style={{ width: '30px', height: '30px' }}
      src={params.folderUrl + "/" + params.value}
      className="rounded"
    />;
  };

  _renderColumnBodyRating(rowData): JSX.Element {
    return <Rating value={rowData.rating} readOnly cancel={false} />;
  };

  _renderColumnBodyTag(rowData): JSX.Element {
    return <Tag value={rowData.inventoryStatus} severity={getSeverity(rowData)}></Tag>;
  };

  _renderColumnBodyAction(rowData): JSX.Element {
    return (
      <>
        <Button icon="pi pi-pencil" rounded outlined className="mr-2" onClick={() => editProduct(rowData)} />
        <Button icon="pi pi-trash" rounded outlined severity="danger" onClick={() => confirmDeleteProduct(rowData)} />
      </>
    );
  };

  /*
   * Render body for Column (PrimeReact column)
   */
  _renderColumnBody(columnName: string, column: FormColumnParams, data: any, options: any) {
    const columnValue: any = data[columnName];
    const enumValues = column.enumValues;

    if (enumValues) return <span style={{fontSize: '10px'}}>{enumValues[columnValue]}</span>;

    switch (column.type) {
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
        //let key = 0;
        //return <div>
        //  {columnValue.map((value: any) => {
        //    return <span className="badge badge-info mx-1" key={key++}>{value[column.dataKey]}</span>;
        //  })}
        //</div>
      }
      default: return columnValue;
    }
  }

  _renderRows(): JSX.Element[] {
    return Object.keys(this.state.columns).map((columnName: string) => {
      const column: FormColumnParams = this.state.columns[columnName];
      return <Column
        key={columnName}
        field={columnName}
        header={column.title}
        body={(data: any, options: any) => this._renderColumnBody(columnName, column, data, options)}
        style={{ width: 'auto' }}
        sortable
      ></Column>;
    });
  }

  _rowClassName(rowData: any) {
    return rowData.id % 2 === 0 ? '' : 'bg-light';
  }

  render() {
    if (!this.state.data || !this.state.columns) {
      return <ProgressBar mode="indeterminate" style={{ height: '8px' }}></ProgressBar>;
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
              globalThis.ADIOS.modalToggle(this.props.uid);
            }}
            onDeleteCallback={() => {
              this.loadData();
              globalThis.ADIOS.modalToggle(this.props.uid);
            }}
          />
        </Modal>

        <div
          id={"adios-table-prime-" + this.props.uid}
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

            {/*<Toolbar className="mb-4" left={leftToolbarTemplate} right={rightToolbarTemplate}></Toolbar>*/}

            <div id={"adios-table-prime-body-" + this.props.uid}>
              <DataTable
                className="border rounded"
                ref={this.dt}
                value={this.state.data.data}
                lazy={true}
                dataKey="id"
                first={(this.state.page - 1) * this.state.itemsPerPage}
                paginator
                rows={this.state.itemsPerPage}
                totalRecords={this.state.data.total}
                rowsPerPageOptions={[15, 30, 50, 100]}
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="{first}-{last} of {totalRecords} records"
                onRowClick={(data: DataTableRowClickEvent) => this.onRowClick(data.data.id as number)}
                onPage={(event: DataTablePageEvent) => this.onPaginationChangeCustom(event)}
                onSort={(event: DataTableSortEvent) => this.onSortByChangeCustom(event)}
                sortOrder={this.state.sortOrder}
                sortField={this.state.sortField}
                rowClassName={this._rowClassName}
                //globalFilter={globalFilter}
                //header={header}
                //selection={selectedProducts}
                //onSelectionChange={(e) => setSelectedProducts(e.value)}
              >
                {/*<Column selectionMode="multiple" exportable={false}></Column>*/}
                {this._renderRows()}
              </DataTable>
            </div>
          </div>
        </div>
      </>
    );
  }
}

