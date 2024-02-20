import React, { useState, useEffect, useRef, createRef } from 'react';
import { classNames } from 'primereact/utils';
import { DataTable, DataTableRowClickEvent } from 'primereact/datatable';
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

import Table from './../Table';
import Modal from '../Modal';
import Form, { FormColumnParams } from '../Form';

export default class PrimeTable extends Table {
  dt: HTMLInputElement|null;
  products: Array<any>;

  constructor(props) {
    super(props);

    this.dt = createRef();
    this.products = ProductService.getProductsArray();
  }

  //let emptyProduct = {
  //  id: null,
  //  name: '',
  //  image: null,
  //  description: '',
  //  category: null,
  //  price: 0,
  //  quantity: 0,
  //  rating: 0,
  //  inventoryStatus: 'INSTOCK'
  //};
  //
  //const [products, setProducts] = useState(null);
  //const [productDialog, setProductDialog] = useState(false);
  //const [deleteProductDialog, setDeleteProductDialog] = useState(false);
  //const [deleteProductsDialog, setDeleteProductsDialog] = useState(false);
  //const [product, setProduct] = useState(emptyProduct);
  //const [selectedProducts, setSelectedProducts] = useState(null);
  //const [submitted, setSubmitted] = useState(false);
  //const [globalFilter, setGlobalFilter] = useState(null);
  //const toast = useRef(null);
  //
  //
  //
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

  openForm(id: number) {
    console.log(id);
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

  onRowClick(data: DataTableRowClickEvent) {
    this.openForm(data.data.id as number);
  }

  _renderColumnBodyImage(rowData): JSX.Element {
    return <img
      src={`https://primefaces.org/cdn/primereact/images/product/${rowData.image}`}
      alt={rowData.image}
      className="shadow-2 border-round"
      style={{ width: '64px' }} 
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
  _renderColumnBody(columnName: string, column: FormColumnParams, data: any, options: any): JSX.Element {
    switch (column.type) {
      default: return data[columnName];
    }
  }

  _renderRows(): JSX.Element {
    return Object.keys(this.state.columns).map((columnName: string) => {
      const column: FormColumnParams = this.state.columns[columnName];
      return <Column
        key={columnName}
        field={columnName}
        header={column.title}
        body={(data: any, options: any) => this._renderColumnBody(columnName, column, data, options)}
        sortable
        style={{ minWidth: '8rem' }}
      ></Column>;
    });
  }

  render() {
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
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid);
            }}
            onDeleteCallback={() => {
              this.loadData();
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid);
            }}
          />
        </Modal>

        <div>
          <div className="card">
            {/*<Toolbar className="mb-4" left={leftToolbarTemplate} right={rightToolbarTemplate}></Toolbar>*/}

            {this.state.data && this.state.columns ? (
              <DataTable
                ref={this.dt}
                value={this.state.data.data}
                onRowClick={(data: DataTableRowClickEvent) => this.onRowClick(data)}
                //selection={selectedProducts}
                //onSelectionChange={(e) => setSelectedProducts(e.value)}
                //dataKey="id"  paginator rows={10} rowsPerPageOptions={[5, 10, 25]}
                //paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                //currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products" globalFilter={globalFilter} header={header}
              >
                <Column selectionMode="multiple" exportable={false}></Column>
                {this._renderRows()}
              </DataTable>
            ) : ''}
          </div>
        </div>
      </>
    );
  }
}

