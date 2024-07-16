import request from "./Request";
import React, { Component, RefObject, useRef, useEffect } from "react";
import { ProgressBar } from 'primereact/progressbar';

interface ViewProps {
  uid: string,
  controller: string,
  evalScriptTags?: boolean,
  params?: Array<any>,
}

interface ViewState {
  controller: string,
  params?: Array<any>,
  html: string,
}

export default class View extends Component<ViewProps> {
  divRef: RefObject<HTMLDivElement>;

  state: ViewState;

  constructor(props: ViewProps) {
    super(props);

    this.divRef = React.createRef();

    this.state = {
      controller: props.controller,
      params: props.params,
      html: '',
    }

  }

  loadHtml() {
    request.get(
      this.state.controller,
      {
        __IS_AJAX__: true
      },
      (data: any) => {
        this.setState({html: data}, () => {
          if (this.props.evalScriptTags) {
            let scripts = this.divRef.current?.getElementsByTagName('script');
            if (scripts) {
              for (var n = 0; n < scripts.length; n++) {
                eval(scripts[n].innerHTML)//run script inside div
              }
            }
          }
        })
      }
    )
  }

  componentDidMount() {
    this.loadHtml();
  }

  componentDidUpdate(prevProps: ViewProps, prevState: ViewState) {
    if (
      prevProps.params != this.props.params
      || prevProps.controller != this.props.controller
    ) {
      this.loadHtml();
    }
  }

  render() {

    if (this.state.html == '') return <ProgressBar mode="indeterminate" style={{ height: '30px' }}></ProgressBar>;
;

    return (
      <div
        ref={this.divRef}
        id={"adios-view-" + this.props.uid}
        className="adios component view"
        dangerouslySetInnerHTML={{__html: this.state.html}}
      >
      </div>
    );
  }
}
