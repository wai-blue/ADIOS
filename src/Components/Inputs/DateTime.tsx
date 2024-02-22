import React, { Component } from 'react'
import "flatpickr/dist/themes/material_blue.css";
import Flatpickr from "react-flatpickr";
import { FormColumnParams } from '../Form'

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

interface DateTimeInputProps {
  parentForm: any,
  columnName: string,
  type: string,
  params: any
}

export default class DateTime extends Component<DateTimeInputProps> {
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
      case 'time': 
        this.options = {
          ...this.options,
          ...{
            dateFormat: 'H:i',
            enableTime: true,
            noCalendar: true,
            time_24hr: true
          }
        };
      break;
    }
  }

  render() {
    let value: string = this.props.parentForm.state.data[this.props.columnName] ?? "";

    let parentForm = this.props.parentForm;
    let pfState = parentForm.state;
    let columnName = this.props.columnName;
    let column: FormColumnParams = pfState.columns[columnName];

    switch (this.props.type) {
      case 'datetime': 
        //value = datetimeToEUFormat(value);
      break;
      case 'date':
        //value = dateToEUFormat(value);
      break;
    }
    
    return (
      <>
        <div className={"max-w-250 input-group"}>
          <Flatpickr
            value={value}
            onChange={(data: any) => this.props.parentForm.inputOnChangeRaw(this.props.columnName, data[0] ?? null)}
            className={`form-control ${this.props.parentForm.state.invalidInputs[this.props.columnName] ? 'is-invalid' : ''}`}
            disabled={this.props.parentForm.props.readonly || this.props.parentForm.state.columns[this.props.columnName].readonly}
            options={this.options}
          />
          <div className="input-group-append">
            <span className="input-group-text">
              {this.props.type == 'datetime' ? <i className="fas fa-calendar"></i> : ''}
              {this.props.type == 'date' ? <i className="fas fa-calendar"></i> : ''}
              {this.props.type == 'time' ? <i className="fas fa-clock"></i> : ''}
            </span>
          </div>
        </div>
      </>
    );
  } 
}
