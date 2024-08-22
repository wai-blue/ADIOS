import React from 'react'
import ImageUploading, { ImageType } from 'react-images-uploading';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'
import { WithContext as ReactTags } from 'react-tag-input';
import request from "../Request";
import { ProgressBar } from 'primereact/progressbar';
import Swal, { SweetAlertOptions } from 'sweetalert2';

import './../../Assets/Css/Components/Inputs/Tags.css';
import {capitalizeFirstLetter} from "../Helper";
import Notification from "../Notification";

interface TagBadge {
  id: string,
  name: string,
  className: string
}

interface TagsInputProps extends InputProps {
  dataKey?: string,
  model?: string,
  recordId?: number,
  enableDelete?: boolean,
  enableAdd?: boolean,
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

    if (this.props.enableAdd ?? true) {
      setTimeout(() => {
        const tagInput = document.querySelector('.ReactTags__tagInput') as HTMLElement | null;
        if (tagInput) tagInput.remove();
      }, 500);
    }
  }

  componentDidUpdate(prevProps: TagsInputProps) {
    if (prevProps.recordId != this.props.recordId) {
      this.loadData();
    }
  }

  loadData() {
    request.get(
      'components/inputs/tags/data',
      {
        model: this.props.model,
        junction: this.props.params.junction,
        __IS_AJAX__: '1',
        id: this.props.recordId
      },
      (data: any) => {
        const tmpTags: Array<any> = data.data;
        const selected: Array<number> = data.selected;

        let tags: Array<TagBadge> = [];

        tmpTags.map((item: any) => {
          const tagBadge: TagBadge = {
            id: item.id + '',
            name: item.name,
            className: (selected.includes(item.id) ? " ReactTags__active" : "")
              + (this.state.readonly ? " ReactTags__disabled" : "")
          };

          tags.push(tagBadge);
          //else if (this.props.params['addNewTags'] != undefined) tagInput['className'] += ' ReactTags__not_removable'
        });

        this.onChange(selected);

        this.setState({
          isInitialized: true,
          tags: tags
        }, () => {
          if (this.props.enableDelete ?? true) {
            const removeButtons = document.querySelectorAll('.ReactTags__remove');
            removeButtons.forEach((button: Element) => {
              (button as HTMLElement).remove();
            });
          }
        });
      }
    );
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
        id: this.props.recordId,
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
    if (this.state.readonly) return;

    const tmpTagId: number = parseInt(this.state.tags[tagIndex].id);
    let value: Array<number> = this.state.value;
    let className: string = "";

    // Insert or delete
    if (value.includes(tmpTagId)) {
      value.splice(value.indexOf(tmpTagId), 1);
    } else {
      value.push(tmpTagId);
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

    return (
      <ReactTags
        tags={this.state.tags}
        labelField={this.props.params.dataKey}
        handleDelete={(tagIndex: number) => this.onTagDelete(tagIndex)}
        handleTagClick={(tagIndex: number) => this.onTagClick(tagIndex)}
        handleAddition={(tag: any) => this.onTagAdd(tag)}
        allowDragDrop={false}
        inputFieldPosition="bottom"
        allowDeleteFromEmptyInput={false}
        autocomplete
        readOnly={this.state.readonly}
        allowUnique={false}
        editable={false}
      />
    );
  } 
}
