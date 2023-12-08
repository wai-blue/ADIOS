import { createRoot } from "react-dom/client";
import React from "react";

import { v4 } from 'uuid';

/**
 * Components
 */
import Form from "./Components/Form";
import Table from "./Components/Table";
import CardButton from "./Components/CardButton";
import Title from "./Components/Title";
import Breadcrumbs from "./Components/Breadcrumbs";

//import FloatingModal from "./Components/FloatingModal";

/**
* Examples
*/

import Example from "./Components/Example";
import ExampleModelHover from "./Components/Examples/ModelHover";

/**
* Initialize ADIOS components
*/
const initializeComponents = [
  'breadcrumbs',
  'card-button',
  'card',
  'form',
  'table',
  'title',
  
  // Examples
  'example',
  'example-model-hover'
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
    case 'title': return <Title {...params} />;
    //@ts-ignore
    case 'breadcrumbs': return <Breadcrumbs {...params} />;

    //case 'floating-modal': return <FloatingModal>xxx</FloatingModal>;

    // Examples
    case 'example': return <Example {...params} />;
    case 'example-model-hover': return <ExampleModelHover {...params} />;
    default: return <b style={{color: 'red'}}>Component {componentName} doesn't exist</b>; 
  }
};

/**
 * Render React component (create HTML tag root and render) 
 */
const renderComponent = (component: string) => {
  const allComponentsWithSameId = document.querySelectorAll('adios-' + component);

  allComponentsWithSameId.forEach((element, _index) => {
    let componentProps: Object = {};

    for (let i = 0;i < element.attributes.length;i++) {
      let elementValue = element.attributes[i].value;

      if (isValidJSON(elementValue)) {
        elementValue = JSON.parse(elementValue);
      }

      componentProps[element.attributes[i].name] = elementValue; 
    }

    // Check if uid exists or create custom
    if (componentProps['uid'] == undefined) {
      componentProps['uid'] = v4();
    }

    createRoot(element).render(getComponent(component, componentProps));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initializeComponents.map(item => renderComponent(item))
});

function isValidJSON(jsonString: string) {
  try {
    JSON.parse(jsonString);
    return true;
  } catch (error) {
    return false;
  }
}

/**
 * Define global functions
 */
declare global {
  interface Window {
    getComponent: (componentName: string, params: Object) => React.JSX.Element;
  }
}

window.getComponent = getComponent;
