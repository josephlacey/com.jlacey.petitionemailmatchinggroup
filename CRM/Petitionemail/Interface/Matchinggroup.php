<?php
/**
 * @file
 * Matching Group email interface.
 */

/**
 * An interface to send a single email.
 *
 * @extends CRM_Petitionemail_Interface
 */
class CRM_Petitionemail_Interface_Matchinggroup extends CRM_Petitionemail_Interface {

  /**
   * Instantiate the delivery interface.
   *
   * @param int $surveyId
   *   The ID of the petition.
   */
  public function __construct($surveyId) {
    parent::__construct($surveyId);

    $this->neededFields[] = 'Support_Subject';
    $this->neededFields[] = 'Support_Message';
    $this->neededFields[] = 'Thank_You_Subject';
    $this->neededFields[] = 'Thank_You_Message';
    $this->neededFields[] = 'Recipient_Matching_Group';
    $this->neededFields[] = 'Recipient_Matching_Group_Level';

    $fields = $this->findFields();
    $petitionemailval = $this->getFieldsData($surveyId);

    foreach ($this->neededFields as $neededField) {
      if (empty($fields[$neededField]) || empty($petitionemailval[$fields[$neededField]])) {
        return;
      }
    }
    // If all needed fields are found, the system is no longer incomplete.
    $this->isIncomplete = FALSE;
  }

  /**
   * Find the custom fields.
   *
   * @return string
   *   The field name for API purposes like "custom_123".
   */
  public function findFields() {
    if (empty($this->fields)) {
      try {
        $fieldParams = array(
          'custom_group_id' => ['IN' => ["Email_Recipient", "Support_Message", "Thank_You_Message"]],
          'sequential' => 1,
        );
        $result = civicrm_api3('CustomField', 'get', $fieldParams);
        if (!empty($result['values'])) {
          foreach ($result['values'] as $f) {
            $this->fields[$f['name']] = "custom_{$f['id']}";
          }
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.jlacey.petitionemailmatchinggroup')));
      }
    }
    return $this->fields;
  }

  /**
   * Take the signature form and send an email to the recipient.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function processSignature($form) {
    //Submitters matching fields
    //TODO Should this be abstracted with the Electoral API
    //API Key
    $googleApiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

    //Assemble the API URL
    $petition_level = $this->petitionEmailVal[$this->fields['Recipient_Matching_Group_Level']];
    $streetAddress = rawurlencode($form->_submitValues['street_address-1']);
    $city = rawurlencode($form->_submitValues['city-1']);
    $stateProvinceAbbrev = CRM_Core_PseudoConstant::stateProvinceAbbreviation($form->_submitValues['state_province-1']);
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?fields=divisions&levels=$petition_level&roles=legislatorUpperBody&roles=legislatorLowerBody&key=$googleApiKey&address=$streetAddress%20$city%20$stateProvinceAbbrev";

    //Intitalize curl
    $ch = curl_init();
    //Set curl options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $verifySSL = civicrm_api('Setting', 'getvalue', ['version' => 3, 'name' => 'verifySSL']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);
    //Get results from API and decode the JSON
    $districts = json_decode(curl_exec($ch), TRUE);
    //Close curl
    curl_close($ch);

    //Process the response
    //Check for errors first
    if (isset($districts['error']) ) {
      //FIXME Some user feedback
      return;
    } else {
      //Process divisions
      $ocdDivision = strtolower("ocd-division/country:us/state:$stateProvinceAbbrev");
      foreach($districts['divisions'] as $divisionKey => $division) {
        //Check if there's a district
        $divisionDistrict = '';
        //Country
        if($ocdDivision != $divisionKey) {
          $divisionParts = explode(':', str_replace($ocdDivision, '', $divisionKey));
          //State
          if ($divisionParts[0] == 'sldu') {
            $chamber = 'upper';
          }
          if ($divisionParts[0] == 'sldl') {
            $chamber = 'lower';
          }
          $divisionDistrict = $divisionParts[1];
          //County
          if(substr($divisionParts[0], 0, 6) == 'county' &&
             substr($divisionParts[1], 0, 16) == 'council_district' &&
             in_array(substr($divisionParts[0], 7), $counties)) {
            $county = ucwords(substr($divisionParts[0], 7));
            $divisionDistrict = substr($divisionParts[1], 17);
          }
          //City
          if(substr($divisionParts[0], 0, 5) == 'place' &&
             substr($divisionParts[1], 0, 16) == 'council_district' &&
             in_array(substr($divisionParts[0], 6), $cities)) {
            $city = ucwords(substr($divisionParts[0], 6));
            $divisionDistrict = substr($divisionParts[1], 17);
          }
        }
      }

      //Process email options
      $ufFields = array(
        'support_subject' => 'Support_Subject',
        'support_message' => 'Support_Message',
        'thank_you_subject' => 'Thank_You_Subject',
        'thank_you_message' => 'Thank_You_Message',
      );
      // Get the message.
      foreach($ufFields as $type => $name) {
        $fieldName = $name . '_Field';
        $field = $this->findUFField("$fieldName");
        if ($field === FALSE) {
          return;
        }
        $$type = empty($form->_submitValues[$field]) ? $this->petitionEmailVal[$this->fields["$name"]] : $form->_submitValues[$field];
        // If message is left empty and no default message, don't send anything.
        if (empty($$type)) {
          return;
        }
      }

      // Fetch bill for sponsorship check
      $billNumber = $this->petitionEmailVal[$this->fields['Recipient_Matching_Group_Bill']];
      if (!empty($billNumber)) {
        $proPublicaApiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'proPublicaCongressAPIKey']);

        //TODO Congress Number is hardcoded here
        $billUrl = "https://api.propublica.org/congress/v1/115/bills/$billNumber/cosponsors.json";

        //Intitalize curl
        $ch = curl_init();
        //Set curl options
        curl_setopt($ch, CURLOPT_URL, $billUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-API-Key: $proPublicaApiKey"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $verifySSL = civicrm_api('Setting', 'getvalue', ['version' => 3, 'name' => 'verifySSL']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);
        //Get results from API and decode the JSON
        $billCosponsors = json_decode(curl_exec($ch), TRUE);
        //Close curl
        curl_close($ch);
      }

      //Process matching fields
      //TODO There's probably a better way to do this
      $electoral_fields = civicrm_api3('CustomField', 'get', ['return' => ["name"],'custom_group_id' => "electoral_districts",]);
      foreach($electoral_fields['values'] as $electoral_field_id => $electoral_field) {
        $matching_fields[substr($electoral_field['name'], 10)] = 'custom_' . $electoral_field_id;
      }
      $level = $matching_fields['level'];
      $states_provinces = $matching_fields['states_provinces'];

      //Process groups
      $groupIds = $this->petitionEmailVal[$this->fields['Recipient_Matching_Group']];
      foreach ($groupIds as $groupId) {
        $groupContacts = civicrm_api3(
          'GroupContact',
          'get',
          ['sequential' => 1,
          'return' => ["contact_id"],
          'group_id' => "$groupId",
          "contact_id.$level" => $petition_level,
          "contact_id.$states_provinces" => $form->_submitValues['state_province-1'],
          'options' => ['limit' => 999999],]
        );
        foreach ($groupContacts['values'] as $groupContact) {
          $contact = civicrm_api3('Contact', 'getsingle', [ "sequential" => 1,'return' => ["display_name", "email", "external_identifier", $matching_fields['district']],'id' => $groupContact['contact_id'],]);
          //FIXME being empty here might not be deterministic.
          if ($contact[$matching_fields['district']] == $divisionDistrict ||
            empty($contact[$matching_fields['district']])) {

            //Set default message to urge support
            $subject = $support_subject;
            $message = $support_message;
            $which_message = 'urging support';
            //Check bill sponsorship and change message if so
            if (!empty($contact['external_identifier']) &&
              $billCosponsors['status'] == 'OK'){
              //Check if legislator is the primary sponsor
              if ($billCosponsors['results'][0]['sponsor_id'] == $contact['external_identifier']) {
                //Overwrite urge support message with thank you message
                $subject = $thank_you_subject;
                $message = $thank_you_message;
                $which_message = 'thanking for support';
              } else {
                //if primary sponsor, check if cosponsor
                foreach($billCosponsors['results'][0]['cosponsors'] as $cosponsor){
                  if ($cosponsor['cosponsor_id'] == $contact['external_identifier']) {
                    //Overwrite urge support message with thank you message
                    $subject = $thank_you_subject;
                    $message = $thank_you_message;
                    $which_message = 'thanking for support';
                  }
                }
              }
            }

            // Setup email message:
            $mailParams = array(
              'groupName' => 'Activity Email Sender',
              'from' => $this->getSenderLine($form->_contactId),
              'toName' => $contact['display_name'],
              'toEmail' => $contact['email'],
              'subject' => $subject,
              'text' => strip_tags($message),
              'html' => $message,
            );
            if (!CRM_Utils_Mail::send($mailParams)) {
              CRM_Core_Session::setStatus(ts('Error sending message to %1', array('domain' => 'com.jlacey.petitionemailmatchinggroup', 1 => $mailParams['toName'])));
            }
            else {
              CRM_Core_Session::setStatus(ts('Message %2 sent successfully to %1', array('domain' => 'com.jlacey.petitionemailmatchinggroup', 1 => $mailParams['toName'], 2 => $which_message)));
            }
          }
          parent::processSignature($form);
        }
      }
    }
  }

  /**
   * Prepare the signature form with the default message.
   *
   * @param CRM_Campaign_Form_Petition_Signature $form
   *   The petition form.
   */
  public function buildSigForm($form) {
    $defaults = $form->getVar('_defaults');

    $ufFields = array('Support_Subject', 'Support_Message', 'Thank_You_Subject', 'Thank_You_Message');
    // Get the message.
    foreach($ufFields as $name) {
      $fieldName = $name . '_Field';
      $field = $this->findUFField("$fieldName");
      if ($field === FALSE) {
        return;
      }
      if (empty($this->petitionEmailVal[$this->fields["$name"]])) {
        return;
      }
      else {
        $defaultValue = $this->petitionEmailVal[$this->fields["$name"]];
      }

      foreach ($form->_elements as $element) {
        if ($element->_attributes['name'] == $field) {
          if ($element->_type == 'text') {
            $element->_attributes['value'] = $defaultValue;
          } elseif ($element->_type == 'textarea') {
            $element->_value = $defaultValue;
          }
        }
      }
      $defaults[$field] = $form->_defaultValues[$field] = $defaultValue;
    }
    $form->setVar('_defaults', $defaults);
  }
}
