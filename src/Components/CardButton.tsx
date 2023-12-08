import React, { Component } from "react";

interface CardButtonProps {
  uid: string,
  onClick?: string,
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
      <div  
        id={"adios-card-button-" + this.props.uid}
        className="adios react ui card-button"
      >
        <a 
          href={this.props.href}
          //onClick={this.props.onClick}
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
      </div>
    );
  }
}
