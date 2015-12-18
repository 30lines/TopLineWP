jQuery(document).ready(function() {
  // option script inits
});
function addPropertyEntry() {
  /* Init vars */
  var propNameContainer = jQuery('tr[data-option-name=property_name]');
  var propCodeContainer = jQuery('tr[data-option-name=property_code]');
  var formTable = jQuery('form');
  var currentProps = propNameContainer.length;
  /* Clone defaults */
  var newNameInput = propNameContainer.eq(0).clone();
  var newCodeInput = propCodeContainer.eq(0).clone();
  /* Clear old values and add new input names */
  newNameInput.find('input').val(' ');
  newNameInput.find('input').attr('name', 'properties[property_name_'+(currentProps+1)+']');
  newCodeInput.find('input').val(' ');
  newCodeInput.find('input').attr('name', 'properties[property_code_'+(currentProps+1)+']');
  formTable.find('tbody').append(newNameInput);
  formTable.find('tbody').append(newCodeInput);
}
