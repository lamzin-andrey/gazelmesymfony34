<template>
	<div>
        <ul id="paglist" v-if="isPaginationVisible">
            <li v-for="p in items" :class="p.css" >
                <a v-if="p.active == 0" :href="setUrlVar('page', p.n)">{{ p.text }}</a>
                <span v-if="p.active == 1">{{ p.n }}</span>
            </li>
        </ul>  
	</div>
</template>
<script>
	//import '../css/cityfilter.css';
	require('./../../landlib/net/httpquerystring');
	//require('./../../landlib/nodom/textformat');
    export default {
		name: 'additems',

		computed: {
			
		},


		props: {
			
		},

        //вызывается раньше чем mounted
        data: function(){return {
			/** @property {Boolean} Отвечает за показ поля ввода и кнопки Сохранить */
			isVisible:false,

        /** @property {Array} Список объявлений */
			items: [
            ],
            isPaginationVisible : false
		};},
        //
        methods:{
            setUrlVar(sPage, n) {
                n = n == '1' ? 'CMD_UNSET' : n;
                return HttpQueryString.setVariable(location.href.replace(/#/g, ''), sPage, n);
            },
			/**
			 *  @description 
			*/
			setPagination(raw) {
                let i, o;
                this.items = [];
                for (i = 0; i < raw.length; i++) {
                    o = new Object();
                    o.n = raw[i].n;
                    o.text = raw[i].text ? raw[i].text : o.n;
                    o.css = raw[i].active == '1' ? 'active' : '';
                    o.active = raw[i].active;
                    this.items.push(o);
                }
				this.isPaginationVisible = true;
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