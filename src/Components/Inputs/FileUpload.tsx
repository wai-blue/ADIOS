import React, { RefObject, createRef } from "react";
import { FileUpload as FileUploadPrime, FileUploadErrorEvent, FileUploadUploadEvent, FileUploadRemoveEvent } from 'primereact/fileupload';
import Notification from "./../Notification";
import * as uuid from 'uuid';
import { Input, InputProps, InputState } from '../Input'
import Swal from "sweetalert2";
import request from "../Request";
import { adiosError } from "../Helper";

interface FileUploadInputProps extends InputProps {
  uid: string,
  folderPath?: string,
  renamePattern?: string,
  multiselect?: boolean,
  accept?: string
}

interface UploadedFile {
  fullPath: string
}

interface FileUploadInputState extends InputState {
  endpoint: string,
  files: Array<string>
}

interface UploadResponse {
  status: string,
  message: string,
  uploadedFiles: Array<UploadedFile>
}

export default class FileUpload extends Input<FileUploadInputProps, FileUploadInputState> {
  fileUploadPrimeRef: RefObject<FileUploadPrime>;

  static defaultProps = {
    inputClassName: 'file-upload',
    id: uuid.v4(),
  }

  constructor(props: FileUploadInputProps) {
    super(props);

    let files: Array<string> = [];

    if (props.value && props.multiselect !== false) {
      if (Array.isArray(props.value)) files = props.value;
      else adiosError("Multiselect value must be type of Array");
    } else if (props.value) files.push(props.value);

    this.state = {
      ...this.state,
      files: files,
      endpoint: globalThis.app.config.url + '/components/inputs/fileupload/upload?__IS_AJAX__=1'
        + (props.folderPath ? '&folderPath=' + props.folderPath : '')
        + (props.renamePattern ? '&renamePattern=' + props.renamePattern : '')
        + (props.accept ? '&accept=' + props.accept : '')
    };

    this.fileUploadPrimeRef = createRef<FileUploadPrime>();
  }

  onSuccess(event: FileUploadUploadEvent) {
    let response: UploadResponse = JSON.parse(event.xhr.response);
    Notification.success(response.message);

    let uploadedFilesPaths: Array<string> = this.state.files;
    if (this.props.multiselect === false && this.state.files.length > 0) {
      uploadedFilesPaths = [];
      const currentFileToDelete = this.state.files.pop();
      if (currentFileToDelete) {
        request.delete(
          'components/inputs/fileupload/delete',
          { fileFullPath: currentFileToDelete }
        );
      }
    }

    response.uploadedFiles.map((file: UploadedFile) => uploadedFilesPaths.push(file.fullPath));

    this.setState({
      files: uploadedFilesPaths
    });

    this.onChange(uploadedFilesPaths);
    this.clearUploadFiles();
  }

  onError(event: FileUploadErrorEvent) {
    let response: UploadResponse = JSON.parse(event.xhr.response);
    Notification.error(response.message);
    this.clearUploadFiles();
  }

  onDelete(fileFullPath: string) {
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        request.delete(
          'components/inputs/fileupload/delete',
          {
            fileFullPath: fileFullPath
          },
          () => {
            Notification.success("File deleted");
            this.deleteFileFromFiles(fileFullPath);
          }
        );
      }
    });
  }

  deleteFileFromFiles(fileFullPathToDelete: string) {
    const updatedFiles = this.state.files.filter((filePath: string) => filePath != fileFullPathToDelete);
    this.setState({
      files: updatedFiles
    });
  }

  onUploadedImageClick(fileFullPath: string) {
    Swal.fire({
      imageUrl: globalThis.app.config.url + '/upload/' + fileFullPath,
      imageAlt: 'Image',
      showConfirmButton: false
    });
  };

 onUploadedFileClick(fileFullPath: string) {
    Swal.fire({
      html: `<iframe src="${globalThis._APP_UPLOAD_URL}/${fileFullPath}" width="100%" height="750px" frameborder="0"></iframe>`,
      width: '80%',
      heightAuto: false,
      showCloseButton: true,
      showConfirmButton: false,
      scrollbarPadding: false
    });
}

  clearUploadFiles() {
    setTimeout(() => {
      this.fileUploadPrimeRef.current?.clear();
    }, 100);
  }

  getFileExtension(fileName: string): string|null {
    const extenstion = fileName.split('.').pop();
    if (extenstion == undefined) return null;
    return extenstion.toLowerCase();
  };

  getFileName(fileFullPath: string): string {
    if (!this.props.folderPath) return fileFullPath;

    const lastIndex = fileFullPath.lastIndexOf(this.props.folderPath);
    return fileFullPath.slice(lastIndex + this.props.folderPath.length + 1);
  }

  renderFontAwesomeIcon(icon: string, fileFullPath: string) {
    return <div style={{ textAlign: 'center' }}>
      <i
        className={icon}
        onClick={() => this.onUploadedFileClick(fileFullPath)}
        style={{ fontSize: '45px', cursor: 'pointer' }}
      />
      <label className="m-0 p-0 mt-1" style={{ fontSize: '10px', display: 'block' }}>
        {this.getFileName(fileFullPath)}
      </label>
    </div>;
  }

  renderFileIcon(fileFullPath: string): JSX.Element {
    const extension = this.getFileExtension(fileFullPath);

    switch (extension) {
      case 'pdf':
        return this.renderFontAwesomeIcon("fas fa-file-pdf", fileFullPath);
      case 'doc':
      case 'docx':
        return this.renderFontAwesomeIcon("fas fa-file-word", fileFullPath);
      case 'jpg':
      case 'jpeg':
      case 'png':
        return <img
          className="img-fluid border border-gray"
          src={globalThis._APP_UPLOAD_URL + '/' + fileFullPath}
          alt={`Image ${fileFullPath}`}
          onClick={() => this.onUploadedImageClick(fileFullPath)}
          style={{ height: '65px', width: '65px', cursor: 'pointer' }}
        />
      default:
        return this.renderFontAwesomeIcon("fas fa-file", fileFullPath);
    }
  }

  serialize(): string {
    if (this.props.multiselect === false) return this.state.value ? this.state.value.toString() : '';
    return JSON.stringify(this.state.files);
  }

  renderInputElement() {
    return (
      <div 
        id={"adios-title-" + this.props.uid}
        className="adios component file-upload"
      >
        {this.state.files.length > 0 && (
          <div className="d-flex flex-wrap">
            {this.state.files.map((fileFullPath: string, index: number) => (
              <div key={index} className="d-flex flex-column align-items-center m-2">
                {this.renderFileIcon(fileFullPath)}
                <button
                  className="btn btn-sm btn-danger m-2"
                  onClick={() => this.onDelete(fileFullPath)}
                >
                  <i className="fas fa-trash"></i>
                </button>
              </div>
            ))}
          </div>
        )}
        <div className="card">
          <FileUploadPrime
            ref={this.fileUploadPrimeRef}
            name="upload[]"
            auto={true}
            multiple={this.props.multiselect ?? true}
            url={this.state.endpoint}
            onUpload={(event: FileUploadUploadEvent) => this.onSuccess(event)}
            onError={(event: FileUploadErrorEvent) => this.onError(event)}
            accept={this.props.accept}
            maxFileSize={1000000}
            emptyTemplate={<p className="m-0">Drag and drop files to here to upload.</p>} />
        </div>
      </div>
    );
  }
}
