<?php

class CRM_Correctcontribution_Task {
    
    public static function getSelect() {
        return "select mt.id as memberhsip_type_id, c.total_amount as `bedrag_p_contr`, c.id as contribution_id , 0 as jaar, quarter(m.join_date) as kwartaal, ms.label as `status`, ms.id as `status_id` ";
    }
    
    public static function getQuery() {
        return "
            from civicrm_contribution  c
            inner join civicrm_membership_payment mp on c.id = mp.contribution_id
            inner join civicrm_membership m on m.id = mp.membership_id
            inner join civicrm_membership_status ms on m.status_id = ms.id
            inner join civicrm_membership_type mt on mt.id = m.membership_type_id
            where payment_instrument_id = 9
            and (year(receive_date) = 2015)
               ";
    }
    
    public static function getOrderBy() {
        return " order by `memberhsip_type_id`, `bedrag_p_contr`, jaar, kwartaal, `status` desc";
    }
    
    public static function correctAcceptGiroContributions($offset, $limit) {
        $sql = self::getSelect().self::getQuery().self::getOrderBy()." LIMIT %1, %2";
        $params[1] = array($offset, 'Integer');
        $params[2] = array($limit, 'Integer');
        
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        while($dao->fetch()) {
            $division_factor = self::determineDivisionFactor($dao->memberhsip_type_id, $dao->kwartaal, $dao->bedrag_p_contr);
            if ($division_factor > 1 && $dao->bedrag_p_contr > 0) {
                $newAmount = $dao->bedrag_p_contr / $division_factor;
                $sql = "UPDATE `civicrm_contribution` SET `total_amount` = %1 WHERE `id` = %2";
                $updateParams = array();
                $updateParams[1] = array($newAmount, 'Float');
                $updateParams[2] = array($dao->contribution_id, 'Integer');
                CRM_Core_DAO::executeQuery($sql, $updateParams);
            }
        }
    }

    protected static function determineDivisionFactor($membership_type_id, $join_kwartaal, $amount) {
        if (($amount - (int) $amount) > 0) {
            return 0;
        }
        $div = self::getDivisionFactorTable();
        if (!isset($div[$membership_type_id]) && !isset($div[$membership_type_id][(int) $amount])) {
            return 0;
        }
        if (is_array($div[$membership_type_id][(int) $amount])) {
            if (!isset($div[$membership_type_id][(int) $amount][$join_kwartaal])) {
                return 0;
            }
            return $div[$membership_type_id][(int) $amount][$join_kwartaal];
        } else {
            return $div[$membership_type_id][(int) $amount];
        }
        return 0;
    }
    
    protected static function getDivisionFactorTable() {
        return array (
            //SP
            1 => array (
                //bedrag
                12 => array (
                    //kwartaal
                    1 => 3, //gezins leden
                    2 => 2, //gewone leden
                    3 => 2,
                    4 => 2,
                    
                ),
                16 => 4,
                18 => 3,
                20 => 4,
                24 => 4,
            ),
            //SP + Rood
            2 => array(
                10 => 2,
                12 => 2,
                18 => 3,
                20 => 4,
                24 => 4
            )
        );
    }

}
