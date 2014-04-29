<?php

class CRM_Reports_Form_Report_UitnodigingenLijst extends CRM_Report_Form {

  protected $_addressField = FALSE;
  protected $_emailField = TRUE;
  protected $_eventField = TRUE;
  protected $_summary = NULL;
  protected $_customGroupExtends = array('Contact', 'Individual', 'Organization', 'Household');
  protected $_customGroupGroupBy = FALSE;

  function __construct() {
    $this->_columns = array(
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'required' => true,
            'title' => ts('E-mailadres'),
            'no_repeat' => true,
          ),
        ),
        'filters' => array(
          'location_type_id' => array(
            'title' => ts('Location type (E-mail)'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'),
            'type' => CRM_Utils_Type::T_INT,
          )
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => FALSE,
            'default' => false,
            'no_repeat' => TRUE,
            'no_display' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'email_greeting_display' => array(
            'title' => ts('Email Greeting'),
            'default' => TRUE,
          ),
          'postal_greeting_display' => array(
            'title' => ts('Postal Greeting'),
            'default' => TRUE,
          ),
           'addressee_display' => array(
            'title' => ts('Addressee'),
            'default' => TRUE,
          ),
           'gender_id' => array(
            'title' => ts('Gender'),
            'default' => FALSE,
          ),
           'birth_date' => array(
            'title' => ts('Birth Date'),
            'default' => FALSE,
          ),
        ),
        'filters' => array(
          'id' => array(
            'no_display' => TRUE,
          ),
          'privacy' => array (
            'title' => ts('Privacy'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_SelectValues::privacy(),
          )
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array('title' => ts('State/Province')),
          'country_id' => array('title' => ts('Country')),
        ),
        'filters' => array(
          'location_type_id' => array(
            'title' => ts('Location type (Adres)'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'),
            'type' => CRM_Utils_Type::T_INT,
          )
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => array(
        ),
        'grouping' => 'event-fields',
        'filters' => array(
          'event_id' => array('name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Utils_Type::T_INT,
            'options' => $this->getEventFilterOptions(),
          ),
          'sid' => array(
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ),
          'participant_register_date' => array(
            'title' => 'Registration Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
    );
    $this->_groupFilter = TRUE;
    $this->_tagFilter = FALSE;
    
    if (!$this->_addressField) {
      unset($this->_columns['civicrm_address']);
    }
    if (!$this->_emailField) {
      unset($this->_columns['civicrm_email']);
    }
    if (!$this->_eventField) {
      unset($this->_columns['civicrm_participant']);
    }
    parent::__construct();
  }
  
  function setDefaultValues($freeze = TRUE) {
    parent::setDefaultValues($freeze);
    
    $this->_defaults['privacy_op'] = 'notin';
    $this->_defaults['privacy_value'] = 'do_not_email,do_not_trade,is_opt_out';
    
    CRM_Report_Form_Instance::setDefaultValues($this, $this->_defaults);
    
    return $this->_defaults;
  }

  function getEventFilterOptions() {
    $events = array();
    $events[''] = ts('- any -');
    $query = "
			select id, start_date, title from civicrm_event
			where (is_template IS NULL OR is_template = 0) AND is_active
			order by start_date DESC, title ASC 
		";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $events[$dao->id] = CRM_Utils_Date::customFormat(substr($dao->start_date, 0, 10)) . " :: {$dao->title} (ID {$dao->id})";
    }
    return $events;
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Uitnodigingen lijst'));
    parent::preProcess();
  }

  /*
   * Adds group filters to _columns (called from _Constuct
   */

  function buildGroupFilter() {
    $this->_columns['civicrm_group']['filters'] = array(
      'gid' =>
      array(
        'name' => 'group_id',
        'title' => ts('Group'),
        'operatorType' => CRM_Report_Form::OP_SELECT,
        'group' => TRUE,
        'type' => CRM_Utils_Type::T_INT,
        'options' => $this->getGroups()
      ),
    );
    if (empty($this->_columns['civicrm_group']['dao'])) {
      $this->_columns['civicrm_group']['dao'] = 'CRM_Contact_DAO_GroupContact';
    }
    if (empty($this->_columns['civicrm_group']['alias'])) {
      $this->_columns['civicrm_group']['alias'] = 'cgroup';
    }
  }
  
  function getGroups() {
    $return = CRM_Contact_BAO_Group::getGroupsHierarchy(CRM_Core_PseudoConstant::group(), NULL, '&nbsp;&nbsp;', TRUE);
    $return = array_merge(array('' => ts('- any -')), $return);
    return $return;
  }

  function whereGroupClause($field, $value, $op) {
    if (!is_array($value)) {
      $value = array($value);
    }

    //retrieve subgroups
    $gids = CRM_Contact_BAO_GroupNesting::getDescendentGroupIds($value, TRUE);
    $smartGroupQuery = "";

    $group = new CRM_Contact_DAO_Group();
    $group->is_active = 1;
    $group->find();
    $smartGroups = array();
    while ($group->fetch()) {
      if (in_array($group->id, $gids) && $group->saved_search_id) {
        $smartGroups[] = $group->id;
      }
    }

    CRM_Contact_BAO_GroupContactCache::check($smartGroups);

    $smartGroupQuery = '';
    if (!empty($smartGroups)) {
      $smartGroups = implode(',', $smartGroups);
      $smartGroupQuery = " UNION DISTINCT
                  SELECT DISTINCT smartgroup_contact.contact_id
                  FROM civicrm_group_contact_cache smartgroup_contact
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }

    $sqlOp = $this->getSQLOperator($op);
    $clause = "{$field['dbAlias']} IN (" . implode(', ', $gids) . ")";

    return " {$this->_aliases['civicrm_contact']}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added'
                          {$smartGroupQuery} ) ";
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
              CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            } elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            } elseif ($tableName == 'civicrm_participant') {
              $this->_eventField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
           ";
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id\n";
    } else {
      unset($this->_columns['civicrm_address']['filters']);
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              INNER JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id\n";
    } else {
      unset($this->_columns['civicrm_email']['filters']);
    }
  }
  
  function storeWhereHavingClauseArray(){
    $participantClauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          // respect pseudofield to filter spec so fields can be marked as
          // not to be handled here
          if(!empty($field['pseudofield'])){
            continue;
          }
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            if (CRM_Utils_Array::value('operatorType', $field) == CRM_Report_Form::OP_MONTH) {
              $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (is_array($value) && !empty($value)) {
                $clause = "(month({$field['dbAlias']}) $op (" . implode(', ', $value) . '))';
              }
            }
            else {
              $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
              $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
              $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
              $fromTime = CRM_Utils_Array::value("{$fieldName}_from_time", $this->_params);
              $toTime   = CRM_Utils_Array::value("{$fieldName}_to_time", $this->_params);
              $clause   = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type'], $fromTime, $toTime);
            }
          } elseif ($fieldName == 'gid') {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op && strlen(CRM_Utils_Array::value("{$fieldName}_value", $this->_params))) {
              $clause = $this->whereClause($field,
                $op,
                array(CRM_Utils_Array::value("{$fieldName}_value", $this->_params)),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          } elseif ($fieldName == 'privacy') {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
            $sqlOp = false;
            if ($op == 'in') {
              $sqlOp = '=';
            } elseif ($op == 'notin') {
              $sqlOp = '!=';
            }
            if ($sqlOp != false && is_array($value) && count($value)) {
                $clause = " (1 ";
               foreach($value as $f) {
                 $clause .= " AND {$this->_aliases['civicrm_contact']}.".$f." ".$sqlOp." '1'";
               }
               $clause .= ") ";
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            if (CRM_Utils_Array::value('having', $field)) {
              $this->_havingClauses[] = $clause;
            } 
            elseif ($tableName == 'civicrm_participant') {
              $participantClauses[] = $clause;
            }
            else {
              $this->_whereClauses[] = $clause;
            }
          }
        }
      }
    }
    
    if (count($participantClauses)) {
      $this->_whereClauses[] = "{$this->_aliases['civicrm_contact']}.id NOT IN (
      SELECT {$this->_aliases['civicrm_participant']}.contact_id FROM
      civicrm_participant {$this->_aliases['civicrm_participant']} WHERE (
        ".implode(' AND ', $participantClauses)."
       ) AND {$this->_aliases['civicrm_participant']}.is_test = '0' )"; 
    }

  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);
    
    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
              is_array($checkList[$colName]) &&
              in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      /* if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
        ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
        'reset=1&cid=' . $row['civicrm_contact_id'],
        $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
        } */

      if (!$entryFound) {
        break;
      }
    }
  }

  function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'gid') {
      return array(
        'in' => ts('Is one of'),
        'notin' => ts('Is not one of'),
      );
    }
    return parent::getOperationPair($type, $fieldName);
  }

}
