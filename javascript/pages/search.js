function setSearchCheckboxes() {
	var id = new Array('developer', 'regex', 'exact', 'library', 'css', 'packages'); // 'case-sensitive', 'show', 'pages', 'parts'
	var element = new Array();
	var disabled = new Array();
	for ( var i in id ) {
		name = id[i];		
		element[name] = document.getElementsByName(name)[0];
		disabled[name] = false;
	}
		
	if (element['regex'].checked) { 
		disabled['exact'] = true;
		disabled['case-sensitive'] = true;
	} 
	
	if (element['developer'].checked != true) {
		disabled['includes'] = true;
		disabled['css'] = true;
		disabled['packages'] = true;
	}
	
	for ( var i in id ) {
		name = id[i];		
		element[name].disabled = disabled[name];
	}
}