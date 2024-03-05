import React from 'react'
import ImageUploading, { ImageType } from 'react-images-uploading';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'
import { WithContext as ReactTags } from 'react-tag-input';
import request from "../Request";
import { ProgressBar } from 'primereact/progressbar';

import './../Css/Inputs/Tags.css';
import {capitalizeFirstLetter} from "../Helper";
import Notification from "../Notification";

interface TagBadge {
  id: string,
  name: string,
  className: string
}

interface TagsInputProps extends InputProps {
  dataKey?: string,
  model?: string
}

interface TagsInputState extends InputState {
  tags: Array<TagBadge>,
  value: Array<number>
}

export default class Tags extends Input<TagsInputProps, TagsInputState> {
  static defaultProps = {
    inputClassName: 'tags',
    id: uuid.v4(),
  }

  constructor(props: TagsInputProps) {
    super(props);

    this.state = {
      ...this.state, // Parent state
      tags: [],
      value: []
    };
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    request.get(
      'components/inputs/tags/Data',
      {
        model: this.props.model,
        junction: this.props.params.junction,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        const tmpTags: Array<any> = data.data;
        let tags: Array<TagBadge> = [];

        tmpTags.map((item: any) => {
          const tagBadge: TagBadge = {
            id: item.id + '',
            name: item.name,
            className: (this.isTagSelected(item.id) ? " ReactTags__active" : "")
              + (this.props.readonly ? " ReactTags__disabled" : "")
          };

          tags.push(tagBadge);
          //else if (this.props.params['addNewTags'] != undefined) tagInput['className'] += ' ReactTags__not_removable'
        });

        this.setState({
          isInitialized: true,
          tags: tags
        });
      }
    );
  }

  handleAddition = (tag: object, input: {all: object, values: object}) => {
    //const model = this.props.parentForm.props.model + capitalizeFirstLetter(this.props.parentForm.state.columns[this.props.columnName].relationship).slice(0, -1);
    //let tagInput = {}; tagInput[this.props.dataKey] = tag[this.props.dataKey];
    ////@ts-ignore
    //request.post(
    //  'components/form/onsave',
    //  {
    //    inputs: tagInput
    //  },
    //  {
    //    model: model,
    //    __IS_AJAX__: '1',
    //  },
    //  () => {
    //    this.props.parentForm.fetchColumnData(this.props.columnName);
    //  }
    //);

    //this.props.parentForm.inputOnChangeRaw(this.props.columnName, input);
  };

  isTagSelected(idItem: number): boolean {
    return this.state.value.includes(idItem);
  }

  onTagAdd(tag: any) {
    let currentTags: Array<TagBadge> = this.state.tags;

    request.get(
      'components/inputs/tags/Add',
      {
        model: this.props.model,
        junction: this.props.params.junction,
        __IS_AJAX__: '1',
      },
      (data: any) => {
        //currentTags.push({
        //  id: "xxxx",
        //  name: tag[this.props.dataKey ?? 'name'],
        //  className: ""
        //});
      }
    );
  }

  onTagDelete(tagIndex: number) {
    let value: Array<any> = this.state.value;
    value.splice(tagIndex, 1);
    this.onChange(value);

    let currentTags: Array<TagBadge> = this.state.tags;
    currentTags[tagIndex].className = "";

    this.setState({
      tags: currentTags
    });
  }

  onTagClick(tagIndex: number) {
    const tmpTag = this.state.tags[tagIndex];
    let value: Array<number> = this.state.value;
    value.push(parseInt(tmpTag.id));
    this.onChange(value);

    let currentTags: Array<TagBadge> = this.state.tags;
    currentTags[tagIndex].className = " ReactTags__active";

    this.setState({
      tags: currentTags
    });
  }

  renderInputElement() {
    if (!this.state.isInitialized) {
      return <ProgressBar mode="indeterminate" style={{ height: '3px' }}></ProgressBar>;
    }

    let suggestions = [];

    console.log(this.state.tags);
    return (
      <ReactTags
        tags={this.state.tags}
        suggestions={suggestions}
        labelField={this.props.params.dataKey}
        handleDelete={(tagIndex: number) => this.props.params.readonly || this.props.params['addNewTags'] != undefined ? undefined : this.onTagDelete(tagIndex)}
        handleTagClick={(tagIndex: number) => this.onTagClick(tagIndex)}
        handleAddition={(tag: any) => this.props.params.readonly || this.props.params['addNewTags'] != undefined ? undefined : this.onTagAdd(tag)}
        allowDragDrop={false}
        //handleTagClick={(i) => this.props.params.readonly ? undefined : this.handleTagClick(i, params)}
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
        //readOnly={this.props.params['addNewTags'] != undefined || this.props.params.readonly}
      />
    );
  } 
}
