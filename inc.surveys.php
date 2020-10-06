<?php 
function actionSendInitialMail() {

     $message = '';  $code = 404; $params = ''; global $dbh; global $baseurl;
    $post_vars = file_get_contents("php://input");
   	$params = json_decode($post_vars);
  	global $smtpun; global $smtpps;
  	$code = 200;
  	$message = $params; $surveys = array(); $data = array(); $pr = array(); $encrypted_string1 = ''; $responses = array();
    if($params !== '' && $params !== null) {
    	$projectid = $params->projectid;
    	$wk_number = $params->weekid;
    	$mailtmplate = $dbh->query("SELECT * FROM `tbl_mail_templates` WHERE `name` = 'initiationmail' limit 1");
    	$tempdetail = $mailtmplate->fetch_assoc();
    	
    	$aqr = "SELECT me.email, CONCAT(me.`first_name`, ' ', me.`last_name`) AS employee_name, aq.survey_code from master_employee as me INNER JOIN tbl_assigned_question as aq ON me.id = aq.employee_id WHERE aq.project_id = '".$projectid."' AND aq.week_number = '".$wk_number."' group by aq.employee_id";
		// echo $aqr;die;
		$res = $dbh->query($aqr);
		if($res) {
			while($erow = $res->fetch_assoc()) {
				// echo $erow['email']; die;
				$subject = $tempdetail['subject'];
			  	$mail = new PHPMailer();   
				$mail->IsSMTP();
				$mail->SMTPDebug = 0;
				$mail->SMTPAuth = TRUE;
				$mail->SMTPSecure = "ssl";
				$mail->Port     = 465;  
				$mail->Username = $smtpun;
				$mail->Password = $smtpps;
				$mail->Host     = "smtp.gmail.com";
				$mail->Mailer   = "smtp";
				$mail->SetFrom($smtpun, "e2epeoplepractices");
				$mail->Subject = $subject;
				$mail->WordWrap   = 80;
				$to = $erow['email'];	
				$appurl = $baseurl."/beatssurveychat/". $erow['survey_code'];
				$rem_mail_body = str_replace("beats_surveylink",$appurl,$tempdetail['mailbody']);

				$mail->addAddress($to, "e2epeoplepractices");
				$body = $rem_mail_body;
				$mail->MsgHTML($body);
				$mail->IsHTML(true);
				$mail->Send();		
			}
			$updt_mail_status = "UPDATE tbl_project_question_bank set initial_mail_status = '1' where project_id = '".$projectid."' AND week_number = ".$wk_number;
			$updt_mail_status_res = $dbh->query($updt_mail_status);
			$code = 201;
		}	
    }
	http_response_code($code);
    echo json_encode(array('code' => $code, 'message' => 'Survey link send'));
}

function actionSendReminderMail(){
	$message = '';  $code = 404; $params = ''; global $dbh; global $baseurl;
    $post_vars = file_get_contents("php://input");
   	$params = json_decode($post_vars);
  	global $smtpun; global $smtpps;
  	$code = 200;
  	$message = $params; $surveys = array(); $data = array(); $pr = array(); $encrypted_string1 = ''; $responses = array();
    if($params !== '' && $params !== null) {
    	$projectid = $params->projectid;
    	$wk_number = $params->weekid;

    	$mailtmplate = $dbh->query("SELECT * FROM `tbl_mail_templates` WHERE `name` = 'remindermail' limit 1");
    	$tempdetail = $mailtmplate->fetch_assoc();

    	$aqr = "SELECT me.email, CONCAT(me.`first_name`, ' ', me.`last_name`) AS employee_name, aq.survey_code from master_employee as me INNER JOIN tbl_assigned_question as aq ON me.id = aq.employee_id WHERE aq.project_id = '".$projectid."' AND aq.week_number = '".$wk_number."' group by aq.employee_id";
		// echo $aqr;die;
		$res = $dbh->query($aqr);
		if($res) {
			while($erow = $res->fetch_assoc()) {
				// echo $erow['email']; die;
				$subject = 'Beats Survey | Reminder Mail';
			  	$mail = new PHPMailer();   
				$mail->IsSMTP();
				$mail->SMTPDebug = 0;
				$mail->SMTPAuth = TRUE;
				$mail->SMTPSecure = "ssl";
				$mail->Port     = 465;  
				$mail->Username = $smtpun;
				$mail->Password = $smtpps;
				$mail->Host     = "smtp.gmail.com";
				$mail->Mailer   = "smtp";
				$mail->SetFrom($smtpun, "e2epeoplepractices");
				$mail->Subject = $tempdetail['subject'];
				$mail->WordWrap   = 80;
				$to = $erow['email'];	
				$appurl = $baseurl."/beatssurveychat/". $erow['survey_code'];
				$mail->addAddress($to, "e2epeoplepractices");
				$rem_mail_body = str_replace("beats_surveylink",$appurl,$tempdetail['mailbody']);
				$body = $rem_mail_body;
				$mail->MsgHTML($body);
				$mail->IsHTML(true);
				$mail->Send();		
			}
			$code = 200;
		}	
    }
	http_response_code($code);
    echo json_encode(array('code' => $code, 'message' => 'Survey link send'));
}

function saveBeatsSurveyAnswers() {
	$message = ''; $code = 404; $params = ''; global $dbh;

	$post_vars = file_get_contents("php://input");
	$params = json_decode($post_vars);

	// print_r($params);
	if($params) {
		$survey_code = $params->survey_code;
		$ques_ans = $params->ques_ans;

		for($i = 0; $i < count($ques_ans); $i++) {
			// print_r($ques_ans[$i]);die;

			$question_id = $ques_ans[$i]->question_id;
			$employee_answer = $ques_ans[$i]->selected_answer;
			$status = 'complete';

			// Save employee answer
			$saveAns = $dbh->query("UPDATE `tbl_assigned_question` SET `employee_answer` = '".$employee_answer."', `status` = '".$status."', `completedon` = NOW(), `last_modifiedon` = NOW() WHERE `question_id` = ".$question_id." AND `survey_code` = '".$survey_code."'");

		}

		$code = 200;
		http_response_code($code);
		echo json_encode(array('code' => $code, 'message' => 'Success'));
	}
}
function saveBeatsSurveychat() {
	$message = ''; $code = 404; $params = ''; global $dbh;

	$post_vars = file_get_contents("php://input");
	$params = json_decode($post_vars);

	// print_r($params);
	if($params) {
		$employeeId = $params->userId;
		$projectId = $params->projectId;
		$surveyCode = $params->usrveyCode;
		$ques_ans = $params->userAnswers;
		foreach($params->userAnswers as $q => $qobj) {
			$aorder = 1;	
			$questionId = $qobj->questionId;
			foreach($qobj->answers as $ans) {
				$employee_answer = $ans->option_value;
				$emp_option_value = $ans->option_order;
				$status = 'complete';
				$saveAns = $dbh->query("UPDATE `tbl_assigned_question` SET `employee_answer` = '".$employee_answer."', `emp_option_value` = ".$emp_option_value.", `status` = '".$status."', `completedon` = NOW(), `last_modifiedon` = NOW() WHERE `question_id` = ".$questionId." AND `employee_id` = '".$employeeId."' AND `project_id` = '".$projectId."' AND `survey_code` = '".$surveyCode."'");
				if($saveAns) {
					$code = 200;
					$message = 'Success';
				}
				else {
					$message = 'Failed to save Survey data ' . mysqli_error($dbh);
				}
			}
			$survey_status = 'complete';
		}
		$code = 200;
		http_response_code($code);
		echo json_encode(array('code' => $code, 'message' => 'Success', 'survey_status' => $survey_status));
	}
}


