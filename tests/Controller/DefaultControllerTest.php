<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

//тут тестируются nojs варианты
class DefaultControllerTest extends WebTestCase
{
	//Форма установки фильтра типа автомобиля (Грузовая, Пассажирская и т п)
    public function testFormFilter()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/showfilter');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        //Ожидаем что на странице 6 чекбоксов
        $aCheckboxes = $crawler->filter('input[type=checkbox]');
        $nCheckboxesCount = $crawler->filter('input[type=checkbox]')->count();
        $this->assertEquals(6, $nCheckboxesCount);
        //Ожидаем что все галки сняты
        $aCheckboxes->each(function($oNode, $i){
			$this->assertEquals('', $oNode->attr('checked'));
		});
		//Кликаем на галку near, Отправляем форму
		$oCheckbox = $aCheckboxes->first();
        $submitButton = $crawler->selectButton('Найти');
        $oForm = $submitButton->form(['near' => 1]);
        $crawler = $client->submit($oForm);
        
        //Нас должно было направить на страницу , содержащую ссылку Фильтр, перейдём по ней
        $link = $crawler->selectLink('Фильтр')->link();
        $crawler = $client->click($link);
        
        //Ожидаем что галка near установлена
        $oCheckbox = $crawler->filter('input[id=near]')->first();
        $sValue = $oCheckbox->attr('checked');
        $this->assertEquals('"checked"', $sValue);
        
        //Ожидаем что все галки, кроме near сняты, а near установлена
        $aCheckboxes = $crawler->filter('input[type=checkbox]');
        $aCheckboxes->each(function($oNode, $i){
			if ($oNode->attr('id') != 'near') {
				$this->assertEquals('', $oNode->attr('checked'));
			} else {
				$this->assertEquals('"checked"', $oNode->attr('checked'));
			}
		});
    }
    
    //Установка региона
    public function testSetLocation()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        //Убеждаемся, что на странице есть фраза Населенный пункт: Не выбран
        $n = $crawler->filter('html:contains("Населенный пункт:")')->count(); // Населенный пункт
        $oNode = $crawler->filter('span:contains("Не выбран")')->first();//Не выбран
        $this->assertEquals('Не выбран', $oNode->text());
        
        
        //Перейдём по ссылке "Изменить"
        $link = $crawler->selectLink('Изменить')->link();
        $crawler = $client->click($link);
        
        //Перейдём по ссылке Москва
        $link = $crawler->selectLink('Москва')->link();
        $crawler = $client->click($link);
        
        //Убеждаемся, что на странице есть фраза Населенный пункт: Москва
        $n = $crawler->filter('html:contains("Населенный пункт:")')->count(); // Населенный пункт
        $oNode = $crawler->filter('#hDisplayLocation')->first();//Москва выбран
        $this->assertEquals(1, $n);
        $this->assertEquals('Москва', $oNode->text());
    }
}
