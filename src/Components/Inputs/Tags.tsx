import React from 'react'
import ImageUploading, { ImageType } from 'react-images-uploading';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'
import { WithContext as ReactTags } from 'react-tag-input';
import request from "../Request";
import { ProgressBar } from 'primereact/progressbar';
import Swal, { SweetAlertOptions } from 'sweetalert2';

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
      'components/inputs/tags/data',
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
    let postData: any = {};

    if (this.props.params.dataKey) {
      postData['dataKey'] = this.props.params.dataKey;
      postData[this.props.params.dataKey] = tag[this.props.params.dataKey];
    }

    request.post(
      'components/inputs/tags/add',
      postData,
      {
        model: this.props.model,
        junction: this.props.params.junction,
        __IS_AJAX__: '1',
      },
      () => {
        Notification.success("Tag pridaný");
        this.loadData();
      }
    );
  }

  onTagDelete(tagIndex: number) {
    // @ts-ignore
    Swal.fire({
      title: 'Ste si istý?',
      html: 'Ste si istý, že chcete vymazať tento tag?',
      icon: 'question',
      showCancelButton: true,
      cancelButtonText: 'Nie',
      confirmButtonText: 'Áno',
      confirmButtonColor: '#dc4c64',
      reverseButtons: false,
    } as SweetAlertOptions).then((result) => {
      if (result.isConfirmed) {
        let id: number = parseInt(this.state.tags[tagIndex].id);
        request.delete(
          'components/inputs/tags/delete',
          {
            model: this.props.model,
            junction: this.props.params.junction,
            __IS_AJAX__: '1',
            id: id
          },
          () => {
            Notification.success("Tag zmazaný");
            this.loadData();
          }
        );
      }
    })
  }

  onTagClick(tagIndex: number) {
    const tmpTag = this.state.tags[tagIndex];
    let value: Array<number> = this.state.value;
    let className: string = "";

    // Insert or delete
    if (value.includes(parseInt(tmpTag.id))) {
      value.splice(tagIndex, 1);
    } else {
      value.push(parseInt(tmpTag.id));
      className = " ReactTags__active";
    }

    let currentTags: Array<TagBadge> = this.state.tags;
    currentTags[tagIndex].className = className;

    this.onChange(value);
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
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
        //readOnly={this.props.params['addNewTags'] != undefined || this.props.params.readonly}
      />
    );
  } 
}
