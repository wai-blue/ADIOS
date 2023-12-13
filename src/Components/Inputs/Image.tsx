import React, { Component } from 'react'
import ImageUploading from 'react-images-uploading';

interface ImageInputProps {
  parentForm: any,
  columnName: string
}

interface ImageInputState {
  images: Array<any>
}

export default class Image extends Component<ImageInputProps> {
  state: ImageInputState;

  constructor(props: ImageInputProps) {
    super(props);

    this.state = {
      images: []
    }
  }

 onChange = (images: Array<any>, addUpdateIndex: any) => {
    // data for submit
    console.log(images);

    this.setState({
      images: images
    })
  };

  render() {
    return (
      <ImageUploading
        multiple
        value={this.state.images}
        onChange={this.onChange}
        maxNumber={1}
        dataURLKey="data_url"
      >
        {({
          imageList,
          onImageUpload,
          onImageRemoveAll,
          onImageUpdate,
          onImageRemove,
          isDragging,
          dragProps,
        }) => (
          // write your building UI
          <div className="upload__image-wrapper">
            {this.state.images.length == 0 ? (
              <button
                className="btn btn-light btn-sm"
                style={isDragging ? { color: 'red' } : undefined}
                onClick={onImageUpload}
                {...dragProps}
              >
                Vybrať obrázok
              </button>
            ) : ''}

            {imageList.map((image, index) => (
              <div key={index} className="image-item">
                <img src={image['data_url']} alt="" width="100" />
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
