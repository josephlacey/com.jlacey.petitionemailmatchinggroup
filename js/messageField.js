CRM.$(function($) {
  var selectors = ['#customData .custom-group-Thank_You_Message input[data-crm-custom="Thank_You_Message:Thank_You_Message_Field"]',
                   '#customData .custom-group-Thank_You_Message input[data-crm-custom="Thank_You_Message:Thank_You_Subject_Field"]'];
  $(selectors).each(function(index,fieldSelector) {
    var $field = $(fieldSelector);
    var initField = function(m) {
      $field.attr({
        placeholder: '- Select Field -',
        allowClear: 'true',
      });
      createEntityRef(m, $('#profile_id').val());
    };
    initField($field);

    // 4.6 doesn't trigger crmLoad while 4.7 loads the custom data after this runs
    $('body').on('crmLoad', function() {
      $field = $(fieldSelector);
      initField($field);
    });

    $('#profile_id').change( function() {
      $field.crmEntityRef('destroy');
      $field.val('');
      createEntityRef($field, $('#profile_id').val());
    });
  });
  function createEntityRef($field, profileId) {
    var noteFields = ['activity_details'];
    CRM.api3('CustomGroup', 'get', {
      sequential: 1,
      extends: 'Activity',
      'api.CustomField.get': {
        return: 'name'
      }
    }).done(function(result) {
      result['values'].forEach(function(val) {
        val['api.CustomField.get']['values'].forEach(function(field) {
          noteFields.push(field.name);
          noteFields.push('custom_' + field.id);
        });
      });
      $field.crmEntityRef({
        entity: 'UFField',
        placeholder: '- Select Field -',
        api: {
          params: {
            uf_group_id: profileId,
            field_name: {"IN": noteFields}
          }
        },
        select: {minimumInputLength: 0},
      });
    });
  }
});
