<template>
<transition name="slide-fadedown">
	<div class="popupouter " v-if="isVisible">
		<div class="aformwrap">
				<div id="autherror" class="both" v-if="isHasError">{{ $t('app.Usernotfound') }}</div>
				<div class="aphone">
					<label for="login" class="slabel">Номер телефона</label><br/>
					<label for="login">
						<img :alt="$t('app.Phone')" :title="$t('app.Phone')" :src="src" /></label> 
					<input @keydown="onEnterData" type="text" v-model="login">
				</div>
				<div class="apwd">
					<label for="password" class="slabel">{{ $t('app.Password')  }}</label><br/> 
					<input @keydown="onEnterData" type="password" v-model="password">
				</div>
				<div class="left lpm1">
					<a class="smbr" href="/remind" target="_blank">{{ $t('app.Passwordrecovery')  }}</a>
				</div>
				<div class="right prmf">
					<input @click="onClickSendLoginButton" type="button" name="aop" id="aop" :value="$t('app.Enter')">
				</div>
		</div>
	</div>
</transition>
</template>
<script>
	//import '../css/cityfilter.css';
    export default {
		name: 'formlogin',

		computed: {
			
		},

		components:{
			
		},

		props: {
			src:{
				type:String
			},
			csrf:{
				type:String
			},
			action:{
				type:String
			}
		},

        //вызывается раньше чем mounted
        data: function(){return {
			/** @property {Boolean} Отвечает за показ поля ввода и кнопки Сохранить */
			isVisible:false,

			/** @property {Boolean} Отвечает за показ сообщения об ошибке */
			isHasError:false,

			/** @property {String} Логин */
			login:'',

			/** @property {String} Пароль */
			password:''

			
		};},
        //
        methods:{
			setVisible(v){
				this.isVisible = v;
			},
			swapVisible(){
				this.isVisible = !this.isVisible;
			},
			getCsrf(){
				return this.csrf;
			},
			/**
			 * @description Клик нак нопке Войти
			*/
			onClickSendLoginButton(){
				this.isHasError = false;
				$('.aformwrap').css('height', 'auto');
				Rest._post({_csrf_token: Rest._token, _username: this.login, _password: this.password}, (data) => {this.onSuccessLogin(data);}, this.action, (data) => { this.onFailLogin(data); });
			},
			/**
			 * @description Нажатие на кнопку enter в полях ввода
			*/
			onEnterData(evt){
				if (evt.keyCode == 13) {
					this.onClickSendLoginButton();
				}
				return true;
			},
			/**
			 * @description Удачный логин
			*/
			onSuccessLogin(data) {
				if (!this.onFailLogin(data)) {
					return;
				}
				if (data.success == true) {
					this.isVisible = false;
					this.$root.setAuthView(true);
				}
			},
			/**
			 * @description Неудачный логин
			*/
			onFailLogin(data) {
				if (data.message && !data.success) {
					this.isHasError = true;
					$('.aformwrap').css('height', '220px');
					if (~data.messageimdexOf('CSRF')) {
						location.reload();
					}
					return false;
				}
				return true;
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