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
		$this->_oContainer = static::$kernel->getContainer();
        $this->_oEm = $this->_oContainer
            ->get('doctrine')
            ->getManager();
		
	}
	//Форма подачи объявлений (десктоп), успешная подача анонимным пользователем без авторизации
	//Использование гуглокаптчи должно быть выключено в настройках
    public function testSuccessAddAnonymousAdvert()
    {
        $client = static::createClient();
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
        $aFormData = [
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[company_name]' => 'Rogue and Copue',
			'advert_form[far]' => 1,
			'advert_form[title]' => 'TestTitle',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[agreement]' => '1',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено
		];

        $oForm = $submitButton->form($aFormData);
        $crawler = $client->submit($oForm);
		
		//Просто проверяем есть ли данные в базе
		
		$oRepository = $this->_oEm->getRepository('App:Main');
		$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);

		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == $this->_oContainer->getParameter('app.city_zero_id') );
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
    
	
	//Форма подачи объявлений (десктоп), подача анонимным пользователем с успешной и неуспешной авторизацией
	//Использование гуглокаптчи должно быть выключено в настройках
    public function testSuccessAddAnonymousWithPasswordAndWrongPassword()
    {
		$client = static::createClient();
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$this->assertEquals($client->getResponse()->getStatusCode(), 200);
		$sPhone = $this->_oContainer->getParameter('test')['phone'];
		$sPassword = $this->_oContainer->getParameter('test')['password'];
		$sEmail = $this->_oContainer->getParameter('test')['email'];
		
		//Удалить всех с известным номером (пользователей и объявления)
		$this->_deleteAllTestAdverts();

		$this->_deleteTestUser();

		//Подать объявление новым пользователем без email и пароля
		$submitButton = $crawler->selectButton('Подать объявление');
		$oForm = $submitButton->form([
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[far]' => 1,
			'advert_form[agreement]' => '1',
			'advert_form[title]' => 'TestTitle',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено
		]);
		$crawler = $client->submit($oForm);
		//убедиться, что user_id правильный и is_anonymous = 1
		$oUserRepository = $this->_oEm->getRepository('App:Users');
		$qB = $oUserRepository->createQueryBuilder('m');
		$oUser = $qB->where( $qB->expr()->eq('m.username', $sPhone) )->
			getQuery()->
			setCacheable(false)->
			useResultCache(false)->
			useQueryCache(false)->
			execute();

		$oMainRepository = $this->_oEm->getRepository('App:Main');
		$qB = $oMainRepository->createQueryBuilder('m');
		$oAdvert = $qB->where( $qB->expr()->eq('m.phone', $sPhone) )->
			getQuery()->
			setCacheable(false)->
			useResultCache(false)->
			useQueryCache(false)->
			execute();

		$this->assertTrue( $oAdvert[0]->getUserId() == $oUser[0]->getId() );
		$this->assertTrue( $oUser[0]->getIsAnonymous() === true );
		$this->assertTrue( $oUser[0]->getPhone() === $sPhone );

		//Подать объявление новым пользователем с email и паролем
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$submitButton = $crawler->selectButton('Подать объявление');
		$oForm = $submitButton->form([
			'advert_form[email]' => $sEmail,
			'advert_form[password]' => $sPassword,
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[far]' => 1,
			'advert_form[title]' => 'TestTitle',
			'advert_form[company_name]' => 'RIg',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено
		]);
		$crawler = $client->submit($oForm);
		$s = $crawler->filter('input[type=text]')->first()->attr('value');

		//TODO убедиться, что user_id тот же самый и is_anonymous = 0
		/*** @var \Doctrine\ORM\EntityRepository $oUserRepository */
		$oUserRepository = $this->_oEm->getRepository('App:Users');
		$oUserRepository->clear();
		$qB = $oUserRepository->createQueryBuilder('k');
		$oUser = $qB->where( $qB->expr()->eq('k.username', $sPhone) )->
			getQuery()->
			setCacheable(false)->
			useQueryCache(false)->
			getResult();

		$oMainRepository = $this->_oEm->getRepository('App:Main');
		$oMainRepository->clear();
		$qB = $oMainRepository->createQueryBuilder('m');
		$oAdvert = $qB->where( $qB->expr()->eq('m.phone', $sPhone) )->
			orderBy('m.id', 'DESC')->
			getQuery()->
			setCacheable(false)->
			useResultCache(false)->
			useQueryCache(false)->
			execute();


		$this->assertTrue( $oAdvert[0]->getUserId() == $oUser[0]->getId() );
		$this->assertTrue( $oUser[0]->getIsAnonymous() === false );
		$this->assertTrue( $oUser[0]->getPhone() === $sPhone );

		$this->_deleteAllTestAdverts();
		$this->_deleteTestUser();

		//Подать объявление с новым пользователем с e/p данными (скорее всего должно быть is_deleted true, проверить на продакшене)
		$submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[email]' => $sEmail,
			'advert_form[password]' => $sPassword,
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
		
		//убедиться, что создалась запись и в users и в main
		//Просто проверяем есть ли данные в базе
		
		//main
		$oRepository = $this->_oEm->getRepository('App:Main');
		$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);//->orderBy();
		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == $this->_oContainer->getParameter('app.city_zero_id') );
		$this->assertTrue($oAdvert->getPeople() == 1);
		$this->assertTrue($oAdvert->getFar() == 1);
		$this->assertTrue($oAdvert->getNear() == 0);
		$this->assertTrue($oAdvert->getTitle() == 'TestTitle');
		$this->assertTrue($oAdvert->getAddtext() == 'Test data');
		$this->assertTrue($oAdvert->getPrice() == 250);
		$nLastAdvertId = $oAdvert->getId();
		
		//users
		$oRepository = $this->_oEm->getRepository('App:Users');
		$oUser = $oRepository->findOneBy(['username' => $sPhone], ['id' => 'DESC']);
		$this->assertTrue($oUser !== null);
		$this->assertTrue($oUser->getEmailCanonical() == $sEmail);
		$this->assertTrue($oUser->getEmail() == $sEmail);
		
		
		//Подать объявление с известной учёткой и неверным паролем
		$sWrongPassword = $this->_oContainer->getParameter('test')['wrong_password'];
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[email]' => $sEmail,
			'advert_form[password]' => $sWrongPassword,
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
		//Найти на странице надпись "Для этого номера телефона уже задан пароль отличный от того, что вы ввели."
		$n = $crawler->filter('html:contains("Для этого номера телефона уже задан пароль отличный от того, что вы ввели.")')->count(); // Населенный пункт
		$this->assertTrue($n == 1);
		
		//Подать объявление с известной учёткой и верным паролем
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[email]' => $sEmail,
			'advert_form[password]' => $sPassword,
			'advert_form[region]' => 1,
			'advert_form[city]' => 0,
			'advert_form[people]' => 1,
			'advert_form[company_name]' => 'Roguie',
			'advert_form[far]' => 1,
			'advert_form[title]' => 'TestTitle',
			'advert_form[phone]' => $sPhone,
			'advert_form[addtext]' => 'Test data',
			'advert_form[price]' => 250 //это необязательное поле и оно должно быть равно 1 если не заполнено			
		]);
        $crawler = $client->submit($oForm);
		//Убедиться, что в базе создалось объявление и его номер более чем у предыдущего поданного в этом тесте
		//main
		/** var \Doctrine\ORM\EntityRepository $oRepository */
		/** var \Doctrine\ORM\EntityManager $oRepository */
		$oRepository = $this->_oEm->getRepository('App:Main');
		
		//$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);
		$qb = $oRepository->createQueryBuilder('m');
		$adverts = $qb->where( $qb->expr()->eq('m.phone', $sPhone) )->orderBy('m.id', 'DESC')
				->getQuery()
				->setCacheable(false)
				->useResultCache(false)
				->execute();
		$oAdvert = ($adverts[0] ?? null);
		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == $this->_oContainer->getParameter('app.city_zero_id'));
		$this->assertTrue($oAdvert->getPeople() == 1);
		$this->assertTrue($oAdvert->getFar() == 1);
		$this->assertTrue($oAdvert->getNear() == 0);
		$this->assertTrue($oAdvert->getTitle() == 'TestTitle');
		$this->assertTrue($oAdvert->getAddtext() == 'Test data');
		$this->assertTrue($oAdvert->getPrice() == 250);
		$this->assertTrue($oAdvert->getId() > $nLastAdvertId);
		$nLastAdvertId = $oAdvert->getId();
		
		//С существующими в базе tel+p и несоответствующим ему email
		//Должно подаваться без проблем
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[email]' => $sEmail . 'm',
			'advert_form[password]' => $sPassword,
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
		//Убедиться, что в базе создалось объявление и его номер более чем у предыдущего поданного в этом тесте
		//main
		$oRepository = $this->_oEm->getRepository('App:Main');
		//$oAdvert = $oRepository->findOneBy(['phone' => $sPhone], ['id' => 'DESC']);
		$qb = $oRepository->createQueryBuilder('m');
		$adverts = $qb->where( $qb->expr()->eq('m.phone', $sPhone) )->orderBy('m.id', 'DESC')
				->getQuery()
				->setCacheable(false)
				->execute();
		$oAdvert = ($adverts[0] ?? null);
		$this->assertTrue($oAdvert !== null);
		$this->assertTrue($oAdvert->getRegion() == 1);
		$this->assertTrue($oAdvert->getCity() == $this->_oContainer->getParameter('app.city_zero_id') );
		$this->assertTrue($oAdvert->getPeople() == 1);
		$this->assertTrue($oAdvert->getFar() == 1);
		$this->assertTrue($oAdvert->getNear() == 0);
		$this->assertTrue($oAdvert->getTitle() == 'TestTitle');
		$this->assertTrue($oAdvert->getAddtext() == 'Test data');
		$this->assertTrue($oAdvert->getPrice() == 250);
		$this->assertTrue($oAdvert->getId() > $nLastAdvertId);
		$nLastAdvertId = $oAdvert->getId();
		
		//Если введён email, должен быть и пароль введён
		$crawler = $client->request('GET', '/podat_obyavlenie');
		$submitButton = $crawler->selectButton('Подать объявление');
        $oForm = $submitButton->form([
			'advert_form[email]' => $sEmail . 'm',
			'advert_form[password]' => '',
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
		
		$n = $crawler->filter('html:contains("Необходимо указать пароль или удалить email")')->count();
		$this->assertTrue($n == 1);
		
		//Удалить всех
		$this->_deleteAllTestAdverts();
		$this->_deleteTestUser();
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
		$this->_oEm->createQuery('DELETE FROM App:Users AS u '
			. 'WHERE u.username = :p OR u.emailCanonical = :e')
			->setParameters([
				':p' => $sPhone,
				':e' => $sEmail
				])
			->execute();
	}
}
