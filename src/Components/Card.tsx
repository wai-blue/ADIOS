import React, { Component } from "react";

interface CardProps {
  uid: string,
  title?: string,
  content: string
}

export default class Card extends Component<CardProps> {

  constructor(props: CardProps) {
    super(props);
  }

  render() {
    return (
      <div 
        id={"adios-card-" + this.props.uid}
        className="adios react ui card"
      >
        <div className="card shadow-sm mb-2">
          {this.props.title ? (
            <div className="card-header py-3">
              <h6 className="m-0 font-weight-bold text-primary">{ this.props.title }</h6>
            </div>
          ) : ''}

          <div className="card-body">
            { this.props.content }
          </div>
        </div>
      </div>
    );
  }
}
