<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_Migrate_Export {

  const XML_VALUE_SEPARATOR = ":;:;:;";

  protected $_xml;

  function __construct() {
    $this->_xml = array(
      'customGroup' => array(
        'data' => array(),
        'name' => 'CustomGroup',
        'scope' => 'CustomGroups',
        'required' => FALSE,
        'idNameFields' => array('id', 'name'),
        'idNameMap' => array(),
      ),
      'customField' => array(
        'data' => array(),
        'name' => 'CustomField',
        'scope' => 'CustomFields',
        'required' => FALSE,
        'idNameFields' => array('id', 'column_name'),
        'idNameMap' => array(),
        'mappedFields' => array(
          array('optionGroup', 'option_group_id', 'option_group_name'),
          array('customGroup', 'custom_group_id', 'custom_group_name'),
        )
      ),
      'optionGroup' => array(
        'data' => array(),
        'name' => 'OptionGroup',
        'scope' => 'OptionGroups',
        'required' => FALSE,
        'idNameMap' => array(),
        'idNameFields' => array('id', 'name'),
      ),
      'relationshipType' => array(
        'data' => array(),
        'name' => 'RelationshipType',
        'scope' => 'RelationshipTypes',
        'required' => FALSE,
        'idNameFields' => array('id', 'name_a_b'),
        'idNameMap' => array(),
      ),
      'locationType' => array(
        'data' => array(),
        'name' => 'LocationType',
        'scope' => 'LocationTypes',
        'required' => FALSE,
        'idNameFields' => array('id', 'name'),
        'idNameMap' => array(),
      ),
      'optionValue' => array(
        'data' => array(),
        'name' => 'OptionValue',
        'scope' => 'OptionValues',
        'required' => FALSE,
        'idNameMap' => array(),
        'idNameFields' => array('value', 'name', 'prefix'),
        'mappedFields' => array(
          array('optionGroup', 'option_group_id', 'option_group_name'),
        ),
      ),
      'profileGroup' => array(
        'data' => array(),
        'name' => 'ProfileGroup',
        'scope' => 'ProfileGroups',
        'required' => FALSE,
        'idNameFields' => array('id', 'title'),
        'idNameMap' => array(),
      ),
      'profileField' => array(
        'data' => array(),
        'name' => 'ProfileField',
        'scope' => 'ProfileFields',
        'required' => FALSE,
        'idNameMap' => array(),
        'mappedFields' => array(
          array('profileGroup', 'uf_group_id', 'profile_group_name')
        ),
      ),
      'profileJoin' => array(
        'data' => array(),
        'name' => 'ProfileJoin',
        'scope' => 'ProfileJoins',
        'required' => FALSE,
        'idNameMap' => array(),
        'mappedFields' => array(
          array('profileGroup', 'uf_group_id', 'profile_group_name')
        ),
      ),
      'mappingGroup' => array(
        'data' => array(),
        'name' => 'MappingGroup',
        'scope' => 'MappingGroups',
        'required' => FALSE,
        'idNameFields' => array('id', 'name'),
        'idNameMap' => array(),
        'mappedFields' => array(
          array('optionValue', 'mapping_type_id', 'mapping_type_name', 'mapping_type'),
        )
      ),
      'mappingField' => array(
        'data' => array(),
        'name' => 'MappingField',
        'scope' => 'MappingFields',
        'required' => FALSE,
        'idNameMap' => array(),
        'mappedFields' => array(
          array('mappingGroup', 'mapping_id', 'mapping_group_name'),
          array('locationType', 'location_type_id', 'location_type_name'),
          array('relationshipType', 'relationship_type_id', 'relationship_type_name'),
        ),
      ),
    );
  }

  /**
   * Scan local customizations and build an in-memory representation
   *
   * @return void
   */
  function build() {
    // fetch the option group / values for
    // activity type and event_type

    $optionGroups = "( 'activity_type', 'event_type', 'mapping_type' )";

    $sql = "
SELECT distinct(g.id), g.*
FROM   civicrm_option_group g
WHERE  g.name IN $optionGroups
";
    $this->fetch('optionGroup', 'CRM_Core_DAO_OptionGroup', $sql);

    $sql = "
SELECT distinct(g.id), g.*
FROM   civicrm_option_group g,
       civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  f.option_group_id = g.id
AND    f.custom_group_id = cg.id
AND    cg.is_active = 1
";
    $this->fetch('optionGroup', 'CRM_Core_DAO_OptionGroup', $sql);

    $sql = "
SELECT v.*, g.name as prefix
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
AND    g.name IN $optionGroups
";

    $this->fetch('optionValue', 'CRM_Core_DAO_OptionValue', $sql);

    $sql = "
SELECT distinct(v.id), v.*, g.name as prefix
FROM   civicrm_option_value v,
       civicrm_option_group g,
       civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  v.option_group_id = g.id
AND    f.option_group_id = g.id
AND    f.custom_group_id = cg.id
AND    cg.is_active = 1
";

    $this->fetch('optionValue', 'CRM_Core_DAO_OptionValue', $sql);

    $sql = "
SELECT rt.*
FROM   civicrm_relationship_type rt
WHERE  rt.is_active = 1
";
    $this->fetch('relationshipType', 'CRM_Contact_DAO_RelationshipType', $sql);

    $sql = "
SELECT lt.*
FROM   civicrm_location_type lt
WHERE  lt.is_active = 1
";
    $this->fetch('locationType', 'CRM_Core_DAO_LocationType', $sql);

    $sql = "
SELECT cg.*
FROM   civicrm_custom_group cg
WHERE  cg.is_active = 1
";
    $this->fetch('customGroup', 'CRM_Core_DAO_CustomGroup', $sql);

    $sql = "
SELECT f.*
FROM   civicrm_custom_field f,
       civicrm_custom_group cg
WHERE  f.custom_group_id = cg.id
AND    cg.is_active = 1
";
    $this->fetch('customField', 'CRM_Core_DAO_CustomField', $sql);

    $this->fetch('profileGroup', 'CRM_Core_DAO_UFGroup');

    $this->fetch('profileField', 'CRM_Core_DAO_UFField');

    $sql = "
SELECT *
FROM   civicrm_uf_join
WHERE  entity_table IS NULL
AND    entity_id    IS NULL
";
    $this->fetch('profileJoin', 'CRM_Core_DAO_UFJoin', $sql);

    $this->fetch('mappingGroup', 'CRM_Core_DAO_Mapping');

    $this->fetch('mappingField', 'CRM_Core_DAO_MappingField');
  }

  /**
   * Render the in-memory representation as XML
   *
   * @return string XML
   */
  function toXML() {
    $buffer = '<?xml version="1.0" encoding="iso-8859-1" ?>';
    $buffer .= "\n\n<CustomData>\n";
    foreach (array_keys($this->_xml) as $key) {
      if (!empty($this->_xml[$key]['data'])) {
        $buffer .= "  <{$this->_xml[$key]['scope']}>\n";
        foreach ($this->_xml[$key]['data'] as $item) {
          $buffer .= $this->renderKeyValueXML($this->_xml[$key]['name'], $item);
        }
        $buffer .= "  </{$this->_xml[$key]['scope']}>\n";
      }
      elseif ($this->_xml[$key]['required']) {
        CRM_Core_Error::fatal("No records in DB for $key");
      }
    }
    $buffer .= "</CustomData>\n";
    return $buffer;
  }

  function fetch($groupName, $daoName, $sql = NULL) {
    $idNameFields = isset($this->_xml[$groupName]['idNameFields']) ? $this->_xml[$groupName]['idNameFields'] : NULL;
    $mappedFields = isset($this->_xml[$groupName]['mappedFields']) ? $this->_xml[$groupName]['mappedFields'] : NULL;

    $dao = new $daoName();
    if ($sql) {
      $dao->query($sql);
    }
    else {
      $dao->find();
    }

    while ($dao->fetch()) {
      $this->_xml[$groupName]['data'][] = $this->exportDAO($this->_xml[$groupName]['name'], $dao, $mappedFields);
      if ($idNameFields) {
        // index the id/name fields so that we can translate from FK ids to FK names
        if (isset($idNameFields[2])) {
          $this->_xml[$groupName]['idNameMap'][$dao->{$idNameFields[2]} . '.' . $dao->{$idNameFields[0]}] = $dao->{$idNameFields[1]};
        }
        else {
          $this->_xml[$groupName]['idNameMap'][$dao->{$idNameFields[0]}] = $dao->{$idNameFields[1]};
        }
      }
    }
  }

  /**
   * Compute any fields of the entity defined by the $mappedFields specification
   *
   * @param array $mappedFields each item is an array(0 => MappedEntityname, 1 => InputFieldName (id-field), 2 => OutputFieldName (name-field), 3 => OptionalPrefix)
   * @param CRM_Core_DAO $dao the entity for which we want to prepare mapped fields
   * @return array new fields
   */
  public function computeMappedFields($mappedFields, $dao) {
    $keyValues = array();
    if ($mappedFields) {
      foreach ($mappedFields as $mappedField) {
        if (isset($dao->{$mappedField[1]})) {
          if (isset($mappedField[3])) {
            $label = $this->_xml[$mappedField[0]]['idNameMap']["{$mappedField[3]}." . $dao->{$mappedField[1]}];
          }
          else {
            $label = $this->_xml[$mappedField[0]]['idNameMap'][$dao->{$mappedField[1]}];
          }
          $keyValues[$mappedField[2]] = $label;
        }
      }
    }
    return $keyValues;
  }

  /**
   * @param CRM_Core_DAO $object
   * @param string $objectName business-entity/xml-tag name
   * @return array
   */
  function exportDAO($objectName, $object, $mappedFields) {
    $dbFields = & $object->fields();

    // Filter the list of keys and values so that we only export interesting stuff
    $keyValues = array();
    foreach ($dbFields as $name => $dontCare) {
      // ignore all ids
      if ($name == 'id' || substr($name, -3, 3) == '_id') {
        continue;
      }
      if (isset($object->$name) && $object->$name !== NULL) {
        // hack for extends_entity_column_value
        if ($name == 'extends_entity_column_value') {
          if ($object->extends == 'Event' ||
            $object->extends == 'Activity' ||
            $object->extends == 'Relationship'
          ) {
            if ($object->extends == 'Event') {
              $key = 'event_type';
            }
            elseif ($object->extends == 'Activity') {
              $key = 'activity_type';
            }
            elseif ($object->extends == 'Relationship') {
              $key = 'relationship_type';
            }
            $keyValues['extends_entity_column_value_option_group'] = $key;
            $types = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($object->$name, 1, -1));
            $values = array();
            foreach ($types as $type) {
              $values[] = $this->_xml['optionValue']['idNameMap']["$key.{$type}"];
            }
            $value = implode(',', $values);
            $keyValues['extends_entity_column_value_option_value'] = $value;
          }
          else {
            echo "This extension: {$object->extends} is not yet handled";
            exit();
          }
        }

        $value = $object->$name;
        if ($name == 'field_name') {
          // hack for profile field_name
          if (substr($value, 0, 7) == 'custom_') {
            $cfID = substr($value, 7);
            list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($cfID);
            $value = "custom.{$tableName}.{$columnName}";
          }
        }
        $keyValues[$name] = $value;
      }
    }

    $keyValues += $this->computeMappedFields($mappedFields, $object);

    return $keyValues;
  }

  /**
   * @param string $tagName
   * @param array $keyValues
   * @param string $additional XML
   * @return string XML
   */
  public function renderKeyValueXML($tagName, $keyValues) {
    $xml = "    <$tagName>";
    foreach ($keyValues as $k => $v) {
      $xml .= "\n      " . $this->renderTextTag($k, str_replace(CRM_Core_DAO::VALUE_SEPARATOR, self::XML_VALUE_SEPARATOR, $v));
    }
    $xml .= "\n    </$tagName>\n";
    return $xml;
  }

  /**
   * @param string $name tag name
   * @param string $value text
   * @param string $prefix
   * @return string XML
   */
  function renderTextTag($name, $value, $prefix = '') {
    if (!preg_match('/^[a-zA-Z0-9\_]+$/', $name)) {
      throw new Exception("Malformed tag name: $name");
    }
    return $prefix . "<$name>" . htmlentities($value) . "</$name>";
  }
}

