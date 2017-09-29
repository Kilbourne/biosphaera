<!--
function PO_form_validation(frmId) {
	var frm = jQuery('#'.frmId);
	var pass=true;
	var frmElements = jQuery('#'+frmId+' input, #'+frmId+' textarea');
	for (var i=0; i < frmElements.length; i++){
		//alert(frmElements[i].name + " = " + regex[frmElements[i].name]);
		if (typeof(regex[frmElements[i].name]) == 'undefined') {
			regex[frmElements[i].name] = regex['default'];
		}
		
		var labelName = frmElements[i].name.replace('[','');
		labelID = labelName.replace(']','');
		if (frmElements[i].type != 'button' && frmElements[i].type != 'file' && frmElements[i].type != 'submit' && frmElements[i].type != 'image' && frmElements[i].type != 'hidden' && !regex[frmElements[i].name].test(frmElements[i].value)) {
			pass = false;
			var label=jQuery('#'+labelID + "Label");
			if (label) {
				alert(label.html() + ' is invalid.');
				jQuery('#'+labelID + "Label").addClass('badInputLabel');
			} else if (frmElements[i].title) {
				alert(frmElements[i].title + ' is invalid.');
				frmElements[i].addClass('badInput');
			} else {
				frmElements[i].addClass('badInput');
			}
			
		} else if (frmElements[i].type != 'file' && frmElements[i].type != 'button' && frmElements[i].type != 'submit' && frmElements[i].type != 'image' && frmElements[i].type != 'hidden') {
			var label=jQuery('#'+labelID + "Label");
			if (label) {
				jQuery('#'+labelID + "Label").removeClass('badInputLabel');
			} else {
				frmElements[i].removeClass('badInput');
			}
		}
	}
	return pass;
}
-->