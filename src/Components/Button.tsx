import React, { Component } from "react";

interface ButtonProps {
  uid: string,
  type?: string,
  onClick?: string,
  href?: string,
  text: string,
  icon: string,
  css?: string
}

interface ButtonParams {
  uid: string,
  type?: string,
  onClick?: string,
  href?: string,
  text: string,
  icon: string,
  css?: string
}

export default class Button extends Component<ButtonProps> {
  params: ButtonParams  = {
    uid: this.props.uid,
    type: "",
    onClick: "",
    href: "",
    text: "",
    icon: "fas fa-check",
    css: "btn-primary"
  };

  constructor(props: ButtonProps) {
    super(props);

    this.params = {...this.params, ...this.props};

    if (this.props.type) {
      switch (this.props.type) {
        case 'save':
          this.params.onClick = 'Save';
          this.params.icon = 'fas fa-check';
          this.params.css = 'btn-success';
        break;
        case 'delete':
          this.params.onClick = 'Delete';
          this.params.icon = 'fas fa-check';
          this.params.css = 'btn-danger';
        break;
        case 'close':
          this.params.onClick = 'Close';
          this.params.icon = 'fas fa-times';
          this.params.css = 'btn-light';
        break;
      }
    }

console.log(this.params);
  }


  render() {
    return (
      <div  
        id={"adios-button-" + this.props.uid}
        className="adios react ui button"
      >
        <a 
          className={"adios ui Button btn " + this.params.css + " btn-icon-split"}
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

