<?php

App::uses('Submit', 'Model');


/**
 * List Matching Products Model
 *
 */
class SubmitFeed extends Submit {
	
	public $order = "SubmitFeed.updated DESC";
	
	public $useTable = 'submit_feed';
	
	public $hasOne = array(
			'ProcessingReport' => array(
					'className' => 'ProcessingReport',
					'foreignKey' => 'submit_feed_id',
					'dependent' => true
			)
	);	
	


	public $config;

	public $serviceUrl = "https://mws.amazonservices.com";

	public $service;

	public $request;
	
	public $filename = ''; 


	/**
	 * Set all MWS Objects relative to ListMatchingProducts
	 *
	 * @param unknown_type $id
	 * @param unknown_type $table
	 * @param unknown_type $ds
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		

		$this->config = array (
				'ServiceURL' => $this->serviceUrl,
				'ProxyHost' => null,
				'ProxyPort' => -1,
				'MaxErrorRetry' => 3,
		);

		$this->service = new MarketplaceWebService_Client(
				Configure::read('PETER.MWS.AWS_ACCESS_KEY_ID'),
				Configure::read('PETER.MWS.AWS_SECRET_ACCESS_KEY'),
				$this->config,
				Configure::read('PETER.MWS.APPLICATION_NAME'),
				Configure::read('PETER.MWS.APPLICATION_VERSION'));
	}
	
	
	/**
	 * Update the Response field once the Submition is completed
	 * 
	 * @param string $status
	 * @param string $submittedFeedId
	 */
	public function updateStatus($status, $submittedFeedId){
		
		$this->query('UPDATE `submit_feed` SET Response  = "'.$status.'" WHERE FeedSubmissionId = "'.$submittedFeedId.'"');
	}
	
	/**
	 * The SubmitFeed table stores all submition done
	 * This provide an array of submition id which are response = null
	 * 
	 * 
	 * @return Array with response null
	 */
	public function findSubmittedFeedId(){
		
		return $this->find('list', array(
				'fields' => array('SubmitFeed.FeedSubmissionId'), 
				'conditions' => array('SubmitFeed.Response = ' => NULL)));
	}
	
	public function test2(){
		
		return $this->findSubmittedFeedId();
	}

	public function test(){

		$a = array(array('SKU'=>'103974306', 'ANSI'=>'0897936507', 'Estimated'=>'32.20'));

		$b = array(array('SKU'=>'103974306', 'ANSI'=>'0897936507', 'Quantity'=>'3'));

		debug($b);

		$this->flushFeed($a, 'repricing');
	}



	/**
	 * This invoque the Repricing and Inventort feed.
	 * Repricing command argument: feed repricing
	 * Inventory command argument: feed inventory
	 * Invoque both: feed
	 *
	 * @param String $feedType [ inventory | repricing ]
	 */
	public function flushFeed($items, $feedType = 'inventory'){

		//invoque Repricing

		if($feedType == 'repricing'){

			$xmlFeeds = $this->creating_POST_PRODUCT_PRICING_DATA($items);
			
			if($this->filename == '')
				$this->filename = 'reprice_'.date("Ymd-H-is").'.xml';

			$feedHandle =  @fopen(WWW_ROOT.'files/FEED_LOG/'.$this->filename, 'arw+');

			fwrite($feedHandle, $xmlFeeds);

			rewind($feedHandle);

			$this->request = new MarketplaceWebService_Model_SubmitFeedRequest();

			$this->request->setMerchant(Configure::read('PETER.MWS.SELLER_ID'));

			$this->request->setMarketplaceIdList(array("Id" => array(Configure::read('PETER.MWS.MARKETPLACE_ID'))));

			$this->request->setFeedType('_POST_PRODUCT_PRICING_DATA_');

			$this->request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));

			rewind($feedHandle);

			$this->request->setPurgeAndReplace(false);

			$this->request->setFeedContent($feedHandle);

			$this->invokeSubmitFeed($this->service, $this->request);

			rewind($feedHandle);

		}

		if($feedType == 'inventory'){

			// 			debug('INVEN');

			$xmlFeeds = $this->creating_POST_INVENTORY_AVAILABILITY_DATA($items);
			
			$this->filename = 'inventory_'.date("Ymd-H-is").'.xml';

			$feedHandle =  @fopen(WWW_ROOT.'files/FEED_LOG/'.$this->filename, 'arw+');

			fwrite($feedHandle, $xmlFeeds);

			rewind($feedHandle);

			$this->request = new MarketplaceWebService_Model_SubmitFeedRequest();

			$this->request->setMerchant(Configure::read('PETER.MWS.SELLER_ID'));

			$this->request->setMarketplaceIdList(array("Id" => array(Configure::read('PETER.MWS.MARKETPLACE_ID'))));

			$this->request->setFeedType('_POST_INVENTORY_AVAILABILITY_DATA_');

			$this->request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));

			rewind($feedHandle);

			$this->request->setPurgeAndReplace(false);

			$this->request->setFeedContent($feedHandle);

			$this->invokeSubmitFeed($this->service, $this->request);

			rewind($feedHandle);

		}

		return null;
	}

	/**
	 * Submit Feed Action Sample
	 * Uploads a file for processing together with the necessary
	 * metadata to process the file, such as which type of feed it is.
	 * PurgeAndReplace if true means that your existing e.g. inventory is
	 * wiped out and replace with the contents of this feed - use with
	 * caution (the default is false).
	 *
	 * Pedro: Save in submit_feed table
	 *
	 * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
	 * @param mixed $request MarketplaceWebService_Model_SubmitFeed or array of parameters
	 */
	function invokeSubmitFeed(MarketplaceWebService_Interface $service, $request)
	{
		//@TODO: Log the response
		try {
			$response = $service->submitFeed($request);

			//echo ("Service Response\n");
			//echo ("=============================================================================\n");

			//echo("        SubmitFeedResponse\n");
			if ($response->isSetSubmitFeedResult()) {
				//echo("            SubmitFeedResult\n");
				$submitFeedResult = $response->getSubmitFeedResult();
				if ($submitFeedResult->isSetFeedSubmissionInfo()) {
					//echo("                FeedSubmissionInfo\n");
					$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
						
						
					if ($feedSubmissionInfo->isSetFeedSubmissionId())
					{
						//echo("                    FeedSubmissionId\n");
						//echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
					}
					if ($feedSubmissionInfo->isSetFeedType())
					{
						//echo("                    FeedType\n");
						//echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
					}
					if ($feedSubmissionInfo->isSetSubmittedDate())
					{
						//echo("                    SubmittedDate\n");
						//echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format('Y-m-d\TH:i:s\Z') . "\n");
					}
					if ($feedSubmissionInfo->isSetFeedProcessingStatus())
					{
						//echo("                    FeedProcessingStatus\n");
						//echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
					}
					if ($feedSubmissionInfo->isSetStartedProcessingDate())
					{
						//echo("                    StartedProcessingDate\n");
						//echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format('Y-m-d\TH:i:s\Z') . "\n");
					}
					if ($feedSubmissionInfo->isSetCompletedProcessingDate())
					{
						//echo("                    CompletedProcessingDate\n");
						//echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format('Y-m-d\TH:i:s\Z') . "\n");
					}
				}


				if(!$this->save(array(
						'FeedSubmissionId' 		=> $feedSubmissionInfo->getFeedSubmissionId(),
						'FeedType'				=> $feedSubmissionInfo->getFeedType(),
						'SubmittedDate'			=> $feedSubmissionInfo->getSubmittedDate()->format('Y-m-d\TH:i:s\Z'),
						'FeedProcessingStatus'	=> $feedSubmissionInfo->getFeedProcessingStatus(),
						'File'					=> $this->filename

				))){
					
					//@TODO: Log file;
					debu(array(
							'FeedSubmissionId' 		=> $feedSubmissionInfo->getFeedSubmissionId(),
							'FeedType'				=> $feedSubmissionInfo->getFeedType(),
							'SubmittedDate'			=> $feedSubmissionInfo->getSubmittedDate()->format('Y-m-d\TH:i:s\Z'),
							'FeedProcessingStatus'	=> $feedSubmissionInfo->getFeedProcessingStatus()

					));
				}
				else{
					
					$this->ProcessingReport->create();
					$ProcessingReport = $this->ProcessingReport->save(array(
							'ProcessingReport' => array('Document' 	=> $feedSubmissionInfo->getFeedSubmissionId(),
														'submit_feed_id'			=> $this->id)));
						
				}
			}
			if ($response->isSetResponseMetadata()) {
				//echo("            ResponseMetadata\n");
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId())
				{
					//echo("                RequestId\n");
					//echo("                    " . $responseMetadata->getRequestId() . "\n");
				}
			}

			//echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
		} catch (MarketplaceWebService_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
			echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
		}
	}

	/**
	 * 
	 * @update 2021-05-18
	 * Create the Inventory Availability XML
	 *
	 * @param unknown_type $items
	 */
	public function creating_POST_INVENTORY_AVAILABILITY_DATA($items = array()){


		if($items == null) return null;

		$myXmlOriginal = '<?xml version="1.0" encoding="utf-8" ?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
		<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>'.$items['MerchantIdentifier'].'</MerchantIdentifier>
		</Header>
		<MessageType>Inventory</MessageType>
		</AmazonEnvelope>';

		$xml = Xml::build($myXmlOriginal);

		$i = 1;
		foreach ($items['Messages'] as $item) {

			$message = $xml->addChild('Message');
			$message->addChild('MessageID',$i);
			$message->addChild('OperationType',$item['OperationType']);
			$inventory = $message->addChild('Inventory');
			$inventory->addChild('SKU',$item['ViewMatchInv']['SKU']);
			$inventory->addChild('Quantity',$item['ViewMatchInv']['Quantity']);
			$inventory->addChild('FulfillmentLatency',$item['ViewMatchInv']['FulfillmentLatency']);

			++$i;

		}

		return $xml->asXML();

	}


	/**
	 * 
	 * @update: 2021-05-18
	 * Create the PRODUCT PRICING XML
	 *
	 * @param unknown_type $items ('SKU'=>sku, 'Estimated'=>$$$)
	 */
	public function creating_POST_PRODUCT_PRICING_DATA($items = array()){

		if($items == null) return null;

		$myXmlOriginal = '<?xml version="1.0" encoding="utf-8" ?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
		<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>'.$items['MerchantIdentifier'].'</MerchantIdentifier>
		</Header>
		<MessageType>Price</MessageType>
		</AmazonEnvelope>';


		$xml = Xml::build($myXmlOriginal);

		$i = 1;

		foreach ($items['Messages'] as $item) {

			// 			debug($item);
			$message = $xml->addChild('Message');
			$message->addChild('MessageID',$i);
			$price = $message->addChild('Price');
			$price->addChild('SKU',$item['ViewMatchInv']['SKU']);
			$standardPrice = $price->addChild('StandardPrice',$item['ViewMatchInv']['Estimated']);
			$standardPrice->addAttribute('currency','USD');

			++$i;
		}


		return $xml->asXML();

	}

	/**
	 * 
	 * @date: 2021-05-30
	 * 
	 * Repricing 
	 * 
	 */
	public function submitPrice($data = array()){

		if($data == null || count($data) == 0) return null;

		foreach($data as $key => $item){

			$messages[] = array('OperationType'=>'Update', 'ViewMatchInv'=> array('SKU' => $item['MwsInventory']['sku'], 'Estimated' => $item['MwsInventory']['listing_price'], 'FulfillmentLatency'=>'1'));

		}

		$items = array('MerchantIdentifier'=>Configure::read('SPAPI.MerchantIdentifier'), 'Messages' => $messages);

		debug($items);

		
		$pushArray['xml'] = $this->creating_POST_PRODUCT_PRICING_DATA($items);
		$pushArray['feedType'] = 'POST_PRODUCT_PRICING_DATA';
		$pushArray['marketplaceIds'] = array(Configure::read('SPAPI.MARKETPLACE.US'));


		$this->submitInventory($pushArray);

		return $pushArray;

	}

	/**
	 * 
	 * @date: 2021-05-23
	 * 
	 * Submits the inventory based on the inventory type
	 */
	public function submitInventoryQuantity($data = array()){

		if($data == null || count($data) == 0) return null;

		$viewMatchInv = array();

		$messages = array();

		foreach($data as $key => $item){

			// $viewMatchInv[] = array('SKU' => $item['EntrenueProduct']['SKU'], 'Quantity' => $item['EntrenueProduct']['quantity'], 'FulfillmentLatency'=>'1');

			$messages[] = array('OperationType'=>'Update', 'ViewMatchInv'=> array('SKU' => $item['EntrenueProduct']['SKU'], 'Quantity' => $item['EntrenueProduct']['quantity'], 'FulfillmentLatency'=>'1'));

		}

			
		$items = array('MerchantIdentifier'=>Configure::read('SPAPI.MerchantIdentifier'), 'Messages' => $messages);

		debug($items);

			// debug($submitFeed->creating_POST_INVENTORY_AVAILABILITY_DATA($items));

		$pushArray['xml'] = $this->creating_POST_INVENTORY_AVAILABILITY_DATA($items);
		$pushArray['feedType'] = 'POST_INVENTORY_AVAILABILITY_DATA';
		$pushArray['marketplaceIds'] = array(Configure::read('SPAPI.MARKETPLACE.US'));


		$this->submitInventory($pushArray);

		return $pushArray;


	}

	/**
	 * @date: 2021-05-15
	 * This submit the quantity of the products in MWS through SP-API
	 * 
	 * @param array('MerchantIdentifier'=>Configure::read('SPAPI.MerchantIdentifier'), 'Messages' => array(array('OperationType'=>'Update', 'ViewMatchInv'=>array('SKU'=>'45-87DE-NQ23', 'Quantity'=>'9','FulfillmentLatency'=>'1'))))
	 * 
	 * @return Bolean 
	 * 
	*/
	public function submitInventory($xmlFeed = ''){

		$config = $this->configSPAPI();

		$feedApi = new \ClouSale\AmazonSellingPartnerAPI\Api\FeedsApi($config);

		$contentType = 'text/xml; charset=UTF-8'; // please pay attention here, the content_type will be used many time

		$feedDocument = $feedApi->createFeedDocument(new \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedDocumentSpecification([
			'content_type' => $contentType,
		]));

		$feedDocumentId = $feedDocument->getPayload()->getFeedDocumentId();
		$url = $feedDocument->getPayload()->getUrl();
		$key = $feedDocument->getPayload()->getEncryptionDetails()->getKey();
		$key = base64_decode($key);

		$initializationVector = base64_decode($feedDocument->getPayload()->getEncryptionDetails()->getInitializationVector(), true);
		$encryptedFeedData = openssl_encrypt(utf8_encode($xmlFeed['xml']), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $initializationVector);

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POSTFIELDS => $encryptedFeedData,
			CURLOPT_HTTPHEADER => [
				'Accept: application/xml',
				'Content-Type: ' . $contentType,
			],
		));



		$response = curl_exec($curl);

		$error = curl_error($curl);
		$httpcode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($httpcode >= 200 && $httpcode <= 299) {
			// success
			$createFeedParams = [
				"feedType" => $xmlFeed['feedType'],
					"marketplaceIds" => $xmlFeed['marketplaceIds'],
					"inputFeedDocumentId" => $feedDocumentId
				];
				// $r = $feedApi->createFeed(json_encode($createFeedParams));

				// $body = new \Swagger\Client\Models\CreateFeedSpecification();

				try {
					$result = $feedApi->createFeed(json_encode($createFeedParams));
					debug('SUCCESSSSSSSS');

					return true;

				} catch (Exception $e) {

					debug($e);

					echo 'Exception when calling FeedsApi->createFeed: ', $e->getMessage(), PHP_EOL;
				}


		} else {
			// error
			debug($error);
		}

		return false;



	}


}