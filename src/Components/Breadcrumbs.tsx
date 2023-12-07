import React, { Component } from "react";

interface BreadcrumbsProps {
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
      <nav aria-label="breadcrumb">
        <ol className="breadcrumb">
          {this.state.items.map((item, i) => (
            <li className={"breadcrumb-item " + (this.state.items.length - 1 == i ? "active" : "")}>
              <a 
                href={item.url} 
                className="text-primary"
              >{ item.text }</a>
            </li>
          ))}
        </ol>
      </nav>
    );
  }
}
