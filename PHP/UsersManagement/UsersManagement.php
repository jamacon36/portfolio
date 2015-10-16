<?php
//Get Salesforce Manager
include ($_SERVER['DOCUMENT_ROOT'] .'/_functions/Salesforce/SalesforceManager.php');

class User
{
	public $today;
	public $csc_uid;
	public $csc_udata;
	public $sf_connect;
	public $sf_id;
	public $is_sf_lead;
	//@TODO: Add reseller check to __constructor get_sf_id function
	//@TODO: Double check class properties are used with $this-> in all methods
	//public $reseller;

	private function get_csc_profile()
	{
		$csc_profile_assoc = db_get_row('
				SELECT *
				FROM ?:user_profiles
				WHERE user_id = ?i',
				$this->csc_uid);
		$csc_user_assoc = db_get_row('
				SELECT *
				FROM ?:users
				WHERE user_id = ?i',
				$this->csc_uid);
		$sfuser['firstname'] = $csc_user_assoc['firstname'];
		$sfuser['lastname'] = $csc_user_assoc['lastname'];
		!empty($csc_user_assoc['company']) ? $sfuser['company'] = $csc_user_assoc['company'] : $sfuser['company'] = $sfuser['firstname'] . ' ' . $sfuser['lastname'];
		$sfuser['address'] = $csc_profile_assoc['b_address'];
		$sfuser['address2'] = $csc_profile_assoc['b_address_2'];
		$sfuser['city'] = $csc_profile_assoc['b_city'];
		$sfuser['zip'] = $csc_profile_assoc['b_zipcode'];
		$sfuser['phone'] = $csc_profile_assoc['b_phone'];
		$sfuser['email'] = (!empty($csc_user_assoc['email']) && strpos($csc_user_assoc['email'], '@') !== false) ? $csc_user_assoc['email'] : false;
		
		$bcountry = db_get_row('
			SELECT *
			FROM ?:country_descriptions
			WHERE code = ?s',
			$csc_profile_assoc['b_country']
		);
		$bstateinfo = db_get_row('
			SELECT *
			FROM ?:states
			WHERE country_code = ?s AND code = ?s',
			$csc_profile_assoc['b_country'], $csc_profile_assoc['b_state']
		);
		$bstate = db_get_row('
			SELECT *
			FROM ?:state_descriptions
			WHERE state_id = ?s',
			$bstateinfo['state_id']
		);
		
		$sfuser['country'] = $bcountry['country'];
		$sfuser['state'] = $bstate['state'];
		
		return $sfuser;
	}
	
	private function get_sf_id()
	{
		$csc_sfid_assoc = db_get_row('
				SELECT *
				FROM ?:users_management
				WHERE csc_id = ?i',
				$this->csc_uid);
		if (!empty($csc_sfid_assoc['sf_id']) && $csc_sfid_assoc['sf_id'])
		{
			if ($csc_sfid_assoc['is_sf_lead'] == 1)
			{
				$email = $this->csc_udata['email'];
				$leadId = $csc_sfid_assoc['sf_id'];
				
				$lead = $this->sf_connect->searchForLead($email, $leadId);
				$lead->IsConverted ? $this->is_sf_lead = false : $this->is_sf_lead = true;
				if (!$this->is_sf_lead)
				{
					$update = array (
						'sf_id' => $lead->ConvertedContactId,
						'is_sf_lead' => 0
					);
					$set_sf_lead_status = db_query('
						UPDATE ?:users_management
						SET ?u
						WHERE csc_id = ?i',
						$update, $this->csc_uid);
					
					return $update['sf_id'];
				}
				else 
				{
					return $leadId;
				}
			}
			else
			{
				$contact = $this->sf_connect->searchForContact(false, $csc_sfid_assoc['sf_id']);
				
				$update = array (
					'sf_id' => $contact->Id,
					'is_sf_lead' => 0
				);
				$set_sf_lead_status = db_query('
					UPDATE ?:users_management
					SET ?u
					WHERE csc_id = ?i',
					$update, $this->csc_uid);
				
				return $update['sf_id'];
			}
		}
		else
		{
			$sf_id = false;
			if ($this->csc_udata['email'])
			{
				$sf_id = $this->sf_connect->searchForContact($this->csc_udata['email'], false);
			}
			if ($sf_id)
			{
				$this->is_sf_lead = false;
				if (empty($sf_id->AccountId))
				{
					$sf_aid = $this->sf_connect->searchForAccount(false, $this->csc_udata['company'], $this->csc_udata['firstname'], $this->csc_udata['lastname'], $this->csc_udata['email'], $this->csc_udata['zip']);
					if ($sf_aid)
					{
						$updateContact = new stdClass;
						$updateContact->Id = $sf_id;
						$updateContact->AccountId = $sf_aid;
						$updatedContact = $this->sf_connect->mySforceConnection->update(array($updateContact), 'Contact');
					}
					else
					{
						$newAccount = new stdClass;
						$newAccount->Name = $this->csc_udata['company'];
						$newAccount->BillingAddress = $this->csc_udata['address'] . $this->csc_udata['address2'];
						$newAccount->BillingCity = $this->csc_udata['city'];
						$newAccount->BillingState = $this->csc_udata['state'];
						$newAccount->BillingCountry = $this->csc_udata['country'];
						$newAccount->BillingZip = $this->csc_udata['zip'];
						$addedAccount = $this->sf_connect->mySforceConnection->create(array($newAccount), 'Account');
					}
				}
				return $sf_id->Id;
			}
			else
			{
				$sf_aid = $this->sf_connect->searchForAccount(false, $this->csc_udata['company'], $this->csc_udata['firstname'], $this->csc_udata['lastname'], $this->csc_udata['email'], $this->csc_udata['zip']);
				if ($sf_aid)
				{
					$this->is_sf_lead = false;
					$accountId = $sf_aid->Id;;
					$firstName = $this->csc_udata['firstname'];
					$lastName = $this->csc_udata['lastname'];
					$email = $this->csc_udata['email'];
					$phone = $this->csc_udata['phone'];
					$address = $this->csc_udata['address'] . $this->csc_udata['address2'];
					$city = $this->csc_udata['city'];
					$country = $this->csc_udata['country'];
					$state = $this->csc_udata['state'];
					$postalCode = $this->csc_udata['zip'];
					$hasOptedOutOfEmail = false;
					$keycontact = false;
					$cscId = $this->csc_uid;
					$ownerId = '005D0000004JLPKIA4';
					$leadsource = 'BFX Webshop';
					
					$new_sf_contact = $this->sf_connect->createContact($accountId, $firstName, $lastName, $email, $phone, $address, $city, $country, $state, $postalCode, $hasOptedOutOfEmail, $keycontact, $cscId, $ownerId, $leadsource);
					$insert = array(
						'csc_id' =>$this->csc_uid,
						'sf_id' => $new_sf_contact,
						'is_sf_lead' => 0
					);
					$insert_to_table = db_query('
						REPLACE INTO ?:users_management VALUES (?i, ?s, ?i)', $insert['csc_id'], $insert['sf_id'], $insert['is_sf_lead']
					);
					return $new_sf_contact;
				}
				else
				{
					$this->is_sf_lead = true;
					$ownerId = '005D0000004JLPKIA4';
					$company = $this->csc_udata['company'];
					$firstname = $this->csc_udata['firstname'];
					$lastname = $this->csc_udata['lastname'];
					$country = $this->csc_udata['country'];
					$state = $this->csc_udata['state'];
					$email = $this->csc_udata['email'];
					$phone =  $this->csc_udata['phone'];
					$city = $this->csc_udata['city'];
					$leadSource = 'BFX Webshop';
					
					$new_sf_lead = $this->sf_connect->createLead($ownerId, $firstname, $lastname, $company, $email, $phone, $city, $country, $state, false, $leadSource, false, false, $this->download, false, $this->today);
					
					$insert = array(
						'csc_id' => $this->csc_uid,
						'sf_id' => $new_sf_lead,
						'is_sf_lead' => 1
					);
					$insert_to_table = db_query('
						REPLACE INTO ?:users_management VALUES (?i, ?s, ?i)', $insert['csc_id'], $insert['sf_id'], $insert['is_sf_lead']
					);
					return $insert['sf_id'];
				}
			}
		}
	}
	
	private function new_sf_opp($order_id)
	{
		$order_id = (int) $order_id;
		$order_info = db_get_row('
			SELECT *
			FROM ?:orders
			WHERE order_id = ?i',
			$order_id
		);
		
		$product_info = db_get_array('
			SELECT *
			FROM ?:order_details
			WHERE order_id = ?i',
			$order_id
		);
		
		$this->csc_uid = (int) $order_info['user_id'];
		$this->csc_udata = $this->get_csc_profile();
		$this->sf_id = $this->get_sf_id();
		
		$opportunity = array();
		$opportunity['user_id'] = $order_info['user_id'];
		$opportunity['createdby'] = "Webshop";
		$opportunity['billingInfo'] = array(
			'FName' => $order_info['b_firstname'],
			'LName' => $order_info['b_lastname'],
			'Company' => !empty($order_info['company']) ? $order_info['company'] : $order_info['b_firstname']. ' ' .$order_info['b_lastname'],
			'Address' => $order_info['b_address'],
			'Address 2' => $order_info['b_address_2'],
			'City' => $order_info['b_city'],
			'Counrty' => $order_info['b_country'],
			'Phone' => $order_info['b_phone'],
			'State' => $order_info['b_state'],
			'Zip' => $order_info['b_zipcode']
		);
		$opportunity['shippingInfo'] = array(
			'FName' => $order_info['s_firstname'],
			'LName' => $order_info['s_lastname'],
			'Address' => $order_info['s_address'],
			'Address 2' => $order_info['s_address_2'],
			'City' => $order_info['s_city'],
			'Country' => $order_info['s_country'],
			'Phone' => $order_info['s_phone'],
			'State' => $order_info['s_state'],
			'Zip' => $order_info['s_zipcode']
		);
		
		$bcountry = db_get_row('
			SELECT *
			FROM ?:country_descriptions
			WHERE code = ?s',
			$opportunity['billingInfo']['Counrty']
		);
		$bstateinfo = db_get_row('
			SELECT *
			FROM ?:states
			WHERE country_code = ?s AND code = ?s',
			$opportunity['billingInfo']['Counrty'], $opportunity['billingInfo']['State']
		);
		$bstate = db_get_row('
			SELECT *
			FROM ?:state_descriptions
			WHERE state_id = ?s',
			$bstateinfo['state_id']
		);
		$scountry = db_get_row('
			SELECT *
			FROM ?:country_descriptions
			WHERE code = ?s',
			$opportunity['shippingInfo']['Counrty']
		);
		$sstateinfo = db_get_row('
			SELECT *
			FROM ?:states
			WHERE country_code = ?s AND code = ?s',
			$opportunity['shippingInfo']['Counrty'], $opportunity['shippingInfo']['State']
		);
		$sstate = db_get_row('
			SELECT *
			FROM ?:state_descriptions
			WHERE state_id = ?s',
			$sstateinfo['state_id']
		);
		
		$opportunity['billingInfo']['Counrty'] = $bcountry['country'];
		$opportunity['billingInfo']['State'] = $bstate['state'];
		$opportunity['shippingInfo']['Counrty'] = $scountry['country'];
		$opportunity['shippingInfo']['State'] = $sstate['state'];
		
		$paymentId = $order_info['payment_id'];
		$opportunity['formOfPayment'] = "Credit Card";
		$opportunity['Rep'] = '@@@';
		if ($order_info['status'] == 'P' && !$order_error)
		{
			$opportunity['stage'] = 'Post Opps Processing';
		}
		else
		{
			$opportunity['stage'] = 'Follow Up';
		}
		
		$opportunity['type'] = 'Webshop Order';
		$opportunity['Currency'] = 'USD';
		//Reseller Users will have to Change this
		$opportunity['PriceBookId'] = '@@@';
		!empty($order_info['promotion_ids']) ? $promotion = $order_info['promotions'] : $promotion = false;
		if ($promotion)
		{
			$promotion = unserialize($promotion);
			$promoArray = array();
			foreach ($promotion as $promo)
			{
				foreach ($promo['bonuses'] as $bonus)
				{
					$promoId = $bonus['promotion_id'];
					$promoRow = db_get_row('
					SELECT *
					FROM ?:promotion_descriptions
					WHERE promotion_id = ?s',
					$promoId
					);
					array_push($promoArray, $promoRow['name']);
				}
			}
			$opportunity['leadSource'] = implode("', '", $promoArray);
		}
		else
		{
			$opportunity['leadSource'] = 'Webshop';
		}
		$opportunity['Products'] = array();
		//Temp array of Bundle Products for upgrade treatment
		$bundleProds = array(
				'BCCFECAVX',
				'BCCFECAVX',
				'MGPAE',
				'MGPAVX',
				'MGPOFX',
				'BOX',
				'BOXU',
				'BOXAVX',
				'BOXAVXU',
				'BOXAVXX',
				'BLOCK3D1',
				'STERNFX1',
				'STERNFX2',
				'STERNFX3',
				'ESFBUN',
				'ESFTITLES4',
				'ESFTITLES5',
				'ESFREDV1',
				'ESFREDV3',
				'ESFREDV2',
				'ESFTITLES2',
				'ESFTITLES3',
				'ESFTITLES1'
		);
		foreach ($product_info as $product)
		{
			$sku = $product['product_code'];
			$product['Upgrade'] = ((substr($sku, -1) == 'U') || (substr($sku, 13, 2) == 'UP') || (in_array($sku, $bundleProds)));
			if ($product['Upgrade'] && $opportunity['stage'] != 'Follow Up' && $product['amount'] <= 4)
			{
				$opportunity['nextStage'] = 'Closed: Awaiting License Verification';
			}
			$options = unserialize($product['extra']);
			foreach ($options['product_options_value'] as $option)
			{
				$product['options'][$option['option_name']] = $option['variant_name'];
			}
			$opportunity['Products'][$sku] = $product;
		}
		$sf_accountId = '';
		if ($this->is_sf_lead)
		{
			$sf_lead = $this->sf_connect->searchForLead(false, $this->sf_id);
			$sf_lead->IsConverted ? $this->is_sf_lead = false : $this->is_sf_lead = true;
			$this->is_sf_lead ? $sf_contact = $this->sf_connect->convertLeadToContact($this->sf_id, $opportunity['Rep'], 'Qualified', false, false, true) : $sf_contact = $sf_lead->ConvertedContactId;
			$sf_id = $sf_contact->result->contactId;
			$sf_accountId = $sf_contact->result->accountId;
			
			$sfObject_account = new stdClass();
			$sfObject_account->Id = $sf_accountId;
			$sfObject_account->BillingStreet = $opportunity['billingInfo']['Address'] . ' ' . $opportunity['billingInfo']['Address 2'];
			$sfObject_account->BillingCity = $opportunity['billingInfo']['City'];
			$sfObject_account->BillingCountry = $opportunity['billingInfo']['Country'];
			$sfObject_account->BillingState = $opportunity['billingInfo']['State'];
			$sfObject_account->BillingPostalCode = $opportunity['billingInfo']['Zip'];
			$sf_updateAccount = $this->sf_connect->mySforceConnection->update(array($sfObject_account), 'Account');
			
			$update = array (
				'sf_id' => $sf_id,
				'is_sf_lead' => 0
			);
			$set_sf_lead_status = db_query('
				UPDATE ?:users_management
				SET ?u
				WHERE csc_id =?i',
				$update, $this->csc_uid);
			$this->sf_id = $sf_id;
			
			if (isset($_COOKIE['bfxuser']))
				unset($_COOKIE['bfxuser']);
			
			$cData['csc_uid'] = $this->csc_uid;
			$cData['sf_id'] = $this->sf_id;
			$cData['lead'] = $this->is_sf_lead;
			$cData = json_encode($cData);
			setcookie('bfxuser', $cData, 0, '/', 'borisfx.com');
		}
		else
		{
			$sf_id = $this->sf_id;
			$sf_contact = $this->sf_connect->searchForContact(false, $sf_id);
			$sf_accountId = $sf_contact->AccountId;
			if (empty($sf_accountId))
			{
				$sf_aid = $this->sf_connect->searchForAccount(false, $opportunity['billingInfo']['Company'], $opportunity['billingInfo']['FName'], $opportunity['billingInfo']['LName'], $this->csc_udata['email'], $opportunity['billingInfo']['Zip']);
				if ($sf_aid)
				{
					$updateContact = new stdClass;
					$updateContact->Id = $sf_id;
					$updateContact->AccountId = $sf_aid;
					$updatedContact = $this->sf_connect->mySforceConnection->update(array($updateContact), 'Contact');
					$sf_accountId = $sf_aid;
				}
				else 
				{
					$newAccount = new stdClass;
					$newAccount->Name = $opportunity['billingInfo']['Company'];
					$newAccount->BillingAddress = $opportunity['billingInfo']['Address'] . ' ' . $opportunity['billingInfo']['Address 2'];
					$newAccount->BillingCity = $opportunity['billingInfo']['City'];
					$newAccount->BillingState = $opportunity['billingInfo']['State'];
					$newAccount->BillingCountry = $opportunity['billingInfo']['Country'];
					$newAccount->BillingZip = $opportunity['billingInfo']['Zip'];
					$addedAccount = $this->sf_connect->mySforceConnection->create(array($newAccount), 'Account');
					$sf_accountId = $addedAccount->id;
				}
			}
			else
			{
				$sf_accountId = $sf_contact->AccountId;
				$sfObject_account = new stdClass();
				$sfObject_account->Id = $sf_accountId;
				$sfObject_account->BillingStreet = $opportunity['billingInfo']['Address'] . ' ' . $opportunity['billingInfo']['Address 2'];
				$sfObject_account->BillingCity = $opportunity['billingInfo']['City'];
				$sfObject_account->BillingCountry = $opportunity['billingInfo']['Country'];
				$sfObject_account->BillingState = $opportunity['billingInfo']['State'];
				$sfObject_account->BillingPostalCode = $opportunity['billingInfo']['Zip'];
				$sf_updateAccount = $this->sf_connect->mySforceConnection->update(array($sfObject_account), 'Account');
			}
		}
		$reseller = null;
		if (($opportunity['shippingInfo']['FName'] != $opportunity['billingInfo']['FName'] && $opportunity['shippingInfo']['LName'] != $opportunity['billingInfo']['LName']) || $reseller)
		{
			if ($reseller)
			{
				//@TODO: Need to search for existing customer accounts based on who the reseller is shipping to
			}
		}
		
		$sf_opportunity = $this->sf_connect->createOpportunity($opportunity['Rep'], $sf_accountId, 'Webshop Order #B' . $order_id, $opportunity['type'], $opportunity['stage'], $this->today, 'B' . $order_id, $reseller, $opportunity['formOfPayment'], $opportunity['Currency'], $opportunity['leadSource'], $this->today, $opportunity['PriceBookId']);
		$opportunity['sfOID'] = $sf_opportunity->id;
		$prodcodes = array();
		foreach ($opportunity['Products'] as $prod_code => $prod_info)
		{
			array_push($prodcodes, $prod_code);
		}
		$prod_query = implode("','", $prodcodes);
		$prod_Ids = $this->sf_connect->getPriceBook($prod_query, $opportunity['PriceBookId'], $opportunity['Currency']);
		foreach ($prod_Ids as $PBE => $Entry)
		{
			foreach ($opportunity['Products'] as $p => $i)
			{
				if ($prod_Ids[$PBE]->ProductCode == $p)
					$opportunity['Products'][$p]['sfPBID'] = $prod_Ids[$PBE]->Id;
			}
		}
		$sfOlis = array();
		foreach ($opportunity['Products'] as $Code => $OLI)
		{
			$sfOli = new stdClass;
			$sfOli->OPPORTUNITYID = $opportunity['sfOID'];
			$sfOli->PRICEBOOKENTRYID = $OLI['sfPBID'];
			$sfOli->QUANTITY = (int) $OLI['amount'];
			$sfOli->UNITPRICE = (int) $OLI['price'];
			$sfOli->UPGRADED_SERIAL__C = $OLI['options']['Current Serial Number'];
			array_push($sfOlis, $sfOli);
		}
		
		$sfOppContacts = array();
		$sfOppContact = new stdClass;
		$sfOppContact->OPPORTUNITYID = $opportunity['sfOID'];
		$sfOppContact->CONTACTID = $this->sf_id;
		$sfOppContact->ISPRIMARY = true;
		$sfOppContact->ROLE = 'Webshop Customer';
		array_push($sfOppContacts, $sfOppContact);
		
		$sf_OppContact = $this->sf_connect->mySforceConnection->create(array_values($sfOppContacts), 'OpportunityContactRole');
		$sf_oli = $this->sf_connect->mySforceConnection->create(array_values($sfOlis), 'OpportunityLineItem');
		
		if(!$opportunity['nextStage'] && $opportunity['stage'] != 'Follow Up')
		{
			$opportunity['nextStage'] = 'Closed Sending License';
		}
		
		$sObject_opportunity = new stdClass;
		$sObject_opportunity->id = $opportunity['sfOID'];
		$sObject_opportunity->StageName = $opportunity['nextStage'];
		$updateOpp = $this->sf_connect->mySforceConnection->update(array($sObject_opportunity), 'Opportunity');
		
		return $opportunity['sfOID'];
	}

	public function __construct($csc_uid, $udata, $order_id)
	{
		$this->today = date('Y-m-d');
		$this->sf_connect = new SalesforceManager();
		if ($order_id)
		{
			$new_sf_opp = $this->new_sf_opp($order_id);
		}
		else
		{
			$this->csc_uid = (int) $csc_uid;
			$this->csc_udata = $this->get_csc_profile();
			$this->sf_id = $this->get_sf_id();
		}
	}
}