import React, { Component } from 'react'
import * as uuid from 'uuid';
import Form from './Form';

import ReactQuill, {Value} from 'react-quill';
import 'react-quill/dist/quill.snow.css';

import InputLookup from "./Inputs/Lookup";
import InputVarchar from "./Inputs/Varchar";
import InputTextarea from "./Inputs/Textarea";
import InputInt from "./Inputs/Int";
import InputBool from "./Inputs/Bool";
import InputBoolean from "./Inputs/Boolean";
import InputMapPoint from "./Inputs/MapPoint";
import InputColor from "./Inputs/Color";
import InputImage from "./Inputs/Image";
import InputTags from "./Inputs/Tags";
import InputDateTime from "./Inputs/DateTime";
import InputEnumValues from "./Inputs/EnumValues";

export function InputFactory(inputProps: any): JSX.Element {
  let inputToRender: JSX.Element = <></>;
  if (inputProps.params.enumValues) {
    inputToRender = <InputEnumValues {...inputProps} enumValues={inputProps.params.enumValues}/>
  } else {
    if (typeof inputProps.params.inputJSX === 'string' && inputProps.params.inputJSX !== '') {
      inputToRender = globalThis.app.getComponent(inputProps.params.inputJSX, inputProps) ?? <></>;
    } else {
      switch (inputProps.params.type) {
        case 'text': inputToRender = <InputTextarea {...inputProps} />; break;
        case 'float': case 'int': inputToRender = <InputInt {...inputProps} />; break;
        case 'bool': inputToRender = <InputBool {...inputProps} />; break;
        case 'boolean': inputToRender = <InputBoolean {...inputProps} />; break;
        case 'lookup': inputToRender = <InputLookup {...inputProps} />; break;
        case 'color': inputToRender = <InputColor {...inputProps} />; break;
        case 'tags': inputToRender = <InputTags {...inputProps} model={this.props.model} formId={this.state.id} />; break;
        case 'image': inputToRender = <InputImage {...inputProps} />; break;
        case 'datetime': case 'date': case 'time': inputToRender = <InputDateTime {...inputProps} type={inputProps.params.type} />; break;
        //case 'MapPoint':
        //  inputToRender = <InputMapPoint {...inputProps} />;
        //  break;
        case 'editor':
          inputToRender = (
            <div
              className={'h-100 form-control ' + `${this.state.invalidInputs[inputProps.columnName] ? 'is-invalid' : 'border-0'}`}>
              <ReactQuill
                theme="snow"
                value={this.state.data[inputProps.columnName] as Value}
                onChange={(value) => this.inputOnChangeRaw(inputProps.columnName, value)}
                className="w-100"
              />
            </div>
          );
          break;
        default: inputToRender = <InputVarchar {...inputProps} />;
      }
    }
  }

  return inputToRender;
}
