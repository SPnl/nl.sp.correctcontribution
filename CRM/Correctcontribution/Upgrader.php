<?php

/**
 * Collection of upgrade steps
 */
class CRM_Correctcontribution_Upgrader extends CRM_Correctcontribution_Upgrader_Base {

    const BATCH_SIZE = 150;
    
  public function upgrade_1001() {
    $minId = CRM_Core_DAO::singleValueQuery('SELECT min(id) FROM civicrm_contact');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Correct contacts (%1 / %2)', array(
        1 => $startId,
        2 => $maxId,
      ));
      $this->addTask($title, 'correct', $startId, $endId);
    }
    
    return true;
  }

  public function upgrade_1003() {
    $sql = "SELECT c.id as contribution_id, mp.membership_id FROM `civicrm_contribution` `c`
                    INNER JOIN `civicrm_membership_payment` `mp` ON c.id = mp.contribution_id
                    INNER JOIN `civicrm_membership` `m` ON `mp`.`membership_id` = `m`.`id`
                    INNER JOIN `civicrm_membership_status` `ms` ON `m`.`status_id` = `ms`.`id`
                    INNER JOIN `civicrm_membership_type_pay_quartely` `mq` ON `mq`.`membership_type_id` = `m`.`membership_type_id`
                    WHERE
                        DATE(c.receive_date) >= DATE('2015-02-01')
                        AND DATE(c.receive_date) <= DATE('2015-02-28')
                        AND c.contribution_status_id = 2
                        AND ms.is_current_member = 1
                        AND `mq`.`pay_quartely` = 1
                        ";

    $deletableContributions = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $dao->contribution_id));
      CRM_Correctcontribution_Task::addNewContribution(new DateTime('2015-04-01'), $dao->membership_id, $contribution);
      CRM_Correctcontribution_Task::addNewContribution(new DateTime('2015-07-01'), $dao->membership_id, $contribution);
      CRM_Correctcontribution_Task::addNewContribution(new DateTime('2015-10-01'), $dao->membership_id, $contribution);

      $deletableContributions[] = $dao->contribution_id;
    }

    foreach($deletableContributions as $contribution_id) {
      CRM_Contribute_BAO_Contribution::deleteContribution($contribution_id);
    }

    return true;
  }
  
  public static function correct($startId, $endId) {
      CRM_Correctcontribution_Task::correctContacts($startId, $endId);
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
