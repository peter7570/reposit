<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Documents extends CI_Controller {

	public function generateResume( $id = NULL ){

		//Проверяем, зарегестрированн ли пользователь и его роль
		if( isset( $this->session->userdata['id'] ) ){
			$user = Doctrine::getTable( 'User' )->findOneBy( 'id', $this->session->userdata['id'] );
			if( $user->getRole() == 'applicant' ){
				echo json_encode( array( 'status' => false, 'msg' => 'Скачивать резюме могут только работодатели и агентства!' ) );
				exit();
			}
		}else{
			echo json_encode( array( 'status' => false, 'msg' => 'Скачивать резюме могут только зарегестрированные пользователи!' ) );
			exit();
		}		
		
		if( empty( $id ) ){ 
			echo json_encode( array( 'status' => false ) );
			exit();
		}
		
		$resume = Doctrine::getTable( 'Resume' )->findOneBy( 'id', $id );
		
		if( empty( $resume ) ) { 
			echo json_encode( array( 'status' => false ) );
			exit();
		}
		
		//Папка для хранения файлов
		$path = str_replace( 'system/', '', BASEPATH ).'downloads/resumes/';
		//Создаем ее, если она была созданна ранее
		if( !file_exists( $path ) ){
			mkdir( $path, 0, true );
		}
		
		//Имя файла с резюме
		$file_name = 'resume_'.$id.'.docx';
		//Если такой файл уже есть, то проверяем дату его создания
		if( file_exists ( $path.$file_name ) ){
			if( $resume->modification_date != NULL AND $resume->modification_date >= date( 'Y-m-d H:i:s', filectime( $path.$file_name ) ) ){				
				echo json_encode( array( 'status' => true, 'url' => site_url().'downloads/resumes/'.$file_name ) );
				exit();
			}else{
				unlink( $path.$file_name );	
			}
		}
		
		$this->load->library( 'PHPWord' );
		
		$word = new PHPWord();
		$section = $word->createSection();
		
		$section->addText( 'Резюме от: '.date( 'Y.m.d', $resume->getPlacementDate() ), array('name'=>'Verdana', 'color'=>'006699') );
		$section->addText( $resume->formatName( 's n p' ), array( 'name'=>'Verdana', 'bold'=>true, 'size'=>20 ) );
		
		$section->addImage( $resume->getRealImagePath(), array( 'align'=>'left') );
		$section->addTextBreak();
		
		$bold = array( 'bold'=>true );
		$grey = array( 'bgColor'=>'dcdcdc', 'valign'=>'center' );
		$center = array( 'valign'=>'center' );
		
		$styleTable = array( 'borderSize'=>4, 'borderColor'=>'dcdcdc' );
		
		$word->addTableStyle( 'myOwnTableStyle', $styleTable );
		
		$table = $section->addTable( 'myOwnTableStyle' );
		
		$data = array(
				'Возраст:' => $resume->getResumeAuthorAge(),
				'Пол:'	   => $resume->gender->getDescription(),
				'Семейное положение:' => $resume->family_state->getValue(),
				'Наличие детей:' => $resume->getChilds(),
				'Город проживания:' => $resume->getCity(),
				'Район проживания:' => $resume->getArea(),
				'Национальность:' => $resume->getNationality(),
				'E-mail:' => $resume->getContactEmail(),
				'Контактный телефон:' => $resume->getContactPhone()
			);
		
		foreach ( $data as $key => $value ){
			$table->addRow();
			$table->addCell(3000, $grey)->addText( $key, $bold, array('spaceAfter' => 0) );
			$table->addCell(6000, $center)->addText( $value, array(), array('spaceAfter' => 0) );
		}
		$section->addTextBreak();
		
		//Желаемая должность
		$section->addText( ( trim(  $resume->getDesiredPosition() ) == '' ) ? 'Точная информация о интересующей должности не указанна' : $resume->getDesiredPosition(), array( 'name'=>'Verdana', 'bold'=>true, 'size'=>16 ) );
		//Дополнительно рассматриваемые должности
		if( count( $resume->getPositions() ) > 0 ){
			$section->addText( 'Также рассматриваются:', array( 'name'=>'Verdana', 'bold'=>true, 'size'=>12 ) );
			foreach ( $resume->getPositions() as $position_id ) {
				$position = Doctrine::getTable( 'Position' )->findOneBy( 'id', $position_id );
				$section->addText( $position->getName() );
			}
		}
					
		$table = $section->addTable( 'myOwnTableStyle' );
		
		$table->addRow();
		$table->addCell(3000, $grey)->addText( "Квалификация:", $bold );
		$cell = $table->addCell( '3000', $center );
		foreach ( $resume->getQualificationForWord() as $item ){
			$cell->addText( $item );
		}
		
		$data = array(
				'Описание опыта работы:' => $resume->getExperienceDescription(),
				'Период опыта работы:' => $resume->experience->getValue(),
				'Наличие рекомендаций:' => $resume->getGuidanceAvailability()
		);
		
		foreach ( $data as $key => $value ){
			$table->addRow();
			$table->addCell(3000, $grey)->addText( $key, $bold, array('spaceAfter' => 0) );
			$table->addCell(6000, $center)->addText( $value, array(), array('spaceAfter' => 0) );
		}	
		$section->addTextBreak();
				
		$section->addText( 'Образование', array('name'=>'Verdana', 'color'=>'006699', 'bold'=>true, 'size'=>16) );		
		
		$table = $section->addTable( 'myOwnTableStyle' );
		$table->addRow();
		$table->addCell(3000, $grey)->addText( "Образование:", $bold, array('spaceAfter' => 0) );
		$table->addCell(6000, $center)->addText( $resume->education->getValue(), array(), array('spaceAfter' => 0) );
		$table->addRow();
		$table->addCell(3000, $grey)->addText( "Специальность:", $bold, array('spaceAfter' => 0) );
		$table->addCell(6000, $center)->addText( $resume->getSpeciality(), array(), array('spaceAfter' => 0) );
		$section->addTextBreak();
		
		$section->addText( 'Пожелания к работе', array('name'=>'Verdana', 'color'=>'006699', 'bold'=>true, 'size'=>16) );
		
		$table = $section->addTable( 'myOwnTableStyle' );
		
		
		$data = array(
				'Регион работы:' => $resume->getWorkRegion()->getValue(),
				'Работа с проживанием в семье:' => ( (int)$resume->getHomestay() == 1 ) ? 'Да' : 'Нет',
				'Вид занятости:' => $resume->getOperatingSchedulesText(),
				'График работы:' => $resume->getWorkTimetable(),
				'Заработная плата:' => $resume->getPaymentDetails()
		);
		foreach ( $data as $key => $value ){
			$table->addRow();
			$table->addCell(3000, $grey)->addText( $key, $bold, array('spaceAfter' => 0) );
			$table->addCell(6000, $center)->addText( $value, array(), array('spaceAfter' => 0) );
		}
		$section->addTextBreak();
	
		$section->addText( 'Предоставляемые услуги', array('name'=>'Verdana', 'color'=>'006699', 'bold'=>true, 'size'=>16) );
		
		foreach ( $resume->getResposibilitysArrayForWord() as $item ){
			$section->addListItem( $item , 0, 'fNormal', array( 'listType' => 7 ), 'pNormal' );
		}
		
		$section->addText( 'Дополнительные данные', array('name'=>'Verdana', 'color'=>'006699', 'bold'=>true, 'size'=>16 ) );
	
		$table = $section->addTable( 'myOwnTableStyle' );
		
		$table->addRow();
		$table->addCell(3000, $grey)->addText( "Владение языками:", $bold, array('spaceAfter' => 0) );
		$cell = $table->addCell( '3000', $center, array('spaceAfter' => 0) );
		
		foreach ( $resume->getLanguageSkillsArrayForWord() as $item ){
			$cell->addText( $item );
		}

		$data = array(
			
				'Наличие загранпаспорта:' => ( (int)$resume->getForeignPassport() == 0 ) ? 'Нет' : 'Есть',
				'Вероисповедание:' => $resume->getFaith(),
				'Водительские права:' => $resume->getDriverLicence(),
				'Наличие собственного авто:' => $resume->getOwnCar(),
				'Наличие медкниги:' => ( (int)$resume->getMedicalBook() == 0 ) ? 'Нет' : 'Есть',
				'Отношение к животным:' => $resume->getAnimalsAttitude(),
				'Вредные привычки:' => $resume->getBadHabits()
				
		);
		foreach ( $data as $key => $value ){
			$table->addRow();
			$table->addCell(3000, $grey)->addText( $key, $bold, array( 'spaceAfter' => 0 ) );
			$table->addCell(6000, $center)->addText( $value, array(), array( 'spaceAfter' => 0 ) );
		}
						
		$objWriter = PHPWord_IOFactory::createWriter( $word, 'Word2007');
		$objWriter->save( $path.$file_name );
		
		echo json_encode( array( 'status' => true, 'url' => site_url().'downloads/resumes/'.$file_name ) );
		
	}

}

	
	
