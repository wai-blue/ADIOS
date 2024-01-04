import React, { Component } from "react";

interface ButtonProps {
  uid: string,
  type?: string,
  onclick?: string,
  href?: string,
  text?: string,
  icon?: string,
  css?: string
}

interface ButtonState {
  icon: string,
  css: string
}

export default class Button extends Component<ButtonProps> {
  state: ButtonState;

  constructor(props: ButtonProps) {
    super(props);

    this.state = {
      css: props.css ?? 'btn-primary',
      icon: props.icon ?? 'fas fa-check',
    };

    switch (this.props.type) {
      case 'save':
        this.state = {
          icon: 'fas fa-check',
          css: 'btn-success'
        }
      break;
      case 'delete':
        this.state = {
          icon: 'fas fa-check',
          css: 'btn-danger'
        }
      break;
      case 'close':
        this.state = {
          icon: 'fas fa-times',
          css: 'btn-light'
        }
      break;
    }
  }

  render() {
    return (
      <div
        id={"adios-button-" + this.props.uid}
        className="adios react ui button"
      >
        <a 
          className={"adios ui Button btn " + this.state.css + " btn-icon-split"}
          href={
            this.props.href ? (
              this.props.href.startsWith('/') 
                ? window._APP_URL + this.props.href 
                : window.location.href + '/' + this.props.href
            ) : '#'
          }
        >
          <span className="icon">
            <i className={this.state.icon}></i>
          </span>
          <span className="text">{this.props.text}</span>
        </a>
      </div>
    );
  }
}

