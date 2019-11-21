window.jQuery = window.$ = window.jquery = require('jquery');
window.Vue = require('vue');

//cache
import CacheSw from './cachesw.js';
window.cacheClient = new CacheSw();

require('./../../vendor/lazyloadxt1.1.0.min.js');
require('./../landlib/net/rest.js');


//Интернациализация
import VueI18n  from 'vue-i18n';
import locales  from './vue-i18n-locales';

const i18n = new VueI18n({
    locale: 'ru', // set locale
    messages:locales, // set locale messages
});
//end Интернациализация


//Back to top button
import BackToTop from 'vue-backtotop';
Vue.use(BackToTop);

//customize btt
import './css/backtotop.css'
// /Back to top button



Vue.component('phoneview', require('./views/phoneview'));
Vue.component('cityfilter', require('./views/cityfilter'));
Vue.component('typefilter', require('./views/typefilter'));
Vue.component('loginform', require('./views/loginform'));


window.app = new Vue({
    i18n : i18n,
	el: '#app',
	
	delimiters : ['[[', ']]'],
    
	// router,
	/**
	* @property Данные приложения
	*/
	data: {
		/** @property {String} name desc  */
	},
	/**
	* @description Событие, наступающее после связывания el с этой логикой
	*/
	mounted() {
		Rest._token = 'open';//TODO real value
		if (this.$refs.loginform) {
			Rest._token = Rest._token = this.$refs.loginform.getCsrf();
		}
		this.$refs.cityfilter.setLocation(cityId, regionId, isCity);
		$('#bttimg').css('display', 'block');
		Rest._get((data) => { this.onSuccessGetIsAuth(data); }, '/getauthstate', () => {});
	},
	/**
	* @property methods эти методы можно указывать непосредственно в @ - атрибутах
	*/
	methods:{
		/**
		 * @description Клик на ссылке Изменить регион
		*/
		onClickChangeRegion(ev) {
			ev.preventDefault();
			this.$refs['cityfilter'].swapVisible();
			return false;
		},
		/**
		 * @description Клик на ссылке Мои объявления
		*/
		onShowAuthFormClick(ev) {
			let s = ev.currentTarget.getAttribute('href');
			console.log(ev.target);
			console.log(ev.currentTarget);
			if (s != '/cabinet') {
				ev.preventDefault();
				//$('#alayer').toggleClass('hide');
				this.$refs['loginform'].swapVisible();
				return false;
			}
			return true;
		},
		/**
		 * @description Клик на ссылке Фильтр
		*/
		onClickSwapFilter(ev) {
			ev.preventDefault();
			this.$refs['typefilter'].swapVisible();
			return false;
		},
		/**
		 * @description Клик на ссылке Получить изображение с телефоном пользователя
		 * @param {Number} id
		*/
		onClickGetPhone(ev, id) {
			ev.preventDefault();
			this.$refs[`pv${id}`].setSrc(`/phones/${id}`);
			return false;
		},
		/** 
		 * @description Установить вид контролов связанных с авторизацией пользователя (Показать / скрыть кнопки Выход и Настройки)
		 * @param {Boolean} bIsAuth 
		*/
		setAuthView(bIsAuth) {
			let m = 'addClass',
				cablink = '/login';
			if (bIsAuth) {
				m = 'removeClass';
				cablink = '/cabinet';
			}
			$('#profilelinkwrap')[m]('hide');
			$('#logoutlinkwrap')[m]('hide');
			$('#cablink').attr('href', cablink);
		},
		/**
		 * @description Успещшное получение данных, авторизован ли пользователь (связанно с кэшированием через sw)
		*/
		onSuccessGetIsAuth(data) {
			this.setAuthView( parseInt(data.uid) > 0 );
		},
		/**
		 * @description Извлекает clientX из 0 элемента changedTouches события TouchStartEvent
		 * @param {TouchStartEvent} evt
		 * @return Number
		*/
		getClientXFromTouchEvent(evt){
			if (evt.changedTouches && evt.changedTouches[0] && evt.changedTouches[0].clientX) {
				return evt.changedTouches[0].clientX;
			}
			return 0;
		},
		/**
		 * @description Индексирует массив по указанному полю
		 * @param {Array} data
		 * @param {String} id = 'id'
		 * @return Object
		*/
		storage(key, data) {
			var L = window.localStorage;
			if (L) {
				if (data === null) {
					L.removeItem(key);
				}
				if (!(data instanceof String)) {
					data = JSON.stringify(data);
				}
				if (!data) {
					data = L.getItem(key);
					if (data) {
						try {
							data = JSON.parse(data);
						} catch(e){;}
					}
				} else {
					L.setItem(key, data);
				}
			}
			return data;
		},
		/**
		 * @return String title
		*/
		getTitle(){
			return document.getElementsByTagName('title')[0].innerHTML.trim();
		},
		/**
		 * 
		 * @param {*} d Может быть объектом данных с сервера, а может быть объектом события ошибки передачи данных
		 * @return {Boolean} false если произошла ошибка передачи данных или приложения
		 */
		defaultAjaxFail(d){
			if (d.status == 'ok') {
				return true;
			}
			if (d.statuc == 'error' && d.message) {
				this.alert(d.message);
				return false;
			}
			this.alert(this.$t('app.Default_error'));
			return false;
		},
		alert(s){
			alert(s);
		}
	}//end methods

	}).$mount('#app');


