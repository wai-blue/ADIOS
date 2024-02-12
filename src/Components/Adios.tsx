import { createRoot } from "react-dom/client";
import React from "react";
import * as uuid from 'uuid';
import { isValidJson, kebabToPascal, camelToKebab } from './Helper';

/**
 * Components
 */
import Form from "./Form";
import Table from "./Table";
import CardButton from "./CardButton";
import Title from "./Title";
import Breadcrumbs from "./Breadcrumbs";
import Card from "./Card";
import Button from "./Button";
import Modal from "./Modal";
import FormButton from "./FormButton";
import FormCardButton from "./FormCardButton";
import View from "./View";

export class ADIOS {
  APP_URL: string = '';

  reactElements: any = {};

  /**
  * Define attributes which will not removed
  */
  attributesToSkip = [
    'onclick'
  ];

  constructor() {
    this.registerReactElement('Form', Form);
    this.registerReactElement('Table', Table);
    this.registerReactElement('CardButton', CardButton);
    this.registerReactElement('Title', Title);
    this.registerReactElement('Breadcrumbs', Breadcrumbs);
    this.registerReactElement('Card', Card);
    this.registerReactElement('Button', Button);
    this.registerReactElement('Modal', Modal);
    this.registerReactElement('FormButton', FormButton);
    this.registerReactElement('FormCardButton', FormCardButton);
    this.registerReactElement('View', View);
  }
  
  registerReactElement(elementName, elementObject) {
    this.reactElements[elementName] = elementObject;
  }

  /**
  * Get specific ADIOS component with destructed params 
  */
  getComponent(componentName: string, props: Object) {
    // Check if uid exists or create custom
    if (props['uid'] == undefined) {
      props['uid'] = uuid.v4();
    }
    
    let componentNamePascalCase = kebabToPascal(componentName);

    if (!this.reactElements[componentNamePascalCase]) {
      console.error('ADIOS: getComponent(' + componentNamePascalCase + '). Component does not exist. Use `adios.registerReactElement()` in your project\'s index.tsx file.');
      return null;
    } else {
      return React.createElement(
        this.reactElements[componentNamePascalCase],
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
  renderReactComponentsIntoBody() {
    document.querySelectorAll('body *').forEach((element, _index) => {
      let component: string = '';
      let componentProps: Object = {};
      let _this = this;

      if (element.tagName.substring(0, 6) != 'ADIOS-' && element.tagName.substring(0, 4) != 'APP-') return;

      if (element.tagName.substring(0, 6) == 'ADIOS-') {
        component = element.tagName.substring(6).toLowerCase();
      } else if (element.tagName.substring(0, 4) == 'APP-') {
        component = element.tagName.substring(4).toLowerCase();
      } else {
        component = '';
      }
console.log(element.tagName, component);
      // Find attribute and also delete him using [0] index
      let i: number = 0
      while (element.attributes.length > i) {
        let attributeName: string = element.attributes[i].name.replace(/-([a-z])/g, (_: any, letter: string) => letter.toUpperCase());
        let attributeValue: any = element.attributes[i].value;

        if (isValidJson(attributeValue)) {
          let attributeValues: Object|Array<any> = JSON.parse(attributeValue);
          if (!Array.isArray(attributeValues)) {
            attributeValue = {};

            attributeValue  = Object.keys(attributeValues).reduce(function(result, key) {
              result[key] = _this.getValidatedAttributeValue(key, attributeValues[key]);
              return result;
            }, {});
          } else {
            attributeValue = attributeValues;
          }
        }

        componentProps[attributeName] = this.getValidatedAttributeValue(attributeName, attributeValue); 

        if (this.attributesToSkip.includes(attributeName)) {
          i++;
          continue;
        }

        // Remove attributes from HTML DOM
        element.removeAttribute(element.attributes[i].name);
      }

      let componentBuildElement = createRoot(element);
      componentBuildElement.render(this.getComponent(component, componentProps));
    });
  }
}

export const adios = new ADIOS();
