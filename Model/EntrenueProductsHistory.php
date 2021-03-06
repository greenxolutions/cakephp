<?php
App::uses('AppModel', 'Model');
/**
 * EntrenueProductsHistory Model
 *
 */
class EntrenueProductsHistory extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'entrenue_products_history';

	public $obj = 'EntrenueProduct';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'id';

	/**
	 * 
	 * @date: 2021-05-23
	 * 
	 * Returns updated & activated EntrenueProduct list
	 * 
	 */
	public function insertRecord($newValue =  array(), $oldValue = array()){

		App::import('Model','EntrenueProduct');

		$entrenueProduct = new EntrenueProduct();

		$data = array();


		foreach ($entrenueProduct->history_fields as $key => $field) {

			if($newValue['EntrenueProduct'][$field] != $oldValue['EntrenueProduct'][$field]){

				$entrenueHistory = array('EntrenueProductsHistory'=>array('field'=>$field, 
				'newvalue'=>$newValue['EntrenueProduct'][$field], 
				'oldvalue'=>$oldValue['EntrenueProduct'][$field],
				'entrenue_product_id'=>intval ($oldValue['EntrenueProduct']['id']),
				'field_type'=>gettype($newValue['EntrenueProduct'][$field])));

				debug($entrenueHistory);

				$this->create();

				$this->save($entrenueHistory);

				if($oldValue['EntrenueProduct']['activated'] == true){

					$newValue['EntrenueProduct']['activated'] = $oldValue['EntrenueProduct']['activated'];


					$data = $newValue;

				}
				


			}
		}

		return count($data)>0?$data:null;
	}

}
