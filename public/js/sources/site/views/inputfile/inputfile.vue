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
			'csrf_token' : {type:String},
			//Для использования например с Symfony, если передан, то все поля будут отправляться как fieldwrapper[fieldname]
			'fieldwrapper': {type:String, default: ''},
			'uploadButtonLabel' : {type:String, default : 'Upload'},
			//Отправляем дополнительно данные перечисленных инпутов
			'sendInputs' : {type:Array, default : () => { return []; }},
			'token_field_name' : {type:String, default : '_token'},
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

				s = this.wrap(iFile.id);
				form.append(s, iFile.files[0]);
				//form.append("isFormData", 1);
				form.append("path", this.url);
				t = this.csrf_token;
				if (t) {
					//console.log('Add token ' + t);
					s = this.wrap(this.token_field_name);
					form.append(s, t);
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
				} else if(d.status == 'error'){
					this.$emit('uploadapperror', d);
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
			 * @description Заворачивает имя переменной в fieldwrapper если fieldwrapper не пуст
			 * @param {String} fieldName
			 * @return String
			*/
			wrap(s) {
				if (this.fieldwrapper) {
					return this.fieldwrapper + '[' + s + ']';
				}
				return s;
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
			console.log('this.csrfToken', this.csrf_token);
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