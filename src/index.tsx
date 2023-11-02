import { createRoot } from "react-dom/client";
import React from "react";

import Form from "./Core/Views/React/Form";
import Example from "./Core/Views/React/Example";

const initializeComponents = [
  'form',
  'example'
];

const getComponent = (componentName: string, params: Object) => {
  let components: any = {
    form: <Form {...params} />,
    example: <Example {...params} />
  }; 

  return components[componentName];
};

declare global {
  interface Window {
    getComponent: (componentName: string, params: Object) => React.JSX.Element;
  }
}

window.getComponent = getComponent;

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

