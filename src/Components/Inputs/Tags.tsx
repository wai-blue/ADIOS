import React, { Component } from 'react'
import { WithContext as ReactTags } from 'react-tag-input';
import request from "../Request";

import './../Css/Inputs/Tags.css';
import {capitalizeFirstLetter} from "../Helper";
import Notification from "../Notification";

interface TagsInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Tags extends Component<TagsInputProps> {

  constructor(props: TagsInputProps) {
    super(props);
  }

  handleDelete = (index: number, input: {all: object, values: object}) => {
    const tag = input['all'][index];
    const tagIndex = input['values'].findIndex((t) => t.name === tag.name);
    const model = this.props.parentForm.props.model + capitalizeFirstLetter(this.props.parentForm.state.columns[this.props.columnName].relationship).slice(0, -1);

    request.delete(
      'components/form/ondelete',
      {
        model: model,
        id: tag.id,
        __IS_AJAX__: '1',
      },
      () => {
        if (tagIndex !== -1) input['values'].splice(tagIndex, 1);
        input['all'].splice(index, 1);
        this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
      },
      () => {
        console.log("Tento tag sa nedá zmazať, pretože ešte prislúcha iným modelom.");
      }
    );
  };

  handleAddition = (tag: {id: string, name: string}, input: {all: object, values: object}) => {
    const model = this.props.parentForm.props.model + capitalizeFirstLetter(this.props.parentForm.state.columns[this.props.columnName].relationship).slice(0, -1);

    //@ts-ignore
    request.post(
      'components/form/onsave',
      {
        inputs: {name: tag.name}
      },
      {
        model: model,
        __IS_AJAX__: '1',
      },
      () => {
        this.props.parentForm.fetchColumnData(this.props.columnName);
      }
    );

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
  };

  handleDrag = (tag: {id: string, text: string}, currPos: number, newPos: number) => {
    console.log('drag tag' + tag)
  };

  handleTagClick = (index: string, input: { all: object, values: object }) => {
    const tag = input['all'][index];
    const tagIndex = input['values'].findIndex((t) => t.name === tag.name);

    if (tagIndex === -1) input['values'].push({id: tag.id, name: tag.name});
    else input['values'].splice(tagIndex, 1);

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
  };

  render() {
    const params = this.props.parentForm.state.inputs[this.props.columnName] ?? {all: [], values: []};

    let tags = [];
    let suggestions = [];

    params['all'].forEach((role) => {
      suggestions.push({id: role.name, name: role.name, db_id: role.id});
      if (params['values'].find((r) => r.name === role.name) !== undefined) {
        tags.push({id: role.name, name: role.name, className: "ReactTags__active"});
      } else {
        tags.push({id: role.name, name: role.name, className: ""});
      }
    });

    return (
      <ReactTags
        tags={tags}
        suggestions={suggestions}
        labelField={'name'}
        //delimiters={this.state.delimiters}
        handleDelete={(tag) => this.handleDelete(tag, params)}
        handleAddition={(tag) => this.handleAddition(tag, params)}
        handleDrag={this.handleDrag}
        handleTagClick={(i) => this.handleTagClick(i, params)}
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
      />
    );
  }
}
