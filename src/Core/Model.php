<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

use ADIOS\Core\Db\DataType;
use ADIOS\Core\DB\Query;
use ADIOS\Core\Exceptions\DBException;
use ADIOS\Core\Exceptions\RecordDeleteException;
use ADIOS\Core\Exceptions\RecordSaveException;
use ADIOS\Core\ViewsWithController\Form;
use ADIOS\Core\ViewsWithController\Table;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;

/**
 * Core implementation of database model. Extends from Eloquent's model and adds own
 * functionalities.
 */
class Model
{
  const HAS_ONE = 'hasOne';
  const HAS_MANY = 'hasMany';
  const BELONGS_TO = 'belongsTo';

  /**
   * Full name of the model. Useful for getModel() function
   */
  public string $fullName = "";

  /**
   * Short name of the model. Useful for debugging purposes
   */
  public string $shortName = "";

  /**
   * Reference to ADIOS object
   *
   * @var mixed
   */
  public ?Loader $app = NULL;

  /**
   * Shorthand for "global table prefix"
   */
  public ?string $gtp = "";

  /**
   * Name of the table in SQL database. Used together with global table prefix.
   */
  public string $sqlName = '';

  /**
   * SQL-compatible string used to render displayed value of the record when used
   * as a lookup.
   */
  public ?string $lookupSqlValue = NULL;

  /**
   * If set to TRUE, the SQL table will not contain the ID autoincrement column
   */
  public bool $isJunctionTable = FALSE;

  var $pdo;

  /**
   * Property used to store original data when recordSave() method is calledmodel
   *
   * @var mixed
   */
  // var $recordSaveOriginalData = NULL;
  // protected string $fullTableSqlName = "";
  public string $table = '';
  public string $eloquentClass = '';
  public array $relations = [];

  public ?array $junctions = [];
  public ?\Illuminate\Database\Eloquent\Model $eloquent = null;


  /**
   * Creates instance of model's object.
   *
   * @param mixed $app
   * @return void
   */
  public function __construct(\ADIOS\Core\Loader $app)
  {
    $this->gtp = $app->config['gtp'] ?? '';

    // if (empty($this->table)) {
    //   $this->table = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName;
    // }

    if (empty($this->table)) {
      $this->table = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName; // toto je kvoli Eloquentu
    }

    $eloquentClass = $this->eloquentClass;
    if (empty($eloquentClass)) throw new Exception(get_class($this). ' - empty eloquentClass');
    $this->eloquent = new $eloquentClass;
    $this->eloquent->setTable($this->table);

    $this->fullName = str_replace("\\", "/", get_class($this));

    $tmp = explode("/", $this->fullName);
    $this->shortName = end($tmp);

    $this->app = $app;

    try {
      $this->pdo = $this->eloquent->getConnection()->getPdo();
    } catch (Exception $e) {
      $this->pdo = null;
    }

    // During the installation no SQL tables exist. If child's init()
    // method uses data from DB, $this->init() call would fail.
    // Therefore the 'try ... catch'.
    try {
      $this->init();
    } catch (Exception $e) {
      //
    }

    $this->app->db->addTable(
      $this->table,
      $this->columns(),
      $this->isJunctionTable
    );

    $currentVersion = (int)$this->getCurrentInstalledVersion();
    $lastVersion = $this->getLastAvailableVersion();

    if ($lastVersion == 0) {
      $this->saveConfig('installed-version', $lastVersion);
    }

    if ($this->hasAvailableUpgrades()) {

      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has new upgrades available (from {$currentVersion} to {$lastVersion}).
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Install upgrades</a>
      ");
    } else if (!$this->hasSqlTable()) {
      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has no SQL table.
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Create table</a>
      ");
    } else if (!$this->isInstalled()) {
      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> is not installed.
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Install model</a>
      ");
    }
  }

  /**
   * Empty placeholder for callback called after the instance has been created in constructor.
   *
   * @return void
   */
  public function init()
  { /* to be overriden */
  }

  /**
   * Retrieves value of configuration parameter.
   *
   * @return void
   */
  public function getConfig(string $configName): string
  {
    return $this->app->config['models'][str_replace("/", "-", $this->fullName)][$configName] ?? "";
  }

  /**
   * Sets the value of configuration parameter.
   *
   * @return void
   */
  public function setConfig(string $configName, $value): void
  {
    $this->app->config['models'][str_replace("/", "-", $this->fullName)][$configName] = $value;
  }

  /**
   * Persistantly saves the value of configuration parameter to the database.
   *
   * @return void
   */
  public function saveConfig(string $configName, $value): void
  {
    $this->app->saveConfig([
      "models" => [
        str_replace("/", "-", $this->fullName) => [
          $configName => $value,
        ],
      ],
    ]);
  }

  /**
   * Shorthand for ADIOS core translate() function. Uses own language dictionary.
   *
   * @param string $string String to be translated
   * @param string $context Context where the string is used
   * @param string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->app->translate($string, $vars, $this);
  }

  public function hasSqlTable()
  {
    return in_array($this->table, $this->app->db->existingSqlTables);
  }

  /**
   * Checks whether model is installed.
   *
   * @return bool TRUE if model is installed, otherwise FALSE.
   */
  public function isInstalled(): bool
  {
    return $this->getConfig('installed-version') != "";
  }

  /**
   * Gets the current installed version of the model. Used during installing upgrades.
   *
   * @return void
   */
  public function getCurrentInstalledVersion(): int
  {
    return (int)($this->getConfig('installed-version') ?? 0);
  }

  public function getLastAvailableVersion(): int
  {
    return max(array_keys($this->upgrades()));
  }

  /**
   * Returns list of available upgrades. This method must be overriden by each model.
   *
   * @return array List of available upgrades. Keys of the array are simple numbers starting from 1.
   */
  public function upgrades(): array
  {
    return [
      0 => [], // upgrade to version 0 is the same as installation
    ];
  }

  /**
   * Installs the first version of the model into SQL database. Automatically creates indexes.
   *
   * @return void
   */
  public function install()
  {
    if (!empty($this->table)) {
      $this->app->db->createSqlTable($this->table);

      foreach ($this->indexes() as $indexOrConstraintName => $indexDef) {
        if (empty($indexOrConstraintName) || is_numeric($indexOrConstraintName)) {
          $indexOrConstraintName = md5(json_encode($indexDef) . uniqid());
        }

        $tmpColumns = "";

        foreach ($indexDef['columns'] as $tmpKey => $tmpValue) {
          if (!is_numeric($tmpKey)) {
            // v tomto pripade je nazov stlpca v kluci a vo value mozu byt dalsie nastavenia
            $tmpColumnName = $tmpKey;
            $tmpOrder = strtolower($tmpValue['order'] ?? 'asc');
            if (!in_array($tmpOrder, ['asc', 'desc'])) {
              $tmpOrder = 'asc';
            }
          } else {
            $tmpColumnName = $tmpValue;
            $tmpOrder = '';
          }

          $tmpColumns .=
            ($tmpColumns == '' ? '' : ', ')
            . '`' . $tmpColumnName . '`'
            . (empty($tmpOrder) ? '' : ' ' . $tmpOrder);
        }

        switch ($indexDef["type"]) {
          case "index":
            $this->app->db->query("
              alter table `" . $this->table . "`
              add index `{$indexOrConstraintName}` ({$tmpColumns})
            ");
            break;
          case "unique":
            $this->app->db->query("
              alter table `" . $this->table . "`
              add constraint `{$indexOrConstraintName}` unique ({$tmpColumns})
            ");
            break;
        }
      }

      $this->createSqlForeignKeys();

      $this->saveConfig('installed-version', max(array_keys($this->upgrades())));

      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function hasAvailableUpgrades(): bool
  {
    $currentVersion = $this->getCurrentInstalledVersion();
    $lastVersion = $this->getLastAvailableVersion();
    return ($lastVersion > $currentVersion);
  }

  /**
   * Installs all upgrades of the model. Internaly stores current version and
   * compares it to list of available upgrades.
   *
   * @return void
   * @throws DBException When an error occured during the upgrade.
   */
  public function installUpgrades(): void
  {
    if ($this->hasAvailableUpgrades()) {
      $currentVersion = (int)$this->getCurrentInstalledVersion();
      $lastVersion = $this->getLastAvailableVersion();

      try {
        $this->app->db->startTransaction();

        $upgrades = $this->upgrades();

        for ($v = $currentVersion + 1; $v <= $lastVersion; $v++) {
          if (is_array($upgrades[$v])) {
            foreach ($upgrades[$v] as $query) {
              $this->app->db->query($query);
            }
          }
        }

        $this->app->db->commit();
        $this->saveConfig('installed-version', $lastVersion);
      } catch (DBException $e) {
        $this->app->db->rollback();
        throw new DBException($e->getMessage());
      }
    }
  }

  public function dropTableIfExists(): \ADIOS\Core\Model
  {
    $this->app->db->query("set foreign_key_checks = 0");
    $this->app->db->query("drop table if exists `" . $this->table . "`");
    $this->app->db->query("set foreign_key_checks = 1");
    return $this;
  }

  /**
   * Create foreign keys for the SQL table. Called when all models are installed.
   *
   * @return void
   */
  public function createSqlForeignKeys()
  {

    $sql = '';
    foreach ($this->columns() as $column => $columnDefinition) {
      if (!empty($onlyColumn) && $onlyColumn != $column) continue;

      if (
        !($columnDefinition['disableForeignKey'] ?? false)
        && 'lookup' == $columnDefinition['type']
      ) {
        $lookupModel = $this->app->getModel($columnDefinition['model']);
        $foreignKeyColumn = $columnDefinition['foreignKeyColumn'] ?? "id";
        $foreignKeyOnDelete = $columnDefinition['foreignKeyOnDelete'] ?? "RESTRICT";
        $foreignKeyOnUpdate = $columnDefinition['foreignKeyOnUpdate'] ?? "RESTRICT";

        $sql .= "
          ALTER TABLE `{$this->table}`
          ADD CONSTRAINT `fk_" . md5($this->table . '_' . $column) . "`
          FOREIGN KEY (`{$column}`)
          REFERENCES `" . $lookupModel->getFullTableSqlName() . "` (`{$foreignKeyColumn}`)
          ON DELETE {$foreignKeyOnDelete}
          ON UPDATE {$foreignKeyOnUpdate};;
        ";
      }
    }

    if (!empty($sql)) {
      $this->app->db->multiQuery($sql);
    }

    // if (!empty($this->table)) {
    //   $this->app->db->createSqlForeignKeys($this->table);
    // }
  }

  /**
   * Returns full name of the model's SQL table
   *
   * @return string Full name of the model's SQL table
   */
  public function getFullTableSqlName()
  {
    return $this->table;
  }

  //////////////////////////////////////////////////////////////////
  // misc helper methods

  public function findForeignKeyModels()
  {
    $foreignKeyModels = [];

    foreach ($this->app->models as $model) {
      foreach ($model->columns() as $colName => $colDef) {
        if (!empty($colDef["model"]) && $colDef["model"] == $this->fullName) {
          $foreignKeyModels[$model->fullName] = $colName;
        }
      }
    }

    return $foreignKeyModels;
  }

  public function getEnumValues()
  {
    $tmp = $this->eloquent
      ->selectRaw("{$this->table}.id")
      ->selectRaw("(" . str_replace("{%TABLE%}", $this->table, $this->lookupSqlValue()) . ") as ___lookupSqlValue")
      ->orderBy("___lookupSqlValue", "asc")
      ->get()
      ->toArray();

    $enumValues = [];
    foreach ($tmp as $key => $value) {
      $enumValues[$value['id']] = $value['___lookupSqlValue'];
    }

    return $enumValues;
  }

  //////////////////////////////////////////////////////////////////
  // definition of columns

  public function columns(array $columns = []): array
  {
    $newColumns = [];

    if (!$this->isJunctionTable) {
      $newColumns['id'] = [
        'type' => 'int',
        'byte_size' => '8',
        'rawSqlDefinitions' => 'primary key auto_increment',
        'title' => 'ID',
        'readonly' => 'yes',
        'viewParams' => [
          'Table' => ['show' => TRUE],
          'Form' => ['show' => TRUE]
        ],
      ];
    }

    // default column settings
    foreach ($columns as $colName => $colDefinition) {
      $newColumns[$colName] = $colDefinition;

      if ($colDefinition["type"] == "char") {
        $this->app->console->info("{$this->fullName}, {$colName}: char type is deprecated");
      }

      switch ($colDefinition["type"]) {
        case "int":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 8;
          break;
        case "float":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 14;
          $newColumns[$colName]["decimals"] = $colDefinition["decimals"] ?? 2;
          break;
        case "varchar":
        case "password":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 255;
          break;
        case "lookup":
          $newColumns[$colName]["model"] = trim(str_replace("\\", "/", $newColumns[$colName]["model"]), "/");
          break;
      }
    }

    foreach ($newColumns as $colName => $colDef) {
      $colObject = $this->app->db->columnTypes[$colDef['type']] ?? null;

      if ($colObject instanceof DataType) {
        $newColumns[$colName] = $colObject->columnDefinitionPostProcess($colDef);
      }
    }

    $newColumns = $this->app->dispatchEventToPlugins("onModelAfterColumns", [
      "model" => $this,
      "columns" => $newColumns,
    ])["columns"];

    $this->eloquent->fillable = array_keys($newColumns);

    return $newColumns;
  }

  public function columnNames()
  {
    return array_keys($this->columns());
  }

  public function indexes(array $indexes = [])
  {
    return $this->app->dispatchEventToPlugins("onModelAfterIndexes", [
      "model" => $this,
      "indexes" => $indexes,
    ])["indexes"];
  }

  public function indexNames()
  {
    return array_keys($this->indexNames());
  }

  //////////////////////////////////////////////////////////////////
  // CRUD methods

  public function getById(int $id)
  {
    $item = $this->recordGet(function($q) use ($id) { $q->where($this->table . '.id', $id); });
    return $item;
  }

  public function getLookupSqlValueById(int $id)
  {
    $row = $this->app->db->select($this)
      ->columns([
        [$this->lookupSqlValue($this->table), 'lookup_value']
      ])
      ->where([['id', '=', $id]])
      ->fetch();

    return $row[0]['lookup_value'] ?? '';
  }

  public function insertRow($data)
  {
    return $this->app->db->insert($this)
      ->set($data)
      ->execute();
  }

  public function insertRowWithId($data)
  {
    return $this->app->db->insert($this)
      ->set($data)
      ->execute();
  }

  public function insertOrUpdateRow($data)
  {
    unset($data['id']);

    $duplicateKeyData = $data;

    return $this->app->db->insert($this)
      ->set($data)
      ->onDuplicateKey($duplicateKeyData)
      ->execute();
  }

  public function insertRandomRow($data = [], $dictionary = [])
  {
    return $this->insertRow(
      $this->app->db->getRandomColumnValues($this, $data, $dictionary)
    );
  }

  public function updateRow($data, $id)
  {
    $queryOk = $this->app->db->update($this)
      ->set($data)
      ->whereId((int)$id)
      ->execute();

    return ($queryOk ? $id : FALSE);
  }

  public function deleteRow($id)
  {
    return $this->app->db->delete($this)
      ->whereId((int)$id)
      ->execute();
  }

  public function copyRow($id)
  {
    $row = $this->app->db->select($this)
      ->columns([Query::allColumnsWithoutLookups])
      ->where([
        ['id', '=', (int)$id]
      ])
      ->fetchOne();

    unset($row['id']);

    return $this->insertRow($row);
  }

  public function search($q)
  {
  }

  //////////////////////////////////////////////////////////////////
  // lookup processing methods

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupWhere(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = []
  )
  {
    return [];
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupOrder(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = []
  )
  {
    return [['input_lookup_value', 'asc']];
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupQuery(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = [],
    $having = "TRUE"
  ): Query
  {
    $where = $params['where'] ?? $this->lookupWhere($initiatingModel, $initiatingColumn, $formData, $params);
    $order = $params['order'] ?? $this->lookupOrder($initiatingModel, $initiatingColumn, $formData, $params);

    return $this->app->db->select($this)
      ->columns([
        ['id', 'id'],
        [$this->lookupSqlValue($this->table), 'input_lookup_value']
      ])
      ->where($where)
      ->havingRaw($having)
      ->order($order);
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupSqlQuery(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = [],
    $having = "TRUE"
  ): string
  {
    return $this->lookupQuery(
      $initiatingModel,
      $initiatingColumn,
      $formData,
      $params,
      $having
    )->buildSql();
  }

  public function lookupSqlValue($tableAlias = NULL): string
  {
    $value = $this->lookupSqlValue ?? "concat('{$this->fullName}, id = ', {%TABLE%}.id)";

    return ($tableAlias !== NULL
      ? str_replace('{%TABLE%}', "`{$tableAlias}`", $value)
      : $value
    );
  }

  public function tableDescribe(array $description = []): array {
    $columns = $this->columns();
    unset($columns['id']);

    $description = [
      'ui' => [
        'showHeader' => true,
        'showFooter' => true,
        'showFilter' => true,
      ],
      'columns' => $columns,
      'permissions' => [
        'canRead' => $this->app->permissions->granted($description['model'] . ':Read'),
        'canCreate' => $this->app->permissions->granted($description['model'] . ':Create'),
        'canUpdate' => $this->app->permissions->granted($description['model'] . ':Update'),
        'canDelete' => $this->app->permissions->granted($description['model'] . ':Delete'),
      ],
    ];

    return $description;
  }

  public function formDescribe(array $description = []): array {
    $columns = $this->columns();
    unset($columns['id']);

    $description = [
      'columns' => $columns,
      'defaultValues' => $this->recordDefaultValues(),
      'permissions' => [
        'canRead' => $this->app->permissions->granted($description['model'] . ':Read'),
        'canCreate' => $this->app->permissions->granted($description['model'] . ':Create'),
        'canUpdate' => $this->app->permissions->granted($description['model'] . ':Update'),
        'canDelete' => $this->app->permissions->granted($description['model'] . ':Delete'),
      ],
      'includeRelations' => [],
    ];

    return $description;
  }


  //////////////////////////////////////////////////////////////////
  // Column-related methods

  public function columnValidate(string $column, $value): bool
  {
    $valid = TRUE;

    $colDefinition = $this->columns()[$column] ?? [];
    $colType = $colDefinition['type'];

    if ($this->app->db->isRegisteredColumnType($colType)) {
      $valid = $this->app->db->columnTypes[$colType]->validate($this, $value);
    }

    return $valid;
  }

  public function columnNormalize(string $column, $value)
  {
    $colDefinition = $this->columns()[$column] ?? [];
    $colType = $colDefinition['type'];

    if ($this->app->db->isRegisteredColumnType($colType)) {
      $value = $this->app->db->columnTypes[$colType]->normalize($this, $column, $value, $colDefinition);
    }

    return $value;
  }

  public function columnGetNullValue(string $column)
  {
    $colDefinition = $this->columns()[$column] ?? [];
    $colType = $colDefinition['type'];

    if ($this->app->db->isRegisteredColumnType($colType)) {
      $value = $this->app->db->columnTypes[$colType]->getNullValue($this, $column);
    }

    return $value;
  }

  //////////////////////////////////////////////////////////////////
  // Record-related methods

  // public function recordDescribe() {
  //   $description = [
  //     'columns' => $this->columns(),
  //     'defaultValues' => $this->recordDefaultValues(),
  //   ];
  //   return $description;
  // }

  public function recordValidate($data)
  {
    $invalidInputs = [];

    foreach ($this->columns() as $column => $colDefinition) {
      if (
        $colDefinition['required']
        && ($data[$column] == NULL || $data[$column] == '')
      ) {
        $invalidInputs[] = $this->app->translate(
          "`{{ colTitle }}` is required.",
          ['colTitle' => $colDefinition['title']]
        );
      } else if (!$this->columnValidate($column, $data[$column])) {
        $invalidInputs[] = $this->app->translate(
          "`{{ colTitle }}` contains invalid value.",
          ['colTitle' => $colDefinition['title']]
        );
      }
    }

    if (!empty($invalidInputs)) {
      throw new RecordSaveException(
        json_encode(['code' => 87335, 'data' => $invalidInputs])
      );
    }

    return $this->app->dispatchEventToPlugins("onModelAfterRecordValidate", [
      "model" => $this,
      "data" => $data
    ])['params'];
  }

  public function recordNormalize(array $data): array {
    $columns = $this->columns();

    // Vyhodene, pretoze to v recordSave() sposobovalo mazanie udajov
    // foreach ($columns as $colName => $colDef) {
    //   if (!isset($data[$colName])) $data[$colName] = NULL;
    // }

    foreach ($data as $colName => $colValue) {
      if (!isset($columns[$colName])) {
        unset($data[$colName]);
      } else {
        $data[$colName] = $this->columnNormalize($colName, $data[$colName]);
        if ($data[$colName] === null) unset($data[$colName]);
      }
    }

    foreach ($columns as $colName => $colDef) {
      if (!isset($data[$colName])) $data[$colName] = $this->columnGetNullValue($colName);
    }

    return $data;
  }

  /**
   * Check if the lookup table needs the id of the inserted record from this model
   */
  private function ___getInsertedIdForLookupColumn(array $lookupColumns, array $lookupData, int $insertedRecordId): array
  {
    foreach ($lookupColumns as $lookupColumnName => $lookupColumnData) {
      if ($lookupColumnData['type'] != 'lookup') continue;

      if ($lookupColumnData['model'] == $this->fullName) {
        $lookupData[$lookupColumnName] = $insertedRecordId;
        break;
      }
    }

    return $lookupData;
  }

  private function ___validateBase64Image(string $base64String)
  {
    $pattern = '/^data:image\/[^;]+;base64,/';
    return preg_match($pattern, $base64String);
  }

  public function recordCreate(array $data): int {
    return $this->eloquent->create($data)->id;
  }

  public function recordUpdate(int $id, array $data): int {
    $this->eloquent->find($id)->update($data);
    return $id;
  }

  public function recordSave(array $data)
  {
    $id = (int) $data['id'];
    $isCreate = ($id <= 0);

    if ($isCreate) {
      $this->app->permissions->check($this->fullName . ':Create');
    } else {
      $this->app->permissions->check($this->fullName . ':Update');
    }

    $dataForThisModel = $data;

    $this->recordValidate($dataForThisModel);

    if ($isCreate) {
      $dataForThisModel = $this->onBeforeCreate($dataForThisModel);
    } else {
      $dataForThisModel = $this->onBeforeUpdate($dataForThisModel);
    }

    $dataForThisModel = $this->recordNormalize($dataForThisModel);

    if ($isCreate) {
      unset($dataForThisModel['id']);
      $returnValue = $this->recordCreate($dataForThisModel);
      $data['id'] = (int) $returnValue;
      $id = (int) $returnValue;
    } else {
      $returnValue = $this->recordUpdate($id, $dataForThisModel);
    }

    // save cross-table-alignments
    foreach ($this->junctions as $jName => $jParams) {
      if (!isset($data[$jName])) continue;

      $junctions = $data[$jName] ?? NULL;
      if (!is_array($junctions)) {
        $junctions = @json_decode($data[$jName], TRUE);
      }

      if (is_array($junctions)) {
        $junctionModel = $this->app->getModel($jParams["junctionModel"]);

        $this->app->pdo->execute("
          delete from `{$junctionModel->getFullTableSqlName()}`
          where `{$jParams['masterKeyColumn']}` = ?
        ", [$id]);

        foreach ($junctions as $junction) {
          $idOption = (int) $junction;
          if ($idOption > 0) {
            $this->app->pdo->execute("
              insert into `{$junctionModel->getFullTableSqlName()}` (
                `{$jParams['masterKeyColumn']}`,
                `{$jParams['optionKeyColumn']}`
              ) values (?, ?)
            ", [$id, $idOption]);
          }
        }
      }
    }

    if ($isCreate) {
      $returnValue = $this->onAfterCreate($data, $returnValue);
    } else {
      $returnValue = $this->onAfterUpdate($data, $returnValue);
    }

    return $returnValue;
  }

  public function recordDelete(int $id): bool
  {
    return $this->eloquent->where('id', $id)->delete();
  }

  public function recordDefaultValues(): array
  {
    return [];
  }

  public function recordRelations(): array
  {
    $relations = [];

    foreach ($this->relations as $relName => $relDefinition) {
      $relations[$relName]['type'] = $relDefinition[0];
      $relations[$relName]['template'] = [$relDefinition[2] => ['_useMasterRecordId_' => true]];
    }

    return $relations;
  }

  public function loadRecords(callable|null $queryModifierCallback = null, array|null $includeRelations = null, int $maxRelationLevel = 0): array {
    $query = $this->prepareLoadRecordQuery($includeRelations, $maxRelationLevel);
    if ($queryModifierCallback !== null) $queryModifierCallback($query);

    $records = $query->get()?->toArray();

    if (!is_array($records)) $records = [];

    foreach ($records as $key => $record) {
      $records[$key] = $this->recordEncryptIds($records[$key]);
      $records[$key] = $this->recordAddCustomData($records[$key]);
      $records[$key] = $this->onAfterLoadRecord($records[$key]);
      $records[$key]['_RELATIONS'] = array_keys($this->relations);
      if (is_array($includeRelations)) $records[$key]['_RELATIONS'] = array_values(array_intersect($records[$key]['_RELATIONS'], $includeRelations));
    }

    $records = $this->onAfterLoadRecords($records);

    return $records;
  }

  public function recordEncryptIds(array $record) {

    foreach ($this->columns() as $colName => $colDefinition) {
      if ($colName == 'id' || $colDefinition['type'] == 'lookup') {
        if ($record[$colName] !== null) {
          $record[$colName] = \ADIOS\Core\Helper::encrypt($record[$colName]);
        }
      }
    }

    $record['_idHash_'] =  \ADIOS\Core\Helper::encrypt($record['id'], '', true);

    // foreach ($this->rela
    return $record;
  }

  public function recordDecryptIds(array $record) {
    foreach ($this->columns() as $colName => $colDefinition) {
      if ($colName == 'id' || $colDefinition['type'] == 'lookup') {
        if ($record[$colName] !== null && is_string($record[$colName])) {
          $record[$colName] = \ADIOS\Core\Helper::decrypt($record[$colName]);
        }
      }
    }

    foreach ($this->relations as $relName => $relDefinition) {
      if (!is_array($record[$relName])) continue;

      list($relType, $relModelClass) = $relDefinition;
      $relModel = new $relModelClass($this->app);

      switch ($relType) {
        case \ADIOS\Core\Model::HAS_MANY:
          foreach ($record[$relName] as $subKey => $subRecord) {
            $record[$relName][$subKey] = $relModel->recordDecryptIds($record[$relName][$subKey]);
          }
        break;
        case \ADIOS\Core\Model::HAS_ONE:
          $record[$relName] = $relModel->recordDecryptIds($record[$relName]);
        break;
      }
    }

    return $record;
  }

  public function recordGet(
    callable|null $queryModifierCallback = null,
    array|null $includeRelations = null,
    int $maxRelationLevel = 0
  ): array {
    $record = reset($this->loadRecords($queryModifierCallback, $includeRelations, $maxRelationLevel));
    if (!is_array($record)) $record = [];
    return $record;
  }

  public function prepareLoadRecordQuery(array|null $includeRelations = null, int $maxRelationLevel = 0, $query = null, int $level = 0) {
    $tmpColumns = $this->columns();

    if ($maxRelationLevel > 4) $maxRelationLevel = 4;

    $selectRaw = [];
    $withs = [];
    $joins = [];

    $selectRaw[] = $this->table . '.*';
    $selectRaw[] = $level . ' as _LEVEL';
    $selectRaw[] = '(' . str_replace('{%TABLE%}', $this->table, $this->lookupSqlValue()) . ') as _LOOKUP';

    // LOOKUPS and RELATIONSHIPS
    foreach ($tmpColumns as $columnName => $column) {
      if ($column['type'] == 'lookup') {
        $lookupModel = $this->app->getModel($column['model']);
        $lookupConnection = $lookupModel->eloquent->getConnectionName();
        $lookupDatabase = $lookupModel->eloquent->getConnection()->getDatabaseName();
        $lookupTableName = $lookupModel->getFullTableSqlName();
        $joinAlias = 'join_' . $columnName;

        $selectRaw[] = "(" .
          str_replace("{%TABLE%}", $joinAlias, $lookupModel->lookupSqlValue())
          . ") as `_LOOKUP[{$columnName}]`"
        ;

        $joins[] = [
          $lookupDatabase . '.' . $lookupTableName . ' as ' . $joinAlias,
          $joinAlias.'.id',
          '=',
          $this->table.'.'.$columnName
        ];
      }
    }

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    if ($query === null) $query = $this->eloquent;
    $query = $query->selectRaw(join(',', $selectRaw)); //->with($withs);
    foreach ($this->relations as $relName => $relDefinition) {
      if (is_array($includeRelations) && !in_array($relName, $includeRelations)) continue;

      $relModel = new $relDefinition[1]($this->app);

      // switch ($maxRelationLevel) {
      //   case 0: /* */ break;
      //   case 1: $query->with($relName); break;
      //   case 2:
      //     foreach ($relModel->relations as $subRelName => $subRelDefinition) {
      //       $query->with($relName . "." . $subRelName);
      //     }
      //   break;
      //   case 3:
      //     foreach ($relModel->relations as $subRelName => $subRelDefinition) {
      //       $query->with($relName . "." . $subRelName);

      //       $subRelModel = new $subRelDefinition[1]($this->app);
      //       foreach ($subRelModel->relations as $subSubRelName => $subSubRelDefinition) {
      //         $query->with($relName . "." . $subRelName . "." . $subSubRelName);
      //       }
      //     }
      //   break;
      //   case 4:
      //   default:
      //     foreach ($relModel->relations as $subRelName => $subRelDefinition) {
      //       $query->with($relName . "." . $subRelName);

      //       $subRelModel = new $subRelDefinition[1]($this->app);
      //       foreach ($subRelModel->relations as $subSubRelName => $subSubRelDefinition) {
      //         $query->with($relName . "." . $subRelName . "." . $subSubRelName);

      //         $subSubRelModel = new $subSubRelDefinition[1]($this->app);
      //         foreach ($subSubRelModel->relations as $subSubSubRelName => $subSubSubRelDefinition) {
      //           $query->with($relName . "." . $subRelName . "." . $subSubRelName . "." . $subSubSubRelName);
      //         }
      //       }
      //     }
      //   break;
      // }

      if ($maxRelationLevel > 0) {
        $query->with([$relName => function($q) use($relModel, $maxRelationLevel) {
          return $relModel->prepareLoadRecordQuery(null, $maxRelationLevel - 1, $q);
        }]);
      }

      // $query->with([$relName => function($query) use($relModel) {
      //   return
      //     $query
      //       ->selectRaw('
      //         *,
      //         (' . str_replace('{%TABLE%}', $relModel->table, $relModel->lookupSqlValue()) . ') as _lookupText_'
      //       )
      //     ;
      // }]);

    }
    foreach ($joins as $join) {
      $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    }

    return $query;
  }

  // public function getDependentRecords(int $parentId): array {
  //   $dependentRecords = [];
  //   foreach ($this->adios->registeredModels as $modelClass) {
  //     $tmpModel = $this->adios->getModel($modelClass);
  //     foreach ($tmpModel->columns() as $colName => $colDef) {
  //       if ($colDef['type'] == 'lookup' && $colDef['model'] == $this->fullName) {
  //         $count = $tmpModel->where($colName, $parentId)->count();
  //         if ($count > 0) {
  //           $dependentRecords[$tmpModel->fullName . '.' . $colName] = $count;
  //         }
  //       }
  //     }
  //   }

  //   return $dependentRecords;
  // }

  public function getNewRecordDataFromString(string $text): array {
    return [];
  }

  //////////////////////////////////////////////////////////////////
  // callbacks

  /**
   * onBeforeCreate
   *
   * @param mixed $data
   *
   * @return array
   */
  public function onBeforeCreate(array $record): array
  {
    return $record;
  }

  /**
   * onModelBeforeUpdate
   *
   * @param mixed $data
   *
   * @return array
   */
  public function onBeforeUpdate(array $record): array
  {
    return $record;
  }

  /**
   * onAfterCreate
   *
   * @param mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterCreate(array $record, $returnValue)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterCreate", [
      "model" => $this,
      "data" => $record,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }

  /**
   * onModelAfterUpdate
   *
   * mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterUpdate(array $record, $returnValue)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterUpdate", [
      "model" => $this,
      "data" => $record,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }


  public function onBeforeDelete(int $id): int
  {
    return $this->app->dispatchEventToPlugins('onModelBeforeDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
  }

  public function onAfterDelete(int $id): int
  {
    return $this->app->dispatchEventToPlugins('onModelAfterDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
  }

  public function onAfterLoadRecord(array $record): array
  {
    return $record;
  }

  public function recordAddCustomData(array $record): array
  {
    return $record;
  }

  public function onAfterLoadRecords(array $records): array
  {
    return $records;
  }

}
