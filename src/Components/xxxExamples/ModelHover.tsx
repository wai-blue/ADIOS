import React, { Component } from 'react';
import Modal from "./../Modal";

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
        
        <Modal>
          <h1>{this.state.counter}</h1>
        </Modal>
      </>
    );
  }
}
