<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
* Подключаем библиотеку хеширования (она также используеться библиотекой авторизации).
* Выбрана вместо стандартного хеширования по алгоритму MD5 ввиду более высокой стойкости к коллизиям	
*/
require_once(APPPATH.'libraries/phpass-0.1/PasswordHash.php');
/*
* Константы для работы PasswordHash.php определены в файле библиотеки авторизации
*/

class User extends Doctrine_Record {
    
    public function setTableDefinition() {
        
		$this->setTableName( 'users' );
                
        $this->hasColumn( 'id', 'integer', array( 'length' => 10 ) );
		
        $this->hasColumn( 'name', 'string', array( 'length' => 255, 'default' => NULL) );
        $this->hasColumn( 'surname', 'string', array( 'length' => 255, 'default' => NULL) );
        $this->hasColumn( 'patronym', 'string', array( 'length' => 255, 'default' => NULL) );
        
		$this->hasColumn( 'city', 'integer', array( 'length' => 10 ) );
		$this->hasColumn( 'country', 'integer', array( 'length' => 10 ) );
		
		$this->hasColumn( 'user_email', 'string', array( 'length' => 255, 'default' => NULL) );
		$this->hasColumn( 'user_pass', 'string', array( 'length' => 512) );
		
		$this->hasColumn( 'role', 'integer', array( 'length' => 5 ) );
		$this->hasColumn( 'howknow', 'integer', array( 'length' => 10, 'default' => NULL) );
		
		//-------------- Дополнительные поля для библиотеки авторизации -------------//
		$this->hasColumn( 'user_date', 'string', array( 'length' => 255) );
		$this->hasColumn( 'user_modified', 'string', array( 'length' => 255) );
		$this->hasColumn( 'user_last_login', 'string', array( 'length' => 255) );
		$this->hasColumn( 'user_last_ip', 'string', array( 'length' => 255) );
		//---------------------------------------------------------------------------//
		
		$this->hasColumn( 'activation_code', 'string', array( 'length' => 255 ) );
		
		$this->hasColumn( 'change_pass_code', 'string', array( 'length' => 255 ) );
		
		$this->hasColumn( 'is_admin', 'integer', array( 'length' => 1, 'default' => 0 ) );
		
		$this->hasColumn( 'is_blocked', 'integer', array( 'length' => 1, 'default' => 0 ) );
                
                /*
                 * Разработка Андрей Матюшенко
                 */                
                $this->hasColumn( 'get_news', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_resumes', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_vacancys', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_reviews', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_employers_in_bl', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_applicants_in_bl', 'integer', array( 'length' => 1, 'default' => 1 ) );
                $this->hasColumn( 'get_news_resumes_to_vacancys', 'integer', array( 'length' => 1, 'default' => 1 ) );
                /*
                 * andrej_matushenko@i.ua
                 */
    }
	
	public function setUp() {
	
		$this->hasOne( 'WorkRegion as city_id', array( 'local' => 'city', 'foreign' => 'id' ) );
		
		$this->hasOne( 'User_Country as country_id', array( 'local' => 'country', 'foreign' => 'id' ) );
		
		$this->hasOne( 'User_Role as role_id', array( 'local' => 'role', 'foreign' => 'id' ) );
		
		$this->hasOne( 'User_Howknow as howknow_id', array( 'local' => 'howknow', 'foreign' => 'id' ) );
		
		$this->hasOne( 'User_Mail_Options as user_mail_options', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasOne( 'Account as account', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasOne( 'Free_Feature as free_feature', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasOne( 'Paid_Feature as paid_feature', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'User_Phone as phones', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'Vacancy as vacancys', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'Resume as resumes', array( 'local' => 'id', 'foreign' => 'user_id' ) );
                
                $this->hasMany( 'Agency as agency', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'Agency_Rating as agency_rating', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'Course_Review as course_reviews', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'User_Like_Resume as user_like_resumes', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'User_Like_Vacancy as user_like_vacancys', array( 'local' => 'id', 'foreign' => 'user_id' ) );
		
		$this->hasMany( 'User_Send_Resume as user_send_resumes', array( 'local' => 'id', 'foreign' => 'recipient_user_id' ) );
		
	}
	
	public function getId(){
		return $this->id;
	}
	
	// - Необходимость этой функции спорная...
	public function setId($id){
		$this->id = $id;
		return $this;
	}
	// ----------------------------------- //
	
	public function setName($name){
		$this->name = $name;
		return $this;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function setSurname($surname){
		$this->surname = $surname;
		return $this;
	}
	
	public function getSurname(){
		return $this->surname;
	}
	
	public function setPatronym($patronym){
		$this->patronym = $patronym;
		return $this;
	}
	
	public function getPatronym(){
		return $this->patronym;
	}
	
	// - Задавать необходимо ID города из соответствующей таблицы
	public function setCity($city){
		$this->city = $city;
		return $this;
	}
	
	// - Для города возвращаем сразу название
	public function getCityName(){
		return $this->city_id->getValue();
	}
	
	// - Задавать необходимо ID страны из соответствующей таблицы
	public function setCountry($country){
		$this->country = $country;
		return $this;
	}
	
	// - Для страны возвращаем сразу название
	public function getCountryName(){
		return $this->country_id->getName();
	}
		
	public function setEmail($user_email){
		$this->user_email = $user_email;
		return $this;
	}
	
	public function getEmail(){
		return $this->user_email;
	}
	
	/**
	* Методы для работы с полем user_pass не описываються, так как работа с его содержимым	
	* идет через библиотеку авторизации	
	*/
	
	public function getPhones(){
		return $this->phones;
	}
	
	// - Задавать необходимо ID роли из соответствующей таблицы
	public function setRole($role){
		$this->role = $role;
		return $this;
	}
	
	// - Для "роли" пользователя возвращаем значение поля "name" (удобно использовать в редиректах)
	public function getRole(){
		return $this->role_id->getName();
	}
	
	// - Задавать необходимо ID варианта из соответствующей таблицы
	public function setHowknow($howknow){
		$this->howknow = $howknow;
		return $this;
	}
	
	// - Возвращаем сразу текстовое значение
	public function getHowknow(){
		$this->howknow_id->getValue();
	}

	/**
	* Для дополнительных полей библиотеки авторизации
	* используются только методы получающие значения полей.
	* Запись значений идет через саму библиотеку.
	*/
 
	public function getUserDate(){
		return $this->user_date;
	}
	
	public function getUserModified(){
		return $this->user_modified;
	}
	
	public function getUserLastLogin(){
		return $this->user_last_login;
	}
	
	public function getActivationCode(){
		return $this->activation_code;
	}
	
	public function setActivationCode( $activation_code ){
		$this->activation_code = $activation_code;
		return $this;
	}
	
	public function getIsAdmin(){
		return ($this->is_admin == 1||$this->is_admin == 2) ? true : false;
	}
	
	public function getIsBlocked(){
		return $this->is_blocked == 1 ? true : false;
	}
	
	public function getVacancys(){
		return $this->vacancys;
	}
	
	public function getResumes(){
		return $this->resumes;
	}
	
	/**
	* Дополнительные функции модели пользователя
	*/
	
	
	// - Получение дополнительной модели в зависимости от роли пользователя
	public function getAdditionalModel(){
		$role = $this->role_id->getName();
		$model = Doctrine::getTable( ucfirst( $role ) )->findOneBy('user_id', $this->id);
		return $model;
	}
	
	// - Фамилия/Имя/Отчество в заданном формате	
	public function formatName( $format ) {
        $replacement = array(
            's' => $this->surname,
            's*' => '*******',             
            'n' => $this->name,
            'p' => $this->patronym,
            'S' => mb_substr( $this->surname, 0, 1 ),
            'N' => mb_substr( $this->name, 0, 1 ),
            'P' => mb_substr( $this->patronym, 0, 1 ),
        );
        return strtr( $format, $replacement );
    }
	
	/**
	* Смена пароля пользователя
	*/
	public function changeAuthPassword( $new_password ){
		$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
		$this->user_pass = $hasher->HashPassword( $new_password );
		return $this;
	}
	
	/**
	* Проверка, есть ли у пользователя оплаченные услуги 
	*/
	public function hasPaidFeatures(){
		$count = Doctrine_Query::create()
			->from('Paid_Feature p')
			->where( 'p.user_id = ?', $this->id )
			->count();
		return ( $count == 0 ) ? false : true;
	}
    
   public function getAmountVacancies(){
        $res = Doctrine_Query::create()
            ->from('Paid_Feature p')
            ->where( 'p.user_id = ?', $this->id )
            ->execute();
        foreach ($res as $value) {
            return $value->amount_vacancies;
        }   
        return false;
    }
    
    public function getAmountContacts(){
        $res = Doctrine_Query::create()
            ->from('Paid_Feature p')
            ->where( 'p.user_id = ?', $this->id )
            ->execute();
        foreach ($res as $value) {
            return $value->amount_contacts;
        }   
        return false;
    }
	
	/**
	* Создание счета и списка бесплатных услуг для нового пользователя 
	*/
	public function createDefaultAccountAndFeatures(){
		$free_feature = new Free_Feature();
		$free_feature->Create( $this->id );
		$account = new Account();
		$account->Create( $this->id );
		$this->save();
		return $this;
	}	
	
	/**
	* Возвращает объекты услуг, доступные пользователю с данной ролью 
	*/
	public function getFeatures(){
		$features = Doctrine_Query::create()
				->from( 'Feature f' )
				->where( "f.alias LIKE '%".$this->getRole()."'" )
				->andWhere( 'f.active = ?', 1 )
				->orderBy( 'f.order ASC' )
				->execute()
			;
		return $features;
	}
	
	/**
	* Проверка, вносил ли пользователь что либо в черные списки 
	*/
	public function isBlacklistEditor(){
		//Работники
		$workers = (int)Doctrine_Query::create()
						->from( 'Blacklist_Worker bw' )
						->where( 'bw.user_id = ?', $this->id )
						->count()
					;
		//Работодатели
		$employers = (int)Doctrine_Query::create()
						->from( 'Blacklist_Employer be' )
						->where( 'be.user_id = ?', $this->id )
						->count()
					;
		return ( $workers > 0 or $employers > 0 );
	} 

	/**
	* Получаем массив работников из черного списка, созданных данным пользователем 
	*/
	public function getBlackListWorkers(){
		$workers = Doctrine_Query::create()
						->from( 'Blacklist_Worker bw' )
						->where( 'bw.user_id = ?', $this->id )
						->orderBy( 'bw.creation_date DESC' )
						->execute()
					;
		return $workers;
	}
	
	/**
	* Получаем массив работодателей из черного списка, созданных данным пользователем  
	*/
	public function getBlackListEmployers(){
		$employers = Doctrine_Query::create()
						->from( 'Blacklist_Employer be' )
						->where( 'be.user_id = ?', $this->id )
						->orderBy( 'be.creation_date DESC' )
						->execute()
					;
		return $employers;
		
	}
	
	/**
	* Есть ли у пользователя созданные резюме
	* @access public
	* @return boolean 
	*/
	public function hasExistResumes(){
		//Если не соискатель
		if( $this->getRole() != 'applicant' ){
			return false;
		}
		//Если есть созданные резюме
		if( count( $this->resumes ) > 0 ){
			return true;
		}
		return false;
	}
	
}