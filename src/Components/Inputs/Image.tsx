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
    };
  }

 onChange = (images: Array<any>, addUpdateIndex: any) => {
    let image = images[0];

    this.props.parentForm.inputOnChangeRaw(this.props.columnName, {
      fileName: image ? image.file.name : null,
      fileData: image ? image.fileData : null
      //fileSize: image.file.size,
      //fileType: image.file.type,
    });

    this.setState({
      images: images
    })
  };

  render() {
    return (
      <ImageUploading
        value={
          this.props.parentForm.state.inputs[this.props.columnName]
          && this.props.parentForm.state.inputs[this.props.columnName]['fileData'] != null
          ? [this.props.parentForm.state.inputs[this.props.columnName]]
          : []
        }
        onChange={this.onChange}
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
            {this.props.parentForm.state.inputs[this.props.columnName]
              && this.props.parentForm.state.inputs[this.props.columnName]['fileData'] != null
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
