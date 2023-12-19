import { createRoot } from "react-dom/client";
import React from "react";

import { v4 } from 'uuid';

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

//import FloatingModal from "./FloatingModal";

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
  'form',
  'table',
  'title',
  'button',
  'modal',
  
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
    case 'card': return <Card {...params} />;
    //@ts-ignore
    case 'title': return <Title {...params} />;
    //@ts-ignore
    case 'breadcrumbs': return <Breadcrumbs {...params} />;
    //@ts-ignore
    case 'button': return <Button {...params} />;
    //@ts-ignore
    case 'modal': return <Modal {...params} ></Modal>;

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

    let componentBuildElement = createRoot(element);
    componentBuildElement.render(getComponent(component, componentProps));
  });
}

const renderComponents = () => {
  //document.addEventListener('DOMContentLoaded', () => {
  const renderedComponents = initializeComponents.map(item => renderComponent(item))
  //});
}

//const reRenderComponents = () => {
  //console.log(renderedComponents);
  //@ts-ignore
  //renderedComponents.map(item => item.render());
//}

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
 * Define global functions
 */
declare global {
  interface Window {
    //renderComponent: (componentName: string) => void;
    getComponent: (componentName: string, params: Object) => React.JSX.Element;
    adiosModal: (controllerUrl: string) => void,
    _APP_URL: string;
  }
}

window.getComponent = getComponent;
//window.renderComponent = renderComponent;

/*
  * Preskumat moznosti ako znovu vyrenderovat uz niekde renderovane komponenty
  * V tejto funkcii sa predpoklada, ze adios cache je nacitana a tak isto pripnuty bootstrap.js pre modal
  * #adios-modal-global sa vytvara v Desktop.twig
  * Nasledne sa meni iba kontent tohto modalo #adios-modal-body-global
  */
window.adiosModal = (controllerUrl: string) => {
  //@ts-ignore
 _ajax_update(
    controllerUrl,
    {},
    'adios-modal-body-global',
    {
      success: () => {
        //@ts-ignore
        $('#adios-modal-global').modal();
        renderComponents();
      }
    }
  );
}
