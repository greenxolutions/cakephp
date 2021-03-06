<?php
App::uses('AppModel', 'Model');
/**
 * MwsInventory Model
 *
 */
class MwsInventory extends AppModel {

	/**
	 * Use table
	 *
	 * @var mixed False or table name
	 */
	public $useTable = 'mws_inventory';
	
	public $name = 'MwsInventory';

	public $history_fields = array('item_offer');

	public $_MWS_FEE_PERCENT_NOMINAL = 0.25;

	public $_SLEEP_TIME = 5;

	public $_ENTRENUE_FEE = 2;

	public $_ENTRENUE_FLET = 4;

	public $belongsTo = array(
			'Tier' => array(
					'className' => 'Tier',
					'foreignKey' => 'tier_id'
			),
			'EntrenueProduct' => array(
				'className' => 'EntrenueProduct',
				'foreignKey' => 'entrenue_product_id'
			),
	);

	public $hasMany = array(
        'MwsInventoryHistory' => array(
            'className' => 'MwsInventoryHistory'
		)
    );

	public $min_quantity = 2;
	
	public $actsAs = array(
			'Search.Searchable'
	);
	
	public $filterArgs = array(
			'filter' => array(
					'type' => 'query',
					'method' => 'orConditions'
			),
	
	);
	
	
	
	// Or conditions with like
	public function orConditions($data = array()) {
		$filter = $data['filter'];
		$condition = array(
				'OR' => array(
						$this->alias . '.sku LIKE' => '%' . trim($filter) . '%',
						$this->alias . '.asin LIKE' => '%' . trim($filter) . '%',
						$this->alias . '.title LIKE' => '%' . trim($filter) . '%',
				)
		);
		return $condition;
	}
	

	public function updateQuantity(){

		/** UPDATE QUANTITY IN MWS_INVENTORY*/
		$this->query('UPDATE `mws_inventory` AS mws INNER JOIN entrenue_products AS en ON mws.sku = en.SKU SET mws.quantity = en.QUANTITY');

	}
	
	public function updateTierVariable($tier_product){
		
		$product = $this->findBySku($tier_product['SKU']);
		
		$this->id = $product['MwsInventory']['id'];
		
		return $this->saveField('min_price', $tier_product['Min_price'],
								'competitor', $tier_product['Competitor'],
								'earnings', $tier_product['earnings']);
		
	}

// 	public function beforeSave($options = array()){
// 		//@TODO: this should not be in beforesave method...

// // 		if (!empty($this->data['MwsInventory']['sku'])){
				
// // 			App::import('Model','SubmitFeed');

// // 			$submitFeed = new SubmitFeed();

// // 			$_this = $this->findById($this->data['MwsInventory']['id']);

// // 			if($_this['MwsInventory']['price'] != $this->data['MwsInventory']['price']){
					
// // 				$submitFeed->flushFeed(
// // 						array(array(	'SKU'		=> $this->data['MwsInventory']['sku'],
// // 								'Estimated' => $this->data['MwsInventory']['price'])),
// // 						'repricing');
					
// // 			}

// // 			if($_this['MwsInventory']['quantity'] != $this->data['MwsInventory']['quantity']){
				
// // 				$submitFeed->flushFeed(array(array('ViewMatchInv' => array(	'SKU'		=> $this->data['MwsInventory']['sku'],
// // 								'Quantity' => $this->data['MwsInventory']['quantity']))) ,
// // 						'inventory');
					
// // 			}
// // 		}
// 	}


	public function updatePricesWhenSubmit($items){


		foreach ($items as $key => $item){

			$product = $this->findBySku($item['SKU']);
				
			$this->id = $product['MwsInventory']['id'];
				
			$this->saveField('price', $item['Estimated']);
		}

	}
	/**
	 * UPdate Price by SKU
	 *
	 * @param array $item Array('SellerSKU',RegularPrice:Amount)
	 */
	public function updatePriceBySKU($item = null){

		$product = $this->findBySku(key($item));

		$this->id = $product['MwsInventory']['id'];
		
		if(!isset($item[key($item)]['RegularPrice']['Amount'])){
			
			debug($item);
			exit;
		}

		return $this->saveField('price', $item[key($item)]['RegularPrice']['Amount']);

	}

	public function updatePrices($items){

		foreach ($items as $key => $item){
				
			$this->updatePriceBySKU($item);
		}

	}

	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * Returns Entrenue products based on conditions
	 */
	public function pullEntrenueRecordsByConditions($conditions = array(), $limit = 0){

		App::import('Model','EntrenueProduct');

		$eProduct = new EntrenueProduct();

		return $eProduct->find('all',array(
			'fields' => array('id','upc','SKU','pages'),
			'conditions' => $conditions
		));


	}


	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * Pull Entrenue category %book%
	 */
	public function pullEntrenueRecords($amount = 10){

		App::import('Model','EntrenueProduct');

		$eProduct = new EntrenueProduct();

		return $eProduct->find('all',array(
			'limit' => $amount, // int
			'fields' => array('id','upc','SKU','pages'),
			// 'limit' => 5,
			'conditions' => array("EntrenueProduct.categories LIKE" => "%book%", 'quantity >'=>0 )
		));


	}


	public function getConfig(){

		App::import('Model','Submit');

		$submit = new Submit();

		return $submitFeed->configSPAPI();

	}

	/**
	 * 
	 * @date: 2021-06-01
	 * 
	 * Returns the lowest priced offers for a single item based on SKU.
	 */
	public function getItemOfferLowestPriceBySku($result = null){

		if($result == null) return 0;

		$amount = 0;

		if('NoBuyableOffers' != $result->getPayload()->getStatus())
		foreach($result->getPayload()->getSummary()->getLowestPrices() as $key => $lowerprice){

			if($lowerprice->condition == 'new'){

				if($amount == 0){

					$amount = $lowerprice->LandedPrice->Amount;


				}
				else {

					if( $amount > $lowerprice->LandedPrice->Amount){

						$amount = $lowerprice->LandedPrice->Amount;
					}

				}
			}
		}


		return $amount;
	}

	/**
	 * 
	 * @date: 2021-06-01
	 * 
	 * Returns my offer
	 */
	public function getMyOfferBySku($result = null){

		if($result == null) return 0;

		$amount = 0;

		foreach($result->getPayload()->getOffers() as $key => $offer){

			if($offer->getMyOffer() == true){

				$amount = $offer->getListingPrice()->getAmount();

			}

		}

		return $amount;
	}

	/**
	 * 
	 * Date: 2021-05-16
	 * 
	 * Returns the lowest priced offers for a single item based on ASIN.
	 */
	public function getItemOffers($config = array(), $sku = ''){

		$apiInstance = new \ClouSale\AmazonSellingPartnerAPI\Api\ProductPricingApi($config);


		// $marketplace_id = Configure::read('SPAPI.MARKETPLACE.US'); // string | A marketplace identifier. Specifies the marketplace for which prices are returned.
		// $item_condition = "New"; // string | Filters the offer listings to be considered based on item condition. Possible values: New, Used, Collectible, Refurbished, Club.
		// $asin = "1934429953"; // string | The Amazon Standard Identification Number (ASIN) of the item.
		
		try {
			$result = $apiInstance->getListingOffers(Configure::read('SPAPI.MARKETPLACE.US'), 'New', $sku);
			// debug($result);

			// debug($result->getPayload());

			// debug($result->getPayload()->getSummary()->getLowestPrices());

			// debug($result->getPayload()->getOffers()[0]->getListingPrice()->getAmount());

			// debug($result->getPayload()->getOffers()[0]->getListingPrice());


		} catch (Exception $e) {
			debug('Exception when calling ProductPricingApi->getItemOffers: '. $e->getMessage()) ;

			return null;
		}

		return $result;
	}


	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * Returns competitive pricing information for a seller's offer listings based on seller SKU or ASIN.
	 * 
	 * $marketplace_id = "marketplace_id_example"; // string | A marketplace identifier. Specifies the marketplace for which prices are returned.
	 * $item_type = "item_type_example"; // string | Indicates whether ASIN values or seller SKU values are used to identify items. If you specify Asin, the information in the response will be dependent on the list of Asins you provide in the Asins parameter. If you specify Sku, the information in the response will be dependent on the list of Skus you provide in the Skus parameter. Possible values: Asin, Sku.
	 * $asins = array("asins_example"); // string[] | A list of up to twenty Amazon Standard Identification Number (ASIN) values used to identify items in the given marketplace.
	 * $skus = array("skus_example"); // string[] | A list of up to twenty seller SKU values used to identify items in the given marketplace.
	 * 
	 */
	public function getCompetitivePricing($config = array(), $marketplace_id = '', $item_type = '', $asins = '', $skus = ''){

		$apiInstance = new \ClouSale\AmazonSellingPartnerAPI\Api\ProductPricingApi($config);

		try {
			$result = $apiInstance->getCompetitivePricing($marketplace_id, $item_type, $asins, $skus);
			print_r($result);
		} catch (Exception $e) {
			echo 'Exception when calling ProductPricingApi->getCompetitivePricing: ', $e->getMessage(), PHP_EOL;
		}
	}

	/**
	 * @date: 2021-05-29
	 * 
	 * Fee min price
	 * Entrenue: $2
	 * Entrenue shipping $4
	 * MWS: 25%
	 */
	public function minPrice($price = 0){

		return $price + ($price * $this->_MWS_FEE_PERCENT_NOMINAL) + $this->_ENTRENUE_FEE + $this->_ENTRENUE_FLET;
	}

	public function updateItemOfferAndFeedMWS(){

		$data = $this->updateItemOffer();

		
		debug($data);

		///SUBMIT INVENTORY QUANTITY
		App::import('Model','SubmitFeed');

		$submitFeed = new SubmitFeed();

		return $submitFeed->submitPrice($data);

	}

	/**
	 * 
	 * @date: 2021-05-30
	 * 
	 * returns the best price for listing
	 * 
	 */
	public function listingPrice($item = array()){

		if($item['MwsInventory']['my_offer'] == $item['MwsInventory']['item_offer'] && $item['MwsInventory']['my_offer'] > $item['MwsInventory']['min_price']  ) {

			return $item['MwsInventory']['my_offer'];
		}

		$reference_price = ($item['MwsInventory']['item_offer'] == 0)?$item['MwsInventory']['price']:$item['MwsInventory']['item_offer'];

		$min_price = $item['MwsInventory']['min_price'];

		if($min_price < ($reference_price - 0.05 )){

			return ($reference_price - 0.05 );
		}

		return $min_price;

	}

	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * Updates the price in the ItemOffer in MWSInventory
	 */
	public function updateItemOffer(){

		App::import('Model','Submit');

		$submit = new Submit();

		$config = $submit->configSPAPI();

		$conditions = array( 'MwsInventory.activated' => 1);

		$data = $this->find('all', array('fields' => array('id', 'sku', 'asin', 'item_offer', 'min_price', 'price'),'conditions' => $conditions));

		$count=0;

		foreach($data as $key => $item){

			$count++;
		
			if(($count%10)==0)
			{
				sleep($this->_SLEEP_TIME);
			}

			$item_offer_old = $item['MwsInventory']['item_offer'];

			try {

				$result = $this->getItemOffers($config, $asin = $item['MwsInventory']['sku']);

				$item['MwsInventory']['item_offer'] = $this->getItemOfferLowestPriceBySku($result);
	
				$item['MwsInventory']['my_offer'] = $this->getMyOfferBySku($result);
	
				$item['MwsInventory']['listing_price'] = $this->listingPrice($item);

				//debug($item);exit();
				$this->clear();

				if($this->save($item)){

					debug('updated MwsInventory.: '.$item['MwsInventory']['asin'].'-: '.$item['MwsInventory']['item_offer']);

					$oldRecord = $item;
					
					$oldRecord['MwsInventory']['item_offer'] = $item_offer_old;
					
					$updatedProduct[] = $this->MwsInventoryHistory->insertRecord($item, $oldRecord );
				}

			}catch (Exception $e) {
				
					echo 'Caught MwsInventoryAPI exception: '.$e->getFile().  $e->getMessage(). "\n";
				
			}

		}

		return array_filter($updatedProduct);

	}

	/**
	 * @date: 2021-05-15
	 * 
	 * Get the Fees Estimated
	 * 
	 * NOT WORK
	 */
	public function getFeesEstimated($config = array(), $SKUs = array()){

		$apiInstance = new \ClouSale\AmazonSellingPartnerAPI\Api\FeesApi($config);

		//C:\Users\pgunt\php\cakephp\app\Vendor\vendor\clousale\amazon-sp-api-php\lib\Models\ProductFees\GetMyFeesEstimateRequest.php

		//C:\Users\pgunt\php\cakephp\app\Vendor\vendor\clousale\amazon-sp-api-php\lib\Api\FeesApi.php

		$body = new \ClouSale\AmazonSellingPartnerAPI\Models\ProductFees\GetMyFeesEstimateRequest(); // \Swagger\Client\Models\GetMyFeesEstimateRequest |

		$feesEstimateRequest = new \ClouSale\AmazonSellingPartnerAPI\Models\ProductFees\FeesEstimateRequest(array('marketplace_id'=>Configure::read('SPAPI.MARKETPLACE.US'),
																		'is_amazon_fulfilled' => false,
																		'price_to_estimate_fees' => 100,
																		'identifier' => 'asdf3342sdas3'));

		$return['FeedEstimated'] = array();

		// $this->container['marketplace_id'] = isset($data['marketplace_id']) ? $data['marketplace_id'] : null;
        // $this->container['is_amazon_fulfilled'] = isset($data['is_amazon_fulfilled']) ? $data['is_amazon_fulfilled'] : null;
        // $this->container['price_to_estimate_fees'] = isset($data['price_to_estimate_fees']) ? $data['price_to_estimate_fees'] : null;
        // $this->container['identifier'] = isset($data['identifier']) ? $data['identifier'] : null;

		$feedEstimate = array('marketplace_id'=>Configure::read('SPAPI.MARKETPLACE.US'),
							'is_amazon_fulfilled' => false,
							'price_to_estimate_fees' => 100,
							'identifier' => 'asdf3342sdas3');

		

		$body->setFeesEstimateRequest($feesEstimateRequest);

		debug($body->getFeesEstimateRequest());

		foreach ($SKUs as $key => $seller_sku){

			try {
				$result = $apiInstance->getMyFeesEstimateForSKU($feesEstimateRequest, '45-87DE-NQ23');
				print_r($result);
			} catch (Exception $e) {
				echo 'Exception when calling FeesApi->getMyFeesEstimateForSKU: ', $e->getMessage(), PHP_EOL;
			}

		}
	}




	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * 
	 */
	public function getListCatalogItems($config = array(), $param = array()){

		$apiInstance = new \ClouSale\AmazonSellingPartnerAPI\Api\CatalogApi($config);
		
		$marketplace_id = Configure::read('SPAPI.MARKETPLACE.US');

		// $query = ""; // string | Keyword(s) to use to search for items in the catalog. Example: 'harry potter books'.
		// $query_context_id = ""; // string | An identifier for the context within which the given search will be performed. A marketplace might provide mechanisms for constraining a search to a subset of potential items. For example, the retail marketplace allows queries to be constrained to a specific category. The QueryContextId parameter specifies such a subset. If it is omitted, the search will be performed using the default context for the marketplace, which will typically contain the largest set of items.
		// $seller_sku = ""; // string | Used to identify an item in the given marketplace. SellerSKU is qualified by the seller's SellerId, which is included with every operation that you submit.
		// $upc = ""; // string | A 12-digit bar code used for retail packaging.
		// $ean = ""; // string | A European article number that uniquely identifies the catalog item, manufacturer, and its attributes.
		// $isbn = $entrenueProduct['EntrenueProduct']['upc']; // string | The unique commercial book identifier used to identify books internationally.
		// $jan = ""; // string | A Japanese article number that uniquely identifies the product, manufacturer, and its attributes.

		try {
			$results = $apiInstance->listCatalogItems($marketplace_id, $param['query'], $param['query_context_id'], $param['seller_sku'], $param['upc'], $param['ean'], $param['isbn'], $param['jan']);
			// debug($results,2);

			return $results->getPayload();
		} catch (\Exception $e) {
			echo 'Exception when calling CatalogApi->listCatalogItems: ', $e->getMessage(), PHP_EOL;
		}


		return null;

	}

	/**
	 * 
	 * @date: 2021-05-21
	 * 
	 * Returns activated MWS and Entrenue.Id
	 * 
	 */
	public function activatedProductArray(){

		$query = "SELECT `MwsInventory`.`sku`, `EntrenueProduct`.`id` FROM `greencloud`.`mws_inventory` AS `MwsInventory` LEFT JOIN `greencloud`.`entrenue_products` AS `EntrenueProduct` ON (`MwsInventory`.`entrenue_products_id` = `EntrenueProduct`.`id`) where `MwsInventory`.`activated` = true";

		App::import('Model','SubmitFeed');

		$submitFeed = new SubmitFeed();

		$data = array();

		foreach($submitFeed->query($query) as $key => $value){

			$data[$value['EntrenueProduct']['id']] = $value['MwsInventory']['sku'];


		}

		return $data;

	}

	/**
	 * 
	 * @date: 2021-05-16
	 * 
	 * 
	 */
	public function importFromCatalogBasedOnEntrenueCategory($config = array()){

		$data = $this->pullEntrenueRecordsByConditions(array("EntrenueProduct.categories LIKE" => "%Intimacy Devices%", 'quantity >'=>0, 'penalized' => false ));

		// debug($data);

		foreach($data as $key => $item){

			$param = array('query' => '', 'query_context_id' => '', 'seller_sku' => '', 'upc' => '', 'ean' => $item['EntrenueProduct']['upc'], 'isbn' => '', 'jan' => '');


			// if($item['EntrenueProduct']['upc'] == null) continue;

			debug($item);

			debug($param);

			try {
				$playLoad = $this->getListCatalogItems($config, $param );

				foreach ($playLoad->getItems() as $value) {

					debug($value);
		
							$this->create();
							$this->save(array('MwsInventory'=>array('MarketplaceId'=>$value->Identifiers->MarketplaceASIN->MarketplaceId,
																	'asin'=>$value->Identifiers->MarketplaceASIN->ASIN,
																	'Title'=>$value->AttributeSets[0]->Title,
																	'price'=>$value->AttributeSets[0]->ListPrice->Amount,
																	'image'=>$value->AttributeSets[0]->SmallImage->URL,
																	'provider'=>$item['EntrenueProduct']['SKU'],
																	'entrenue_products_id'=>$item['EntrenueProduct']['id'] )));
				
				}
			} catch (\Throwable $th) {
				continue;
				
			}
			catch (\Exception $emysql) {
				print  'ERROR MYSQL-'.$emysql->getMessage();

			}
			finally{
				// print_r($results);
				
			}


			




		}

	}


	/**
	 * @date: 2021-05-15
	 * 
	 * Insert in MWSInventory from EntrenueProduct calling the Catalog
	 * Category: books
	 * 
	 */
	public function importMatchingSPAPI($limit = 10){


		$accessToken = \ClouSale\AmazonSellingPartnerAPI\SellingPartnerOAuth::getAccessTokenFromRefreshToken(
			Configure::read('SPAPI.refresh_token'),
			Configure::read('SPAPI.client_id'),
			Configure::read('SPAPI.client_secret')
		);
		$assumedRole = \ClouSale\AmazonSellingPartnerAPI\AssumeRole::assume(
			Configure::read('SPAPI.region'),
			Configure::read('SPAPI.access_key'),
			Configure::read('SPAPI.secret_key'),
			Configure::read('SPAPI.role_arn')
		);
		$config = \ClouSale\AmazonSellingPartnerAPI\Configuration::getDefaultConfiguration();
		$config->setHost(Configure::read('SPAPI.endpoint') );
		$config->setAccessToken($accessToken);
		$config->setAccessKey($assumedRole->getAccessKeyId());
		$config->setSecretKey($assumedRole->getSecretAccessKey());
		$config->setRegion(Configure::read('SPAPI.region'));
		$config->setSecurityToken($assumedRole->getSessionToken());
		
		$apiInstance = new \ClouSale\AmazonSellingPartnerAPI\Api\CatalogApi($config);
		
		$marketplace_id = Configure::read('SPAPI.MARKETPLACE.US');

		
		// $result = $apiInstance->getCatalogItem($marketplace_id, $asin);

		// debug($result->getPayload()->getAttributeSets()[0]->getTitle());
		
		// debug($result->getPayload()->getAttributeSets());

		$pulledEntrenue = $this->pullEntrenueRecords($limit);

		// debug($pulledEntrenue);
		$count=0;


		foreach ($pulledEntrenue as $key => $entrenueProduct) {

			$count++;
		
			if(($count%10)==0)
			{
				sleep(5);
			}

			$query = ""; // string | Keyword(s) to use to search for items in the catalog. Example: 'harry potter books'.
			$query_context_id = ""; // string | An identifier for the context within which the given search will be performed. A marketplace might provide mechanisms for constraining a search to a subset of potential items. For example, the retail marketplace allows queries to be constrained to a specific category. The QueryContextId parameter specifies such a subset. If it is omitted, the search will be performed using the default context for the marketplace, which will typically contain the largest set of items.
			$seller_sku = ""; // string | Used to identify an item in the given marketplace. SellerSKU is qualified by the seller's SellerId, which is included with every operation that you submit.
			$upc = ""; // string | A 12-digit bar code used for retail packaging.
			$ean = ""; // string | A European article number that uniquely identifies the catalog item, manufacturer, and its attributes.
			$isbn = $entrenueProduct['EntrenueProduct']['upc']; // string | The unique commercial book identifier used to identify books internationally.
			$jan = ""; // string | A Japanese article number that uniquely identifies the product, manufacturer, and its attributes.
	
			try {
				$results = $apiInstance->listCatalogItems($marketplace_id, $query, $query_context_id, $seller_sku, $upc, $ean, $isbn, $jan);

				foreach ($results->getPayload()->getItems() as $value) {

					try {
						
						if($value->AttributeSets[0]->NumberOfPages == $entrenueProduct['EntrenueProduct']['pages']){

							$this->create();
							$this->save(array('MwsInventory'=>array('MarketplaceId'=>$value->Identifiers->MarketplaceASIN->MarketplaceId,
																	'asin'=>$value->Identifiers->MarketplaceASIN->ASIN,
																	'Title'=>$value->AttributeSets[0]->Title,
																	'NumberOfPages'=>$value->AttributeSets[0]->NumberOfPages,
																	'price'=>$value->AttributeSets[0]->ListPrice->Amount,
																	'image'=>$value->AttributeSets[0]->SmallImage->URL,
																	'provider'=>$entrenueProduct['EntrenueProduct']['SKU'],
																	'Binding'=>$value->AttributeSets[0]->Binding,
																	'entrenue_product_id'=>$entrenueProduct['EntrenueProduct']['id'] )));
	
						}

					} catch (\Exception $th) {

					
						//UPDATE
						if(strpos ($th->getMessage() , '1062 Duplicate' ) > 0){

							$oldRecord = $this->findByAsin($value->Identifiers->MarketplaceASIN->ASIN);

							debug($oldRecord);



							$this->clear();
							$result = $this->save(array('MwsInventory'=>array(	'id'=>$oldRecord['MwsInventory']['id'],
																		'MarketplaceId'=>$value->Identifiers->MarketplaceASIN->MarketplaceId,
																		'asin'=>$value->Identifiers->MarketplaceASIN->ASIN,
																		'Title'=>$value->AttributeSets[0]->Title,
																		'NumberOfPages'=>$value->AttributeSets[0]->NumberOfPages,
																		'price'=>$value->AttributeSets[0]->ListPrice->Amount,
																		'image'=>$value->AttributeSets[0]->SmallImage->URL,
																		'provider'=>$entrenueProduct['EntrenueProduct']['SKU'],
																		'Binding'=>$value->AttributeSets[0]->Binding,
																		'entrenue_product_id'=>$entrenueProduct['EntrenueProduct']['id'] )));

							debug($result);


						}
					}

					
				}
			
			} catch (\Exception $e) {
				echo 'Exception when calling CatalogApi->listCatalogItems: ', $e->getMessage(), PHP_EOL;

				
			}
					
			
		
		}
		
	}


	/**
	 * Import the csv file in the table
	 * The csv file is downloade from MWS Inventory report
	 *
	 * @param unknown_type $filename
	 * @param String [inventory_report | open_listings_report_lite
	 */
	public function import($filename = 'InventoryReport07042017.txt', $file_type = 'inventory_report'){
		// to avoid having to tweak the contents of
		// $data you should use your db field name as the heading name
		// eg: Post.id, Post.title, Post.description

		// set the filename to read CSV from
		$filename = WWW_ROOT.DS.'files'.DS.'DOWNLOAD_INV_FILES'.DS . $filename;

		// open the file
		$handle = fopen($filename, "r");

		if(!$handle) return null;
			
		$this->truncate();

		// read the 1st row as headings
		$header = fgetcsv($handle, 0, '	');

		// create a message container
		$return = array(
				'messages' => array(),
				'errors' => array(),
		);


		$i = 0;
		// read each data row in the file
		while (($row = fgetcsv($handle, 0, '	')) !== FALSE) {
			$i++;
			$data = array();

				
			foreach ($row as $k=>$head) {
				// get the data field from Model.field

				if($file_type == 'inventory_report'){
				
					$data['MwsInventory']['sku']	=(isset($row[0])) ? $row[0] : '';
					$data['MwsInventory']['asin']	=(isset($row[1])) ? $row[1] : '';
					$data['MwsInventory']['price']	=(isset($row[2])) ? $row[2] : '';
					$data['MwsInventory']['quantity']	=(isset($row[3])) ? $row[3] : '';
					$data['MwsInventory']['tier_id']	=	2;
				
				}
				
				if($file_type == 'open_listings_report_lite'){
					
					$data['MwsInventory']['sku']	=(isset($row[0])) ? $row[0] : '';
					$data['MwsInventory']['quantity']	=(isset($row[1])) ? $row[1] : '';
					$data['MwsInventory']['price']	=(isset($row[2])) ? $row[2] : '';
					$data['MwsInventory']['asin']	=(isset($row[3])) ? $row[3] : '';
					$data['MwsInventory']['tier_id']	=	2;
				}

				debug($data);


			}
				
			$this->create();
			if (!$this->save($data)) {
				debug( __(sprintf('MwsInventory for Row %d failed to save.',$i), true));
			}
			else{
				debug( __(sprintf('MwsInventory for Row %d was saved.',$i), true));
			}
				
		}

		// close the file
		fclose($handle);

		// return the messages
		return $return;
	}


	/**
	 * Truncate the table
	 *
	 */
	private function truncate(){

		return $this->query('TRUNCATE mws_inventory');

	}
}
