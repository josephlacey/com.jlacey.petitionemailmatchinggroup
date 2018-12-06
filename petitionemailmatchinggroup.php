<?php

require_once 'petitionemailmatchinggroup.civix.php';
use CRM_Petitionemailmatchinggroup_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function petitionemailmatchinggroup_civicrm_config(&$config) {
  _petitionemailmatchinggroup_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function petitionemailmatchinggroup_civicrm_xmlMenu(&$files) {
  _petitionemailmatchinggroup_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function petitionemailmatchinggroup_civicrm_install() {
  _petitionemailmatchinggroup_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function petitionemailmatchinggroup_civicrm_postInstall() {
  _petitionemailmatchinggroup_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function petitionemailmatchinggroup_civicrm_uninstall() {
  _petitionemailmatchinggroup_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function petitionemailmatchinggroup_civicrm_enable() {
  _petitionemailmatchinggroup_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function petitionemailmatchinggroup_civicrm_disable() {
  _petitionemailmatchinggroup_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function petitionemailmatchinggroup_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _petitionemailmatchinggroup_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function petitionemailmatchinggroup_civicrm_managed(&$entities) {
  _petitionemailmatchinggroup_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function petitionemailmatchinggroup_civicrm_caseTypes(&$caseTypes) {
  _petitionemailmatchinggroup_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function petitionemailmatchinggroup_civicrm_angularModules(&$angularModules) {
  _petitionemailmatchinggroup_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function petitionemailmatchinggroup_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _petitionemailmatchinggroup_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function petitionemailmatchinggroup_civicrm_entityTypes(&$entityTypes) {
  _petitionemailmatchinggroup_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function petitionemailmatchinggroup_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Custom_Form_CustomDataByType':
      CRM_Core_Resources::singleton()->addScriptFile('com.jlacey.petitionemailmatchinggroup', 'js/petitionemailmatchinggroup.js');
      break;
    case 'CRM_Campaign_Form_Petition':
      CRM_Core_Resources::singleton()->addScriptFile('com.jlacey.petitionemailmatchinggroup', 'js/messageField.js');
      break;
    case 'CRM_Campaign_Form_Petition_Signature':
      //Check that the recpipient is matching group
      $petitionRecipientFieldId = civicrm_api3('CustomField', 'getvalue', ['return' => 'id', 'name' => 'Email_Recipient_System']);
      $petitionRecipient = civicrm_api3('Survey', 'getvalue', ['return' => "custom_$petitionRecipientFieldId", 'id' => "$form->_surveyId"]);
      if ($petitionRecipient == 'Matchinggroup') {
        //If so, pass in the Google API Key and the Petition level for to signature page js handling
        $googleApiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);
        $petitionLevelFieldId = civicrm_api3('CustomField', 'getvalue', ['return' => 'id', 'name' => 'Recipient_Matching_Group_Level']);
        $petitionLevel = civicrm_api3('Survey', 'getvalue', ['return' => "custom_$petitionLevelFieldId", 'id' => "$form->_surveyId"]);
        CRM_Core_Resources::singleton()->addVars('signature', ['googleApiKey' => $googleApiKey]);
        CRM_Core_Resources::singleton()->addVars('signature', ['petitionLevel' => $petitionLevel]);
        //Invoke the js file which hides the activity profile and show the user their matching legislators
        CRM_Core_Resources::singleton()->addScriptFile('com.jlacey.petitionemailmatchinggroup', 'js/signature.js');
      }
      break;
  }
}

/**
 * Implements hook_civicrm_fieldOptions().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_fieldOptions/
 */
function petitionemailmatchinggroup_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if ($entity == 'Survey') {

    //Set the Field Id for comparison
    $fieldId = (int) substr($field, 7);

    //Matching Groups
    $recipientMatchingGroupId =  civicrm_api3('CustomField', 'getvalue', ['return' => "id",'name' => "Recipient_Matching_Group",]);
    if ($fieldId == $recipientMatchingGroupId) {
      $groupOptions =  civicrm_api3('Group', 'get', ['return' => ['title'], 'options' => ['limit' => 999999],]);
      foreach($groupOptions['values'] as $groupOptionId => $groupOption) {
        $options[$groupOptionId] = $groupOption['title'];
      }
    }

    //Matching Groups Field
    $recipientMatchingGroupFieldId =  civicrm_api3('CustomField', 'getvalue', ['return' => "id",'name' => "Recipient_Matching_Group_Level",]);
    if ($fieldId == $recipientMatchingGroupFieldId) {
      $levelOptions =  civicrm_api3('OptionValue', 'get', ['return' => ['label', 'value'], 'option_group_id' => 'electoral_districts_level_options', 'options' => ['limit' => 999999],]);
      foreach($levelOptions['values'] as $levelOptionId => $levelOption) {
        $options[$levelOption['value']] = $levelOption['label'];
      }
    }

  }
}
