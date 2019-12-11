<template>
	<div>
		<!-- File input for immediately upload file on Select file event -->
		<div v-if="!immediateleyUploadOff" class="custom-file mt-2">
			<input type="file"
			:class="'custom-file-input' + (className ? (' ' + className) : '')"
			:aria-describedby="id + 'FileImmediatelyHelp'"
			:id="id + 'FileImmediately'" :name="id + 'FileImmediately'"
			@change="onSelectFile"
			>
			
			<label class="hide" :for="id + 'FileImmediately'">{{label}}</label>
			<div class="hide"></div>
			<small :id="id + 'FileImmediatelyHelp'" class="hide"></small>
			
		</div>

		<!-- File input for  upload file on click Upload button  event -->
		<div v-if="immediateleyUploadOff">
			<div class="custom-file mt-2">
				<input type="file"
				:class="'custom-file-input' + (className ? (' ' + className) : '')"
				
				:aria-describedby="id + 'FileDefferHelp'"
				:id="id + 'FileDeffer'" :name="id + 'FileDeffer'"
				>
				<label class="hide" :for="id + 'FileDeffer'">{{label}}</label>
				<small :id="id + 'FileDefferHelp'" class="none"></small>
			</div>
			<div class="hide">
				<button type="button"  @click="onClickUploadButton">{{ uploadButtonLabel }}</button>
			</div>
		</div>


		<!-- Input with path to uploaded image file -->
		<input type="hidden" :id="id" :name="id"
			:value="value"
			@input="$emit('input', $event.target.value)"
		>

		<!--input type="number" @input="onTestPercents"-->
		
	</div> <!-- /root -->
	
</template>
<script>
	import './defaultupload.css';
    export default {
		model: {
			prop: 'value',
			event: 'input'
		},
		props: {
			'label' : {type:String},
			'validators' : {type:String},
			'url' : {type:String, required:true},
			'id' : {type:String},
			'value' : {type:String},
			//Если передан, немедленной загрузки файла на сервер при выборе не происходит, а вместо показа инпута выбора файла показывается другой инпут выбора файла, с двумя кнопками "Выбрать" и "Загрузить"
			'immediateleyUploadOff' : {type:String},
			//Для прелоадера по умолчанию необходимо изображение token.png. Через этот атрибут можно указать путь к нему
			'tokenImagePath' : {type:String, default : '/js/inputfileb4/images/token.png'},
			'oken' : {type:String},
			'uploadButtonLabel' : {type:String, default : 'Upload'},
			//Отправляем дополнительно данные перечисленных инпутов
			'sendInputs' : {type:Array, default : () => { return []; }},
			'className' : {type:String}
		},
		name: 'inputfile',
		
        //вызывается раньше чем mounted
        data: function(){return {
            input:null
			
        }; },
        //
        methods:{
			onTestPercents(evt){
				let n = parseInt(evt.target.value);
				this.onProgress(n);
			},
            b4InpOnSelectFile(evt) {
				this.onSelectFile(evt);
				return;
			},
			/**
			 * @description Обработка выбора файла
			*/
			onSelectFile(evt) {
				this.sendFile(evt.target);
			},
			/**
			 * @description Отправка файла
			 * @param {InputFile}
			*/
			sendFile(iFile) {
				let xhr = new XMLHttpRequest(), form = new FormData(), t, that = this, i, s, inp;
				form.append('ajax_file_upload_form[' + iFile.id + ']', iFile.files[0]);
				//form.append("isFormData", 1);
				form.append("path", this.url);
				t = this.oken;
				if (t) {
					console.log('Add token ' + t);
					form.append("ajax_file_upload_form[_token]", t);
				}
			

				if (this.sendInputs && this.sendInputs.length) {
					for (i = 0; i < this.sendInputs.length; i++) {
						s = this.sendInputs[i];
						inp = $('#' + s)[0];
						if (inp && (inp.value || inp.checked)) {
							if (inp.checked) {
								form.append(s, (inp.value ? inp.value : 'true') );
							} else if (inp.type != 'checkbox' && inp.value.trim()){
								form.append(s, inp.value.trim() );
							}
						}
					}
				}
				xhr.upload.addEventListener("progress", (pEvt) => {
					let loadedPercents, loadedBytes, total;
					if (pEvt && pEvt.lengthComputable) {
						total = pEvt.total;
						loadedBytes = pEvt.loaded;
						loadedPercents = Math.round((pEvt.loaded * 100) / pEvt.total);
					}
					this.onProgress(loadedPercents, loadedBytes, total);
				});
				xhr.upload.addEventListener("error", () => {this.onFail(); });
				xhr.onreadystatechange = function () {
					t = this;
					if (t.readyState == 4) {
						if(this.status == 200) {
							var s;
							try {
								s = JSON.parse(t.responseText);
							} catch(e){;}
							that.onSuccess(s);
						} else {
							that.onFail(t.status, arguments);
						}
					}
				};
				xhr.open("POST", this.url);
				this.$emit('startupload');
				xhr.send(form);
			},
			/**
			 * @description Обработка процесса загрузки файлов по умолчанию
			 * @param {Number} nPercents
			*/
			onSuccess(d) {
				this.hideFileprogress();
				if (d && d.status == 'ok') {
					this.$emit('input', d.path);
					this.$emit('uploadcomplete', d.path);
				} else if(d.status == 'error' && d.errors && d.errors.file && String(d.errors.file)){
					this.$emit('uploadapperror', String(d.errors.file));
				}
			},
			onFail() {
				this.$emit('uploadneterror', this.$root.$t('app.DefaultError'));
			},
			/**
			 * @description Обработка процесса загрузки файлов по умолчанию
			 * @param {Number} nPercents
			*/
			onProgress(nPercents, loadedBytes, total) {
				this.$emit('uploadprogress', nPercents, loadedBytes, total);
			},
			/**
			 * @see onProgress
			 * @param {Number} nPercents
			*/
			showFileprogress(a) {
				let h = 'height', m = 'margin-top', l = 'margin-left',
					r = $('#uploadProcessRightSide' + this.id),
					L = $('#uploadProcessLeftSide' + this.id);
					$('#uploadBtn' + this.id).addClass('hide');
				$('#uploadProcessView' + this.id)[0].style.display = null;
				r.css(h, '0px');
				L.css(h, '0px');
				L.css(m, '0px');
				r.css(l, '10px')
				var t = a, bar = a < 50 ? r : L,
					mode = a < 50 ? 1 : 2, v;
				a = a < 50 ? a : a - 50;
				a *= 2;
				v = (a / 5);
				bar.css(h, v + 'px');
				if (mode == 2) {
					bar.css(m, (20 - v) + 'px');
					r.css(h, '20px')
					r.css(l, '0px')
				}
				$('#uploadProcessText' + this.id).text(t);
			},
			/**
			 * @see onProgress
			*/
			hideFileprogress() {
				let b = $('#uploadProcessView' + this.id)[0];
				if (b) {
					b.style.display = 'none';
				}
			},
			/**
			 * @description
			*/
			onClickUploadButton() {
				this.sendFile( $('#' + this.id + 'FileDeffer')[0] );
			}
           
        }, //end methods
        //вызывается после data, поля из data видны "напрямую" как this.fieldName
        mounted() {
			console.log('this.csrfToken', this.oken);
			//let self = this;
			
            /*this.$root.$on('showMenuEvent', function(evt) {
                self.menuBlockVisible   = 'block';
                self.isMainMenuVisible  = true;
                self.isScrollWndVisible = false;
                self.isColorWndVisible  = false;
                self.isHelpWndVisible   = false;
                self.nStep = self.$root.nStep;
            })/**/
            //console.log('I mounted!');
        }
    }
</script>