import LandCacheClient from './../landcache/sources/js/land_cache_client'

class CacheSw extends LandCacheClient {
	/**
	 * @description Не кэшируем запросы, заканчивающиеся на '*.jn/'
	 * @override in child
	 * @return Object {data:ArrayOfString, type:'filterlist'}
	*/
	getExcludeFilterList() {
		let o = new Object();
		o.type = 'filterlist';
		o.data = ['*.jn/', '*.jn'];
		return o;
	}
	/**
	 * @description Override it Вот это можно перегрузить в наследнике. 
	*/ 
	showUpdateMessage() {
		//alert('New version this page available!');
	}
	/**
	 * @description Сообщение о том, что все ресурсы закэшированы (вызывается при первом входе на страницу после кэширования, полезно для pwa)
	 * For progressive web
	*/
	showFirstCachingCompleteMessage() {
		//alert('All resources loaded, add us page on main screen and use it offline.');
	}
}
export default CacheSw;