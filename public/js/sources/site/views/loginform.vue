<template>
<transition name="slide-fadedown">
	<div class="popupouter " v-if="isVisible">
		<div class="aformwrap">
				<div id="autherror" class="both hide">{{ $t('app.Usernotfound') }}</div>
				<div class="aphone">
					<label for="login" class="slabel">Номер телефона</label><br/>
					<label for="login">
						<img :alt="$t('app.Phone')" :title="$t('app.Phone')" :src="src" /></label> 
					<input type="text" name="login" id="login">
				</div>
				<div class="apwd">
					<label for="password" class="slabel">{{ $t('app.Password')  }}</label><br/> 
					<input type="password" name="password" id="password">
				</div>
				<div class="left lpm1">
					<a class="smbr" href="/remind" target="_blank">{{ $t('app.Passwordrecovery')  }}</a>
				</div>
				<div class="right prmf">
					<input type="button" name="aop" id="aop" :value="$t('app.Enter')">
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
			}
		},

        //вызывается раньше чем mounted
        data: function(){return {
			/** @property {Boolean} Отвечает за показ поля ввода и кнопки Сохранить */
			isVisible:false

			
		};},
        //
        methods:{
			setVisible(v){
				this.isVisible = v;
			},
			swapVisible(){
				this.isVisible = !this.isVisible;
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