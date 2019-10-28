<template>
<transition name="slide-fadedown">
	<div class="m10p" v-if="isVisible">
		
	<!-- City or/and Region select-->
	<!-- 
		Без tags-changed="newTags => tags = newTags" не заполняется tags при вводе тегов
		Определять newTags в data не обязательно - всё и без него работает
	-->
	<label>{{ $t('app.bindArticle') }}</label>
	<vue-tags-input
		v-model="location"
		:tags="locations"
		:autocomplete-items="filteredItems"
		:add-only-from-autocomplete="true"
		:max-tags="1"
		:placeholder="$t('app.bindArticle')"
		@tags-changed="newTags => locations = newTags"
		@before-deleting-tag="onDeleteLocation"
		@input="onInput"
	/>
	<!-- TODO тут форма с тремя скрытыми инпутами (city_id, region_id, is_city )-->
	<!-- @tags-changed="newTags => locations = newTags" - тут возможно нужно tags вместо locations -->
	
	<!-- /City or/and Region select-->

	<!-- TODO получать id и is_reg выбранного через this.locations[0].id-->
	</div>
</transition>
</template>
<script>
    export default {
		name: 'cityfilter',

		computed: {
			/** @description Для компонента тагов, передрано из документации http://www.vue-tags-input.com/#/examples/autocomplete */
			filteredItems() {
				return this.autocompleteItems.filter(i => {
					return i.text.toLowerCase().indexOf(this.location.toLowerCase()) !== -1;
				});
			}
		},

		components:{
			vuetag: require('@johmun/vue-tags-input')
		},

		props: {
			
		},

        //вызывается раньше чем mounted
        data: function(){return {
			isVisible:false,

			//переменные связанные с инпутом выбора города
			/** @property {String} Модель локации, её будем обновлять при выборе */
			location: '',
			/** @property {Array} Список доступных городов, его будем обновлять при ajax-запросах */
			//TODO формат ещё не закончен

			//Это надо будет заполнять, если регион уже выбран в сессии пользователя
			locations: [
				{
					id: 1,
					text: 'Астраханская область, Астрахань',
					is_reg:0 //Это не "мегаполис" вроде Москвы (они показываются без области)
				}
			],
			//Это будет заполняться по мере получения результата ajax запроса
			/** @property {Array} autocompleteItems здесь будут храниться все  */
			autocompleteItems: [{
					id: 1,
					text: 'Москва',
					region:{
						id: 9,
						region_name: 'Московская область'
					}
				},
				{
					id: 2,
					text: 'Санкт-Петербург',
					region:{
						id: 9,
						region_name: 'Ленинградская область'
					}
				}],
			//end переменные связанные с инпутом выбора города
		};},
        //
        methods:{
			setVisible(v){
				this.isVisible = v;
			},
			swapVisible(){
				this.isVisible = !this.isVisible;
			},
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
			 * TODO найти в api компонента правильный способ отправки запроса и переработать в локации
			 * Помимо всего прочего, собирать уникальные регионы и добавлять их в конец списка
			 * @description Получение данных о существующих статьях, переработать в локации
			*/
			onSuccessGetLocationsList(data) {
				let i;
				this.autocompleteItems = [];
				for (i = 0; i < data.data.length; i++) {
					data.data[i].text = data.data[i].heading;
					delete data.data[i].heading;
					this.autocompleteItems.push(data.data[i]);
				}
				this.setRelatedLocation();
			},
			/** TODO тут должно быть заполнение locations, то есть если регион уже выбран в сессии пользователя
			 * @description Отработает только тогда, когда есть и this.relatedArticles  и this.autocompleteItems
			*/
			setRelatedLocation(){
				if (!this.relatedArticlesFromServer.length || !this.autocompleteItems.length) {
					this.tags = [];
					return;
				}
				let i, j;
				for (i = 0; i < this.relatedArticlesFromServer.length; i++) {
					for (j = 0; j < this.autocompleteItems.length; j++) {
						if (this.relatedArticlesFromServer[i].page_id == this.autocompleteItems[j].id) {
							this.tags.push(this.autocompleteItems[j]);
						}
					}
				}
				if (this.tags.length) {
					this.relatedArticles = JSON.stringify(this.tags);
				} else {
					this.relatedArticles = '';
				}
			},
			/**TODO
			 * Именно отсюда можно отправлять ajax запросы, а откуда же ещё?
			 * @param {String} textContent
			*/
			onInput(textContent){
				console.log(textContent);
			}
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