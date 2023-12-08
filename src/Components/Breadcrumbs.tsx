import React, { Component } from "react";

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
    return (
      <div 
        id={"adios-breadcrumbs-" + this.props.uid}
        className="adios react ui breadcrumbs"
      >
        <nav 
          aria-label="breadcrumb"
        >
          <ol className="breadcrumb">
            {this.state.items.map((item, i) => (
              <li className={"breadcrumb-item"}>
                <a 
                  href={item.url} 
                  className={"text-primary " + (this.state.items.length - 1 == i ? "active" : "")}
                >{ item.text }</a>
              </li>
            ))}
          </ol>
        </nav>
      </div>
    );
  }
}
