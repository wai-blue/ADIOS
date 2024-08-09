import React, { Component } from 'react';

interface GridProps {
  uid: string,
  type?: string,
  onClick?: string, // TODO: nepouziva sa
  href?: string,
  text: string,
  icon: string,
  cssClass?: string
}

interface GridParams {
  uid: string,
  type?: string,
  onClick?: string, // TODO: preverit sposob pouzitia
  href?: string,
  text: string,
  icon: string,
  cssClass?: string
}

export default class Grid extends Component<GridProps> {
  params: GridParams  = {
    uid: this.props.uid,
    type: "",
    onClick: "",
    href: "",
    text: "",
    icon: "fas fa-check",
    cssClass: "btn-primary"
  };

  constructor(props: GridProps) {
    super(props);

    this.params = {...this.params, ...this.props};

    if (this.props.type) {
      switch (this.props.type) {
        case 'save':
          this.params.onClick = 'Save';
          this.params.icon = 'fas fa-check';
          this.params.cssClass = 'btn-success';
        break;
        case 'delete':
          this.params.onClick = 'Delete';
          this.params.icon = 'fas fa-check';
          this.params.cssClass = 'btn-danger';
        break;
        case 'close':
          this.params.onClick = 'Close';
          this.params.icon = 'fas fa-times';
          this.params.cssClass = 'btn-light';
        break;
      }
    }
  }

  render() {
    return (
      <div  
        id={"adios-button-" + this.props.uid}
        className="adios component button"
      >
        <a 
          className={"adios ui Grid btn " + this.params.cssClass + " btn-icon-split"}
          href={this.params.href} 
          onClick={() => alert(this.params.onClick)}
        >
          <span className="icon">
            <i className={this.params.icon}></i>
          </span>
          <span className="text">{this.params.text}</span>
        </a>
      </div>
    );
  }
}

