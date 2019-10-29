<template>
<transition name="slide-fadedown">
	<div v-if="isVisible">
		<div id="mainsfrorm">
			<form :action="formaction" :querystring="querystring" method="get" name="search" id="search">
				<div class="prmf">
					<div class="checkblock">
						<input type="checkbox" name="people" id="people" value="1" v-model="people"> <label for="people">Пассажирская</label>
					</div>
					<div class="checkblock">
						<input type="checkbox" name="box" id="box" value="1" v-model="box"> <label for="box">Грузовая</label>
					</div>
					<div class="checkblock">
						<input type="checkbox" name="term" id="term" value="1" v-model="term"> <label for="term">Термобудка</label>
					</div>
					<div class="checkblock">	
						<input type="checkbox" name="far" id="far" value="1" v-model="far"> <label for="far">Межгород</label>
					</div>
					<div class="checkblock">
						<input type="checkbox" name="near" id="near" value="1" v-model="near"> <label for="near">По городу</label>
					</div>
					<div class="checkblock">
						<input type="checkbox" name="piknik" id="piknik" value="1" v-model="piknik"> <label for="piknik">За город (пикник)</label>
					</div>
				</div>
				<div class="text-right pt-2">
					<input type="submit" value="Найти"/>
				</div>
			</form>
		</div>
	</div>
</transition>
</template>
<script>
	import '../../landlib/net/httpquerystring';
    export default {
		name: 'typefilter',

		computed: {},

		props: {
			formaction: {
				type: String,
				default: '/'
			},
			querystring: {
				type: String,
				default: ''
			}
		},

        //вызывается раньше чем mounted
        data: function(){return {
			/** @property {Boolean} Отвечает за показ поля ввода и кнопки Сохранить */
			isVisible:false,

			//Модели чекбоксов
			people: false,
			box: false,
			term: false,
			far: false,
			near: false,
			piknik: false
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
			 * @param {String} s
			*/
			setCheckbox(s) {
	           	this[s] = parseInt(HttpQueryString._GET(s, '0', this.querystring) ) == 1 ? true : false;
			}

        }, //end methods
        //вызывается после data, поля из data видны "напрямую" как this.fieldName
        mounted() {
			this.setCheckbox('people');
			this.setCheckbox('box');
			this.setCheckbox('term');
			this.setCheckbox('far');
			this.setCheckbox('near');
			this.setCheckbox('piknik');
        }
    }
</script>