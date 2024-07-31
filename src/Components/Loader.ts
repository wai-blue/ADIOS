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
  getComponent(componentName: string, props: Object, children: any) {
    if (!componentName) return null;

    let componentNamePascalCase = kebabToPascal(componentName);

    if (!this.reactComponents[componentNamePascalCase]) {
      console.error('ADIOS: getComponent(' + componentNamePascalCase + '). Component does not exist. Use `adios.registerReactComponent()` in your project\'s index.tsx file.');
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

  /**
  * Render React component (create HTML tag root and render) 
  */
  renderReactComponents(renderIntoElement: string = 'body') {

    $(renderIntoElement).addClass('react-elements-rendering')

    document.querySelectorAll(renderIntoElement + ' *').forEach((element, _index) => {
     
      let component: string = '';
      let componentProps: Object = {};
      let _this = this;
      let _element = element;

      if (element.tagName.substring(0, 6) != 'ADIOS-' && element.tagName.substring(0, 4) != 'APP-') return;

      if (element.tagName.substring(0, 6) == 'ADIOS-') {
        component = element.tagName.substring(6).toLowerCase();
      } else if (element.tagName.substring(0, 4) == 'APP-') {
        component = element.tagName.substring(4).toLowerCase();
      } else {
        component = '';
      }

      let attributesDoNotConvert: Array<string> = [];
      for (let i in element.attributes) {
        if (element.attributes[i].name == '--adios-do-not-convert') {
          attributesDoNotConvert = element.attributes[i].value.split(',');
        }
      }
      // Find attribute and also delete it using [0] index
      let i: number = 0
      while (element.attributes.length > i) {
        let attributeName: string = element.attributes[i].name.replace(/-([a-z])/g, (_: any, letter: string) => letter.toUpperCase());
        let attributeValue: any = element.attributes[i].value;

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
        element.removeAttribute(element.attributes[i].name);
      }

      let elementRoot = createRoot(element);
      this.reactElementsWaitingForRender++;

      if (componentProps['uid'] == undefined) {
        componentProps['uid'] = '_' + uuid.v4().replace('-', '_');
      }

      componentProps['ref'] = (element) => { this.reactElements[componentProps['uid']] = element; }

      const reactElement = this.getComponent(
        component,
        componentProps,
        element.innerHTML == '' ? null : React.createElement('inner-html', {dangerouslySetInnerHTML: {__html: element.innerHTML}})
      );

      elementRoot.render(reactElement);

      // https://stackoverflow.com/questions/75388021/migrate-reactdom-render-with-async-callback-to-createroot
      // https://blog.saeloun.com/2021/07/15/react-18-adds-new-root-api/
      requestIdleCallback(() => {
        this.reactElementsWaitingForRender--;

        if (this.reactElementsWaitingForRender <= 0) {
          $(renderIntoElement)
            .removeClass('react-elements-rendering')
            .addClass('react-elements-rendered')
          ;
        }
      });
    });
  }
}

// export const adios = new ADIOS();
