import React, { Component } from 'react'
import { Input, InputProps, InputState } from '../Input'
import * as uuid from 'uuid';
//@ts-ignore
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet'

export default class MapPoint extends Input<InputProps, InputState> {
  static defaultProps = {
    inputClassName: 'varchar',
    id: uuid.v4(),
    type: 'text',
  }

  constructor(props: InputProps) {
    super(props);
  }


  renderInputElement() {
    const position = [51.505, -0.09];
    return <>
      <input
        type='text'
        value={this.state.value}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => this.onChange(e.currentTarget.value)}
        placeholder={this.props.params?.placeholder}
        className={
          (this.state.invalid ? 'is-invalid' : '')
          + " " + (this.props.cssClass ?? "")
          + " " + (this.state.readonly ? "bg-muted" : "")
        }
        disabled={this.state.readonly}
      />
      <div style={{ height: '100vh', width: '100%' }}>
        <MapContainer center={position} zoom={13} scrollWheelZoom={false}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <Marker position={position}>
            <Popup>
              A pretty CSS3 popup. <br /> Easily customizable.
            </Popup>
          </Marker>
        </MapContainer>
      </div>
    </>;
  }
}
