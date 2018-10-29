(function($){
  //Initial recipient system processing
  CRM.api3('CustomField', 'getsingle', {
    "return": ["id"],
    "name": "Email_Recipient_System"
  }).done(function(recipientSystemField) {
    //Page load
    recipient_system_matching_group(recipientSystemField.id);
    //When the salutation type changes
    CRM.$("select[id*='custom_" + recipientSystemField.id + "']").change(function() {
      recipient_system_matching_group(recipientSystemField.id);
    });
  });
})(CRM.$);

function recipient_system_matching_group (fieldId){
  //Process the selected option
  CRM.api3('CustomField', 'get', {
    "sequential": 1,
    "return": ["id"],
    "name": {"IN":["Recipient_Matching_Group","Recipient_Matching_Group_Level","Recipient_Matching_Group_Bill"]}
  }).done(function(recipientMatchingGroupField) {
    if (CRM.$("select[id*='custom_" + fieldId + "'] option:selected").val() == 'Matchinggroup') {
      //Show Matching Group and Matching Group Field fields
      CRM.$(recipientMatchingGroupField.values).each(function(){
        CRM.$("tr[class*='custom_" + this.id + "']").show();
      });
      recipient_single_hide_matching_group(true);
    }
    if (CRM.$("select[id*='custom_" + fieldId + "'] option:selected").val() != 'Matchinggroup') {
      CRM.$(recipientMatchingGroupField.values).each(function(){
        CRM.$("tr[class*='custom_" + this.id + "']").hide();
      });
    }
    if (CRM.$("select[id*='custom_" + fieldId + "'] option:selected").val() == 'Single') {
      recipient_single_hide_matching_group(false);
    }
  });
}

function recipient_single_hide_matching_group(hide){
  CRM.api3('CustomField', 'get', {
    "return": ["id"],
    "name": {"IN":["Recipient_Name","Recipient_Email"]}
  }).done(function(recipientSingle) {
    if (hide) {
      CRM.$.each(recipientSingle.values, function(id,fieldId) {
        CRM.$("tr[class*='custom_" + id + "']").hide();
      });
    } else {
      CRM.$.each(recipientSingle.values, function(id, fieldId) {
        CRM.$("tr[class*='custom_" + id + "']").show();
      });
    }
  });
}
