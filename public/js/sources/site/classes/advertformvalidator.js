class AdvertFormValidator {

	constructor(vueApp){
		this.vueApp = vueApp;
		this.t = vueApp.$t;
		console.log(this.t('app.Type_auto_required'));
	}

	isAdditionalValid() {
		this.vueApp.pageErrorBlockVisible = false;
		this.vueApp.clearInputErrors();
		//валидация заполненности хотя бы одного из чекбоксов
		//Тип авто
		let bType = this.vueApp.people || this.vueApp.box || this.vueApp.term;
		if (!bType) {
			this._addError('Type_auto_required', 'people');
			return false;
		}
		//Тип дистанции
		let bDistance = this.vueApp.far || this.vueApp.near || this.vueApp.piknik;
		if (!bDistance) {
			this._addError('Distance_required', 'far');
			return false;
		}
		//валидация логина и пароля, который может быть введён
		//Если введён email то должен быть введён и пароль
		let sEmail = this.vueApp.email;
		let sPassword = this.vueApp.password;
		
		if (sEmail || sPassword) {
			if (!sEmail || !sPassword ) {
				this._addError('Email and Password required if on from these no empty', 'email');
				return false;
			}
		}
		return true;
	}
	_addError(text, sInputKey){
		text = text.replace(/\s/mig, '_');

		if (this.vueApp[sInputKey + 'ErrorList']){
			this.vueApp[sInputKey + 'ErrorList'].push(this.t('app.' + text));
			this.vueApp[sInputKey + 'ErrorsVisible'] = true;
		}	
		this.vueApp.pageErrorText = this.t('app.Advert_form_has_errors');
		this.vueApp.pageErrorBlockVisible = true;
		
	}
	
}

export default AdvertFormValidator;