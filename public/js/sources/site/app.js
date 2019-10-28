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

Vue.component('phoneview', require('./views/phoneview'));
Vue.component('cityfilter', require('./views/cityfilter'));

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
		//TODO from old app constructor
		Rest._token = 'open';//TODO real value
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
		 * @description Клик на ссылке Получить изображение с телефоном пользователя
		 * @param {Number} id
		*/
		onClickGetPhone(ev, id) {
			ev.preventDefault();
			this.$refs[`pv${id}`].setSrc(`/phones/${id}`);
			return false;
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
		}
	}//end methods

	}).$mount('#app');


