import React, { Component } from 'react'
import { WithContext as ReactTags } from 'react-tag-input';

import './../Css/Inputs/Tags.css';

interface TagsInputProps {
  parentForm: any,
  columnName: string
}

export default class Tags extends Component<TagsInputProps> {
  state: any;

  constructor(props: TagsInputProps) {
    super(props);

    this.state = {
      tags: [
        { id: 'Thailand', text: 'Thailand' },
        { id: 'India', text: 'India' },
        { id: 'Vietnam', text: 'Vietnam' },
        { id: 'Turkey', text: 'Turkey' }
      ]
    }
  }

  handleDelete = (tagIndex: string) => {
    let newTags: Array<any> = this.state.tags.filter((_, index) => index !== tagIndex);

    this.setState({
      tags: newTags
    });
  };

  handleAddition = (tag: {id: string, text: string}) => {
    this.setState({
      tags: [...this.state.tags, tag]
    });

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, JSON.stringify(this.state.tags));
  };

  handleDrag = (tag: {id: string, text: string}, currPos: number, newPos: number) => {
    let newTags: Array<any> = this.state.tags.slice();

    newTags.splice(currPos, 1);
    newTags.splice(newPos, 0, tag);

    this.setState({
      tags: newTags
    });
  };

  handleTagClick = index => {
    console.log('The tag at index ' + index + ' was clicked');
  };

  render() {
    return (
      <ReactTags
        tags={this.state.tags}
        //suggestions={this.state.suggestions}
        //delimiters={this.state.delimiters}
        handleDelete={this.handleDelete}
        handleAddition={this.handleAddition}
        handleDrag={this.handleDrag}
        handleTagClick={this.handleTagClick}
        inputFieldPosition="bottom"
        autocomplete
      />
    );
  } 
}
