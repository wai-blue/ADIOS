import { createRoot } from "react-dom/client";
import React, { useRef } from 'react';
import ReactDOM from 'react-dom';
import * as uuid from 'uuid';
import {isValidJson, kebabToPascal, camelToKebab, deepObjectMerge} from './Helper';
import Dialog from "./Dialog";

import 'primereact/resources/primereact.css';
import 'primereact/resources/themes/lara-light-indigo/theme.css';

export class ADIOS {
  config: object = {};

  reactComponents: any = {};
  reactElementsWaitingForRender: number = 0;
  reactElements: Object = {};

  primeReactTailwindTheme: any = {
    dataTable: {
      // root: { className: 'bg-primary' },
      headerRow: { className: 'bg-primary' },
    },
  };

  language: string = '';
  dictionary: any = {};
  lastShownDialogRef: any;

  /**
  * Define attributes which will not removed
  */
  attributesToSkip = [
    'onclick'
  ];

  constructor(config: object) {
    this.config = config;
  }

  translate(orig: string): string {
    let translated: string = orig;
    if (this.dictionary[this.language] && this.dictionary[this.language][orig]) {
      translated = this.dictionary[this.language][orig];
    }
    return translated;
  }

  makeErrorResultReadable(error: any): JSX.Element {
    console.log('makeErrorResultReadable', error, error.code, error.data);
    if (error.code && error.data) {
      switch (error.code) {
        case 87335:
          return <>
            <b>Some inputs need your attention</b><br/>
            <br/>
            {error.data.map((item) => <div>{item}</div>)}
          </>;
        break;
        default:
          return <>
            <div>Error #{error.code}</div>
            <pre style={{fontSize: '8pt', textAlign: 'left'}}>{JSON.stringify(error.data)}</pre>
          </>;
        break;
      }
    } else if (typeof error == 'object') {
      return <>
        <pre style={{fontSize: '8pt', textAlign: 'left'}}>{JSON.stringify(error)}</pre>
      </>;
    } else {
      return error;
    }
  }

  showDialog(content: JSX.Element, props?: any) {
    const root = ReactDOM.createRoot(document.getElementById('app-dialogs'));

    this.lastShownDialogRef = React.createRef();

    root.render(<>
      <Dialog
        ref={this.lastShownDialogRef}
        uid={'app_dialog_' + uuid.v4().replace('-', '_')}
        visible
        style={{minWidth: '50vw'}}
        {...props}
      >{content}</Dialog>
    </>);
  }

  showDialogDanger(content: JSX.Element, props?: any) {
    let defaultProps: any = {
      headerClassName: 'dialog-danger-header',
      contentClassName: 'dialog-danger-content',
      header: "🥴 Ooops",
      footer: <div className={"flex w-full justify-start"}>
        <button
          className="btn btn-transparent"
          onClick={() => { this.lastShownDialogRef.current.hide(); }}
        >
          <span className="icon"><i className="fas fa-check"></i></span>
          <span className="text">OK, I understand</span>
        </button>
      </div>
    };

    if (!props || !props.headerClassName) props.headerClassName = defaultProps.headerClassName;
    if (!props || !props.contentClassName) props.contentClassName = defaultProps.contentClassName;
    if (!props || !props.header) props.footer = defaultProps.header;
    if (!props || !props.footer) props.footer = defaultProps.footer;

    this.showDialog(content, props);
  }

  showDialogWarning(content: JSX.Element, props?: any) {
    let defaultProps = {
      headerClassName: 'dialog-warning-header',
      contentClassName: 'dialog-warning-content',
      header: "🥴 Ooops",
      footer: <div className={"flex w-full justify-start"}>
        <button
          className="btn btn-transparent"
          onClick={() => {
            this.lastShownDialogRef.current.hide()
          }}
        >
          <span className="icon"><i className="fas fa-check"></i></span>
          <span className="text">OK, I understand</span>
        </button>
      </div>
    };

    if (!props.headerClassName) props.headerClassName = defaultProps.headerClassName;
    if (!props.contentClassName) props.contentClassName = defaultProps.contentClassName;
    if (!props.header) props.footer = defaultProps.header;
    if (!props.footer) props.footer = defaultProps.footer;

    this.showDialog(content, props);
  }

  registerReactComponent(elementName: string, elementObject: any) {
    this.reactComponents[elementName] = elementObject;
  }

  /**
   * Get specific ADIOS component with destructed params
   */
  renderReactElement(componentName: string, props: Object, children: any) {
    if (!componentName) return null;

    let componentNamePascalCase = kebabToPascal(componentName);

    if (!this.reactComponents[componentNamePascalCase]) {
      console.error('ADIOS: renderReactElement(' + componentNamePascalCase + '). Component does not exist. Use `adios.registerReactComponent()` in your project\'s index.tsx file.');
      return null;
    } else {
      return React.createElement(
        this.reactComponents[componentNamePascalCase],
        props,
        children
      );
    }
  };

  /**
  * Validate attribute value
  * E.g. if string contains Callback create frunction from string
  */
  // getValidatedAttributeValue(attributeName: string, attributeValue: any): Function|any {
  //   return attributeName.toLowerCase().includes('callback') ? new Function(attributeValue) : attributeValue;
  // }

  convertDomToReact(domElement) {
    let isAdiosComponent = false;
    let component: string = '';
    let componentProps: Object = {};

    if (domElement.nodeType == 3) { /* Text node: https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType */
      return <>{domElement.textContent}</>;
    } else {
      if (domElement.tagName.substring(0, 4) != 'APP-') {
        component = domElement.tagName.toLowerCase();
      } else {
        component = domElement.tagName.substring(4).toLowerCase();
        isAdiosComponent = true;
      }

      let attributesDoNotConvert: Array<string> = [];
      for (let i in domElement.attributes) {
        if (domElement.attributes[i].name == 'adios-do-not-convert') {
          attributesDoNotConvert = domElement.attributes[i].value.split(',');
        }
      }

      let i: number = 0
      while (domElement.attributes.length > i) {
        let attributeName: string = domElement.attributes[i].name.replace(/-([a-z])/g, (_: any, letter: string) => letter.toUpperCase());
        let attributeValue: any = domElement.attributes[i].value;

        if (!attributesDoNotConvert.includes(attributeName)) {
          if (attributeName.startsWith('json:')) {
            attributeName = attributeName.replace('json:', '');
            attributeValue = JSON.parse(attributeValue);
          } else if (attributeName.startsWith('string:')) {
            attributeName = attributeName.replace('string:', '');
            attributeValue = attributeValue;
          } else if (attributeName.startsWith('int:')) {
            attributeName = attributeName.replace('int:', '');
            attributeValue = parseInt(attributeValue);
          } else if (attributeName.startsWith('bool:')) {
            attributeName = attributeName.replace('bool:', '');
            attributeValue = attributeValue == 'true';
          } else if (attributeName.startsWith('function:')) {
            attributeName = attributeName.replace('function:', '');
            attributeValue = new Function(attributeValue);
          } else if (attributeValue === 'true') {
            attributeValue = true;
          } else if (attributeValue === 'false') {
            attributeValue = false;
          } else if (isValidJson(attributeValue)) {
            attributeValue = JSON.parse(attributeValue);
          }
        }

        componentProps[attributeName] = attributeValue;

        if (this.attributesToSkip.includes(attributeName)) {
          i++;
          continue;
        }
        // Remove attributes from HTML DOM
        domElement.removeAttribute(domElement.attributes[i].name);
      }

      let children: Array<any> = [];

      domElement.childNodes.forEach((subElement, _index) => {
        children.push(this.convertDomToReact(subElement));
      });

      let reactElement: any = null;

      if (isAdiosComponent) {
        if (componentProps['uid'] == undefined) {
          componentProps['uid'] = '_' + uuid.v4().replace('-', '_');
        }

        reactElement = this.renderReactElement(
          component,
          componentProps,
          children
        );

        domElement.setAttribute('adios-react-rendered', 'true');
      } else {
        reactElement = React.createElement(
          component,
          componentProps,
          children
        );
      }

      return reactElement;
    }

  }

  /**
  * Render React component (create HTML tag root and render) 
  */
  renderReactElements(rootElement?) {
    if (!rootElement) rootElement = document;

    rootElement.querySelectorAll('*').forEach((element, _index) => {

      if (element.tagName.substring(0, 4) != 'APP-') return;
      if (element.attributes['adios-react-rendered']) return;

      //@ts-ignore
      $(rootElement).addClass('react-elements-rendering');

      let elementRoot = createRoot(element);
      this.reactElementsWaitingForRender++;
      const reactElement = this.convertDomToReact(element)
      elementRoot.render(reactElement);


      // https://stackoverflow.com/questions/75388021/migrate-reactdom-render-with-async-callback-to-createroot
      // https://blog.saeloun.com/2021/07/15/react-18-adds-new-root-api/
      requestIdleCallback(() => {
        this.reactElementsWaitingForRender--;

        if (this.reactElementsWaitingForRender <= 0) {
          //@ts-ignore
          $(rootElement)
            .removeClass('react-elements-rendering')
            .addClass('react-elements-rendered')
          ;
        }
      });
    });

  }
}

// export const adios = new ADIOS();
