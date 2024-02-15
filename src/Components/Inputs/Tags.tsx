import React, { Component } from 'react'
import { WithContext as ReactTags } from 'react-tag-input';
import request from "../Request";

import './../Css/Inputs/Tags.css';
import {capitalizeFirstLetter} from "../Helper";
import Notification from "../Notification";

interface TagsInputProps {
  parentForm: any,
  columnName: string,
  params: any,
  dataKey: string,
}

export default class Tags extends Component<TagsInputProps> {

  constructor(props: TagsInputProps) {
    super(props);

    this.props.params.addNewTags = true;
  }

  handleDelete = (index: number, input: {all: object, values: object}) => {
    const tag = input['all'][index];
    const tagIndex = input['values'].findIndex((t) => t[this.props.dataKey] === tag[this.props.dataKey]);
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

  handleAddition = (tag: object, input: {all: object, values: object}) => {
    const model = this.props.parentForm.props.model + capitalizeFirstLetter(this.props.parentForm.state.columns[this.props.columnName].relationship).slice(0, -1);
    let tagInput = {}; tagInput[this.props.dataKey] = tag[this.props.dataKey];
    //@ts-ignore
    request.post(
      'components/form/onsave',
      {
        inputs: tagInput
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

  handleTagClick = (index: string, input: { all: object, values: object }) => {
    const tag = input['all'][index];
    const tagIndex = input['values'].findIndex((t) => t[this.props.dataKey] === tag[this.props.dataKey]);
    const tagInput = {id: tag.id}; tagInput[this.props.dataKey] = tag[this.props.dataKey];
    if (tagIndex === -1) input['values'].push(tagInput);
    else input['values'].splice(tagIndex, 1);

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
  };

  render() {
    const params = this.props.parentForm.state.inputs[this.props.columnName] ?? {all: [], values: []};

    let tags = [];
    let suggestions = [];

    params['all'].forEach((role) => {
      let suggestionsInput = {id: role[this.props.dataKey], db_id: role.id};
      suggestionsInput[this.props.dataKey] = role[this.props.dataKey];
      suggestions.push(suggestionsInput);
      let tagInput = {id: role[this.props.dataKey]}; tagInput[this.props.dataKey] = role[this.props.dataKey]
      if (params['values'].find((r) => r[this.props.dataKey] === role[this.props.dataKey]) !== undefined) {
        tagInput['className'] = "ReactTags__active";
      }
      if (this.props.params.readonly) tagInput['className'] += ' ReactTags__disabled'
      else if (this.props.params['addNewTags'] != undefined) tagInput['className'] += ' ReactTags__not_removable'
      tags.push(tagInput);
    });

    return (
      <ReactTags
        tags={tags}
        suggestions={suggestions}
        labelField={this.props.dataKey}
        handleDelete={(tag) => this.props.params.readonly || this.props.params['addNewTags'] != undefined ? undefined : this.handleDelete(tag, params)}
        handleAddition={(tag) => this.props.params.readonly || this.props.params['addNewTags'] != undefined ? undefined : this.handleAddition(tag, params)}
        allowDragDrop={false}
        handleTagClick={(i) => this.props.params.readonly ? undefined : this.handleTagClick(i, params)}
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
        readOnly={this.props.params['addNewTags'] != undefined || this.props.params.readonly}
      />
    );
  }
}
