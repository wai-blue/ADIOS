import { createRoot } from "react-dom/client";
import { createPortal } from "react-dom";
import React from "react";

import Form from "./Core/Views/React/Form";
import Example from "./Core/Views/React/Example";

const components = {
  form: <Form />,
  example: <Example />
};

const renderComponent = (component: string) => {
  const componentElement = document.getElementById(component + '-component') as HTMLElement;
  if (componentElement != null) createRoot(componentElement).render(components[component]);
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

