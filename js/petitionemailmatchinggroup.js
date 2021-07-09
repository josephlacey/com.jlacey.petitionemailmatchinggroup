(function($){
  //Initial recipient system processing
  CRM.api3('CustomField', 'getsingle', {
    "return": ["id"],
    "name": "Email_Recipient_System"
  }).done(function(recipientSystemField) {
    update_matching_group_recipient_display(recipientSystemField.id);
    CRM.$("select[id*='custom_" + recipientSystemField.id + "']").change(function() {
      update_matching_group_recipient_display(recipientSystemField.id);
    });
  });
})(CRM.$);

function update_matching_group_recipient_display(fieldId){
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
      CRM.$('.custom-group-Thank_You_Message').show();
    }
    else {
      CRM.$(recipientMatchingGroupField.values).each(function(){
        CRM.$("tr[class*='custom_" + this.id + "']").hide();
      });
      CRM.$('.custom-group-Thank_You_Message').hide();
    }
  });
}

