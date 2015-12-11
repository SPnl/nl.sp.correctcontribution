<?php

/**
 * Collection of upgrade steps
 */
class CRM_Correctcontribution_Upgrader extends CRM_Correctcontribution_Upgrader_Base {

    const BATCH_SIZE = 500;
    
  public function upgrade_1001() {
    $sql = "select contact.display_name, mp16.membership_id as membership_id, c2015.payment_instrument_id as pyament_instrument_2015, c2016.payment_instrument_id as pyament_instrument_2016

from civicrm_contribution c2016
inner join civicrm_membership_payment mp16 on mp16.contribution_id = c2016.id
inner join (select payment_instrument_id, m.id as membership_id, c2015.id as contribution_id from civicrm_contribution c2015
	inner join civicrm_membership_payment mp on c2015.id = mp.contribution_id
	inner join civicrm_membership m on mp.membership_id = m.id
	where year(m.end_date) >= 2016 and c2015.receive_date = '2015-10-01') as c2015 on c2015.membership_id = mp16.membership_id
inner join civicrm_contact contact on contact.id = c2016.contact_id
where c2016.receive_date = '2016-01-01' and c2016.payment_instrument_id != c2015.payment_instrument_id and c2015.payment_instrument_id != 10;";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $i =1;
    while($dao->fetch()) {
      $title = ts('Correct membershippayments for 2016 (%1)', array(
        1 => $i
      ));
      $this->addTask($title, 'correct', $dao->membership_id, $dao->pyament_instrument_2015);
      $i++;
    }
    
    return true;
  }
  
  public static function correct($membership_id, $payment_instrument_id) {
      CRM_Correctcontribution_Task::correctMembershipPayment($membership_id, $payment_instrument_id);
      return true;
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }*/

  
  public function enable() {
      CRM_Core_BAO_Setting::setItem('1000', 'Extension', 'nl.sp.correctcontribution:version');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
