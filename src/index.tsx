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

const renderComponent = (component: string) => {
  const allComponentsWithSameId = document.querySelectorAll('#' + component + '-component');

  allComponentsWithSameId.forEach((element, index) => {
    let componentProps: Object = {};

    for (let i = 0;i < element.attributes.length;i++) {
      componentProps[element.attributes[i].name] = element.attributes[i].value;   
    }

    createRoot(element).render(getComponent(component, componentProps));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initializeComponents.map(item => renderComponent(item))
});

