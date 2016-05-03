<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('manualbill/lib.php');
require_once('billslib.php');
require_once('credit/lib.php');

global $SESSION;
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/programmanagement/ajax.php', $pageparams);

$functionname = optional_param('fname', '', PARAM_ALPHAEXT); //function name as argument

if ($functionname) {

    switch ($functionname) {

        case "get_depts":
            $organization = optional_param('org', 0, PARAM_INT);
            $result = get_depts($organization);
            $result = json_encode($result);
            break;
        case "get_progs":
            $organization = optional_param('org', 0, PARAM_INT);
            $result = get_progs($organization);
            $result = json_encode($result);
            break;
        case "get_feeheads":
            $feecategory = optional_param('feecatid', 0, PARAM_INT);
            $result = get_feeheads($feecategory);
            $result = json_encode($result);
            break;
        case "get_cycles":
            $plans = optional_param('planid', 0, PARAM_INT);
            $pid = optional_param('pid', 0, PARAM_INT);
            $result = get_cycles($pid, $plans);
            $result = json_encode($result);
            break;
        case "get_progs_org":
            $organization = optional_param('org', 0, PARAM_INT);
            $result = get_progs_org($organization);
            $result = json_encode($result);
            break;
        case "get_plans":
            $pid = optional_param('pid', 0, PARAM_INT);
            $result = get_plans($pid);
            $result = json_encode($result);
            break;
//        case "get_cycles_plan":
//            $plans = optional_param('planid', 0, PARAM_INT);
//            $pid = optional_param('pid', 0, PARAM_INT);
//            $result = get_cycles_plan($pid, $plans);
//            $result = json_encode($result);
//            break;
        case "get_bills":
            $pid = optional_param('pid', 0, PARAM_INT);
            $result = get_bills($pid);
            $result = json_encode($result);
            break;
        
        case "get_my_due_bills":
            $userid = required_param('userid', PARAM_INT);
            $orgid = required_param('orgid', PARAM_INT);
            $bills = new fee_bills();
            $duebills = $bills->get_my_due_bills($userid, array('organization'=>$orgid));
            if($duebills){
                $result = json_encode($duebills);
            }else{
                $result = false;
            }            
            break;
            
        case "get_credit_balance":
            $userid = optional_param('userid', $USER->id, PARAM_INT);
            $orgid = required_param('orgid', PARAM_INT);
            
            $credit = new credit($userid);
            $mycredit = $credit->get_credit_balance($userid, $orgid);
            if($mycredit){
                $result = json_encode($mycredit);
            }else{
                $result = false;
            }    
            $SESSION->mypayment->creditapplied = TRUE;             
            break;
        
        default:
            $result = false;
    }
} else {
    $result = false;
}
echo $result;
