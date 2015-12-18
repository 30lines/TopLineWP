<?php
/**
 * Topline API Manager
 * Author:  DJF
 * Company: 30Lines
 */
include_once('topline_OptionsManager.php');
include_once('topline_ArraySteps.php');

class topline_API extends topline_OptionsManager {

  protected $apiKey;
  protected $apiUsername;

  public function __construct($apiKey, $apiUsername)
  {
    $this->apiKey = $apiKey;
    $this->apiUsername = $apiUsername;
    $this->baseUrl = 'https://app.30lines.com/'; // base url
    // $this->baseUrl = 'http://topline.service.local/';
    $this->steps = new topline_ArraySteps($this->getOption('properties'));
  }

  /**
	 * Single property create/update
	 */
	public function toplineMultiPropertyRefresh($propertyList = null) {
    $properties = isset($propertyList) ? $propertyList : $this->getOption('properties');
    $this->steps->setArrayVal($properties);
    foreach ($properties as $metaKey => $metaValue) {
      if(!is_numeric($metaValue)) {
        $propName = $metaValue;
        $propCode = $this->steps->setCurrent($metaValue);
      } else {
        continue;
      }
      $args = [
        'include' => [
          'floorplans'
        ]
      ];
      $propData = $this->getProperty($propCode, $args);
      $propData = json_decode($propData);
      /* Check if API response contains errors, then display them */
      if(isset($propData->error)) {
        $message = $propData->error == 'invalid_credentials' ? 'Invalid Request: '.$propData->error : 'Please check your TopLine configuration. Error: '.$propData->error;
				echo "<div style='background:#d32f2f;padding:20px;color:white;'>".$message."</div>";
				exit;
			}
			$toplineProperty = $propData->data;

      // Data Variables
      $floorPlans     = array();
      $propRentRange  = array();
      $propBedRange   = array();
      $propBathRange  = array();
      $propSQFTrange  = array();
      $unitData       = array();
      /* Set Floorplans */
      foreach ($toplineProperty->floorplans->data->Floorplans as $key => $floorPlan) {
        $minSqrFt = isset($floorPlan->Units[$key]) ? $floorPlan->Units[$key]->SquareFeet->Min : '0';
				$maxSqrFt = isset($floorPlan->Units[$key]) ? $floorPlan->Units[$key]->SquareFeet->Max : '0';
				$availCount = 0;
				$fpTaxonomy = strtolower($floorPlan->FloorplanName);
				$fpTaxonomy = trim(str_replace(' ', '-', $fpTaxonomy));
        /* Set Floorplan Units */
				foreach($floorPlan->Units as $unit) {
					$unitArray = $this->createUnitItem($unit, $floorPlan, $fpTaxonomy);
					array_push($unitData, $unitArray);
					if($unit->isAvailable) $availCount++;
				}
        $fpArray = $this->createFpItem($floorPlan, $minSqrFt, $maxSqrFt, $availCount);
				array_push($floorPlans, $fpArray);
				array_push($propBedRange, intval($floorPlan->Rooms->Bed));
				array_push($propBathRange, intval($floorPlan->Rooms->Bath));
				array_push($propRentRange, intval($floorPlan->RentRange->Market->Max));
				array_push($propRentRange, intval($floorPlan->RentRange->Market->Min));
				array_push($propSQFTrange, intval($minSqrFt));
				array_push($propSQFTrange, intval($maxSqrFt));
      }
      sort($propRentRange, SORT_NUMERIC);
			$propMaxRent = end($propRentRange);
			sort($propBedRange, SORT_NUMERIC);
			$propMaxBed = end($propBedRange);
			sort($propBathRange, SORT_NUMERIC);
			$propMaxBath = end($propBathRange);
			sort($propSQFTrange, SORT_NUMERIC);
			$propMaxSQFT = end($propSQFTrange);
      $details = [
        'MinRent'       => $propRentRange[0],
        'MaxRent'       => $propMaxRent,
        'MinBeds'       => $propBedRange[0],
        'MaxBeds'       => $propMaxBed,
        'MinBaths'      => $propBathRange[0],
        'MaxBaths'      => $propMaxBath,
        'MinSize'       => $propSQFTrange[0],
        'MaxSize'       => $propMaxSQFT,
      ];
      $propInfo = $this->createPropItem($toplineProperty, $details);
      $propertyTaxonomy = strtolower($propInfo['propName']);
			$propertyTaxonomy = trim(str_replace(' ', '-', $propertyTaxonomy));
			$results[$propCode] = [
					'propInfo' => $propInfo,
					'floorplanInfo' => $floorPlans,
					'unitInfo' => $unitData,
					'taxonomy' => $propertyTaxonomy
			];
			$results['codes'][] = $propCode;
    }
    return $results;
	}

  private function createPropItem($toplineProperty, $details)
  {
    return array(
      'propName'          => $toplineProperty->Property->PropertyName,
      'propAddress'       => $toplineProperty->Property->Address->Location,
      'propCity'          => $toplineProperty->Property->Address->City,
      'propState'         => $toplineProperty->Property->Address->State,
      'propZip'           => $toplineProperty->Property->Address->PostalCode,
      'propURL'           => isset($toplineProperty->Property->url) ? $toplineProperty->Property->url : '#',
      'propDescription'   => isset($toplineProperty->Property->PropertyDescription) ? $toplineProperty->Property->PropertyDescription : 'No Description',
      'propEmail'         => isset($toplineProperty->Property->Contact->Email) ? $toplineProperty->Property->Contact->Email : NULL,
      'propLatitude'      => NULL,
      'propLongitude'     => NULL,
      'prop_code'         => $toplineProperty->Property->PropertyCode,
      'propMinRent'       => $details['MinRent'],
      'propMaxRent'       => $details['MaxRent'],
      'propMinBeds'       => $details['MinBeds'],
      'propMaxBeds'       => $details['MaxBeds'],
      'propMinBaths'      => $details['MinBaths'],
      'propMaxBaths'      => $details['MaxBaths'],
      'propMinSQFT'       => $details['MinSize'],
      'propMaxSQFT'       => $details['MaxSize'],
    );
  }

  private function createFpItem($floorPlan, $minSqrFt, $maxSqrFt, $availCount)
  {
    return array(
      'fpID' 						=> $floorPlan->FloorplanID,
      'fpName'          => $floorPlan->FloorplanName,
      'fpBeds'          => $floorPlan->Rooms->Bed,
      'fpBaths'         => $floorPlan->Rooms->Bath,
      'fpMinSQFT'       => $minSqrFt,
      'fpMaxSQFT'       => $maxSqrFt,
      'fpMinRent'       => $floorPlan->RentRange->Market->Min,
      'fpMaxRent'       => $floorPlan->RentRange->Market->Max,
      'fpMinDeposit'    => $floorPlan->Deposit->Min,
      'fpMaxDeposit'    => $floorPlan->Deposit->Max,
      'fpAvailUnitCount'=> $availCount,
      'fpAvailURL'      => isset($floorPlan->AvailabilityURL) ? strval($floorPlan->AvailabilityURL): '#',
      'fpImg'           => count($floorPlan->Files) > 0 ? $floorPlan->Files->ImageSrc : 'http://placehold.it/200x200',
      'fpPhone'         => isset($floorPlan->ContactNumber) ? $floorPlan->ContactNumber : '555-555-5555'
    );
  }

  private function createUnitItem($unit, $fp, $fpTaxonomy) {
    return [
        'fp_id' => $fp->FloorplanID,
        'unit_number' => $unit->UnitNumber,
        'status' => $unit->Status,
        'rent_minimum' => $unit->Rent->Range->Min,
        'rent_maximum' => $unit->Rent->Range->Min,
        'square_foot_minimum' => $unit->SquareFeet->Min,
        'square_foot_maximum' => $unit->SquareFeet->Max,
        'taxonomy' => $fpTaxonomy
    ];
  }

  /**
   *  Property Data cURL request
   *  store as json string in return value
   *
   */
  public function getProperty($propCode, $args = array()) {
    $action = 'api/v1/properties/'.$propCode;

    /* Construct json request string with credentials for validation */
    $parameters = $this->getJsonRequest($args);

    return $this->jsonRequest($action, $parameters);
  }

  /**
   * Format API request string
   *
   * @param string $methodName //name of API method being used
   * @param array $requestParams //array of method parameters provided by user
   * @return JSON_STRING
   */
  private function getJsonRequest($args = array())
  {
    /* Format arguments for API */
    $json['arguments'] = [
      'wp_curl' => array(
        'method' => isset($args['method']) ? $args['method'] : 'GET',
        'sslverify' => isset($args['sslverify']) ? $args['sslverify'] : false,
        'compress' => isset($args['compress']) ? $args['compress'] : false
      ),
      'topline' => [
        'auth' => [
            'token' => $this->apiKey,
            'username' => $this->apiUsername
        ],
        'propertyLimit' => isset($args['limit']) ? $args['limit'] : '-1'
      ]
    ];
    /* Check for includes, implode on comma, add to topline include array */
    $includes = isset($args['include']) ? count($args['include']) : 0;
    if($includes > 0) $json['arguments']['topline']['include'] = implode(',', $args['include']);
    return $json;
  }

  /**
   * Send JSON Request to TopLine
   *
   * @param string $action     // URI for target action
   * @param array  $parameters // Array of POST parameters to send through the request
   */
  private function jsonRequest($action, $parameters = array()) {
    if(count($parameters) == 0) $parameters = $this->getJsonRequest();
    /* Set includes, if any */
    $includes = isset($parameters['arguments']['topline']['include']) ? '?include='.$parameters['arguments']['topline']['include'] : '';

    /* Send remote API request to TopLine */
    $results = wp_remote_request(
      $this->baseUrl.$action.$includes, // build request url
      array(
        'method' => $parameters['arguments']['wp_curl']['method'],
        'sslverify' => $parameters['arguments']['wp_curl']['sslverify'],
        'body' => $parameters['arguments']['topline'],
        'compress' => $parameters['arguments']['wp_curl']['compress'],
        'headers' => array( /* set token and username */
          'X-Topline-Token' => $this->apiKey,
          'X-Topline-User' => $this->apiUsername
        ),
        'timeout' => 60
      )
    );
    /* If API key refresh was requested, return the token to the requester */
    if(isset($results['headers']['authorization'])) {
      $token = explode(" ", $results['headers']['authorization']);
      $realToken = $token[1];
      if (preg_match('/*Bearer*/', $realToken)) return $realToken;
    }
    /* Return body of results */
    return $results['body'];
  }
}
