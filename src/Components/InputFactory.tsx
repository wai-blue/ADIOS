import React, { Component } from 'react'

import ReactQuill, {Value} from 'react-quill';
import 'react-quill/dist/quill.snow.css';

import InputLookup from "./Inputs/Lookup";
import InputVarchar from "./Inputs/Varchar";
import InputPassword from "./Inputs/Password";
import InputTextarea from "./Inputs/Textarea";
import InputInt from "./Inputs/Int";
import InputBoolean from "./Inputs/Boolean";
import InputColor from "./Inputs/Color";
import InputImage from "./Inputs/Image";
import InputTags from "./Inputs/Tags";
import InputDateTime from "./Inputs/DateTime";
import InputEnumValues from "./Inputs/EnumValues";

export function InputFactory(inputProps: any): JSX.Element {
  let inputToRender: JSX.Element = <></>;
  if (inputProps.params.enumValues) {
    inputToRender = <InputEnumValues {...inputProps} enumValues={inputProps.params.enumValues} enumCssClasses={inputProps.params.enumCssClasses}/>
  } else {
    if (typeof inputProps.params.inputJSX === 'string' && inputProps.params.inputJSX !== '') {
      inputToRender = globalThis.app.renderReactElement(inputProps.params.inputJSX, inputProps) ?? <></>;
    } else {
      switch (inputProps.params.type) {
        case 'varchar': inputToRender = <InputVarchar {...inputProps} />; break;
        case 'password': inputToRender = <InputPassword {...inputProps} />; break;
        case 'text': inputToRender = <InputTextarea {...inputProps} />; break;
        case 'float': case 'int': inputToRender = <InputInt {...inputProps} />; break;
        case 'boolean': inputToRender = <InputBoolean {...inputProps} />; break;
        case 'lookup': inputToRender = <InputLookup {...inputProps} />; break;
        case 'color': inputToRender = <InputColor {...inputProps} />; break;
        case 'tags': inputToRender = <InputTags {...inputProps} model={this.props.model} recordId={this.state.id} />; break;
        case 'image': inputToRender = <InputImage {...inputProps} />; break;
        case 'datetime': case 'date': case 'time': inputToRender = <InputDateTime {...inputProps} type={inputProps.params.type} />; break;
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
