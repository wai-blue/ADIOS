import React, { Component } from "react";

interface ButtonProps {
  uid: string,
  type?: string,
  onClick?: any,
  href?: string,
  text?: string,
  title?: string,
  icon?: string,
  target?: string,
  cssClass?: string
  cssStyle?: object
}

interface ButtonState {
  icon: string,
  cssClass: string
  cssStyle?: object
}

export default class Button extends Component<ButtonProps> {
  state: ButtonState;

  constructor(props: ButtonProps) {
    super(props);

    this.state = {
      cssClass: props.cssClass ?? 'btn-primary',
      cssStyle: props.cssStyle,
      icon: props.icon ?? 'fas fa-check',
    };

    switch (this.props.type) {
      case 'save':
        this.state = {
          icon: 'fas fa-check',
          cssClass: 'btn-success'
        }
      break;
      case 'delete':
        this.state = {
          icon: 'fas fa-check',
          cssClass: 'btn-danger'
        }
      break;
      case 'close':
        this.state = {
          icon: 'fas fa-times',
          cssClass: 'btn-light'
        }
      break;
    }
  }

  render() {
    return (
      <div
        id={"adios-button-" + this.props.uid}
        className="adios component button"
      >
        <a 
          className={"adios ui Button btn " + this.state.cssClass + (this.props.icon && this.props.text ? " btn-icon-split" : "")}
          style={this.state.cssStyle}
          href={
            this.props.href ? (
              this.props.href.startsWith('/')
                ? globalThis.app.config.url + this.props.href
                : this.props.href.startsWith('?')
                  ? window.location.href + this.props.href
                  : window.location.href + '/' + this.props.href
            ) : '#'
          }
          onClick={this.props.onClick}
          target={this.props.target}
          title={this.props.title}
        >
          <span className="icon">
            <i className={this.state.icon}></i>
          </span>
          {this.props.text ? <span className="text">{this.props.text}</span> : null}
        </a>
      </div>
    );
  }
}

