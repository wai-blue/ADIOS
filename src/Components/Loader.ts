import { createRoot } from "react-dom/client";
import React from "react";
import * as uuid from 'uuid';
import { isValidJson, kebabToPascal, camelToKebab } from './Helper';

import 'primereact/resources/primereact.css';
import 'primereact/resources/themes/lara-light-indigo/theme.css';

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
import ExportButton from "./ExportButton";
import MuiTable from "./Table/MuiTable";
import PrimeTable from "./Table/PrimeTable";

import InputVarchar from "./Inputs/Varchar";
import InputInt from "./Inputs/Int";
import InputLookup from "./Inputs/Lookup";
import InputImage from "./Inputs/Image";

export class ADIOS {
  APP_URL: string = '';

  reactComponents: any = {};
  reactComponentsWaitingForRender: number = 0;

  /**
  * Define attributes which will not removed
  */
  attributesToSkip = [
    'onclick'
  ];

  constructor() {
    this.registerReactComponent('Form', Form);
    this.registerReactComponent('Table', Table);
    this.registerReactComponent('CardButton', CardButton);
    this.registerReactComponent('Title', Title);
    this.registerReactComponent('Breadcrumbs', Breadcrumbs);
    this.registerReactComponent('Card', Card);
    this.registerReactComponent('Button', Button);
    this.registerReactComponent('Modal', Modal);
    this.registerReactComponent('FormButton', FormButton);
    this.registerReactComponent('FormCardButton', FormCardButton);
    this.registerReactComponent('View', View);
    this.registerReactComponent('ExportButton', ExportButton);
    this.registerReactComponent('TableMui', MuiTable);
    this.registerReactComponent('TablePrime', PrimeTable);
    this.registerReactComponent('InputVarchar', InputVarchar);
    this.registerReactComponent('InputInt', InputInt);
    this.registerReactComponent('InputLookup', InputLookup);
    this.registerReactComponent('InputImage', InputImage);
  }
  
  registerReactComponent(elementName: string, elementObject: any) {
    this.reactComponents[elementName] = elementObject;
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
