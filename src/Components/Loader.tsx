import { createRoot } from "react-dom/client";
import React, { useRef } from "react";
import * as uuid from 'uuid';
import { isValidJson, kebabToPascal, camelToKebab } from './Helper';

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
          if (isValidJson(attributeValue)) {
            attributeValue = JSON.parse(attributeValue);
          } else if (attributeName.startsWith('function:')) {
            attributeName = attributeName.replace('function:', '');
            attributeValue = new Function(attributeValue);
          } else if (attributeValue === 'true') {
            attributeValue = true;
          } else if (attributeValue === 'false') {
            attributeValue = false;
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
