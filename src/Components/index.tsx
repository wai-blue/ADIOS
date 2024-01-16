import { createRoot } from "react-dom/client";
import React from "react";

import * as uuid from 'uuid';

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
import Calendar from "./Calendar";

/**
* Examples
*/
import Example from "./Example";
import ExampleModelHover from "./Examples/ModelHover";

/**
* Initialize ADIOS components
*/
const initializeComponents = [
  'breadcrumbs',
  'card-button',
  'card',
  'calendar',
  'form',
  'table',
  'title',
  'button',
  'modal',
  'form-button',
  'form-card-button',
  
  // Examples
  'example',
  'example-model-hover'
];

/**
 * Define attributes which will not removed
 */
const attributesToSkip = [
  'onclick'
];

/**
* Get specific ADIOS component with destructed params 
*/
const getComponent = (componentName: string, params: Object) => {
  //@ts-ignore
  switch (componentName) {
    //@ts-ignore
    case 'form': return <Form {...params} />;
    //@ts-ignore
    case 'table': return <Table {...params} />;
    //@ts-ignore
    case 'card-button': return <CardButton {...params} />;
    //@ts-ignore
    case 'card': return <Card {...params} />;
    //@ts-ignore
    case 'calendar': return <Calendar {...params} />;
    //@ts-ignore
    case 'title': return <Title {...params} />;
    //@ts-ignore
    case 'breadcrumbs': return <Breadcrumbs {...params} />;
    //@ts-ignore
    case 'button': return <Button {...params} />;
    //@ts-ignore
    case 'modal': return <Modal {...params} ></Modal>;
    //@ts-ignore
    case 'form-button': return <FormButton {...params} />;
    //@ts-ignore
    case 'form-card-button': return <FormCardButton {...params} />;

    // Examples
    case 'example': return <Example {...params} />;
    case 'example-model-hover': return <ExampleModelHover {...params} />;
    default: return <b style={{color: 'red'}}>Component {componentName} doesn't exist</b>; 
  }
};

/**
 * Render React component (create HTML tag root and render) 
 */
const renderComponent = (specificHtmlElement: string, component: string) => {
  const allComponentsWithSameId = document.querySelectorAll(
    specificHtmlElement + ' adios-' + component);

  allComponentsWithSameId.forEach((element, _index) => {
    let componentProps: Object = {};

    // Find attribute and also delete him using [0] index
    let i: number = 0
    while (element.attributes.length > i) {
      let attributeName: string = element.attributes[i].name.replace(/-([a-z])/g, (_: any, letter: string) => letter.toUpperCase());
      let attributeValue: any = element.attributes[i].value;

      if (isValidJSON(attributeValue)) {
        let attributeValues: Object|Array<any> = JSON.parse(attributeValue);
        if (!Array.isArray(attributeValues)) {
          attributeValue = {};

          attributeValue  = Object.keys(attributeValues).reduce(function(result, key) {
            result[key] = getValidatedAttributeValue(key, attributeValues[key]);
            return result;
          }, {});
        } else {
          attributeValue = attributeValues;
        }
      }

      componentProps[attributeName] = getValidatedAttributeValue(attributeName, attributeValue); 

      if (attributesToSkip.includes(attributeName)) {
        i++;
        continue;
      }

      // Remove attributes from HTML DOM
      element.removeAttribute(element.attributes[i].name);
    }

    // Check if uid exists or create custom
    if (componentProps['uid'] == undefined) {
      componentProps['uid'] = uuid.v4();
    }

    let componentBuildElement = createRoot(element);
    componentBuildElement.render(getComponent(component, componentProps));
  });
}

const renderComponents = (specificHtmlElement: string = 'body') => {
  //document.addEventListener('DOMContentLoaded', () => {
  initializeComponents.map(item => renderComponent(specificHtmlElement, item))
  //});
}

renderComponents();

function isValidJSON(jsonString: string) {
  try {
    JSON.parse(jsonString);
    return true;
  } catch (error) {
    return false;
  }
}

/**
 * Validate attribute value
 * E.g. if string contains Callback create frunction from string
 */
function getValidatedAttributeValue(attributeName: string, attributeValue: any): Function|any {
  return attributeName.toLowerCase().includes('callback') ? new Function(attributeValue) : attributeValue;
}

/**
 * Define global functions
 */
declare global {
  interface Window {
    //renderComponent: (componentName: string) => void;
    getComponent: (componentName: string, params: Object) => React.JSX.Element;
    adiosModal: (controllerUrl: string) => void,
    adiosModalToggle: (uid: string) => void,
    _APP_URL: string;
  }
}

window.getComponent = getComponent;
//window.renderComponent = renderComponent;

interface AdiosModalParams {
  title: string
}

/*
  * Preskumat moznosti ako znovu vyrenderovat uz niekde renderovane komponenty
  * V tejto funkcii sa predpoklada, ze adios cache je nacitana a tak isto pripnuty bootstrap.js pre modal
  * #adios-modal-global sa vytvara v Desktop.twig
  * Nasledne sa meni iba kontent tohto modalo #adios-modal-body-global
  */
window.adiosModal = (controllerUrl: string, params: any = {}, modalParams: AdiosModalParams|null = null) => {
  $('#adios-modal-title-global').text("");

  if (modalParams != null) {
    //@ts-ignore
    $('#adios-modal-title-global').text(modalParams.title);
  }

  //@ts-ignore
  _ajax_update(
    controllerUrl,
    params,
    'adios-modal-body-global',
    {
      success: () => {
        //@ts-ignore
        $('#adios-modal-global').modal();
        renderComponents('#adios-modal-body-global');
      }
    }
  );
}

window.adiosModalToggle = (uid: string) => {
  //@ts-ignore
  $('#adios-modal-' + uid).modal('toggle');
}

