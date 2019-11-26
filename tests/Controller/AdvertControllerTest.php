<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

//тут тестируются nojs варианты
class AdvertControllerTest extends WebTestCase
{
	 /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
		
		$this->_oContainer = static::$kernel->getContainer();
	}
	//Форма подачи объявлений (десктоп), успешная подача анонимным пользователем без авторизации
	//Использование гуглокаптчи должно быть выключено в настройках
    public function testSuccessAddAnonymousAdvert()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/podat_obyavlenie');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
		
		$sPhone = $this->_oContainer->getParameter('test')['phone']; //TODO сделать получение из настроек
		var_dump($sPhone);
		die;
		$this->em->createQuery('DELETE FROM App:Main AS m WHERE m.phone = :p')->
				setParameters([':p' => $sPhone])->
				execute();
		
        //Ожидаем что на странице 6 чекбоксов
        /*$aCheckboxes = $crawler->filter('input[type=checkbox]');
        $nCheckboxesCount = $crawler->filter('input[type=checkbox]')->count();
        $this->assertEquals(6, $nCheckboxesCount);
        //Ожидаем что все галки сняты
        $aCheckboxes->each(function($oNode, $i){
			$this->assertEquals('', $oNode->attr('checked'));
		});*/
		
		//Заполняем и отправляем форму
		//$oCheckbox = $aCheckboxes->first();
        $submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[far]' => 1,
			'advert_form[title]' => 'TestTitle',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено			
		]);
        $crawler = $client->submit($oForm);
		
		//Просто проверяем есть ли данные в базе
		
		$oRepository = $this->em->getRepository('App:Main');
		$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);//->orderBy();
		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == null);
		$this->assertTrue($oAdvert->getPeople() == 1);
		$this->assertTrue($oAdvert->getFar() == 1);
		$this->assertTrue($oAdvert->getNear() == 0);
		$this->assertTrue($oAdvert->getTitle() == 'TestTitle');
		$this->assertTrue($oAdvert->getAddtext() == 'Test data');
		$this->assertTrue($oAdvert->getPrice() == 250);
		
		$this->em->createQuery('DELETE FROM App:Main AS m WHERE m.phone = :p')->
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
    
   
}
