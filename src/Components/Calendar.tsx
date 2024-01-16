import React, { Component } from "react";
import axios from "axios";

interface CalendarProps {
  uid: string,
  model?: string
}

interface CalendarState {
  rCnt: number,
  data?: Array<Array<Array<any>>>
}

const hoursRange = Array.from({ length: 17 }, (_, index) => index + 6);
const hodiny = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

const dni = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

export default class Calendar extends Component<CalendarProps> {
  state: CalendarState;

  constructor(props: CalendarProps) {
    super(props);

    this.state = {
      rCnt: 0
    }
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    //@ts-ignore
    axios.get(_APP_URL + '/Components/Calendar/OnLoadData', {
      params: {
        model: this.props.model
      }
    }).then(({data}: any) => {
        this.setState({
          data: data.data
        }, () => {
          //@ts-ignore
          this.sortable(document.getElementById('adios-calendar-' + this.props.uid), function(item: any) {
            console.log(item);
          });
        })
    });
  }

  sortable(section: any, onUpdate: any) {
    var dragEl, nextEl, newPos, dragGhost;

    let oldPos = [...section.children].map(item => {
      if (item.id) {
        item.draggable = true;
        let pos = document.getElementById(item.id).getBoundingClientRect();

        return pos;
      }
    });
  
    function _onDragOver(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';

      $('.rezervacka').removeClass('drag-over');
      var target = e.target;

      if (
        target
        && target !== dragEl
        && target.nodeName == 'DIV'
        && target.classList.contains('rezervacka')
        && $(target).data('den') == $(dragEl).data('den')
      ) {
          $(target).addClass('drag-over');
          //  getBoundinClientRect contains location-info about the element (relative to the viewport)
          var targetPos = target.getBoundingClientRect();

          // checking that dragEl is dragged over half the target y-axis or x-axis. (therefor the .5)
          var next =
            (e.clientY - targetPos.top) / (targetPos.bottom - targetPos.top) > .5
            || (e.clientX - targetPos.left) / (targetPos.right - targetPos.left) > .5
          ;
          var next =
            (e.clientX - targetPos.left) / (targetPos.right - targetPos.left) > .5
          ;

          //{# section.insertBefore(dragEl, next && target.nextSibling || target); #}
          section.insertBefore(dragEl, target.nextSibling);
      } else {
        e.stopPropagation();
      }
    }
    
    function _onDragEnd(evt){
      evt.preventDefault();
      newPos = [...section.children].map(child => {
        if (child.id) {
          let pos = document.getElementById(child.id).getBoundingClientRect();
          return pos;
        }
      });
      console.log(newPos);
      dragEl.classList.remove('ghost');
      section.removeEventListener('dragover', _onDragOver, false);
      section.removeEventListener('dragend', _onDragEnd, false);

      nextEl !== dragEl.nextSibling ? onUpdate(dragEl) : false;
    }
      
    section.addEventListener('dragstart', function(e){
      dragEl = e.target; 
      nextEl = dragEl.nextSibling;

      let dragGhostInner = dragEl.cloneNode(true);

      //{# dragGhost = document.createElement('div');
      //dragGhost.classList.add('hidden-drag-ghost');
      //dragGhost.appendChild(dragGhostInner);
      //document.body.appendChild(dragGhost);
      //e.dataTransfer.setDragImage(dragGhost, 0, 0); #}

      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('Text', dragEl.textContent);

      section.addEventListener('dragover', _onDragOver, false);
      section.addEventListener('dragend', _onDragEnd, false);

      setTimeout(function (){
        dragEl.classList.add('ghost');
      }, 0)

    });
  }

  _renderCalendar(): JSX.Element {
    if (!this.state.data) return <p>Loading</p>;

    return (
      <>
        {Array.from({ length: 7 }, (_, d) => (
          <React.Fragment key={d}>
            {(() => {
              let hh = 6;
              let mm = 0;

              return (
                <React.Fragment>
                  <div className="header">30.10.</div>
                  <div className="header">{dni[d]}</div>

                  {this.state.data[d].map((r: any) => {
                    if (r[1] === 0 && r[2] === '' && r[3] === '') {
                      return Array.from({ length: r[0] / 15 }, (_, v) => {
                        mm += 15;
                        if (mm >= 60) {
                          mm -= 60;
                          hh += 1;
                        }

                        return (
                          <div
                            id={`rezervacka-${this.state.rCnt++}`}
                            key={this.state.rCnt}
                            className="rezervacka volne"
                            data-den={d}
                            onClick={() =>
                              adiosModal(
                                'rozpis/form-rezervacia-pridat',
                                { hh, mm },
                                { title: `Rezervácia cvičiska na čas: ${hh}:${mm}` }
                              )
                            }
                          ></div>
                        );
                      });
                    } else {
                      const gridColumn = `span ${(r[0] + r[1]) / 15}`;

                      return (
                        <div
                          id={`rezervacka-${this.state.rCnt++}`}
                          key={this.state.rCnt}
                          className="rezervacka"
                          data-den={d}
                          style={{ gridColumn }}
                        >
                          <div className="rezervacka-inner" style={{ flex: r[0] / 15 }}>
                            {Array.isArray(r[2]) ? (
                              r[2].map((rr) => (
                                <div
                                  key={rr[1]}
                                  className="cast-cviciska"
                                  style={{ background: rr[0] }}
                                  onClick={() =>
                                    adiosModal('rozpis/treningy/form', { hh, mm })
                                  }
                                >
                                  {rr[1]}
                                </div>
                              ))
                            ) : (
                              <div
                                className="cast-cviciska"
                                style={{ background: r[2] }}
                                onClick={() => adiosModal('rozpis/treningy/form', { hh, mm })}
                              >
                                <div className="cas">{`${hh}:${mm}`}</div>
                                <div className="nazov">{r[3]}</div>
                                <div className="trener">Trener</div>
                              </div>
                            )}
                          </div>
                          <div className="rolba" style={{ flex: r[1] / 15 }}></div>
                        </div>
                      );
                    }
                  })}
                </React.Fragment>
              );
            })()}
          </React.Fragment>
        ))}
      </>
    );
  }

  render() {
    return (
      <div id={'adios-calendar-' + this.props.uid} className="rezervacny-kalendar">
        <div className="header" style={{ gridRow: 'span 2' }}>Dátum</div>
        <div className="header">Hodina</div>

        {hoursRange.map((h) => (
          <div key={h} className="header hodiny">
            {h} - {h + 1}
          </div>
        ))}

        <div className="header">Deň</div>
        {this._renderCalendar()}
      </div>
    );
  }
}

