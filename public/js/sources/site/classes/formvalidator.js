class FormValidator {

	constructor(vueApp){
		this.vueApp = vueApp;
		this.t = vueApp.$t;
		console.log(this.t('app.Type_auto_required'));
	}

	isAdditionalValid() {
		$('.j-field-error').remove();
		//валидация заполненности хотя бы одного из чекбоксов
		//Тип авто
		let nType = this.getInt('people') + this.getInt('box') + this.getInt('term');
		if (!nType) {
			this._addError('Type_auto_required', 'people');
			return false;
		}
		//Тип дистанции
		let nDistance = this.getInt('far') + this.getInt('near') + this.getInt('piknik');
		if (!nDistance) {
			this._addError('Distance_required', 'far');
			return false;
		}
		//валидация логина и пароля, который может быть введён
		//Если введён email то должен быть введён и пароль
		let sEmail = this.get('email').trim();
		let sPassword = this.get('password').trim();
		
		if (sEmail || sPassword) {
			if (!sEmail || !sPassword ) {
				this._addError('Email and Password required if on from these no empty', 'email');
				return false;
			}
		}
		return true;
	}
	/**
	 * @param {String} sInputKey
	 * @return Number
	*/
	getInt(sInputKey){
		let v = parseInt(this.get(sInputKey)); 
		v = isNaN(v) ? 0 : v;
		console.log('return' + v + ` for ${sInputKey}`);
		return v;
	}
	/**
	 * @param {String} sInputKey
	 * @return String
	*/
	get(sInputKey){
		let jInp = $('#advert_form_' + sInputKey), v = jInp.val();	
		if (jInp[0].type == 'checkbox') {
			v = jInp.prop('checked') ? '1' : '0';
			console.log('got prop checked = ' + v);
		}
		v = !v ? '' : v;
		return v;
	}
	_addError(text, sInputKey){
		text = text.replace(/\s/mig, '_');
		let jInp = $('#advert_form_' + sInputKey);
		if (jInp[0].type == 'checkbox') {
			//after label
			jInp = $('label[for=' + jInp[0].id + ']').first();
		}
		text = this.t('app.' + text);
		jInp.after($(`<ul class="j-field-error"><li>${text}</li></ul>`));
		text = this.t('app.Advert_form_has_errors');
		$('#errorPlacer').after($(`<div class="j-field-error vis" id="mainsfrormerror">${text}</div>`));
	}
	
}

export default FormValidator;