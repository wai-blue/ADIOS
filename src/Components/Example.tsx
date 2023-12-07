import React, { Component } from "react";
import FloatingModal from "./FloatingModal";

export default class Example extends Component {
  state = {
    name: 'Taylor',
    age: 42,
  };

  handleNameChange = (e: any) => {
    this.setState({
      name: e.target.value
    });
  }

  handleAgeChange = (e: any) => {
    this.setState({
      age: this.state.age + 1 
    });
  };

  render() {
    return (
      <>
        <input
          value={this.state.name}
          onChange={this.handleNameChange}
        />
        <button onClick={this.handleAgeChange}>
          Increment age
        </button>

        <p>Hello, {this.state.name}. You are {this.state.age}.</p>

        <FloatingModal>
          <h1>{this.state.age}</h1>
        </FloatingModal>
      </>
    );
  }
}
