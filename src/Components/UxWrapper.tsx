import React, { Component } from 'react'
import { adios } from "./Loader";

export interface UxWrapperProps {
  component: string;
}

export interface UxWrapperState {
}

export default class UxWrapper<P extends UxWrapperProps, S extends UxWrapperState> extends Component<P, S> {
  render() {
  console.log(this.props);
    return adios.getComponent(this.props.component, this.props);
  }
}
