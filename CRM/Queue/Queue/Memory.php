<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * A queue implementation which stores items in the CiviCRM SQL database
 */
class CRM_Queue_Queue_Memory extends CRM_Queue_Queue {

  /**
   * @var array(queueItemId => queueItemData)
   */
  var $items;

  /**
   * @var array(
     queueItemId => releaseTime), expressed in seconds since epoch
   */
  var $releaseTimes;

  var $nextQueueItemId = 1;

  /**
   * Create a reference to queue. After constructing the queue, one should
   * usually call createQueue (if it's a new queue) or loadQueue (if it's
   * known to be an existing queue).
   *
   * @param $queueSpec, array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp", "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be flushed; default to TRUE
   *   - (additional keys depending on the queue provider)
   */
  function __construct($queueSpec) {
    parent::__construct($queueSpec);
  }

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  function createQueue() {
    $this->items = array();
    $this->releaseTimes = array();
  }

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  function loadQueue() {
    // $this->createQueue();
    throw new Exception('Unsupported: CRM_Queue_Queue_Memory::loadQueue');
  }

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  function deleteQueue() {
    $this->items = NULL;
    $this->releaseTimes = NULL;
  }

  /**
   * Check if the queue exists
   *
   * @return bool
   */
  function existsQueue() {
    return is_array($this->items);
  }

  /**
   * Add a new item to the queue
   *
   * @param $data serializable PHP object or array
   * @param $options queue-dependent options; for example, if this is a
   *   priority-queue, then $options might specify the item's priority
   *
   * @return bool, TRUE on success
   */
  function createItem($data, $options = array()) {
    $id = $this->nextQueueItemId++;
    // force copy, no unintendedsharing effects from pointers
    $this->items[$id] = serialize($data);
  }

  /**
   * Determine number of items remaining in the queue
   *
   * @return int
   */
  function numberOfItems() {
    return count($this->items);
  }

  /**
   * Get and remove the next item
   *
   * @param $leaseTime seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  function claimItem($leaseTime = 3600) {
    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      if (empty($this->releaseTimes[$id]) || $this->releaseTimes[$id] < $nowEpoch) {
        $this->releaseTimes[$id] = $nowEpoch + $leaseTime;

        $item       = new stdClass();
        $item->id   = $id;
        $item->data = unserialize($data);
        return $item;
      }
      else {
        // item in queue is reserved
        return FALSE;
      }
    }
    // nothing in queue
    return FALSE;
  }

  /**
   * Get the next item
   *
   * @param $leaseTime seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  function stealItem($leaseTime = 3600) {
    // foreach hits the items in order -- but we short-circuit after the first
    foreach ($this->items as $id => $data) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      $this->releaseTimes[$id] = $nowEpoch + $leaseTime;

      $item       = new stdClass();
      $item->id   = $id;
      $item->data = unserialize($data);
      return $item;
    }
    // nothing in queue
    return FALSE;
  }

  /**
   * Remove an item from the queue
   *
   * @param $item object The item returned by claimItem
   */
  function deleteItem($item) {
    unset($this->items[$item->id]);
    unset($this->releaseTimes[$item->id]);
  }

  /**
   * Return an item that could not be processed
   *
   * @param $dao object The item returned by claimItem
   *
   * @return bool
   */
  function releaseItem($item) {
    unset($this->releaseTimes[$item->id]);
  }
}

