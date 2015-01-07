<?php

class CRM_Correctcontribution_Task {

    public static function correctContacts($minId, $maxId) {        
        $sql = "SELECT c.*, mp.membership_id FROM `civicrm_contribution` `c`
                    INNER JOIN `civicrm_membership_payment` `mp` ON c.id = mp.contribution_id
                    WHERE 
                        DATE(c.receive_date) = DATE('2015-01-01')
                        AND c.contribution_status_id = 1
                        AND contact_id between %1 AND %2";
        
        $params[1] = array($minId, 'Integer');
        $params[2] = array($maxId, 'Integer');
            
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        while($dao->fetch()) {
            self::correctPaymentInstrument($dao->contact_id, $dao->membership_id, $dao->payment_instrument_id);
        }
        
        $update = "UPDATE `civicrm_contribution` `c`
                    SET receive_date = DATE('2014-10-01')
                    WHERE 
                        DATE(c.receive_date) = DATE('2015-01-01')
                        AND c.contribution_status_id = 1
                        AND contact_id between %1 AND %2";
        CRM_Core_DAO::executeQuery($update, $params);
    }

    protected static function correctPaymentInstrument($contact_id, $membership_id, $correctPaymentInstrumentId) {
        $sql = "UPDATE `civicrm_contribution` `c`
                INNER JOIN `civicrm_membership_payment` `mp` ON c.id = mp.contribution_id
                SET `payment_instrument_id` = %1
                WHERE 
                    mp.membership_id = %2
                    AND c.contact_id = %3
                    AND c.payment_instrument_id != %4
                    AND c.contribution_status_id = 2";
        
        $params[1] = array($correctPaymentInstrumentId, 'Integer');
        $params[2] = array($membership_id, 'Integer');
        $params[3] = array($contact_id, 'Integer');
        $params[4] = array($correctPaymentInstrumentId, 'Integer');
        
        CRM_Core_DAO::executeQuery($sql, $params);
    }

}
