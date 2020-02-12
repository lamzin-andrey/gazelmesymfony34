<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

//тут тестируются nojs варианты
class ResettingControllerTest extends WebTestCase
{
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
		$this->_oContainer = static::$kernel->getContainer();
        $this->_oEm = $this->_oContainer
            ->get('doctrine')
            ->getManager();
		
	}
	//Нет сообщения при попытке восстановления номера "User with phone not found"
    public function testRestorePasswordNotExistsUser()
    {
		$sPhone = $this->_oContainer->getParameter('test')['phone'];
		//1 Удаляем все объявления и все пользователи с такими номерами
		$this->_deleteTestUser();
		//Открываем страницу  /remind
		$client = static::createClient();
		$crawler = $client->request('GET', '/remind');
		$this->assertEquals(200, $client->getResponse()->getStatusCode());
		//и отправляем форму

		$submitButton = $crawler->selectButton('Отправить');
		$aFormData = [
			'username' => $sPhone,
		];
		/*var_dump($aFormData);
		die;/**/
		$oForm = $submitButton->form($aFormData);
		$crawler = $client->submit($oForm);
		$link = $crawler->filter('a')->first()->attr('href');
		$crawler = $client->request('GET', $link);

		/*$link = $crawler->filter('a')->first()->attr('href');
		$crawler = $client->request('GET', $link);*/

		//Должно быть сообщение Не найден пользователь с таким телефоном
		$n = $crawler->filter('html:contains("Не найден пользователь с таким телефоном")')->count();
		//file_put_contents('/home/andrey/123.html', $crawler->html());
		$this->assertTrue($n == 1);

//---------- OLDS
        /*$client = static::createClient();
        $crawler = $client->request('GET', '/podat_obyavlenie');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
		
		$sPhone = $this->_oContainer->getParameter('test')['phone']; //получение из настроек
		
		$this->_oEm->createQuery('DELETE FROM App:Main AS m WHERE m.phone = :p')->
				setParameters([':p' => $sPhone])->
				execute();

		$this->_oEm->createQuery('DELETE FROM App:Users AS u WHERE u.username = :p')->
			setParameters([':p' => $sPhone])->
			execute();
        //Ожидаем что на странице 6 чекбоксов
        $aCheckboxes = $crawler->filter('input[type=checkbox]');
        $nCheckboxesCount = $crawler->filter('input[type=checkbox]')->count();
        $this->assertEquals(6, $nCheckboxesCount);
        //Ожидаем что все галки сняты
        $aCheckboxes->each(function($oNode, $i){
			$this->assertEquals('', $oNode->attr('checked'));
		});

		//Заполняем и отправляем форму
		//$oCheckbox = $aCheckboxes->first();
        $submitButton = $crawler->selectButton('Подать объявление');
        $aFormData = [
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[company_name]' => 'Rogue and Copue',
			'advert_form[far]' => 1,
			'advert_form[title]' => 'TestTitle',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено
		];
        $oForm = $submitButton->form($aFormData);
        $crawler = $client->submit($oForm);
		
		//Просто проверяем есть ли данные в базе
		
		$oRepository = $this->_oEm->getRepository('App:Main');
		$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);
		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == null);
		$this->assertTrue($oAdvert->getPeople() == 1);
		$this->assertTrue($oAdvert->getFar() == 1);
		$this->assertTrue($oAdvert->getNear() == 0);
		$this->assertTrue($oAdvert->getTitle() == 'TestTitle');
		$this->assertTrue($oAdvert->getAddtext() == 'Test data');
		$this->assertTrue($oAdvert->getPrice() == 250);
		
		$this->_oEm->createQuery('DELETE FROM App:Main AS m WHERE m.phone = :p')->
				setParameters([':p' => $sPhone])->
				execute();
        
        //Нас должно было направить на страницу , содержащую ссылку Фильтр, перейдём по ней
        /*$link = $crawler->selectLink('Фильтр')->link();
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
		});*/
    }

 
	
	private function _deleteAllTestAdverts()
	{
		$sPhone = $this->_oContainer->getParameter('test')['phone'];
		$this->_oEm->createQuery('DELETE FROM App:Main AS m WHERE m.phone = :p')->
				setParameters([':p' => $sPhone])->
				execute();
	}
	
	private function _deleteTestUser()
	{
		$sPhone = $this->_oContainer->getParameter('test')['phone'];
		$sEmail = $this->_oContainer->getParameter('test')['email'];

		$this->_oEm->createQuery('DELETE FROM App:Main AS u '
			. 'WHERE u.phone = :p ')
			->setParameters([
				':p' => $sPhone
			])
			->execute();

		$this->_oEm->createQuery('DELETE FROM App:Users AS u '
			. 'WHERE u.username = :p OR u.emailCanonical = :e')
			->setParameters([
				':p' => $sPhone,
				':e' => $sEmail
				])
			->execute();
	}
}
