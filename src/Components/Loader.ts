import { createRoot } from "react-dom/client";
import React from "react";
import * as uuid from 'uuid';
import { isValidJson, kebabToPascal, camelToKebab } from './Helper';

import 'primereact/resources/primereact.css';
import 'primereact/resources/themes/lara-light-indigo/theme.css';

export class ADIOS {
  APP_URL: string = '';

  reactComponents: any = {};
  reactComponentsWaitingForRender: number = 0;
  reactElements: Object = {};

  primeReactTailwindTheme: any = {
    dataTable: {
      // root: { className: 'bg-primary' },
      headerRow: { className: 'bg-primary' },
    },
  };

  dictionary: Object = {};

  /**
  * Define attributes which will not removed
  */
  attributesToSkip = [
    'onclick'
  ];

  constructor() {
  }
  
  registerReactComponent(elementName: string, elementObject: any) {
    this.reactComponents[elementName] = elementObject;
  }

  /**
  * Get specific ADIOS component with destructed params 
  */
  getComponent(componentName: string, props: Object) {
    if (!componentName) return null;

    // Check if uid exists or create custom
    if (props['uid'] == undefined) {
      props['uid'] = uuid.v4();
    }

    let componentNamePascalCase = kebabToPascal(componentName);

    if (!this.reactComponents[componentNamePascalCase]) {
      console.error('ADIOS: getComponent(' + componentNamePascalCase + '). Component does not exist. Use `adios.registerReactComponent()` in your project\'s index.tsx file.');
      return null;
    } else {
      return React.createElement(
        this.reactComponents[componentNamePascalCase],
        props
      );
    }
  };

  /**
  * Validate attribute value
  * E.g. if string contains Callback create frunction from string
  */
  getValidatedAttributeValue(attributeName: string, attributeValue: any): Function|any {
    return attributeName.toLowerCase().includes('callback') ? new Function(attributeValue) : attributeValue;
  }

  /**
  * Render React component (create HTML tag root and render) 
  */
  renderReactComponents(renderIntoElement: string = 'body') {

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

      // Find attribute and also delete it using [0] index
      let i: number = 0
      while (element.attributes.length > i) {
        let attributeName: string = element.attributes[i].name.replace(/-([a-z])/g, (_: any, letter: string) => letter.toUpperCase());
        let attributeValue: any = element.attributes[i].value;

        if (isValidJson(attributeValue)) {
          attributeValue = JSON.parse(attributeValue);
          // let attributeValues: Object|Array<any> = JSON.parse(attributeValue);
          // if (!Array.isArray(attributeValues)) {
          //   attributeValue = {};

          //   attributeValue  = Object.keys(attributeValues).reduce(function(result, key) {
          //     result[key] = _this.getValidatedAttributeValue(key, attributeValues[key]);
          //     return result;
          //   }, {});
          // } else {
          //   attributeValue = attributeValues;
          // }
        } else if (attributeValue === 'true') {
          attributeValue = true;
        } else if (attributeValue === 'false') {
          attributeValue = false;
        }

        componentProps[attributeName] = attributeValue; // this.getValidatedAttributeValue(attributeName, attributeValue); 

        if (this.attributesToSkip.includes(attributeName)) {
          i++;
          continue;
        }
        // Remove attributes from HTML DOM
        element.removeAttribute(element.attributes[i].name);
      }

      let componentBuildElement = createRoot(element);
      this.reactComponentsWaitingForRender++;
      componentBuildElement.render(this.getComponent(component, componentProps));

      // https://stackoverflow.com/questions/75388021/migrate-reactdom-render-with-async-callback-to-createroot
      // https://blog.saeloun.com/2021/07/15/react-18-adds-new-root-api/
      requestIdleCallback(() => {
        this.reactComponentsWaitingForRender--;

        if (this.reactComponentsWaitingForRender <= 0) {
          $(renderIntoElement)
            .removeClass('react-components-rendering')
            .addClass('react-components-rendered')
          ;
        }
      });
    });
  }
}

export const adios = new ADIOS();
