<?php

namespace ADIOS\Core\Views;

/**
 * Renders a button element. Used by many other UI elements.
 *
 * Example code to render button:
 *
 * ```php
 *   $adios->view->create('Button', [
 *     "type" => "close",
 *     "onclick" => "window_close('{$this->uid}');",
 *   ]);
 * ```
 *
 * or

 * ```php
 *   $adios->view->create('Button', [
 *     "type" => "close",
 *     "url" => "url_relative_to_apps_root_url",
 *   ]);
 * ```
 *
 * or

 * ```php
 *   $adios->view->create('Button', [
 *     "type" => "close",
 *     "href" => "any_url_to_open",
 *   ]);
 * ```
 *
 * @package UI\Elements
 */
class Button extends \ADIOS\Core\View {

  /**
   * Type of the button. Determines default configuration.
   * Possible values: 'save', 'search', 'apply', 'close', 'copy', 'add', 'delete', 'cancel', 'confirm'.
   *
   * @var string
   */
  public $type = "";

  /**
   * DOM element's ID.
   *
   * @var string
   */
  // public $id = "";

  /**
   * If not empty, the href will be set to ROOT_URL + url.
   *
   * @var string
   */
  public $url = "";

  /**
   * If not empty, will be used as href attribute. Otherwise will href attribute be set to javascript:void(0).
   *
   * @var string
   */
  public $href = "";

  /**
   * FontAwesome icon in the form of a CSS class name. E.g. 'fas fa-home'.
   *
   * @var string
   */
  public $faIcon = "";

  /**
   * Text on the button, sanitized by htmlspecialchars().
   *
   * @var string
   */
  public $text = "";

  /**
   * Text on the button, not processed. If set, the text property is ignored.
   *
   * @var string
   */
  public $textRaw = "";

  /**
   * Additional CSS classes of the button.
   *
   * @var string
   */
  public $class = "";

  /**
   * Onclick functionality used as an inline Javascript.
   *
   * @var string
   */
  public $onclick = "";

  /**
   * A <i>title</i> attribute of the button.
   *
   * @var string
   */
  public $title = "";

  /**
   * CSS styling in the form of inline style.
   *
   * @var string
   */
  public $style = "";

  /**
   * If set to TRUE, the disabled attribute will be rendered.
   *
   * @var boolean
   */
  public $disabled = FALSE;

  /**
   * @internal
   */
  public function __construct(&$adios, $params = null) {
    $this->adios = $adios;

    // $this->languageDictionary = $this->adios->loadLanguageDictionary($this);

    $defParams = [];
    switch ($params['type'] ?? "") {
      case 'save':
        $defParams['faIcon'] = 'fas fa-check';
        $defParams['text'] = $this->translate("Save");
        $defParams['class'] = "btn-success btn-icon-split {$params['class']}";
        $defParams['onclick'] = "{$this->adios->uid}_save()";
        unset($params['class']);
      break;
      case 'search':
        $defParams['faIcon'] = 'fas fa-search';
        $defParams['text'] = $this->translate("Search");
        $defParams['class'] = "btn-light btn-icon-split {$params['class']}";
        $defParams['onclick'] = "{$this->adios->uid}_search()";
        unset($params['class']);
      break;
      case 'apply':
        $defParams['faIcon'] = 'fas fa-check';
        $defParams['text'] = $this->translate("Apply");
        $defParams['class'] = "btn-success btn-icon-split {$params['class']}";
        $defParams['onclick'] = "{$this->adios->uid}_apply()";
        unset($params['class']);
      break;
      case 'close':
        $defParams['faIcon'] = 'fas fa-times';
        $defParams['class'] = "btn-light {$params['class']}";
        $defParams['title'] = $this->translate("Close");
        $defParams['onclick'] = "{$this->adios->uid}_close()";
        unset($params['class']);
      break;
      case 'copy':
        $defParams['faIcon'] = 'fas fa-copy';
        $defParams['class'] = "btn-secondary btn-icon-split {$params['class']}";
        $defParams['text'] = $this->translate("Copy");
        $defParams['onclick'] = "{$this->adios->uid}_copy()";
        unset($params['class']);
      break;
      case 'add':
        $defParams['faIcon'] = 'fas fa-plus';
        $defParams['text'] = $this->translate("Add");
        $defParams['onclick'] = "{$this->adios->uid}_add()";
        unset($params['class']);
      break;
      case 'delete':
        $defParams['faIcon'] = 'fas fa-trash-alt';
        $defParams['class'] = "text-danger {$params['class']}";
        $defParams['title'] = $this->translate("Delete");
        $defParams['onclick'] = "{$this->adios->uid}_delete()";
        unset($params['class']);
      break;
      case 'cancel':
        $defParams['faIcon'] = 'app/x-mark-3.png';
        $defParams['text'] = $this->translate("Cancel");
        $defParams['onclick'] = "{$this->adios->uid}_cancel()";
        unset($params['class']);
      break;
      case 'confirm':
        $defParams['faIcon'] = 'app/ok.png';
        $defParams['text'] = $this->translate("Confirm");
        $defParams['onclick'] = "{$this->adios->uid}_confirm()";
        unset($params['class']);
      case 'print':
        $defParams['faIcon'] = 'fas fa-print';
        $defParams['text'] = $this->translate("Print");
        $defParams['onclick'] = "{$this->adios->uid}_print()";
        $defParams['class'] = "btn-info btn-icon-split {$params['class']}";
        unset($params['class']);
      break;
    }

    $this->params = array_merge($defParams, $params);

    parent::__construct($adios, $this->params);

    $this->faIcon = $this->params['faIcon'];
    $this->text = $this->params['text'];
    $this->textRaw = $this->params['textRaw'];
    $this->title = $this->params['title'];
    $this->class = $this->params['class'];
    $this->onclick = $this->params['onclick'];
    $this->style = $this->params['style'];
    $this->disabled = $this->params['disabled'];

    if (!empty($this->params['url'])) {
      $this->href = $this->adios->config['url'] . '/' . $this->params['url'];
    } else {
      $this->href = $this->params['href'] ?? 'javascript:void(0);';
    }

  }

  public function render(string $panel = ''): string
  {
    if (_count($this->params['dropdown'])) {
      $dropdowns_html = "";
      foreach ($this->params['dropdown'] as $dropdown) {
        if ($dropdown['faIcon'] != '') {
          $tmp_icon = "<i class='{$dropdown['faIcon']} mr-2'></i>";
        } else {
          $tmp_icon = "";
        }

        $dropdowns_html .= "
          <a
            class='dropdown-item'
            href='javascript:void(0)'
            onclick=\"{$dropdown['onclick']}\"
          >
            {$tmp_icon}
            ".hsc($dropdown['text'])."
          </a>
        ";
      }

      return "
        <span
          class='".($this->faIcon == "" ? "" : "no-arrow")." dropdown'
          title='".ads($this->title)."'
        >
          <a
            href='javascript:void(0);'
            role='button'
            class='
              btn
              dropdown-toggle
              ".($this->class == "" ? "btn-primary" : $this->class)."
            '
            id='{$this->uid}_dropdown_menu_button'
            style='{$this->style}'
            data-toggle='dropdown'
            aria-haspopup='true'
            aria-expanded='false'
            {$this->params['html_attributes']}
          >
            ".(empty($this->faIcon) ? "" : "
              <span class='icon'>
                <i class='{$this->faIcon}'></i>
              </span>
            ")."
            ".(empty($this->text) && empty($this->textRaw)
              ? ""
              : "<span class='text'>".(empty($this->textRaw) ? hsc($this->text) : $this->textRaw)."</span>"
            )."
          </a>
          <div class='dropdown-menu' aria-labelledby='{$this->uid}_dropdown_menu_button'>
            {$dropdowns_html}
          </div>
        </span>
      ";
    } else {
      return "
        <a
          href='".($this->href ?? "javascript:void(0);")."'
          id='".ads($this->uid)."'
          class='
            adios ui Button
            btn
            ".($this->class == "" ? "btn-primary btn-icon-split" : $this->class)."
          '
          style='{$this->style}'
          ".(empty($this->onclick)
            ? ""
            : "
            onclick=\"
              let _this = $(this);
              ".($this->params['cancel_bubble'] ? 'event.cancelBubble = true;' : '')."

              if (!_this.hasClass('disabled')) {
                {$this->onclick}
              }

              _this.addClass('disabled');
              setTimeout(function() {
                _this.removeClass('disabled');
              }, 300);

            \"
            "
          )."
          ".($this->disabled ? "disabled='disabled'" : '')."
          title='".ads($this->title)."'
          {$this->params['html_attributes']}
        >
          ".(empty($this->faIcon) ? "" : "
            <span class='icon'>
              <i class='{$this->faIcon}'></i>
            </span>
          ")."
          ".(empty($this->text) && empty($this->textRaw)
            ? ""
            : "<span class='text'>".(empty($this->textRaw) ? hsc($this->text) : $this->textRaw)."</span>"
          )."
        </a>
      ";
    }
  }
}
