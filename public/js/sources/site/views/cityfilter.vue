<template>
<transition name="slide-fadedown">
	<div class="m10p iblock" v-if="isVisible">
		
	<!-- City or/and Region select-->
	<!-- 
		Без tags-changed="newTags => tags = newTags" не заполняется tags при вводе тегов
		Определять newTags в data не обязательно - всё и без него работает
	-->
	<div class="left">
		<vue-tags-input
			v-model="location"
			:tags="locations"
			:autocomplete-items="filteredItems"
			:add-only-from-autocomplete="true"
			:max-tags="1"
			:placeholder="$t('app.enterCity')"
			@tags-changed="newTags => locations = newTags"
			@before-deleting-tag="onDeleteLocation"
			@input="onInput"
		/>
		<!-- @tags-changed="newTags => locations = newTags" - тут возможно нужно tags вместо locations -->
	</div>
	<!-- /City or/and Region select-->
	<!-- Save button-->
	<div class="locationsave-left">
		<!-- форма с тремя скрытыми инпутами (city_id, region_id, is_city )-->
		<form action="/setregionjs" @submit="onSubmitLocationData" method="POST">
			<input :value="getCityId" type="hidden" id="cityId" name="cityId">
			<input :value="getRegionId" type="hidden" id="regionId" name="regionId" >
			<input :value="getIsCity" type="hidden" id="isCity>" name="isCity" >
			<input type="submit" :value="$t('app.Save')" class="locationsave">
		</form>
	</div>
	<!-- /Save button-->
	<div class="both"></div>
	<!-- TODO получать id и is_reg выбранного через this.locations[0].id-->
	</div>
</transition>
</template>
<script>
	import '../css/cityfilter.css';
	require('./../../landlib/net/httpquerystring');
    export default {
		name: 'cityfilter',

		computed: {
			//Вычисляемые свойства связанные с инпутом выбора города
			/** @description Для компонента тагов, передрано из документации http://www.vue-tags-input.com/#/examples/autocomplete */
			filteredItems() {
				return this.autocompleteItems.filter(i => {
					i.text = String(i.text);
					return (
							i.text.toLowerCase().indexOf(this.location.toLowerCase()) !== -1
							&& this.locations.length == 0
					);
				});
			},
			// /Вычисляемые свойства связанные с инпутом выбора города


			/**
			 *  @description Получаем идентификатор выбранного в инпуте города 
			 *  @return Number
			*/
			getCityId() {
				return this._getCityId();
				if (this.locations[0] && parseInt(this.locations[0].id) && parseInt(this.locations[0].is_region) != 1) {
					if ($('#advert_form_city')[0]) {
						$('#advert_form_city').val(this.locations[0].id);
						$('#advert_form_region').val(this.locations[0].r_id);
						if ($('#hDisplayLocation')[0] && this.locations[0].text) {
							$('#hDisplayLocation').text(this.locations[0].text);
						}
					}
					return this.locations[0].id;
				}
				return 0;
			},
			/**
			 *  @description Получаем идентификатор выбранного в инпуте города 
			 *  @return Number
			*/
			getIsCity() {
				return this._getIsCity();
			},
			/**
			 *  @description Получаем идентификатор региона выбранного в инпуте города 
			 *  @return Number
			*/
			getRegionId() {
				return this._getRegionId();
			},
		},

		components:{
			vuetag: require('@johmun/vue-tags-input')
		},

		props: {
			
		},

        //вызывается раньше чем mounted
        data: function(){return {
			/** @property {Boolean} Отвечает за показ поля ввода и кнопки Сохранить */
			isVisible:false,

			//переменные связанные с инпутом выбора города

			/** @property {String} Модель локации, её будем обновлять при выборе */
			location: '',
			/** @property {Array} Список доступных городов, его будем обновлять при ajax-запросах */
			//TODO формат ещё не закончен

			//Это надо будет заполнять, если регион уже выбран в сессии пользователя
			locations: [/*{
				id: Number,    Идентификатор региона или города
				text: String,  Отображаемое название локации ("Регион" или "Регион, город" или "Город")
				is_region: Number говорит о том, что в id хранится regions.id, а не cities.id
				is_city: Number Нужно учитывать только при is_region = 1. Говорит о том, что это не регион а крупный город (Нехорошее наследие связанное с тем что крупные города показывались в списке регионов)
			}*/],
			//Это будет заполняться по мере получения результата ajax запроса
			/** @property {Array} autocompleteItems здесь будут храниться все  */
			autocompleteItems: [],
			//end переменные связанные с инпутом выбора города

			/** @property {Number} Идентификатор выбранного города*/
			cityId: 0,

			/** @property {Number} Идентификатор выбранного региона*/
			regionId: 0,

			/** @property {Number} @see db regions.is_city comment */
			isCity: 0
		};},
        //
        methods:{
			/**
			 *  @description Получаем идентификатор выбранного в инпуте города 
			 *  @return Number
			*/
			_getIsCity() {
				if (this.locations[0] && parseInt(this.locations[0].is_city)) {
					return this.locations[0].is_city;
				}
				return 0;
			},
			/**
			 *  @description Получаем идентификатор региона выбранного в инпуте города 
			 *  @return Number
			*/
			_getRegionId() {
				if (this.locations[0] && this.locations[0].is_region && parseInt(this.locations[0].id) && parseInt(this.locations[0].is_region)) {
					if ($('#advert_form_region')[0]) {
						$('#advert_form_region').val(this.locations[0].id);
						$('#advert_form_city').val(0);
						if ($('#hDisplayLocation')[0] && this.locations[0].text) {
							$('#hDisplayLocation').text(this.locations[0].text);
						}
					}
					return this.locations[0].id;
				}
				return 0;
			},
			/**
			 *  @description Получаем идентификатор выбранного в инпуте города 
			 *  @return Number
			*/
			_getCityId() {
				if (this.locations[0] && parseInt(this.locations[0].id) && parseInt(this.locations[0].is_region) != 1) {
					if ($('#advert_form_city')[0]) {
						$('#advert_form_city').val(this.locations[0].id);
						$('#advert_form_region').val(this.locations[0].r_id);
						if ($('#hDisplayLocation')[0] && this.locations[0].text) {
							$('#hDisplayLocation').text(this.locations[0].text);
						}
					}
					return this.locations[0].id;
				}
				return 0;
			},
			onSubmitLocationData(evt) {
				let url = HttpQueryString.requestUri().split('?')[0];
				if (url == '/podat_obyavlenie' || ~url.indexOf('/cabinet/edit/')) {
					evt.preventDefault();
					var nCount = 0, nDir = -1, opMin = 0.2, opMax = 1, o = $('#hDisplayLocation'),
						op = 'opacity', step = 0.5;
					var ival = setInterval(() => {
						var currOp = parseFloat(o.css(op));
						if (nCount < 3) {
							if (currOp <= opMin) {
								nDir *= -1;
							}
							if (currOp >= opMax) {
								nDir *= -1;
								nCount++;
							}
							currOp += step * nDir;
							if (currOp > opMax) {
								currOp = opMax;
							}
							if (currOp < opMin) {
								currOp = opMin;
							}
							o.css(op, currOp);
						} else {
							clearInterval(ival);
							o.css(op, 1);
						}
					}, 140);

					Rest._post({regionId: this._getRegionId(), cityId: this._getCityId(), isCity: this._getIsCity()}, (data) => {
						console.log('Saved!');
					}, '/setregionjs', () => {});

					return false;
				}
				return true;
			},
			setVisible(v){
				this.isVisible = v;
			},
			swapVisible(){
				this.isVisible = !this.isVisible;
			},

			//методы, связанные с инпутом выбора города
			/**
			 * @description Обработка удаления тэга локации в фильтре локации
			*/
			onDeleteLocation(evt){
				let delIndexes = [], i;
				//TODO try reduce or other new methods
				for (i = 0; i < this.locations.length; i++) {
					if (this.locations[i].id == evt.tag.id) {
						delIndexes.push(i);
					}
				}
				//sort by desc
				delIndexes.sort((a, b) => {
					if (a < b) {
						return 1;
					}
					
				});
				for (i = 0; i < delIndexes.length; i++) {
					this.locations.splice(delIndexes[i], 1);
				}
			},
			/**
			 * @description Установка списка населенных пунктов в автокомплит vue-tags-input. Регионы городов добавляются в конец списка
			*/
			onSuccessLoadLocations(data) {
				if (!this.onFailLoadLocations(data)) {
					return;
				}
				//Приводим полученные данные к формату, который необходим для инпута тегов
				let i, key, aRegions = {}, sCurrentLocationName, co;
				this.autocompleteItems = [];
				for (i = 0; i < data.list.length; i++) {
					if (data.list[i].region_name) {
						key = 'region_name';
						data.list[i].text = data.list[i][key];
						data.list[i].is_region = 1;
					} else {
						key = 'city_name';
						data.list[i].is_city = 0;
						sCurrentLocationName = data.list[i][key];
						if (data.list[i].r_region_name && data.list[i].r_region_name) {
							sCurrentLocationName = data.list[i].r_region_name + ', ' + sCurrentLocationName;
						}
						data.list[i].text = sCurrentLocationName;

						if (data.list[i].r_is_city != 1) {
							aRegions[data.list[i].r_id] = data.list[i];
						}
					}
					
					delete data.list[i][key];
					this.autocompleteItems.push(data.list[i]);
				}
				for (i in aRegions) {
					co = new Object();
					co.is_region = 1;
					if (aRegions[i].r_region_name) {
						co.text = aRegions[i].r_region_name;
						co.is_city = aRegions[i].r_is_city;
						co.id = i;	
					} else {
						co.text = aRegions[i].region_name;
						co.is_city = aRegions[i].is_city;
						co.id = i;	
					}
					this.autocompleteItems.push(co);
				}
			},
			/**
			 * @description
			 * @param {String} sCityId
			 * @param {String} sRegionId
			 * @param {String} sIsCity
			 * @param {String} sLocationName
			*/
			setLocation(sCityId, sRegionId, sIsCity, sLocationName) {
				
				if (!sLocationName) {
					return;
				}
				if (sLocationName) {
					$('#hDisplayLocation').text(sLocationName);
				}
				let nCityId = parseInt(sCityId),
					nRegionId = parseInt(sRegionId),
					nIsCity = parseInt(sIsCity),
					o = {
						is_region : 0,
						is_city : 0,
						id : 0,
						text : $('#hDisplayLocation').text()
					};
				if (!sLocationName) {
					o.text = '';
				}
				if (nCityId) {
					o.is_city = 0;
					o.id = nCityId;
					o.r_id = nRegionId;
				} else if (nRegionId) {
					 o.is_city = nIsCity;
					 o.id = nRegionId;
					 o.is_region = 1;
				}
				this.locations = [];
				this.locations.push(o);
			},
			/**TODO
			 * Именно отсюда можно отправлять ajax запросы, а откуда же ещё?
			 * @param {String} textContent
			*/
			onInput(textContent){
				if (textContent.length > 3 && this.locations.length == 0) {
					if (!this.requestIsSended) {
						this.requestIsSended = 1;
						//_get(onSuccess, url, onFail) {
						Rest._get((data) => { this.onSuccessLoadLocations(data) }, '/getcitinamesbysubstr?s=' + textContent, (data) => { this.onFailLoadLocations(data);});
					}
				}
			},
			/**
			 * {Object} data
			*/
			onFailLoadLocations(data) {
				this.requestIsSended = 0;
				return this.$root.defaultAjaxFail(data);
			}
			//end методы, связанные с инпутом выбора города

        }, //end methods
        //вызывается после data, поля из data видны "напрямую" как this.fieldName
        mounted() {
            var self = this;
            /*this.$root.$on('showMenuEvent', function(evt) {
                self.menuBlockVisible   = 'block';
                self.isMainMenuVisible  = true;
                self.isScrollWndVisible = false;
                self.isColorWndVisible  = false;
                self.isHelpWndVisible   = false;
                self.nStep = self.$root.nStep;
            })/**/
            
        }
    }
</script>