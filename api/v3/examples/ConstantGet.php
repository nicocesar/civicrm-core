<?php

/*
 
 */
function constant_get_example(){
$params = array(
  'field' => 'location_type_id',
  'version' => 3,
);

  $result = civicrm_api( 'constant','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function constant_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 5,
  'values' => array(
      '5' => 'Billing',
      '1' => 'Home',
      '3' => 'Main',
      '4' => 'Other',
      '2' => 'Work',
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testLocationTypeGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ConstantTest.php
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