import React, { Component } from 'react'
import * as uuid from 'uuid';
import Form from './Form';

export interface InputProps {
  uid: string,
  columnName?: string,
  params?: any,
  inputClassName?: string,
  value?: any,
  onChange?: (value: any) => void,
  readonly?: boolean,
  invalid?: boolean,
  cssClass?: string,
  placeholder?: string,
  isInitialized?: boolean,
  isInlineEditing?: boolean,
  showInlineEditingButtons?: boolean,
  onInlineEditCancel?: () => void,
  onInlineEditSave?: () => void,
  context?: any,
  parentForm?: Form<any, any>,
  children?: any,
}

export interface InputState {
  readonly: boolean,
  invalid: boolean,
  value: any,
  origValue: any,
  onChange: (value: any) => void,
  cssClass: string,
  isInitialized: boolean,
  isInlineEditing: boolean,
  showInlineEditingButtons: boolean,
}

export class Input<P extends InputProps, S extends InputState> extends Component<P, S> {
  static defaultProps = {
    inputClassName: '',
    id: uuid.v4(),
  };

  state: S;

  constructor(props: P) {
    super(props);

    globalThis.app.reactElements[this.props.uid] = this;

    const isInitialized: boolean = props.isInitialized ?? false;
    const isInlineEditing: boolean = props.isInlineEditing ?? false;
    const showInlineEditingButtons: boolean = props.showInlineEditingButtons ?? true;
    const readonly: boolean = props.readonly ?? false;
    const invalid: boolean = props.invalid ?? false;
    const value: any = props.value;
    const onChange: any = props.onChange ?? null;
    const cssClass: string = props.cssClass ?? '';

    this.state = {
      isInitialized: isInitialized,
      isInlineEditing: isInlineEditing,
      showInlineEditingButtons: showInlineEditingButtons,
      readonly: readonly,
      invalid: invalid,
      value: value,
      origValue: value,
      onChange: onChange,
      cssClass: cssClass,
    } as S;
  }

  componentDidUpdate(prevProps: any): void {
    let newState: any = {};
    let setNewState: boolean = false;

    if (this.props.isInitialized != prevProps.isInitialized) {
      newState.isInitialized = this.props.isInitialized;
      setNewState = true;
    }

    if (this.props.isInlineEditing != prevProps.isInlineEditing) {
      newState.isInlineEditing = this.props.isInlineEditing;
      setNewState = true;
    }

    if (this.props.showInlineEditingButtons != prevProps.showInlineEditingButtons) {
      newState.showInlineEditingButtons = this.props.showInlineEditingButtons;
      setNewState = true;
    }

    if (this.props.value != prevProps.value) {
      newState.value = this.props.value;
      setNewState = true;
    }

    if (this.props.cssClass != prevProps.cssClass) {
      newState.cssClass = this.props.cssClass;
      setNewState = true;
    }

    if (this.props.readonly != prevProps.readonly) {
      newState.readonly = this.props.readonly;
      setNewState = true;
    }

    if (this.props.invalid != prevProps.invalid) {
      newState.invalid = this.props.invalid;
      setNewState = true;
    }

    if (setNewState) {
      this.setState(newState);
    }
  }

  getClassName() {
    return (
      "adios component input"
      + " " + this.props.inputClassName
      + " " + (this.state.invalid ? 'invalid' : '')
      + " " + (this.state.cssClass ?? "")
      + " " + (this.state.readonly ? "bg-muted" : "")
    );
  }

  onChange(value: any) {
    this.setState({value: value}, () => {
      if (typeof this.props.onChange == 'function') {
        this.props.onChange(value);
      }
    });
  }

  serialize(): string {
    return this.state.value ? this.state.value.toString() : '';
  }

  switchToInlineEditMode() {
    if (!this.state.readonly) {
      this.setState({
        origValue: this.state.value,
        isInlineEditing: true,
      });
    }
  }

  inlineEditSave() {
    this.setState(
      {
        origValue: this.state.value,
        isInlineEditing: false
      },
      () => {
        if (this.props.onInlineEditSave) {
          this.props.onInlineEditSave()
        }
      }
    );
  }

  inlineEditCancel() {
    this.setState(
      {
        value: this.state.origValue,
        isInlineEditing: false,
      },
      () => {
        this.onChange(this.state.origValue);
        if (this.props.onInlineEditCancel) {
          this.props.onInlineEditCancel()
        }
      }
    );
  }

  renderInputElement() {
    return <input type="text" value={this.state.value}></input>;
  }

  renderValueElement() {
    let value = this.state.value + '';
    if (value == '') return <span className="no-value"></span>;
    else return this.state.value;
  }

  render() {
    return (
      <div className={this.getClassName() + (this.state.isInlineEditing ? ' editing' : '')}><div className="inner">
        {this.state.isInlineEditing
          ? <>
              <input
                id={this.props.uid}
                name={this.props.uid}
                type="hidden"
                value={this.serialize()}
                style={{width: "100%", fontSize: "0.4em"}}
                className="value bg-light"
                readOnly={true}
              ></input>
              <div className="input-element">
                {this.renderInputElement()}
              </div>
              {this.state.showInlineEditingButtons ? 
                <div className="inline-editing-buttons always-visible">
                  <button
                    className={"btn btn-success-outline"}
                    onClick={() => {
                      this.inlineEditSave();
                    }}
                  >
                    <span className="icon !py-0"><i className="fas fa-check"></i></span>
                  </button>
                  <button
                    className={"btn btn-cancel-outline"}
                    onClick={() => {
                      this.inlineEditCancel();
                    }}
                  >
                    <span className="icon !py-0"><i className="fas fa-times"></i></span>
                  </button>
                </div>
                : null
              }
          </>
          : <>
            <div
              className="value-element"
              onClick={() => {
                this.switchToInlineEditMode();
              }}>
              {this.renderValueElement()}
              <div className="input-unit">
                {this.props.params?.unit}
              </div>
            </div>
            {this.state.readonly ? null :
              <div className="inline-editing-buttons">
                <button
                  className="btn btn-transparent"
                  onClick={() => {
                    this.switchToInlineEditMode();
                  }}
                >
                  <span className="icon !py-0"><i className="fas fa-pencil-alt"></i></span>
                </button>
              </div>
            }
          </>
        }
      </div></div>
    );
  }
}
