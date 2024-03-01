import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import "flatpickr/dist/themes/material_blue.css";
import Flatpickr from "react-flatpickr";
import { FormColumnParams } from '../Form'
import * as uuid from 'uuid';

export const dateToEUFormat = (dateString: string): string => {
  if (!dateString || dateString.length != 10) {
    return '';
  } else {
    let d = new Date(dateString);

    return ('0' + d.getDate()).slice(-2) + "."
      + ('0' + (d.getMonth() + 1)).slice(-2)
      + "." + d.getFullYear()
      ;
  }
}

export const datetimeToEUFormat = (dateString: string): string => {
  let d = new Date(dateString);

  return ('0' + d.getDate()).slice(-2) + "."
    + ('0' + (d.getMonth() + 1)).slice(-2)
    + "." + d.getFullYear()
    + " " + ('0' + d.getHours()).slice(-2) + ":" + ('0' + d.getMinutes()).slice(-2)
    ;
}

interface DateTimeInputProps extends InputProps {
  type: string
}

export default class DateTime extends Input<DateTimeInputProps, InputState> {
  static defaultProps = {
    inputClassName: 'datetime',
    id: uuid.v4(),
  }

  options: any = {
    dateFormat: 'd.m.Y',
    allowInput: true,
    locale: {
      weekdays: {
        shorthand: ['Ne.', 'Po.', 'Ut.', 'St.', 'Št.', 'Pi.', 'So.'],
        longhand: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
      },
      months: {
        shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Máj', 'Jún', 'Júl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
        longhand: ['Január', 'Február', 'Marec', 'Apríl', 'Máj', 'Jún', 'Júl', 'August', 'September', 'Október', 'November', 'December']
      },
      weekStart: 1
    }
  };

  constructor(props: DateTimeInputProps) {
    super(props);

    switch (props.type) {
      case 'datetime':
        this.options = {...this.options, ...{ dateFormat: 'd.m.Y H:i' }};
      break;
      case 'date':
        this.options = {...this.options, ...{ dateFormat: 'd.m.Y' }};
      break;
      case 'time':
        this.options = {
          ...this.options,
          ...{
            dateFormat: 'H:i',
            enableTime: true,
            noCalendar: true,
            time_24hr: true,
            minuteIncrement: 15
          }
        };
      break;
    }
  }

  _renderIcon(): JSX.Element {
    switch (this.props.type) {
      case 'time': return <i className="fas fa-clock"></i>;
      default: return <i className="fas fa-calendar"></i>;
    }
  }

  renderInputElement() {
    let value = this.state.value;

    switch (this.props.type) {
      case 'datetime':
        value = datetimeToEUFormat(this.state.value);
      break;
      case 'date':
        value = dateToEUFormat(this.state.value);
      break;
      case 'time':
        this.options = {
          ...this.options,
          ...{
            dateFormat: 'H:i',
            enableTime: true,
            noCalendar: true,
            time_24hr: true,
            minuteIncrement: 15
          }
        };
      break;
    }

    return (
      <>
        <div className={"max-w-250 input-group"}>
          <Flatpickr
            value={value}
            onChange={(data: Date[]) => this.onChange(data[0] ?? null)}
            onBlur={(e: React.FocusEvent<HTMLInputElement>) => this.onChange(e.target.value)}
            className={
              "form-control"
                + " " + (this.state.invalid ? 'is-invalid' : '')
                + " " + (this.props.cssClass ?? "")
                + " " + (this.state.readonly ? "bg-muted" : "")
            }
            placeholder={this.props.params?.placeholder}
            disabled={this.state.readonly}
            options={this.options}
          />
          <div className="input-group-append">
            <span className="input-group-text">
              {this._renderIcon()}
            </span>
          </div>
        </div>
      </>
    );
  }
}
