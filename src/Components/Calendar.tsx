import React, { Component, FC, useState, forwardRef } from "react";
import moment, { Moment } from "moment";
import { dateToString, numberToStringTime } from "./Helper";
import Notification from "./Notification";
import request from "./Request";

import Form from './Form';
import Button from './Button';
import FormCardButton from './FormCardButton';
import Modal from './Modal';

import SwalButton from "./SwalButton";

interface CalendarProps {
  uid: string,
  dataEndpoint?: string,
  cviciska: Array<any>,
  timy: Array<any>,
  typ: number,
  idSportovisko: number,
  jeKoordinator: boolean,
  jeSpravca: boolean,
  userId: number
}

interface CalendarState {
  rCnt: number,
  data?: Array<Array<Array<any>>>,
  idCvicisko?: number,
  idTim?: number,
  typ?: number,
  poradie?: number,
  calendarTitle: string,
  datumOd: Date,
  datumDo: Date,
  rok?: number,
  tyzden?: number,
  isReadonly: boolean,
  warning?: string,
  info?: string,
  rezervaciaDatum?: string,
  rezervaciaCasOd?: string,
  rezervaciaVyuzitie?: number,
  casUprava?: number,
  idZaznam?: number,
  cvicisko?: any
}

const hoursRange = Array.from({ length: 17 }, (_, index) => index + 6);
const hodiny = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

const dni = ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne'];

const REZERVACIA_TYP_VOLNE = 0;
const REZERVACIA_TYP_TRENING = 1;
const REZERVACIA_TYP_ZAPAS = 2;
const REZERVACIA_TYP_PRENAJOM = 3;

export default class Calendar extends Component<CalendarProps> {
  state: CalendarState;
  dataEndpoint: string;

  title: JSX.Element;

  constructor(props: CalendarProps) {
    super(props);

    //console.log(props);
    let now: Date = new Date();
    let lastMonday: Date = new Date(now.getTime() - (now.getDay() - 1) * 24 * 60 * 60 * 1000);
    let lastDayInWeek = new Date(lastMonday.getTime() + 6 * 24 * 60 * 60 * 1000);

    this.state = {
      rCnt: 0,
      idCvicisko: this.props.cviciska[0]?.id ?? 0,
      idTim: this.props.timy.length != 0 ? this.props.timy[Object.keys(this.props.timy)[0]].id : 0,
      typ: this.props.typ ?? REZERVACIA_TYP_TRENING,
      poradie: 0,
      calendarTitle: 'def',
      datumOd: lastMonday,
      datumDo: lastDayInWeek,
      isReadonly: false
    };

    this.dataEndpoint = props.dataEndpoint ?? '';
  }

  componentDidMount() {
    this.loadData();
  }

  loadData() {
    const selectedCvicisko = this.props.cviciska.find((cvicisko) => cvicisko.id == this.state.idCvicisko);
    if (this.state.idTim == 0 && (!this.props.jeKoordinator || selectedCvicisko.id_koordinator != this.props.userId) &&
    !this.props.jeSpravca)
      this.state.idTim = Object.values(this.props.timy)[0].id;

    request.get(
      this.dataEndpoint,
      {
        __IS_AJAX__: '1',
        idTim: this.state.idTim,
        idCvicisko: this.state.idCvicisko,
        typ: this.state.typ,
        datumOd: this.state.datumOd,
        datumDo: this.state.datumDo
      },
      (data: any) => {
        let isReadonly = false;
        let warning = '';
        let info = '';

        if (this.props.jeSpravca) {
          isReadonly = false;
          info = 'Rozpis môžete upravovať ako správca.';
        } else if (this.props.jeKoordinator && selectedCvicisko.id_koordinator == this.props.userId) {
          isReadonly = false;
          info = 'Rozpis môžete upravovať ako koordinátor.';
        } else {
          if (
            data.tyzden < this.getWeekNumber(new Date())
            || data.rok < (new Date()).getFullYear()
          ) {
            isReadonly = true;
            warning = 'Nemôžete upravovať rozpis, pretože je zobrazený týždeň v minulosti.';
          } else if (this.state.idTim && this.props.timy[this.state.idTim].id_trener == this.props.userId) {
            if (this.props.timy[this.state.idTim].poradie != data.poradie) {
              isReadonly = true;
              warning = 'Nemôžete upravovať rozpis, pretože nie ste v poradí. Aktuálne poradie pre tento týždeň je ' + data.poradie + '.';
            } else {
              isReadonly = false;
              info = 'Rozpis môžete upravovať ako tréner. Ste na rade, aby ste zadali svoj rozpis.'
            }
          } else {
            isReadonly = true;
            warning = "Nemáte oprávnenie upravovať tento rozpis.";
          }
        }

        this.setState({
          data: data.data,
          poradie: data.poradie,
          rok: data.rok,
          tyzden: data.tyzden,
          cvicisko: data.cvicisko,
          isReadonly: isReadonly,
          warning: warning,
          info: info,
          casUprava: data.cvicisko?.cas_uprava ?? 0
        }, () => {
          //@ts-ignore
          this.sortable(document.getElementById('adios-calendar-' + this.props.uid), function(item: any) {
            //console.log(item);
          });
        })
      }
    );
  }

  idTimOnChange(event: React.ChangeEvent<HTMLInputElement>) {
    this.setState({
      idTim: event.target.value
    }, () => {
      this.loadData();
    });
  }

  typOnChange(event: React.ChangeEvent<HTMLInputElement>) {
    this.setState({
      typ: event.target.value
    }, () => {
      this.loadData();
    });
  }

  idCviciskoOnChange(event: React.ChangeEvent<HTMLInputElement>) {
    this.setState({
      idCvicisko: event.target.value
    }, () => {
      this.loadData();
    });
  }

  formatTimeNumber(number: number) {
    return `${number < 10 ? '0' : ''}${number}`;
  }

  pickDateTime(slot: Moment, id?: null|number, typ?: null|number, vyuzitie?: number) {
    if (!this.state.isReadonly) {
      this.setState({
        idZaznam: id,
        rezervaciaDatum: `${slot.format('YYYY-MM-DD')}`,
        rezervaciaCasOd: `${slot.format('HH:mm')}`,
        rezervaciaVyuzitie: vyuzitie,
      });

      let modalUid = 'zvolit-typ-rezervacie';

      if (id === null) {
        // ak pridava novu rezervaciu, pouzije nastaveny typ
        modalUid = 'form-modal-' + this.state.typ;
        // if (this.props.jeKoordinator || this.props.jeSpravca) {
        //   // ak pridava koordinator alebo spravca, ponukne mu na vyber, co chce pridat
        //   modalUid = 'zvolit-typ-rezervacie';
        // } else {
        //   // ak pridava trener, rovno mu zobrazi formular pre trening
        //   modalUid = 'form-modal-' + REZERVACIA_TYP_TRENING;
        // }
      } else {
        // ak upravuje existujucu rezervaciu, otvori formular prislusneho typu
        modalUid = 'form-modal-' + typ;
      }

      //@ts-ignore
      ADIOS.modalToggle(this.props.uid + '-' + modalUid);
    } else {
      Notification.error(this.state.warning ?? '');
    }
  }

  sortable(section: any, onUpdate: any) {
    var dragEl, nextEl, newPos, dragGhost;

    let oldPos = [...section.children].map(item => {
      if (item.id) {
        item.draggable = true;
        let pos = document.getElementById(item.id)?.getBoundingClientRect();

        return pos;
      }
    });
  
    function _onDragOver(e:  any) {
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
    
    function _onDragEnd(evt: any){
      evt.preventDefault();
      newPos = [...section.children].map(child => {
        if (child.id) {
          let pos = document.getElementById(child.id)?.getBoundingClientRect();

          return pos;
        }
      });
      //console.log(newPos);
      dragEl.classList.remove('ghost');
      section.removeEventListener('dragover', _onDragOver, false);
      section.removeEventListener('dragend', _onDragEnd, false);

      nextEl !== dragEl.nextSibling ? onUpdate(dragEl) : false;
    }
      
    section.addEventListener('dragstart', function(e: any){
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


  getWeekNumber(date: Date) {
    let newDate = new Date(date);
    newDate.setDate(newDate.getDate() + 4 - (newDate.getDay() || 7));

    let year = newDate.getFullYear();
    let daysDiff = Math.floor((newDate.getTime() - new Date(year, 0, 4).getTime()) / (24 * 60 * 60 * 1000));

    return Math.ceil((daysDiff + 1) / 7);
  }

  calculateWeeks(type: string = 'next') {
    let weeks: Object = {};

    let datumOd = new Date(this.state.datumOd.getTime() + (type == 'next' ? 7 : -7) * 24 * 60 * 60 * 1000);

    weeks = {
      datumOd: datumOd,
      datumDo: new Date(this.state.datumDo.getTime() + (type == 'next' ? 7 : -7) * 24 * 60 * 60 * 1000),
    }

    this.setState({...weeks}, () => {
        this.loadData();
    })
  }

  closeAndLoadData(modalUid: string) {
    this.loadData();
    //@ts-ignore
    ADIOS.modalToggle(this.props.uid + '-' + modalUid);
    //@ts-ignore
    ADIOS.modalToggle(this.props.uid);
  }

  _addOpacity(color: string, opacity: string): string {
    return color + opacity;
  }

  _renderCalendar(): JSX.Element {
    if (!this.state.data) return <p className="d-block text-center pt-4 mt-4 h4" style={{gridColumn: "span 70"}}>Načítavam rozpis, čakajte prosím...</p>;

    return (
      <>
        {Array.from({ length: 7 }, (_, d) => (
          <>
            {(() => {
              let slot = moment(this.state.datumOd).add(d, 'days').startOf('day').set({hour: 6});

              if (!this.state.data) return;

              let dayLine =
                <>
                  <div className="header">{slot.format('D.M.YYYY')}</div>
                  <div className="header">{dni[d]}</div>

                  {this.state.data[d] ? (this.state.data[d].map((r: any) => (
                    <>
                      {(r[1] === 0 && r[2] === 0) ? (
                        Array.from({ length: r[0] / 15 }, (_, v) => {
                          const _slot = moment(slot);

                          let div = (
                            <div
                              id={`rezervacka-${this.state.rCnt++}`}
                              key={this.state.rCnt}
                              className={
                                "rezervacka typ-" + REZERVACIA_TYP_VOLNE
                                + " " + (this.state.rCnt % 4 == 0 ? "cela-hodina" : "")
                                // + " " + (r[7] == this.state.idTim ? "zvyraznene" : "")
                              }
                              title={slot.format('D.M.YYYY HH:mm')}
                              data-den={d}
                              onClick={() => this.pickDateTime(_slot, null, null, 1)}
                            ></div>
                          );

                          slot = slot.add(15, 'minutes');

                          return div
                        })
                      ) : (
                        <>
                          {(() => {
                            const _slot = moment(slot);
                            const span = (r[0] + r[1]) / 15;
                            const gridColumn = `span ${span}`;

                            let typ = r[2];

                            this.state.rCnt += span;

                            let div = (
                              <div
                                id={`rezervacka-${this.state.rCnt}`}
                                key={this.state.rCnt}
                                className={"rezervacka typ-" + typ}
                                title={slot.format('D.M.YYYY HH:mm')}
                                data-den={d}
                                style={{ gridColumn }}
                              >
                                <div className="rezervacka-inner" style={{ flex: r[0] / 15 }}>
                                  {r[3].map((rr) => {
                                    let idTim = rr[5];
                                    let readonly = true;

                                    if (typ == this.state.typ) {
                                      if (this.state.typ == REZERVACIA_TYP_TRENING) {
                                        if (this.state.idTim == 0 || idTim == this.state.idTim) readonly = false;
                                      } else {
                                        readonly = false;
                                      }
                                    }

                                    return (
                                      <div
                                        className={
                                          "cast-cviciska"
                                          + " " + (readonly ? "" : "zvyraznene")
                                          + " " + (idTim == 0 ? "volne" : "")
                                        }
                                        style={{
                                          background: rr[0], //this._addOpacity(rr[0], '60'),
                                          flex: rr[3]
                                        }}
                                        onClick={() => {
                                          if (!readonly) {
                                            // ak klikol na slot, v ktorom je uz cosi rezervovane
                                            if (rr[4] > 0) {
                                              // upravit rezervaciu
                                              this.pickDateTime(_slot, rr[4], typ);
                                            } else if (idTim == 0) {
                                              // pridat rezervaciu na ciastocne vyuzitie plochy
                                              this.pickDateTime(_slot, null, null, 2);
                                            }
                                          }
                                        }}
                                      >
                                        <div className="cas">{numberToStringTime(_slot.hours()) + ':' + numberToStringTime(_slot.minutes())}</div>
                                        <div className="nazov">{rr[1]}</div>
                                        <div className="trener">{rr[2]}</div>
                                      </div>
                                    );
                                  })}
                                </div>
                                <div className="uprava" style={{ flex: r[1] / 15 }}></div>
                              </div>
                            );

                            slot = slot.add(span*15, 'minutes');

                            return div;
                          })()}
                        </>
                      )}
                    </>
                  ))) : ''}
                </>
              ;

              return dayLine;
            })()}
          </>
        ))}
      </>
    );
  }

  render() {
    const selectedCvicisko = this.props.cviciska.find((cvicisko) => cvicisko.id == this.state.idCvicisko);
    let accessibleTimy = Object.entries(this.props.timy);
    if (selectedCvicisko.id_koordinator != this.props.userId && !this.props.jeSpravca)
      accessibleTimy = accessibleTimy.filter(([key, tim]) => tim['id_trener'] == this.props.userId)
    accessibleTimy = Object.keys(Object.fromEntries(accessibleTimy));

    if (accessibleTimy.length == 0) this.state.idTim = 0;

    return (
      <>
        {<Modal
          title={
            "Rezervácia "
            + (this.state.rezervaciaDatum ? this.state.rezervaciaDatum : '')
            + ' '
            + (this.state.rezervaciaCasOd ? this.state.rezervaciaCasOd : '')
          }
          uid={this.props.uid + '-zvolit-typ-rezervacie'}
          type="center"
        >
          <Button
            uid={this.props.uid + '-trening'}
            text="Tréning"
            cssClass="btn-light m-1"
            cssStyle={{borderLeft: "3px solid #5858ff"}}
            icon="fas fa-running"
            onClick={() => {
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid + '-form-modal-' + REZERVACIA_TYP_TRENING);
              //@ts-ignore
              ADIOS.modalToggle(this.props.uid + '-zvolit-typ-rezervacie');
            }}
          ></Button>

          {(this.props.jeKoordinator && selectedCvicisko.id_koordinator == this.props.userId) || this.props.jeSpravca ?
            <Button
              uid={this.props.uid + '-zapas'}
              text="Zápas"
              cssClass="btn-light m-1"
              cssStyle={{borderLeft: "3px solid #ff6262"}}
              icon="fas fa-people-arrows"
              onClick={() => {
                //@ts-ignore
                ADIOS.modalToggle(this.props.uid + '-form-modal-' + REZERVACIA_TYP_ZAPAS);
                //@ts-ignore
                ADIOS.modalToggle(this.props.uid + '-zvolit-typ-rezervacie');
              }}
            ></Button>
          : ''}

          {this.props.jeSpravca ?
            <Button
              uid={this.props.uid + '-prenajom'}
              text="Prenájom"
              cssClass="btn-primary m-1"
              icon="fas fa-euro-sign"
              onClick={() => {
                //@ts-ignore
                ADIOS.modalToggle(this.props.uid + '-form-modal-' + REZERVACIA_TYP_PRENAJOM);
                //@ts-ignore
                ADIOS.modalToggle(this.props.uid + '-zvolit-typ-rezervacie');
              }}
            ></Button>
          : ''}

          {/* <FormCardButton
            uid={this.props.uid + '-trening'}
            text="Tréning"
            cssClass="btn-primary m-1"
            icon="fas fa-running"
            form={{
              uid: this.props.uid + '-trening',
              model: "App/Widgets/Rozpis/Models/Trening",
              onSaveCallback: () => this.closeAndLoadData('trening'),
              onDeleteCallback: () => this.closeAndLoadData('trening'),
              defaultValues: {
                datum: this.state.rezervaciaDatum,
                zaciatok: this.state.rezervaciaCasOd,
                id_tim: this.state.idTim,
                id_cvicisko: this.state.idCvicisko,
                vyuzitie: 1, //cela plocha default,
                cas_uprava: this.state.casUprava
              }
            }}
          ></FormCardButton>

          {this.props.jeKoordinator || this.props.jeSpravca ?
            <FormCardButton
              uid={this.props.uid + '-zapas'}
              text="Zápas"
              cssClass="btn-light"
              icon="fas fa-people-arrows m-1"
              form={{
                uid: this.props.uid + '-zapas',
                model: "App/Widgets/Rozpis/Models/Zapas",
                onSaveCallback: () => this.closeAndLoadData('zapas'),
                onDeleteCallback: () => this.closeAndLoadData('zapas'),
                defaultValues: {
                  datum: this.state.rezervaciaDatum,
                  zaciatok: this.state.rezervaciaCasOd
                }
              }}
            ></FormCardButton>
          : ''}

          {this.props.jeSpravca ?
            <FormCardButton
              uid={this.props.uid + '-prenajom'}
              text="Prenájom"
              cssClass="btn-light m-1"
              icon="fas fa-euro-sign"
              form={{
                uid: this.props.uid + '-prenajom',
                model: "App/Widgets/Rozpis/Models/Prenajom",
                onSaveCallback: () => this.closeAndLoadData('prenajom'),
                onDeleteCallback: () => this.closeAndLoadData('prenajom'),
                defaultValues: {
                  datum: this.state.rezervaciaDatum,
                  zaciatok: this.state.rezervaciaCasOd
                }
              }}
            ></FormCardButton>
          : ''} */}
        </Modal>}

        <Modal
          uid={this.props.uid + '-form-modal-' + REZERVACIA_TYP_TRENING}
          hideHeader={true}
          type="right trening"
        >
          <Form
            uid={this.props.uid + '-form-' + REZERVACIA_TYP_TRENING}
            showInModal={true}
            onClose={() => { this.setState({idZaznam: 0}); }}
            model="App/Widgets/Rozpis/Models/Trening"
            onSaveCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_TRENING)}
            onDeleteCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_TRENING)}
            id={this.state.idZaznam ?? undefined}
            defaultValues={{
              datum: this.state.rezervaciaDatum,
              zaciatok: this.state.rezervaciaCasOd,
              id_tim: this.state.idTim,
              id_cvicisko: this.state.idCvicisko,
              id_sportovisko: this.props.idSportovisko,
              vyuzitie: this.state.rezervaciaVyuzitie,
              trvanie_uprava: this.state.casUprava
            }}
          ></Form>
        </Modal>

        <Modal
          uid={this.props.uid + '-form-modal-' + REZERVACIA_TYP_ZAPAS}
          hideHeader={true}
          type="right zapas"
        >
          <Form
            uid={this.props.uid + '-form-' + REZERVACIA_TYP_ZAPAS}
            showInModal={true}
            onClose={() => { this.setState({idZaznam: 0}); }}
            model="App/Widgets/Rozpis/Models/Zapas"
            onSaveCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_ZAPAS)}
            onDeleteCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_ZAPAS)}
            id={this.state.idZaznam ?? undefined}
            defaultValues={{
              datum: this.state.rezervaciaDatum,
              zaciatok: this.state.rezervaciaCasOd,
              id_cvicisko: this.state.idCvicisko,
              id_sportovisko: this.props.idSportovisko,
              trvanie_uprava: this.state.casUprava
            }}
          ></Form>
        </Modal>

        <Modal
          uid={this.props.uid + '-form-modal-' + REZERVACIA_TYP_PRENAJOM}
          hideHeader={true}
          type="right prenajom"
        >
          <Form
            uid={this.props.uid + '-form-' + REZERVACIA_TYP_PRENAJOM}
            showInModal={true}
            onClose={() => { this.setState({idZaznam: 0}); }}
            model="App/Widgets/Rozpis/Models/Prenajom"
            onSaveCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_PRENAJOM)}
            onDeleteCallback={() => this.closeAndLoadData('form-modal-' + REZERVACIA_TYP_PRENAJOM)}
            id={this.state.idZaznam ?? undefined}
            defaultValues={{
              datum: this.state.rezervaciaDatum,
              zaciatok: this.state.rezervaciaCasOd,
              id_cvicisko: this.state.idCvicisko,
              id_sportovisko: this.props.idSportovisko,
              trvanie_uprava: this.state.casUprava
            }}
          ></Form>
        </Modal>

        <div className="row mt-1 mb-2">
          <div className="col-lg-8 pl-0">
            <div className="d-flex flex-row align-items-center">
              <div className={"d-block w-100 mr-2"}>
                <select
                  className="form-control rounded-sm mr-2"
                  onChange={(event: any) => this.idCviciskoOnChange(event)}
                  value={this.state.idCvicisko}
                >
                  {this.props.cviciska.map((cvicisko: any) => (
                    <option
                      key={cvicisko.id}
                      value={cvicisko.id}
                    >{cvicisko.nazov}</option>
                  ))}
                </select>
              </div>

              <div className={"d-block w-100 mr-2"}>
                <select
                  className="form-control rounded-sm"
                  onChange={(event: any) => this.typOnChange(event)}
                  value={this.state.typ}
                >
                  <option value={REZERVACIA_TYP_TRENING}>Tréning</option>
                  {this.props.jeSpravca || (this.props.jeKoordinator && selectedCvicisko.id_koordinator == this.props.userId) ? <option value={REZERVACIA_TYP_ZAPAS}>Zápas</option> : ""}
                  {this.props.jeSpravca ? <option value={REZERVACIA_TYP_PRENAJOM}>Prenájom</option> : ""}
                </select>
              </div>

              <div className={"d-block w-100 " + (this.state.typ == REZERVACIA_TYP_TRENING ? "" : "invisible")}>
                <select
                  className="form-control rounded-sm"
                  onChange={(event: any) => this.idTimOnChange(event)}
                  value={this.state.idTim}
                >
                  {this.props.jeSpravca || (this.props.jeKoordinator && selectedCvicisko.id_koordinator == this.props.userId) ? <option value={0}>Všetky tímy</option> : ""}
                  {accessibleTimy.length != 0 ? accessibleTimy.map((key: any) => (
                    <option
                      key={this.props.timy[key].id}
                      value={this.props.timy[key].id}
                    >[{this.props.timy[key].poradie}] {this.props.timy[key].nazov}</option>
                  )) : (
                    <option value={0} disabled>-</option>
                  )}
                </select>
              </div>

            </div>
          </div>
          <div className="col-lg-4 pr-0">
            <div className="d-flex flex-row justify-content-end align-items-center">
              <a href="#" onClick={() => this.calculateWeeks('previous')} className="btn btn-primary">«</a>
              <div className="text-primary text-center" style={{margin: "0 0.5em", width: "200px"}}>
                { this.state.datumOd.getDate() }.{ this.state.datumOd.getMonth() + 1 }.{ this.state.datumOd.getFullYear() }
                &nbsp;-&nbsp;
                { this.state.datumDo.getDate() }.{ this.state.datumDo.getMonth() + 1 }.{ this.state.datumOd.getFullYear() }
              </div>
              <a href="#" onClick={() => this.calculateWeeks()} className="btn btn-primary">»</a>
            </div>
          </div>
        </div>

        <div
          id={'adios-calendar-' + this.props.uid}
          className={"rezervacny-kalendar rounded-sm"}
        >
          <div className="header" style={{ gridRow: 'span 2' }}>Dátum</div>
          <div className="header">Hodina</div>

          {hoursRange.map((h) => (
            <div key={h} className="header hodiny">
              {h < 10 ? "0" : ""}{h}:00
            </div>
          ))}

          <div className="header">Deň</div>
          {this._renderCalendar()}
        </div>

        <div className="row mt-4">
          <div className="col-lg-8">
            {this.state.isReadonly ? (
              <div className='alert alert-danger' role='alert'>
                <i className='fas fa-exclamation-triangle mr-4 align-self-center'></i>
                {this.state.warning}
              </div>
            ) : (
              <div className='alert alert-success' role='alert'>
                <i className='fas fa-check mr-4 align-self-center'></i>
                {this.state.info}<br/>
              </div>
            )}

            <div className='alert alert-info' role='alert'>
              <i className='fas fa-info mr-4 align-self-center'></i>
              {this.state.cvicisko?.nazov}, {dateToString(this.state.datumOd)} - {dateToString(this.state.datumDo)}.
              Aktuálne poradie: [{this.state.poradie}].
              Koordinátor: {this.state.cvicisko?.id_koordinator?.meno} {this.state.cvicisko?.id_koordinator?.priezvisko}.
            </div>

          </div>
          <div className="col-lg-4 text-right">
            {!this.state.isReadonly ? (
              <>
                <SwalButton
                  uid={this.props.uid}
                  text="Ukončiť tvorbu rozpisu"
                  css="btn-success"
                  icon="fas fa-check"
                  confirmUrl="rozpis/kalendar/potvrdit-rozpis"
                  successMessage="Rozpis úspešne ukončený"
                  confirmParams={{
                    idSportovisko: this.props.idSportovisko,
                    idCvicisko: this.state.idCvicisko,
                    rok: this.state.rok,
                    tyzden: this.state.tyzden
                  }}
                  onConfirmCallback={() => this.loadData()}
                  swal={{
                    title: "Ukončiť tvorbu rozpisu",
                    html:`
                      <p>
                        Chystáte sa ukončiť tvorbu rozpisu v týždni <b>${dateToString(this.state.datumOd)} - ${dateToString(this.state.datumDo)}</b>
                        na cvičisku <b>${this.state.cvicisko?.nazov}</b>.<br/>
                      </p>
                      
                      <div class='alert alert-warning ${(!this.props.jeSpravca &&
                      !(this.props.jeKoordinator && selectedCvicisko.id_koordinator !== this.props.userId)) || (this.props.timy[this.state.idTim] != undefined 
                      && this.props.timy[this.state.idTim].id_trener == this.props.userId && this.state.poradie == this.props.timy[this.state.idTim].poradie) ? 'd-none' : ''}'
                      role='alert'>
                        <i class='fas fa-exclamation mr-4 align-self-center'></i>
                        Konáte ako ${this.props.jeSpravca ? "správca" : "koordinátor"} v mene trénera, ktorý je v poradí. Určite chcete ukončiť tvorbu rozpisu?<br/>
                      </div>

                      <div class='alert alert-danger' role='alert'>
                        <i class='fas fa-exclamation-triangle mr-4 align-self-center'></i>
                        Krok sa nedá vrátiť späť. Ďalšie zmeny v rozpise bude môcť vykonať iba koordinátor.<br/>
                      </div>

                      <div class='alert alert-success' role='alert'>
                        <i class='fas fa-check mr-4 align-self-center'></i>
                        Potvrdením umožníte tvoriť rozpis pre ďalší tím v poradí.
                      </div>
                    `,
                    icon: "info",
                    confirmButtonText: "Potvrdiť",
                    confirmButtonColor: '#1cc88a',
                    cancelButtonText: "Zrušiť"
                  }}
                ></SwalButton>

                <div className="mt-2">
                  Umožníte tvoriť rozpis pre ďalší tím v poradí.
                </div>
              </>
            ) : ''}
          </div>
        </div>
      </>
    );
  }
}

