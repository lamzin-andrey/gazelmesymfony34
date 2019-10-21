<template>
<div v-if="isVisible" class="b please">
	<div class="please_in">
		<img src="/images/l-w.gif" width="16" class="ldr">
		<span ><div>{{ $t('app.PleaseAdvMe') }}</div><div>{{ siteName }}</div></span>
		<img :src="computedSrc" @load="onImageLoaded" />
		<div>
			<span>{{ $t('app.PleaseSocnet') }}</span>
		</div>
		<div :id="'sp' + id"></div>
	</div>
</div>
</template>
<script>
    export default {
		name: 'phoneview',

		computed: {
			//Если передан url изображения, вернет его
			computedSrc() {
				if (!this.startLoad) {
					return '/images/blank.png';
				}
				return this.path;
			},
			//Так как нужно значение из конфига Symfony, приходится использовать вычисляемое свойство
			siteName() {
				return window.SITE_NAME;
			}
		},

		props: {
			//Путь к изображению с телефоном
			src: {
				type:String,
				default: '/images/blank.png'
			},
			//Идентификатор объявления
			id: {
				type:String
			}
		},

        //вызывается раньше чем mounted
        data: function(){return {
			//Отвечает за видимость блока
			isVisible: false,
			//Путь к изображению
			path: '',
			//Принимает true когда кликнули на соответствующей ссылке Показать телефон
			startLoad: false
		};},
        //
        methods:{
			/**
			 * Не только изменяет значение вычисляемого свойства, но и показывает блок с прелоадером  и кнопки соц. сетей.
			 * @param {String} path путь к изображению
			*/
			setSrc(path) {
				this.isVisible = true;
				this.startLoad = true;
				this.path = path;
				Vue.nextTick(() => {
					let child = document.getElementById('socnetbuttons');
					let prnt = document.getElementById('sp' + this.id);
					prnt.appendChild(child);
				});
			},
			/**
			 * @description Обработка загрузки изображения с телефоном
			*/
			onImageLoaded(){
				$('.ldr').addClass('hide');
			},
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