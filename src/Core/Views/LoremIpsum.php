<?php

namespace ADIOS\Core\Views;


class LoremIpsum extends \ADIOS\Core\View {
  
  public array $dictionary = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
    'Cras id leo ut velit elementum eleifend ac eu diam.',
    'Ut consectetur porttitor enim a maximus.',
    'Mauris egestas pretium neque, id viverra orci sagittis ac.',
    'Sed euismod risus erat, eleifend ullamcorper massa aliquam vitae.',
    'Nullam sed sem ac nisl pulvinar congue sed nec dui.',
    'In sollicitudin dolor sed orci porttitor, non aliquet magna varius.',
    'Pellentesque vestibulum pretium risus id imperdiet.',
    'Curabitur et cursus justo.',
    'Nunc eu elit rhoncus, vestibulum orci pretium, eleifend orci.',
    'Fusce fermentum vitae nisi suscipit congue.',
    'Mauris viverra ac dolor sed ultricies.',
    'Integer mollis dictum gravida.',
    'Nulla facilisi.',
    'Vivamus vitae lectus sit amet arcu porttitor consequat eu ut arcu.',
    'Maecenas augue nisl, imperdiet nec molestie ultrices, fringilla id ipsum.',
    'Nunc efficitur efficitur eros non finibus.',
    'Duis vulputate diam neque, eget convallis diam molestie id.',
    'In euismod ligula vitae efficitur congue.',
    'Vestibulum at justo rhoncus, efficitur urna dignissim, pellentesque nulla.'
  ];

  public function render(string $panel = ''): string
  {
    return "
      <div class='".$this->getCssClassesString()."'>
        " . $this->dictionary[rand(0, count($this->dictionary) - 1)] . "
      </div>
    ";
  }

}
