<template>
	<div>
        <ul class="listnone mlist" id="strip">
            <li v-for="item in items">
                <img :src="item.image" class="ii" :title="item.addtext" />
                <div class="shortitemtext left">
                    <header><a :href='advlink(item.id, item.ccodename, item.rcodename, item.codename, item.city)' :title="item.addtext"> {{ item.title }}</a></header>
                    <div class="text">
                        <div class="vprice b"><span class="name">Цена:</span> {{ rouble(item.price) }}</div>
                        
                        <div class="name">{{ location_name(item.regionName, item.city, item.cityName) }}</div>
                        <div class="name">{{ item.displayName }}</div>
                        <div class="name">{{ type_transfer(item.box, item.term, item.people) }}</div>
                        <div class="phone">
                            Телефон: <a :href="'/phones/' + item.id" target="_blank" class="dashed gn" @click="onClickGetPhone($event, item.id)">Показать</a>
                        </div>
                    </div>
                </div>
                <div class="both"></div>
                <phoneview :id="item.id" :ref="'pv' + item.id"></phoneview>
            </li>
        </ul>
	</div>
</template>
<script>
	//import '../css/cityfilter.css';
	require('./../../landlib/net/httpquerystring');
	require('./../../landlib/nodom/textformat');
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
                /*{
                    id:'1',
                    image: '/images/gazel.jpg',
                    addtext: 'lalalal la',
                    ccodename: 'lalal',
                    rcodename: 'oblka_sa',
                    codename: 'uxty',
                    title: 'Na labitenax ax',
                    price: 10.20,
                    cityName: 'Volyzkaya oblast',
                    city: 10,
                    regionName: 'Galkaxy',
                    displayName: 'Konor',
                    people: 0,
                    box: 1,
                    term: 0
                }*/
            ],
		};},
        //
        methods:{
            addItems(data) {
                let i;
                for (i = 0; i < data.length; i++) {
                    data[i].id = String(data[i].id);
                    this.items.push(data[i]);
                }
                //this.items = [...this.items, ...data];
                //console.log(this.items);
            },
            onClickGetPhone(ev, id){
                ev.preventDefault();
                let i, phv;
                if (this.$refs[`pv${id}`] ) {
                    phv = this.$refs[`pv${id}`];
                    if (phv.setSrc) {
                        phv.setSrc(`/phones/${id}`);
                    } else if (phv instanceof Array) {
                        for (i = 0; i < phv.length; i++) {
                            if (phv[i].id == id) {
                                phv[i].setSrc(`/phones/${id}`);
                                break;
                            }
                        }
                    }
                }
                return false;
            },
            type_transfer($nBox, $nTerm, $nPeople) {
                let $oItem, $a, $s;
                $oItem = {box: $nBox, term: $nTerm, people: $nPeople};
                
                $a = [];
                if ($oItem.box) {
                    $a.push(this.$t('app.Avenger'));
                }
                
                if ($oItem.people) {
                    $a.push(this.$t('app.Passenger'));
                }
                
                if ($oItem.term) {
                    $a.push(this.$t('app.Termobox'));
                }
                $s = $a.join(', ');
                $s = $s.toLowerCase();
                $s = TextFormat.capitalize($s);
                return $s;
            },
            location_name($sRegionName, $nCity, $sCityName) {
                let $sCity, $globals, $vars, $nSpecialCityId, $nCityId;
                
                $sCity = '';
                $nSpecialCityId = window.cityZeroId;
                $nCityId = $nCity;
                if ($nCityId != $nSpecialCityId && $sCityName) {
                    $sCity = (' ' + $sCityName);
                }
                return ($sRegionName + $sCity);
            },
            

            rouble($v) {
                $v = String($v);
                let $sUnit, $a, $s, $q, $i, $j, $sZero, $sRouble;
                $sUnit = this.$t('app.Roubles');//TODO
                if (parseInt($v) == 0 || !$v) {
                    $v = '1';
                }
                $v = $v.replace(/\./, ',');
                $a = $v.split(',');
                $s = $a[0];
                $q = [];
                for ($i = $s.length - 1, $j = 1; $i > -1; $i--, $j++) {
                    $q.push($s[$i]);
                    if ($j % 3 == 0) $q.push(' ');
                }
                $a[0] = $q.reverse().join('');
                $sZero = ($a[1] ? $a[1] : '');
                $sRouble = ' ' + $sUnit + ' ';
                
                if ($sZero == '00') {
                    return $a[0] + $sRouble;
                }
                $v = $a.join('');
                return $v + $sRouble;
            },
            /**
			 *  @description Получаем Ссылку на страницу объявления
			 *  @return String
			*/
            advlink($nId, $sCityCodename, $sRegionCodename, $sAdvCodename, $nCityId) {
                let $sCity, $globals, $vars, $nSpecialCityId;
                $sCity = '';
                $nSpecialCityId = window.cityZeroId;
                if ($nCityId != $nSpecialCityId && $sCityCodename) {
                    $sCity = '/' + $sCityCodename;
                }
                return ('/' + $sRegionCodename + $sCity + '/' + $sAdvCodename + '/' + $nId);
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