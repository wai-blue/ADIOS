import React, { Component } from "react";
import './Css/CardButton.css';

interface CardButtonProps {
  uid: string,
  onClick?: string, // TODO: nepouziva sa
  href?: string,
  text: string,
  icon: string,
  subtitle?: string,
  cssClass?: string,
}

export default class CardButton extends Component<CardButtonProps> {

  constructor(props: CardButtonProps) {
    super(props);
  }

  render() {
    return (
      <a 
        id={"adios-card-button-" + this.props.uid}
        href={
          this.props.href ? (
            this.props.href.startsWith('/')
                //@ts-ignore
              ? window._APP_URL + this.props.href
              : window.location.href + '/' + this.props.href
          ) : '#'
        }
        className={"btn " + this.props.cssClass + " shadow-sm mb-1 p-4"}
        style={{width: '14em'}}
      >
        <i 
          className={this.props.icon} 
          style={{fontSize: '4em'}}
        ></i>

        <div className="text-center pt-4 mt-4 h5">{ this.props.text }</div>
        { this.props.subtitle ? (
          <div className="text-center small">{ this.props.subtitle }</div>
        ) : ''}
      </a>
    );
  }
}
