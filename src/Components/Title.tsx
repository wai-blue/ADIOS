import React, { Component } from "react";

interface TitleProps {
  uid: string,
  title: string,
  right?: string,
  left?: string
}

export default class Title extends Component<TitleProps> {

  constructor(props: TitleProps) {
    super(props);
  }

  render() {
    return (
      <div 
        id={"adios-title-" + this.props.uid}
        className="adios-react-ui Title p-4"
      >
        <div className="row">
          <div className="col-lg-12 p-0">
            <div 
              className="h3 text-primary mb-0"
              dangerouslySetInnerHTML={{ __html: this.props.title }}
            />
          </div>
        </div>
        <div className="row mt-3">
          <div 
            className="col-lg-6 p-0 d-flex"
            style={{ gap: '0.05em' }}
            dangerouslySetInnerHTML={{ __html: this.props.left ?? "" }}
          />
          <div 
            className='col-lg-6 p-0 d-flex justify-content-end' 
            style={{ gap: '0.05em' }}
            dangerouslySetInnerHTML={{ __html: this.props.right ?? "" }}
          />
        </div>
      </div>
    );
  }
}
