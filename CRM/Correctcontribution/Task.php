<?php

class CRM_Correctcontribution_Task {

    public static function correctMembershipPayment($menbership_id, $payment_instrument_2015) {
        $sql = "SELECT c.id from civicrm_contribution c inner join civicrm_membership_payment mp on mp.contribution_id = c.id
                WHERE YEAR(c.receive_date) = 2016 and mp.membership_id = %1 and c.payment_instrument_id != %1";
        $params[1] = array($menbership_id, 'Integer');
        $params[2] = array($payment_instrument_2015, 'Integer');
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        while ($dao->fetch()) {
            $contribution['id'] = $dao->id;
            $contribution['contribution_payment_instrument_id'] = $payment_instrument_2015;
            civicrm_api3('Contribution', 'create', $contribution);
        }
    }

}
