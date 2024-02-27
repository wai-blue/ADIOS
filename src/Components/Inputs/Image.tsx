import React from 'react'
import ImageUploading, { ImageType } from 'react-images-uploading';
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'

interface ImageInputState extends InputState {
  images: Array<any>
}

export default class Image extends Input<InputProps, ImageInputState> {
  static defaultProps = {
    inputClassName: 'image',
    id: uuid.v4(),
  }

  constructor(props: InputProps) {
    super(props);

    this.state = {
      ...this.state, // Parent state
      images: [],
    };
  }

  onImageChange(images: Array<ImageType>, addUpdateIndex: any) {
    let image: any = images[0];

    this.onChange({
      fileName: image ? image.file.name : null,
      fileData: image ? image.fileData : null
      //fileSize: image.file.size,
      //fileType: image.file.type,
    });

    this.setState({
      images: images
    })
  };

  renderInputElement() {
    return (
      <ImageUploading
        value={this.state.value && this.state.value['fileData'] != null
          ? [this.state.value]
          : []
        }
        onChange={(images: Array<ImageType>, addUpdateIndex: any) => this.onImageChange(images, addUpdateIndex)}
        maxNumber={1}
        dataURLKey="fileData"
      >
        {({
          imageList,
          onImageUpload,
          onImageUpdate,
          onImageRemove,
          isDragging,
          dragProps,
        }) => (
            <div className="upload__image-wrapper">
              {this.state.value && this.state.value['fileData'] != null
                ? ''
                : (
                  <button
                    className="btn btn-light btn-sm"
                    style={isDragging ? { color: 'red' } : undefined}
                    onClick={onImageUpload}
                    {...dragProps}
                  >
                    Vybrať obrázok
                  </button>
                )
              }

              {imageList.map((image, index) => (
                <div key={index} className="image-item">
                  <img src={image['fileData']} alt="" width="100" />
                  <div className="image-item__btn-wrapper text-left">
                    <button 
                      className="btn btn-light btn-sm text-info"
                      onClick={() => onImageUpdate(index)}
                    ><i className="fas fa-exchange-alt"></i></button>
                    <button 
                      className="btn btn-light btn-sm text-danger"
                      onClick={() => onImageRemove(index)}
                    ><i className="fas fa-trash-alt"></i></button>
                  </div>
                </div>
              ))}
            </div>
          )}
      </ImageUploading>
    );
  }
}
