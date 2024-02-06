import React, { Component } from 'react'
import { WithContext as ReactTags } from 'react-tag-input';

import './../Css/Inputs/Tags.css';

interface TagsInputProps {
  parentForm: any,
  columnName: string,
  params: any
}

export default class Tags extends Component<TagsInputProps> {

  constructor(props: TagsInputProps) {
    super(props);
  }

  handleDelete = (tagIndex: string) => {
    console.log('remove tag ' + tagIndex);
  };

  handleAddition = (tag: {id: string, text: string}) => {
    console.log('add tag ' + tag);
  };

  handleDrag = (tag: {id: string, text: string}, currPos: number, newPos: number) => {
    console.log('drag tag' + tag)
  };

  handleTagClick = (index: string, input: { all: object, values: object }) => {
    const tag = input['all'][index];
    const tagIndex = input['values'].findIndex((t) => t.name === tag.name);

    if (tagIndex === -1) input['values'].push({id: undefined, name: tag.name});
    else input['values'].splice(tagIndex, 1);

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
  };

  render() {
    const params = this.props.parentForm.state.inputs[this.props.columnName] ?? {all: [], values: []};

    let tags = [];
    let suggestions = params['all'];

    suggestions.forEach((role) => {
      role.id = role.name;
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
        handleDelete={this.handleDelete}
        handleAddition={this.handleAddition}
        handleDrag={this.handleDrag}
        handleTagClick={(i) => this.handleTagClick(i, params)}
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
      />
    );
  }
}
