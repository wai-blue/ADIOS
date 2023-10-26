import { createRoot } from "react-dom/client";
import React from "react";

import Form from "./Core/Views/React/Form";
import Example from "./Core/Views/React/Example";

const getComponent = (componentName: string, params: Object) => {
  let components: any = {
    form: <Form {...params}/>,
    example: <Example />
  }; 

  return components[componentName];
};

const renderComponent = (component: string) => {
  const allComponentsWithSameId = document.querySelectorAll('#' + component + '-component');

  allComponentsWithSameId.forEach((element, index) => {
    let componentParams = element.getAttribute("params");
    let componentParamsParsed = componentParams != null 
      ? JSON.parse(componentParams)
      : {};

    createRoot(element).render(getComponent(component, componentParamsParsed));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  //const rootElement = document.getElementById('page-top') as HTMLElement;
  //const root = createRoot(rootElement);
  
  //const formComponent = document.getElementById('form-component') as HTMLElement;
  //console.log(formComponent);
  //createPortal(<Form />, formComponent);  
  //createRoot(formComponent).render(<Form />);
  
  renderComponent("form");
  renderComponent("example");
});

