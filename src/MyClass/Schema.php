<?php

namespace pierresilva\LaravelCrud\MyClass;

use pierresilva\LaravelCrud\Core\NameResolver;

class Schema
{
  public $model = null;

  public function __construct($schema = null, $model = null)
  {
    if ($model) {
      $this->model = $model;
    }

    if ($schema) {
      $this->id = $schema['id'];
      $this->name = $schema['name'];
      $this->display_name = $schema['display_name'];
      $this->type = $schema['type'];
      $this->input_type = $schema['input_type'];
      $this->varidate = $schema['varidate'];
      $this->faker_type = $schema['faker_type'];
      $this->nullable = $schema['nullable'];
      $this->unique = $schema['unique'];
      $this->show_in_list = $schema['show_in_list'];
      $this->show_in_detail = $schema['show_in_detail'];
      $this->belongsto = $schema['belongsto'];
      $this->parent_id = $schema['parent_id'];
      $this->comment = $schema['comment'] ?? '';
      $this->custom_options = $schema['custom_options'];
      $this->selectOptions = [];
      $this->randomNumber = 0;
      $this->uniqueId = '';
        $this->checkedType = false;
    } else {
      $this->id = 0;
      $this->name = 'id';
      $this->display_name = 'ID';
      $this->type = 'integer';
      $this->input_type = '';
      $this->varidate = '';
      $this->faker_type = '';
      $this->nullable = false;
      $this->unique = false;
      $this->show_in_list = true;
      $this->show_in_detail = true;
      $this->belongsto = '';
      $this->parent_id = '';
      $this->comment = '';
      $this->custom_options = '';
      $this->selectOptions = [];
      $this->randomNumber = 0;
      $this->uniqueId = '';
      $this->checkedType = false;
    }
  }

  public function getVaridate()
  {
    $result = '';
    if ($this->type === 'integer') {
      $result .= '|integer';
    }
    if ($this->input_type === 'password') {
      $result .= '|confirmed';
    }
    if ($this->nullable === false) {
      $result .= '|required';
    } else {
      $result .= '|nullable';
    }
    if ($this->unique === true) {
      $result .= '|unique:\'.$table_name.\',' . NameResolver::solveName($this->name, 'name_name') . '\'.$ignore_unique.\'';  //
    }
    $result = ltrim($result, '|');
    return $result;
  }

  public function getSelectOptions()
  {

    $options = explode(',', $this->custom_options);

    $optionsArray = [];

    if ($this->nullable) {
      $optionsArray[] = [
        'value' => '',
        'text' => 'NA'
      ];
    }

    foreach ($options as $option) {
      $optionParts = explode(':', $option);
      $optionsArray[] = [
        'value' => $optionParts[0],
        'text' => $optionParts[1] ?? $optionParts[0],
      ];
    }

    $this->selectOptions = $optionsArray;
  }

  public function getRandomNumber($digits = 8)
  {
    $faker = \Faker\Factory::create();

    $number = '########';

    $this->randomNumber = $faker->numerify($number);
  }

  public function getUniqueId()
  {
    $faker = \Faker\Factory::create();

    $this->randomNumber = $faker->uuid();
  }

  public function checkType($type)
  {
      if ($this->type == $type) {
          $this->checkedType = true;
      }
  }
}
