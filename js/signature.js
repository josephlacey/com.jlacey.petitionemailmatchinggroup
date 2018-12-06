(function($){
  CRM.$('.crm-petition-activity-profile').hide();
  if (CRM.$('#state_province-1').val().length > 0) {
    get_legislators();
  }
  CRM.$('#state_province-1').change(function(){
    get_legislators();
  });
})(CRM.$);

function get_legislators() {
  //Assemble the API URL
  streetAddress = CRM.$('#street_address-1').val();
  city = CRM.$('#city-1').val();
  stateProvince = CRM.$('#state_province-1 option:selected').text();
  url = "https://www.googleapis.com/civicinfo/v2/representatives?levels="
      + CRM.vars.signature.petitionLevel
      + "&roles=legislatorUpperBody&roles=legislatorLowerBody&key="
      + CRM.vars.signature.googleApiKey
      + "&address=" + streetAddress + " " + city + " " + stateProvince;
  //console.log(url);
  CRM.$.ajax(url).done(function(districts){
    //console.log(districts);
    //Process the response
    //Check for errors first
    if (districts.error) {
      //TODO User feedback
      return;
    } else {
      CRM.$('.crm-petition-activity-profile').css('clear', 'both');
      CRM.$( '<div class="crm-section crm-petition-officials"><div class="label">Officials</div><div class="content"></div></div>' ).insertAfter( ".crm-petition-contact-profile" );
      CRM.$(districts.officials).each(function(index,official){
        //console.log(official);
        CRM.$( '<span class="crm-section official-section form-item" style="float:left;margin-right:10px;"><img src="' + official.photoUrl + '" /><p>' + official.name + '</p></span>' ).appendTo( ".crm-petition-officials .content" );
      });
      CRM.$('.crm-petition-activity-profile').show();
    }
  });
}
