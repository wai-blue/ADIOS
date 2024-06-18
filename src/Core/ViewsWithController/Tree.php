<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core\ViewsWithController;

/* UI komponenta, ktora okrem Input Tree generuje aj nejake buttony/titles a save funkcionalitu okolo */
/* pouziva \ADIOS\Core\ViewsWithController\Inputs\Tree */

class Tree extends \ADIOS\Core\ViewWithController
{

  const INITIAL_STATE_EXPANDED = 0;
  const INITIAL_STATE_COLLAPSED = 1;

  public ?\ADIOS\Core\Model $model = NULL;

  public function __construct(
   object $app,
   array $params = [],
   ?\ADIOS\Core\ViewWithController $parentView = NULL
  ) {

    $params = array_replace_recursive([
      'model' => '',
      'where' => [],
      'order' => '',
      'parentColumn' => '',
      'initialState' => self::INITIAL_STATE_EXPANDED,
      'onclick' => 'alert(nodeId);',
      'enableEditing' => FALSE,
    ], $params);

    // validacia parametrov

    if (empty($params['model'])) {
      exit("Components/Form: Don't know what model to work with.");
      return;
    }

    if (!in_array($params['initialState'], [self::INITIAL_STATE_EXPANDED, self::INITIAL_STATE_COLLAPSED])) {
      $params['initialState'] = self::INITIAL_STATE_EXPANDED;
    }

    // parent constructor
    parent::__construct($app, $params, $parentView);

    // finalizacia
    $this->model = $this->app->getModel($this->params['model']);

  }

  private function _itemDropdownButton($text, $hasSubItems)
  {
    return $this->addView('\\ADIOS\\Core\\ViewsWithController\\Button', [
      "faIcon" => "fas fa-angle-" . ($hasSubItems ? "down" : "right"),
      "text" => $text,
      "class" => "item btn btn-sm btn-secondary btn-icon-split my-1",
      "dropdown" => [
        [
          "faIcon" => "fas fa-pencil-alt",
          "text" => $this->translate("Edit"),
          "onclick" => "
            let li = $(this).closest('li');
            let btn = $(this).closest('.dropdown').find(' > .btn');

            ADIOS.renderWindow(
              '" . $this->model->getFullUrlBase($this->params) . "/' + li.data('id') + '/edit',
              '',
              function(res) {
                _ajax_read('Components/Tree/GetItemText', { model: '{$this->model->fullName}', id: res.data.id }, function(res2) {
                  btn.find('.text').text(res2);
                });
              }
            );
          ",
        ],
        [
          "faIcon" => "fas fa-level-up-alt",
          "text" => $this->translate("Move level up"),
          "onclick" => "
            var src = $(this).closest('li');
            var ul = src.closest('ul');
            var dst = ul.closest('li');
            var itemCnt = src.closest('ul').find('> li').length;
            src.insertBefore(dst);
            if (ul.find('li').length == 0) {
              ul.hide();
            }

            {$this->uid}_serialize();
          ",
        ],
        [
          "faIcon" => "fas fa-level-down-alt",
          "text" => $this->translate("Move level down"),
          "onclick" => "
            var src = $(this).closest('li');
            var ul = src.next('li').find('> ul');
            var dst = ul.find('li').eq(0);

            src.insertBefore(dst);
            ul.show();

            {$this->uid}_serialize();
          ",
        ],
        [
          "faIcon" => "fas fa-trash",
          "text" => $this->translate("Select for deletion"),
          "onclick" => "
            let li = $(this).closest('li');
            li.addClass('to-delete');
            li.find('li').addClass('to-delete');
            li.find('.btn').addClass('btn-danger');

            {$this->uid}_serialize();
          ",
        ],
        [
          "faIcon" => "fas fa-trash-restore",
          "text" => $this->translate("Unselect from deletion"),
          "onclick" => "
            let li = $(this).closest('li');
            li.removeClass('to-delete');
            li.find('li').removeClass('to-delete');
            li.find('.btn').removeClass('btn-danger');

            {$this->uid}_serialize();
          ",
        ],
      ],
    ]);
  }

  private function _renderTree($items, $parentColumn, $parent = 0)
  {
    $itemsHtml = "";

    foreach ($items as $item) {
      if ((int) $item[$parentColumn] == (int) $parent) {
        $subItemsCnt = 0;
        foreach ($items as $subItem) {
          if ((int) $subItem[$parentColumn] == (int) $item['id']) {
            $subItemsCnt++;
          }
        }

        // $this->enumValues = $this->model->getEnumValues();
        // $buttonDropdownHtml = $this->_itemDropdownButton(
        //   $this->enumValues[$item['id']],
        //   $subItemsCnt > 0
        // )->render();

        $itemsHtml .= "
          <li class='node' data-id='{$item['id']}'>
            <div class='item'>
              ".($subItemsCnt == 0 ? "" : "
                <div class='expand-collapse-btn'>
                  <a
                    href='javascript:void(0);'
                    onclick='{$this->uid}_expand(this);'
                    class='btn-sm bg-light m-2 expand-btn'
                    ".($this->params['initialState'] == self::INITIAL_STATE_EXPANDED ? "style='display:none'" : "")."
                  >
                    <span class='icon'>
                      <i class='fas fa-plus'></i>
                    </span>
                  </a>
                  <a
                    href='javascript:void(0);'
                    onclick='{$this->uid}_collapse(this);'
                    class='btn-sm bg-light m-2 collapse-btn'
                    ".($this->params['initialState'] == self::INITIAL_STATE_COLLAPSED ? "style='display:none'" : "")."
                  >
                    <span class='icon'>
                      <i class='fas fa-minus'></i>
                    </span>
                  </a>
                </div>
              ")."
              <div
                class='title ".(empty($this->params['onclick']) ? "" : "clickable")."'
                ".(empty($this->params['onclick']) ? "" : "
                  onclick=\"
                    let node = $(this).closest('.node');
                    let nodeId = $(node).data('id');
                    {$this->params['onclick']}
                  \"
                ")."
              >
                ".($this->params['enableEditing'] ?
                  $this->_itemDropdownButton(
                    $item['name'],
                    $subItemsCnt > 0
                  )->render()
                : hsc($item['name'])
                )."
              </div>
            </div>
            ".($subItemsCnt == 0 ? "" : "
              <div
                class='sub-tree'
                ".($this->params['initialState'] == self::INITIAL_STATE_COLLAPSED ? "style='display:none'" : "")."
              >
                ".$this->_renderTree($items, $parentColumn, $item['id'])."
              </div>
            ")."
          </li>
        ";
      }
    }

    $treeHtml = "
      <ul class='adios ui Tree'>
        {$itemsHtml}
        ".($this->params['enableEditing'] ? "
          <li class='node' data-id='-1' style='display:none'>
            ".$this->_itemDropdownButton("Pridať", FALSE)->render()."
          </li>
          <li>
            ".$this->addView('\\ADIOS\\Core\\ViewsWithController\\Button', [
            "faIcon" => "fas fa-plus",
            "text" => $this->translate("Add"),
            "class" => "item btn btn-sm btn-light btn-icon-split my-1",
            "onclick" => "
                let ul = $(this).closest('ul');
                let li = ul.find(' > li[data-id=-1]');

                ADIOS.renderWindow(
                  '" . $this->model->getFullUrlBase($this->params) . "/{$parent}/Add',
                  {},
                  function(res) {
                    if (res.data.id > 0) {
                      _ajax_read(
                        'Components/Tree/GetItemText',
                        {
                          model: '{$this->model->fullName}',
                          id: res.data.id
                        },
                        function(res2) {
                          let clone = li.clone(true);
                          clone
                            .data('id', res.data.id)
                            .insertBefore(li)
                            .show()
                          ;
                          clone.find('.dropdown').find('.text').text(res2);
                          ul.show();

                          {$this->uid}_serialize();
                        }
                      );
                    }
                  }
                );
              ",
            ])->render()."
          </li>
        " : "")."
      </ul>
    ";

    return $treeHtml;
  }


  public function render(string $panel = ''): string
  {

    $inputUid = $this->app->getUid($this->model->fullName);

    // $contentHtml = (new \ADIOS\Core\ViewsWithController\Inputs\Tree($this->app, $inputUid, $this->params))->render();

    // najdem stlpec pre rodica
    $parentColumn = $this->params['parentColumn'] ?? '';

    if (empty($parentColumn)) {
      foreach ($this->model->columns() as $colName => $colDef) {
        if ($colDef["type"] == "lookup" && $colDef["model"] == $this->model->fullName) {
          $parentColumn = $colName;
        }
      }
    }

    // nacitam data
    $items = $this->model;
    if (!empty($this->params['order'])) {
      $items = $items->orderBy($this->params['order']);
    }
    if (!empty($this->params['where'])) {
      $items = $items->where($this->params['where']);
    }
    $items = $items->get()->toArray();

    $treeHtml = $this->_renderTree($items, $parentColumn);

    // $contentHtml

    $contentHtml = "
      <input type='hidden' id='{$this->uid}' />

      <div class='row mb-3'>
        <div id='{$this->uid}_wrapper'>
          {$treeHtml}
        </div>
      </div>

      <script>

        $('.adios.ui.Tree li .btn-secondary .icon').click(function() {
          $(this).closest('li.node').find(' > ul').toggle();

          let i = $(this).find('i');
          if (i.hasClass('fa-angle-down')) {
            i.removeClass('fa-angle-down');
            i.addClass('fa-angle-right');
          } else {
            i.addClass('fa-angle-down');
            i.removeClass('fa-angle-right');
          }

          return false;
        });

        function {$this->uid}_expand(btn) {
          let item = $(btn).closest('.item');
          let node = $(btn).closest('.node');

          item.find('.expand-btn').hide();
          item.find('.collapse-btn').show();
          node.find('> .sub-tree').show();
        }

        function {$this->uid}_collapse(btn) {
          let item = $(btn).closest('.item');
          let node = $(btn).closest('.node');

          item.find('.expand-btn').show();
          item.find('.collapse-btn').hide();
          node.find('> .sub-tree').hide();
        }

        function {$this->uid}_serialize() {
          let serialized = [];

          $('#{$this->uid}_wrapper').find('li.node').each(function() {
            serialized.push({
              id: $(this).data('id'),
              toDelete: ($(this).hasClass('to-delete') ? true : false),
              parent: $(this).closest('ul').closest('li').data('id') || 0,
            });
          });

          $('#{$this->uid}').val(JSON.stringify(serialized));

          return serialized;
        }

        {$this->uid}_serialize();
      </script>
    ";


    $contentHtml .= "
      <script>
        function {$this->uid}_close() {
          try {
            window_close('{$this->uid}_window');
          } catch(e) { }
        }

        function {$this->uid}_save() {
          let serialized = {$inputUid}_serialize();
          let data = {
            'model': '{$this->model->fullName}',
            'values': serialized,
          };

          _ajax_read('Components/Tree/Save', data, function(res) {
            if (isNaN(res)) {
              _alert(res);
            } else {
              $('#{$this->uid}_save_info_span').fadeIn();
              setTimeout(function() {
                $('#{$this->uid}_save_info_span').fadeOut();
              }, 1000);
            }
          });
        }

      </script>
    ";



    return $this->applyDisplayMode((string) $contentHtml);


    // if ($this->params['__IS_WINDOW__']) {
    //   $contentHtml = $this->app->view->Window(
    //     [
    //       'uid' => "{$this->uid}_window",
    //       'content' => $contentHtml,
    //       'header' => [
    //         $this->addView('\\ADIOS\\Core\\ViewsWithController\\Button', ["text" => $this->translate("Close"), "type" => "close", "onclick" => "{$this->uid}_close();"]),
    //         $this->addView('\\ADIOS\\Core\\ViewsWithController\\Button', ["text" => $this->translate("Save"), "type" => "save", "onclick" => "{$this->uid}_save();"]),
    //         "
    //           <span id='{$this->uid}_save_info_span' class='pl-4' style='color:green;display:none'>
    //             <i class='fas fa-check'></i>
    //               " . $this->translate("Saved") . "
    //           </span>
    //         ",
    //       ],
    //       'form_close_click' => $this->params['onclose'],
    //       'title' => htmlspecialchars($this->params['title']),
    //     ]
    //   )->render();
    // } else {
    //   $html = $this->addView('\\ADIOS\\Core\\ViewsWithController\\Title', [
    //     'left' => [
    //       "
    //         <span id='{$this->uid}_save_info_span' class='pr-4' style='color:green;display:none'>
    //           <i class='fas fa-check'></i>
    //             " . $this->translate("Saved") . "
    //         </span>
    //       ",
    //       $this->addView('\\ADIOS\\Core\\ViewsWithController\\Button', ["text" => $this->translate("Save"), "type" => "save", "onclick" => "{$this->uid}_save();"]),
    //     ],
    //     'center' => $this->params['title']
    //   ])->render();
    //   $html .= $contentHtml;
    // }

    // return $html;
  }
}
