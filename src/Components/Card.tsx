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
        className="adios component card"
      >
        {this.props.title ? (
          <div className="card-header py-3">
            <h6 className="m-0 font-weight-bold text-primary">{ this.props.title }</h6>
          </div>
        ) : ''}

        <div className="card-body">
          <div dangerouslySetInnerHTML={{ __html: this.props.content ?? "" }} />
        </div>
      </div>
    );
  }
}
