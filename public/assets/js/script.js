function isDev() {
	return defaultIsDev;
}

function dd(el1, el2 = null, el3 = null, el4 = null, el5 = null) {
	if (isDev()) {
		if (el1) { console.log(el1); }
		if (el2) { console.log(el2); }
		if (el3) { console.log(el3); }
		if (el4) { console.log(el4); }
		if (el5) { console.log(el5); }
	}
}

function startToastWorking() {

}

function finishToastWorking() {

}

window.addEventListener('load', function(e) {
	var options = {
		'apiUrl': arDefaultOptions['apiUrl'],
		'baseUri': arDefaultOptions['baseUri'],
		'cdnUrl': arDefaultOptions['cdnUrl'],
	};

	querybiz.init(options);
});