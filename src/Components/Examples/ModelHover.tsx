import React, { Component } from "react";
import FloatingModal from "./../FloatingModal";

export default class ModelHover extends Component {
  state = {
    counter: 42
  };

  handleAgeChange = (e: any) => {
    this.setState({
      counter: this.state.counter + 1 
    });
  };

  render() {
    return (
      <>
        <button onClick={this.handleAgeChange}>
          Increment counter
        </button>

        <p>Counter: {this.state.counter}</p>
        
        <FloatingModal>
          <h1>{this.state.counter}</h1>
        </FloatingModal>
      </>
    );
  }
}
