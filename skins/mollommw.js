
function onMollomCaptchaToggle () {
	var type = document.getElementById('mollom-captcha-type');

	var audio = document.getElementById('mollom-captcha-audio');
	var image = document.getElementById('mollom-captcha-image');

	if (type.value == 'text') {
		type.value = 'audio';
		audio.style.display = 'inherit';
		image.style.display = 'none';
	} else {
		type.value = 'text';
		image.style.display = 'inherit';
		audio.style.display = 'none';
	}
}
