<template>
	<div>

		<form method="POST" action="https://money.yandex.ru/quickpay/confirm.xml" id="yaform" class="hide" >
			<input v-model="yacache" type="hidden" name="receiver" id="rec">
			<input type="hidden" name="formcomment" id="comment" :value="getComment()">
			
			<input v-model="tid" type="hidden" name="label" >
			<input type="hidden" name="quickpay-form" value="shop">
			<input v-model="tid" type="hidden" name="targets">
			<input v-model="paysum" type="hidden"  name="sum" data-type="number">
			<input type="hidden" name="comment" id="comment2" :value="getComment()">

			<input v-model="paymentType" type="hidden" name="paymentType" id="paytype" >
		</form>
		
		
			<div class="aformwrap upformwrap inrelative payformwr">
				<div id="hPaymethodGr" v-if="step == STEP_SHOW_PAY_VARIANTS_FORM">
					<p class="b please tj payformmsg">Оплатить {{paysum}} рублей. Выберите способ оплаты. </p>
					<div>
						<img src="/images/p/y.png" class="pblabel">
						<input @click="onClickSendMoney('PC')" type="button" class="payvariant" id="yad" value="Яндекс Деньги">
					</div>
					<div>
						<img src="/images/p/c.png" class="pblabel">
						<input @click="onClickSendMoney('AC')" type="button" class="payvariant" id="card" value="Банковская карта">
					</div>
					<div class="aphone sz12">
						<label for="phonepayform" class="slabel">{{ $t('app.YourPayPhone') }}</label><br>
						<input type="number" v-model="phone" id="phonepayform" >
					</div>
					<div>
						<img src="/images/p/m.png" class="pblabel">
						<input @click="onClickSendMoney('MC')" type="button" class="payvariant" id="mob" value="Со счёта мобильного">
					</div>
					
				</div>
				<!-- идентификаторы крайне важны, сумма -->
				
				<div id="hPaysumGr" v-if="step == STEP_SHOW_PAY_FORM">
					<p class="b please tj payformmsg">Время бесплатных поднятий объявлений на сайте закончилось. <br>Вы можете оплатить поднятия.</p>
					<div>
						<img src="/images/p/m.png" class="pblabel">
						<input @click="onClickSelectSum(60, 1)" type="button" class="paysum" id="s60" value="Поднять 1 раз - 60 Р">
					</div>
					<div>
						<img src="/images/p/m.png" class="pblabel">
						<input @click="onClickSelectSum(200, 5)" type="button" class="paysum" id="s200" value="Поднять 5 раз - 200 Р">
					</div>
					<div>
						<img src="/images/p/m.png" class="pblabel">
						<input @click="onClickSelectSum(700, 31)" type="button" class="paysum" id="s700" value="Поднять 31 раз - 700 Р">
					</div>
				</div>

				<div id="hRoboplace"  v-if="step == STEP_SHOW_RK_INFO">
					<p class="b please tj payformmsg">После нажатия на кнопку вы будете перенаправлены на страницу сервиса Робокасса <br>Выбрав там вашего мобильного оператора вы сможете провести оплату.</p>
				</div>

			</div>
		

	</div>
</template>
<script>
//	import '../css/phd.css';

	//Компонент для аплоадла файлов
	//Vue.component('inputfileb4', require('../../landlib/vue/2/bootstrap/4/inputfileb4/inputfileb4.vue').default);
    export default {
        name: 'PayForm',
        //вызывается раньше чем mounted
        data: function(){return {
			//Определяет, какой экран показывать 
			step: 7,

			//Экран оплаты
			STEP_SHOW_PAY_FORM: 7,
			
			//Экран ожидания оплаты
			STEP_WAIT_PAYMENT: 9,

			//Экран показа инфо о роюбокассе
			STEP_SHOW_RK_INFO: 1,

			//Экран показа вариантов платежа
			STEP_SHOW_PAY_VARIANTS_FORM: 2,

			//связанные с формой скидки
			paysum: 100,

			//связанные с формой оплаты
			//Чем платить
			paymentType: 'MC',//MC (qiwi), AC (ya-bank-card), PC(ya-cache)
			//Идентификатор транзакции из таблицы operations ('582 phd')
			tid: '',
			//Номер яндекс-кошелька
			yacache: '',
			//Номер телефона для оплаты
			phone: '',

			//связанные с экраном ожидания оплаты
			paysystemName: '',

		}; },
		

        //
        methods:{
			getComment(){
				//console.log('getComment: this.quantity = ' + this.quantity);
				return this.$t('app.PayformComment').replace('{n}', this.quantity);
			},
			/**
			 * @description Обработка клика на кнопке выбора количества поднятий
			 * @param {Number} nSum
			 * @param {Number} nQnt
            */
			onClickSelectSum(nSum, nQnt) {
				this.paysum = nSum;
				this.quantity = nQnt;
				//console.log('set this.quantity = ' + this.quantity);
				this.step = this.STEP_SHOW_PAY_VARIANTS_FORM;
			},
			/**
			 * @description Обработка клика на кнопке Перевести (деньги)
            */
			onClickSendMoney(method) {
				this.$root.setMainSpinnerVisible(true);
				this.paymentType = method;
				//TODO должны получить id из таблицы pay_transactions
				//в operations main_id это будет phd_messages.id
				Rest._post({sum:this.paysum, method: method, phone: this.phone}, (data) => { this.onSuccessStartPayTransaction(data);}, '/startpaytransaction.json', (a, b, c) => {this.defaultFailSendFormListener(a, b, c);});
			},
			
			/**
			 * @description Обработка получения с сервера tid, чтобы отправить его на сервер paysystem
			*/
			onSuccessStartPayTransaction(data) {
				if (!this.defaultFailSendFormListener(data)) {
					return;
				}
				this.tid = data.id + 'gw';
				this.yacache = data.yn;
				//TODO Показать информацию о новой вкладке
				
				
				Vue.nextTick(() => {
					$('#yaform').submit();
					//this.step = this.STEP_WAIT_PAYMENT;
				});
			},
			/**
			 * @description Обработка клика на кнопке Продолжить формы предложения скидки
            */
			onClickShowPayform(){
				this.$root.setMainSpinnerVisible(true);
				Rest._post({sum:this.paysum}, (data) => { this.onSuccessSaveSum(data);}, this.$root._serverRoot + '/phddiscount.json', (a, b, c) => {this.defaultFailSendFormListener(a, b, c);});
			},
			/**
			 * @description
			*/
			onSuccessSaveSum(data) {
				if (!this.defaultFailSendFormListener(data)) {
					return;
				}
				this.paysum = data.sum;
				this.yacache = data.yc;
				this.step = this.STEP_SHOW_PAY_FORM;
			},
			
			
			/**
			 * @description Тут локализация некоторых параметров, которые не удается локализовать при инициализации
			*/
			localizeParams() {
				//Текст на кнопках диалога подтверждения действия
				this.countDownMeasure = this.$t('app.seconds_more_19');
				this.fileInQueueWaitScreenMessage = this.$t('app.fileInQueue');
				this.fileInQueueSecondsMessageFragment = this.$t('app.fileInQueueSecondsMessageFragment');
			},
			/**
			 * @description Обработка ответа сервера на ajax запрос по умолчанию
			 * @params see this.$root.defaultFailSendFormListener
			*/
			defaultFailSendFormListener(a, b, c) {
				this.$root.setMainSpinnerVisible(false);
				return this.$root.defaultFailSendFormListener(a, b, c);
			}
        }, //end methods
        //вызывается после data, поля из data видны "напрямую" как this.fieldName
        mounted() {
			this.localizeParams();
			var self = this;
        }
    }
</script>