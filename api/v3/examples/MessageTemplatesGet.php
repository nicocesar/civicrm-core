<?php
/**
 * Test Generated example of using message_templates get API
 * *
 */
function message_templates_get_example(){
$params = array(
  'msg_title' => 'msg_title_2',
  'msg_subject' => 'msg_subject_2',
  'msg_text' => 'msg_text_2',
  'msg_html' => 'msg_html_2',
  'workflow_id' => 2,
  'is_default' => '',
  'is_reserved' => 1,
  'pdf_format_id' => '1',
);

try{
  $result = civicrm_api3('message_templates', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function message_templates_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'msg_title' => 'msg_title_2',
          'msg_subject' => 'msg_subject_2',
          'msg_text' => 'msg_text_2',
          'msg_html' => 'msg_html_2',
          'is_active' => 0,
          'workflow_id' => '2',
          'is_default' => 0,
          'is_reserved' => '1',
          'pdf_format_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MessageTemplatesTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/