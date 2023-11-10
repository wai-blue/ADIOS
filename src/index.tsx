import { createRoot } from "react-dom/client";
import React from "react";

/**
 * Components
 */
import Form from "./Components/Form";
import Example from "./Components/Example";
import Table from "./Components/Table";

/**
* Initialize ADIOS components
*/
const initializeComponents = [
  'form',
  'table',
  'example'
];

/**
* Get specific ADIOS component with destructed params 
*/
const getComponent = (componentName: string, params: Object) => {
  switch (componentName) {
    //@ts-ignore
    case 'form': return <Form {...params} />;
    case 'table': return <Table {...params} />;
    case 'example': return <Example {...params} />;
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

