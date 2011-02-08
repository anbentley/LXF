// issues a server request and calls a defined function on reply
function serverRequest (url, callback, parameter) {
	// blank request, return nothing
	if (url == '') {
		call(callback, '', parameter);
		return;
	}
	
	alert('blur');
	// code for IE7+, Firefox, Chrome, Opera, Safari
	if (window.XMLHttpRequest) {
		xhr = new XMLHttpRequest();
	
	// code for IE6, IE5, and so on
	} else { 
		xhr = new ActiveXObject('Microsoft.XMLHTTP');
	}
	
	// create a response function
	xhr.onreadystatechange = function() { 
		if (xhr.readyState == 4 && xmlhttp.status == 200) {
			alert(xhr.responseText);
			call(callback, xhr.responseText, parameter);
		}
	}
	
	// send the request
	xhr.open(url, true);
	xhr.send();
}

// fills an element with data
function fillElement(data, id) {
	document.getElementById(id).innerHTML = data;
}