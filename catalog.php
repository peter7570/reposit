<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Catalog extends CI_Controller {
	
	public $configuration = array(
			'last_link' => '',
			'first_link' => '',
			'next_link' => '',
			'prev_link' => '',
			'full_tag_open' => '<div class="pages_word2">Страницы:</div><ul class="simplePagerNav">',
			'full_tag_close' => '</ul>',
			'cur_tag_open' => '<li class="currentPage">',
			'cur_tag_close' => '</li>',
			'num_tag_open' => '<li>',
			'num_tag_close' => '</li>',
			'per_page' => '30'
	);
	
	public function __construct(){
		parent::__construct();
		$this->load->library('pagination');
	}
	
	/**
	* Обладает ли текущие пользователь правами администратора
	* @return boolean
	*/
	private function getIsAsdmin(){
		if( isset( $this->session->userdata['id'] ) ) {
			$user = Doctrine::getTable( 'User' )->findOneBy( 'id', (int)$this->session->userdata['id'] );
			if( $user->getIsAdmin() ){
				return true;
			}
		}
		return false;                                                                                                                                
	}
	
	/**
	* Проверка на наличие у пользователя админских прав
	*/
	private function checkPermissions(){
		if( isset( $this->session->userdata['id'] )){
			$user = Doctrine::getTable('User')->findOneBy( 'id', $this->session->userdata['id']);
			if( $user->getIsAdmin() ) return true;
		}else{
			redirect( 'pages/index' );
		}
	}
	
	/**
	* Получение HTML-кода блока последних комментариев
	*/
	private function getLastCommentsBlock(){
		$agency_reviews = Doctrine_Query::Create()
				->from( 'Agency_Review ar'  )
				->leftJoin( 'ar.agency a' )
				->leftJoin( 'a.user u' )
				->leftJoin( 'u.paid_feature p' )
				->where( 'p.show_in_catalog_date > ?', strtotime( 'now' ) )
				->orWhere( 'a.manually_in_catalog = ?', '1' )
				->orderBy('creation_time DESC')
				->limit( 3 )
				->execute()
			;
		return $this->load->view( 'factory/catalog/last_comments_block', array( 'agency_reviews' => $agency_reviews ), true );
	}
	
	public function show( $offset = 0 ){
		$config['base_url'] = site_url( 'catalog/show' );
		$config['total_rows'] = Doctrine_Query::Create()
									->from( 'Agency a' )
									->leftJoin( 'a.user u' )
									->leftJoin( 'u.paid_feature p' )
									->where( 'p.show_in_catalog_date > ?', strtotime( 'now' ) )
									->orWhere( 'a.manually_in_catalog = ?', '1' )
									->count()
								;
		$this->pagination->initialize( array_merge( $this->configuration, $config ) );
		$agencys = Doctrine_Query::Create()
						->from( 'Agency a' )
						->leftJoin( 'a.user u' )
						->leftJoin( 'u.paid_feature p' )
						->where( 'p.show_in_catalog_date > ?', strtotime( 'now' ) )
						->orWhere( 'a.manually_in_catalog = ?', '1' )
						->orderBy( 'super_agency DESC, a.rating DESC' )
						->offset( $offset )
						->limit( $this->configuration['per_page'] )
						->execute()
					;
				
		if( count( $agencys ) > 0 ){
		
			$results = $this->createAgencysHtml( $agencys );
		
		}else{
		
			$results = 'Нет данных об агенствах!';
		
		}
		
		$data = array( 
						'selected_items' => array(), 
						'results' => $results,
						'last_comments' => $this->getLastCommentsBlock(),
						'pagination' => $this->pagination->create_links()
					);
					
		$this->template->view( 'catalog/main', $data );
	
	}
	
	public function filterSearch(){
		
		//Переменные, полученные со страницы запроса
		$agency_name = $this->input->post( 'name', TRUE );
		
		$user_city = $this->input->post( 'city', TRUE );
		
		$sort_by_rate =  $this->input->post( 'rating', TRUE ) ? TRUE : FALSE;
		
		//Формируем строку запроса к базе данных на основе полученных значений фильтров
		$querys = array();
		
		//Если в запросе есть название агенства
		if( !empty( $agency_name ) ) $querys[] = 'a.name LIKE \'%'.$agency_name.'%\'';
		
		//Если явно указан город
		if( $user_city != '0' )	$querys[] = 'a.city_id = '.$user_city;
		
		//Сортировка по рейтингу
		$order = $sort_by_rate ? 'a.rating DESC' : 'a.id ASC';
		
		$query = ( count( $querys ) != 0 ) ? implode( ' AND ', $querys ) : '1';
		
		$agencys = Doctrine_Query::Create()
								->from( 'Agency a' )
								->leftJoin( 'a.user u' )
								->leftJoin( 'u.paid_feature p' )
								->where( 'p.show_in_catalog_date > ?', strtotime( 'now' ) )
								->orWhere( 'a.manually_in_catalog = ?', '1' )
								->andWhere( $query )
								->orderBy( $order )
								->execute()
							;
		
		if( count( $agencys ) > 0 ){
		
			$results = $this->createAgencysHtml( $agencys );
		
		}else{
		
			$results = 'Поиск не дал результатов!';
		
		}
		
		$selected_items = array(
								'name' => $agency_name,
								'city' => $user_city,
								'rating' => $sort_by_rate
							);
		
		$data = array(
						'selected_items' => $selected_items,
						'results' => $results,
						'last_comments' => $this->getLastCommentsBlock(),
						'pagination' => ''
					);
					
		$this->template->view( 'catalog/main', $data );
		
	}
	
	public function createAgencysHtml( $objects ){
		$html = array();
		foreach( $objects as $object ){
			$html[] = '<li>'.$this->returnOneAgencyTable( $object ).'<div class="delimiter"></div></li>';
		}
		$last = str_replace( '<div class="delimiter"></div>', '', array_pop( $html ) );
		$html[] = $last;
		return implode( '', $html );
	}
	
	public function returnOneAgencyTable( $agency ){
		$out = '<table class="agency_preview">';
			$out .= '<tr>';
				$out .= '<td class="photo">';
					$out .= '<div class="image_holder"><a href="'.site_url( 'agency/'.$agency->getId() ).'"><img src="'.$agency->getLogoUrl().'" class="company_logo"/></a></div>';
				$out .= '</td>';
				$out .= '<td class="text">';
					$out .= '<table>';
						$out .= '<tr><td class="agency_name_holder"><a href="'.site_url( 'agency/'.$agency->getId() ).'">'.$agency->getName().'</a><td></tr>';
                                                // значок "рекомендованное"
                                                if($agency->super_agency)
                                                    $out .= '<tr><td class="recommending"><img src="/img/template/views/catalog/recomend.png" alt="Агентство рекомендовано сайтом Homey.Pro, согласно отзывам клиентов и пользователей" class="recomend" title="Агентство рекомендовано сайтом Homey.Pro, согласно отзывам клиентов и пользователей"/><td></tr>';
                                                if($agency->checked_agency)
                                                    $out .= '<tr><td class="checkedagency" title="Данные об агентстве проверены и являются подлинными"><img class="confirmed" src="/img/template/views/catalog/confirmed.png" alt="Данные об агентстве проверены и являются подлинными" title="Данные об агентстве проверены и являются подлинными"/><td></tr>';
						$out .= '<tr><td class="city_holder">'.$agency->getCityName().'<td></tr>';
						$out .= '<tr><td class="metro_holder">'.$agency->getMetroStationName().'</td></tr>';
						$out .= '<tr><td class="adress_holder">'.$agency->getAdress().'<td></tr>';
						$out .= '<tr><td class="phone_holder">Телефон:&nbsp;'.$agency->getAgencyPhone().'<td></tr>';
						$out .= '<tr><td class="site_holder">'.$agency->getSite().'<td></tr>';
					$out .= '</table>';
					$out .= '<div>';
					$out .= '<table><tr>';
					$out .= '<td class="top"><div class="rating_text">Рейтинг:</div></td>';
					$out .= '<td class="top">'.$agency->getRatingBlock().'</td>';
					$out .= '<td class="top"><div class="votes_text"><a href="'.site_url( 'agency/'.$agency->getId() ).'" title="Перейти к просмотру всех отзывов">Отзывов: '.$agency->returnReviewsCount().'</a></div></td>';
					$out .= '</tr></table>';
					$out .= '</div>';
				$out .= '</td>';
			$out .= '</tr>';
		$out .= '</table>';
		return $out;
	}
	
	/**
	* Отображение персональной страницы одного агенства 
	*/
	public function showOneAgency( $id = NULL ){
		//Проверка существования агентства
		if( empty( $id ) ) return false;
		$agency = Doctrine::getTable( 'Agency' )->findOneBy( 'id', $id );
		if( empty( $agency ) ) return false;
		$comments = '';
		if( $agency->returnReviewsCount() == 0 ){
			$comments = '<p class="no-comments">Отзывов пока что нет!</p>';
		}
		foreach( $agency->getAgencyReviews() as $comment ){
			$comments .= $this->load->view( 'factory/catalog/comment_on_agency_page', array( 'comment' => $comment, 'is_admin' => $this->getIsAsdmin() ), true );
		} 
		$data = array( 
			'agency' => $agency, 
			'reviews' => $comments, 
			'user_logged' => isset( $this->session->userdata['id'] ),
                        'user' => (isset( $this->session->userdata['id'] ))?Doctrine::getTable('User')->findOneBy( 'id', $this->session->userdata['id']):''
		);
		$this->template->view( 'catalog/one', $data );		
	}
	
	/**
	* Удаление комментария  к агентству
	*/
	public function deleteComment( $comment_id = NULL ){
		//Проверка прав
		$this->checkPermissions();
		//Проверка существования объекта
		$comment = Doctrine::getTable( 'Agency_Review' )->findOneBy( 'id' , $comment_id );
		if( empty( $comment ) ){
			echo json_encode( array( 'status' => false, 'msg' => 'Не удалось загрузить объект по указанному ID' ) );
			exit();
		}
		//Удаление объекта комментария
		$comment->delete();
		echo json_encode( array( 'status' => true ) );
	}
	
}
