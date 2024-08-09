import React, { Component } from 'react';

interface BreadcrumbsProps {
  uid: string,
  items: Array<BreadcrumbItem>
}

interface BreadcrumbItem {
  url: string;
  text: string;
}

interface BreadcrumbsState {
  items: Array<BreadcrumbItem>
}

export default class Breadcrumbs extends Component<BreadcrumbsProps> {
  state: BreadcrumbsState;

  constructor(props: BreadcrumbsProps) {
    super(props);

    this.state = {
      items: this.props.items
    };
  }

  render() {
    // TODO: ak this.state.items nie je pole (napr.
    // syntax error v JSON v <adios-breadcrumbs>),
    // tak by malo vypisat dajaku error hlasku

    let html: any;

    if (Array.isArray(this.state.items)) {
      html = this.state.items.map((item, i) => (
        <li className="breadcrumb-item" key={i}>
          {this.state.items.length - 1 === i ? (
            <span style={{color: '#e78b00'}}>{item.text}</span>
          ) : (
            <a
              href={globalThis.app.config.url + '/' + item.url}
              className="text-primary"
            >{item.text}</a>
          )}
        </li>
      ));
    } else {
      html = <p>No breadcrubms to show.</p>;
    }

    return (
      <div
        id={"adios-breadcrumbs-" + this.props.uid}
        className="adios component breadcrumbs"
      >
        <nav
          aria-label="breadcrumb"
        >
          <ol className="breadcrumb">
            {html}
          </ol>
        </nav>
      </div>
    );
  }
}
