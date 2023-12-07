import React, { Component } from "react";

export interface BreadcrumbsProps {
  items: Array<[string, string]>
}

export default class Breadcrumbs extends Component<BreadcrumbsProps> {

  constructor(props: BreadcrumbsProps) {
    console.log(props);
    super(props);
  }

  render() {
    return (
      <nav aria-label="breadcrumb">
        <ol className="breadcrumb">
          {this.props.items.map(([href, item]) => (
            <li className="breadcrumb-item">
              <a 
                href={href} 
                className="text-primary"
              >{ item }</a>
            </li>
          ))}
        </ol>
      </nav>
    );
  }
}
