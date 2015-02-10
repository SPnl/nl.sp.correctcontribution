<?php

class CRM_Correctcontribution_Task {

    public static function correctContacts($minId, $maxId) {        
        $sql = "SELECT c.id as contribution_id, mp.membership_id FROM `civicrm_contribution` `c`
                    INNER JOIN `civicrm_membership_payment` `mp` ON c.id = mp.contribution_id
                    INNER JOIN `civicrm_membership` `m` ON `mp`.`membership_id` = `m`.`id`
                    INNER JOIN `civicrm_membership_status` `ms` ON `m`.`status_id` = `ms`.`id`
                    INNER JOIN `civicrm_membership_type_pay_quartely` `mq` ON `mq`.`membership_type_id` = `m`.`membership_type_id`
                    WHERE 
                        DATE(c.receive_date) = DATE('2014-01-01')
                        AND c.contribution_status_id = 1
                        AND ms.is_current_member = 1
                        AND `mq`.`pay_quartely` = 1
                        AND c.contact_id between %1 AND %2";

        $params[1] = array($minId, 'Integer');
        $params[2] = array($maxId, 'Integer');

        $deletableContributions = array();
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        while($dao->fetch()) {
            $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $dao->contribution_id));
            self::addNewContribution(new DateTime('2015-01-01'), $dao->membership_id, $contribution);
            self::addNewContribution(new DateTime('2015-04-01'), $dao->membership_id, $contribution);
            self::addNewContribution(new DateTime('2015-07-01'), $dao->membership_id, $contribution);
            self::addNewContribution(new DateTime('2015-10-01'), $dao->membership_id, $contribution);

            $deletableContributions[] = $dao->contribution_id;
        }

        foreach($deletableContributions as $contribution_id) {
            CRM_Contribute_BAO_Contribution::deleteContribution($contribution_id);
        }
    }

    protected static function addNewContribution(DateTime $receive_date, $membership_id, $first_contribution) {
        $params = $first_contribution;
        $instrument_id = self::getPaymenyInstrument($params);
        $params['receive_date'] = $receive_date->format('YmdHis');
        unset($params['payment_instrument']);
        unset($params['contribution_id']);
        unset($params['id']);
        unset($params['instrument_id']);
        if ($instrument_id) {
            $params['contribution_payment_instrument_id'] = $instrument_id;
        }
        $params['contribution_status_id'] = 2; //pending


        $result = CRM_Contribute_BAO_Contribution::create($params);
        //$result = civicrm_api3('Contribution', 'create', $params);

        //$mpBao = new CRM_Member_BAO_MembershipPayment();
        $mpBao['membership_id'] = $membership_id;
        $mpBao['contribution_id'] = $result->id;
        CRM_Member_BAO_MembershipPayment::create($mpBao);
    }

    protected static function getPaymenyInstrument($contribution) {
        if (empty($contribution['instrument_id'])) {
            return false;
        }

        $instrument_id = CRM_Core_OptionGroup::getValue('payment_instrument', $contribution['instrument_id'], 'id', 'Integer');
        if (empty($instrument_id)) {
            return false;
        }
        return $instrument_id;
    }


}
