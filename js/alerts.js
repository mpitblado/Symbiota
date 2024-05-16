/*
const main = document.getElementById('innertext');
let div = document.createElement('div');
div.id = 'alert-msgs';
div.classList.add('alerts');
main.appendChild(div);
*/

function handleAlerts(alerts,id,limitMessage) {
	if(limitMessage){
		var c = decodeURIComponent(document.cookie);
		if(c.indexOf("alertCnt="+id) > -1) return false;
	}
	let alertDiv = document.getElementById('alert-msgs');
	console.log(alertDiv);
	alertDiv.innerHTML = '';
	alerts.map((alert) => {
		alertDiv.classList.remove('visually-hidden');
		let alertP = document.createElement('p');
		alertP.classList.add('alert');
		alertP.innerHTML = alert.alertMsg + ' Click to dismiss.';
		alertP.onclick = function () {
			if(limitMessage) document.cookie = "alertCnt="+id;
			else document.cookie = "alertCnt=; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
			alertP.remove();
		};
		alertDiv.appendChild(alertP);
	});
}


function initAlerts(options={closeTimeMs: 5000}) {
	// Find Alerts that have been Embeded by Server
	const alerts = document.getElementsByClassName('alert');

	function addCloseButton(alert) {
		// Create Close Marker 
		let closeMarker = document.createElement('div');
		closeMarker.classList.add('closable');
		closeMarker.innerHTML = "x";

		closeMarker.addEventListener('click', () => alert.remove());
		
		alert.appendChild(closeMarker);
	}
	function addCloseTimer(alert) {
		progressBarTillClose = document.createElement('div');
		progressBarTillClose.style.setProperty('--duration', options.closeTimeMs / 1000);
		progressBarTillClose.classList.add('progress-bar');

		alert.appendChild(progressBarTillClose);
		setTimeout(() => alert.remove(), options.closeTimeMs);
	}

	for(let alert of alerts) {
		addCloseButton(alert);
		if(options.closeTimeMs) addCloseTimer(alert);
	}
}
